<?php

    class MW_RewardPoints_Model_Obsever
    {
        public function saveRewardPoints($observer)
        {
            if(!Mage::helper('rewardpoints')->moduleEnabled())
            {
                return;
            }

            $request     = $observer->getEvent()->getRequest();
            $customer    = $observer->getEvent()->getCustomer();
            $customer_id = $customer->getId();
            $data        = $request->getPost();
            Mage::helper('rewardpoints/data')->checkAndInsertCustomerId($customer_id, 0);
            $_customer = Mage::getModel('rewardpoints/customer')->load($customer_id);
            $store_id  = Mage::getModel('customer/customer')->load($customer_id)->getStoreId();
            $oldPoints = $_customer->getMwRewardPoint();
            $amount    = $data['mw_reward_points_amount'];
            $action    = $data['mw_reward_points_action'];
            $comment   = $data['mw_reward_points_comment'];

            $newPoints = $oldPoints + $amount * $action;

            if($newPoints < 0)
            {
                $newPoints = 0;
            }
            $amount = abs($newPoints - $oldPoints);

            if($amount > 0)
            {
                $detail = $comment;
                $_customer->setData('mw_reward_point', $newPoints);
                $_customer->save();
                $balance = $_customer->getMwRewardPoint();

                $results         = Mage::helper('rewardpoints/data')->getTransactionExpiredPoints($amount, $store_id);
                $expired_day     = $results[0];
                $expired_time    = $results[1];
                $point_remaining = $results[2];

                $historyData = array('type_of_transaction' => ($action > 0) ? MW_RewardPoints_Model_Type::ADMIN_ADDITION : MW_RewardPoints_Model_Type::ADMIN_SUBTRACT,
                                     'amount'              => $amount,
                                     'balance'             => $balance,
                                     'transaction_detail'  => $detail,
                                     'transaction_time'    => Mage::getSingleton('core/date')->gmtDate(),
                                     'expired_day'         => $expired_day,
                                     'expired_time'        => $expired_time,
                                     'point_remaining'     => $point_remaining,
                                     'status'              => MW_RewardPoints_Model_Status::COMPLETE);
                $_customer->saveTransactionHistory($historyData);

                // process expired points when spent point
                if($action < 0)
                {
                    Mage::helper('rewardpoints/data')->processExpiredPointsWhenSpentPoints($_customer->getId(), $amount);
                }

                // send mail when points changed
                Mage::helper('rewardpoints')->sendEmailCustomerPointChanged($_customer->getId(), $historyData, $store_id);

                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('rewardpoints')->__('Reward points has successfully saved'));
            }
        }

        public function addPaypalRewardItem(Varien_Event_Observer $observer)
        {
            $paypalCart = $observer->getEvent()->getPaypalCart();
            if($paypalCart && abs($paypalCart->getSalesEntity()->getMwRewardpointDiscount()) > 0.0001)
            {
                $salesEntity = $paypalCart->getSalesEntity();
                $paypalCart->updateTotal(
                    Mage_Paypal_Model_Cart::TOTAL_DISCOUNT,
                    (float)$salesEntity->getMwRewardpointDiscount(),
                    Mage::helper('rewardpoints')->__('Rewardpoint discount %s', $salesEntity->getMwRewardpointDiscountShow())
                );
            }
        }

        public function customerLoginRedirect($observer)
        {
            $mw_redirect = Mage::getModel('core/cookie')->get('mw_redirect');
            if($mw_redirect)
            {
                $session = Mage::getSingleton('customer/session');
                $session->setBeforeAuthUrl(Mage::getUrl('checkout/cart'));
                Mage::getModel('core/cookie')->delete('mw_redirect');
            }

        }

        public function processCustomRule($customer_id, $type_of_transaction, $rule_id, $store_id)
        {
            $transactions      = Mage::getModel('rewardpoints/rewardpointshistory')->getCollection()
                ->addFieldToFilter('customer_id', $customer_id)
                ->addFieldToFilter('type_of_transaction', $type_of_transaction)
                ->addFieldToFilter('transaction_detail', $rule_id)
                ->addFieldToFilter('status', MW_RewardPoints_Model_Status::COMPLETE);
            $customer_group_id = Mage::getModel('customer/customer')->load($customer_id)->getGroupId();

            $results = Mage::getModel('rewardpoints/activerules')->getPointByRuleId($rule_id, $customer_group_id, $store_id);
            if(!sizeof($transactions) && Mage::helper('rewardpoints')->checkCustomerMaxBalance($customer_id, $store_id, $results[0]))
            {
                Mage::helper('rewardpoints/data')->checkAndInsertCustomerId($customer_id, 0);
                $_customer = Mage::getModel('rewardpoints/customer')->load($customer_id);

                $points          = $results[0];
                $expired_day     = $results[1];
                $expired_time    = $results[2];
                $point_remaining = $results[3];

                if($points)
                {
                    $_customer->addRewardPoint($points);
                    $historyData = array('type_of_transaction' => $type_of_transaction,
                                         'amount'              => $points,
                                         'balance'             => $_customer->getMwRewardPoint(),
                                         'transaction_detail'  => $rule_id,
                                         'transaction_time'    => Mage::getSingleton('core/date')->gmtDate(),
                                         'expired_day'         => $expired_day,
                                         'expired_time'        => $expired_time,
                                         'point_remaining'     => $point_remaining,
                                         'status'              => MW_RewardPoints_Model_Status::COMPLETE);
                    $_customer->saveTransactionHistory($historyData);

                    // send mail when points changed
                    Mage::helper('rewardpoints')->sendEmailCustomerPointChanged($_customer->getId(), $historyData, $store_id);
                    Mage::getSingleton('core/session')->addSuccess(Mage::helper('rewardpoints')->__('Congratulation! %s Reward Points have been added to your account', $points));
                }
            }
        }

        public function customerLogin($observer)
        {
            $customer_id         = $observer->getModel()->getId();
            $type_of_transaction = MW_RewardPoints_Model_Type::CUSTOM_RULE;
            $cokie               = (int)Mage::getModel('core/cookie')->get('mw_reward_rule');
            //echo $cokie.'aaaaaa';die();
            if($cokie)
            {
                $rule_id  = $cokie;
                $store_id = Mage::app()->getStore()->getId();

                $this->processCustomRule($customer_id, $type_of_transaction, $rule_id, $store_id);
                //Mage::getModel('core/cookie')->delete('mw_reward_rule');
            }
        }

        public function customerRegister($observer)
        {
            $customer_id         = $observer->getCustomer()->getId();
            $type_of_transaction = MW_RewardPoints_Model_Type::CUSTOM_RULE;
            $cokie               = (int)Mage::getModel('core/cookie')->get('mw_reward_rule');

            if($cokie)
            {
                $rule_id  = $cokie;
                $store_id = Mage::app()->getStore()->getId();

                $this->processCustomRule($customer_id, $type_of_transaction, $rule_id, $store_id);

                Mage::getModel('core/cookie')->delete('mw_reward_rule');
            }
        }

        public function customRule($observer)
        {
            if(strpos(Mage::app()->getRequest()->getPathInfo(), 'mw_re_login'))
            {
                Mage::getModel('core/cookie')->set('mw_redirect', 1, 120);
            }

            $rule_encrypt = trim(Mage::app()->getRequest()->getParam('mw_ref'));
            if($rule_encrypt)
            {
                $data          = base64_decode($rule_encrypt);
                $datas         = explode(",", $data);
                $rule_decrypt  = $datas[1];
                $email_decrypt = $datas[2];
                //zend_debug::dump($datas);die();
                if($rule_decrypt && $email_decrypt)
                {
                    $front      = $observer->getEvent()->getFront();
                    $request    = $front->getRequest();
                    $requestUri = $request->getRequestUri();
                    $baseUrl    = $request->getBaseUrl();
                    $pathInfo   = $request->getPathInfo();
                    $request->setRequestUri($baseUrl . '/rewardpoints/invitation/autologin');
                    $request->setPathInfo();
                }
            }

            $rule_encrypt_new = trim(Mage::app()->getRequest()->getParam('mw_rule'));
            if($rule_encrypt_new)
            {
                $data             = base64_decode($rule_encrypt_new);
                $datas            = explode(",", $data);
                $rule_decrypt_new = $datas[1];
                $rule_id          = $rule_decrypt_new;
                $customer_id      = Mage::getSingleton('customer/session')->getCustomer()->getId();
                $store_id         = Mage::app()->getStore()->getId();

                if($customer_id)
                {
                    $type_of_transaction = MW_RewardPoints_Model_Type::CUSTOM_RULE;
                    $this->processCustomRule($customer_id, $type_of_transaction, $rule_id, $store_id);

                }
                else
                {
                    Mage::getModel('core/cookie')->set('mw_reward_rule', $rule_id, 60 * 60 * 24 * 30);
                    $store_id   = Mage::app()->getStore()->getId();
                    $point_rule = Mage::getModel('rewardpoints/activerules')->getPointByRuleIdNotGroup($rule_id, $store_id);
                    //if($point_rule >0) Mage::getSingleton('core/session')->addSuccess(Mage::helper('rewardpoints')->__("You will be awarded %s points. Please <a href='%s'>Login</a> or <a href='%s'>Create New Account</a> to receive these points.",$point_rule,$link_login,$link_create));
                    if($point_rule > 0)
                    {
                        Mage::getSingleton('core/session')->addSuccess(Mage::helper('rewardpoints')->__("You will be awarded %s points. Please Login or Create New Account to receive these points.", $point_rule));
                    }
                }
            }

            $modules      = Mage::getConfig()->getNode('modules')->children();
            $modulesArray = (array)$modules;
            $modules2     = array_keys((array)Mage::getConfig()->getNode('modules')->children());
            if(!in_array('MW_Mcore', $modules2) || !$modulesArray['MW_Mcore']->is('active') || Mage::getStoreConfig('mcore/config/enabled') != 1)
            {
                Mage::helper('rewardpoints')->disableConfig();
            }

        }

        public function submitTestimonial($obsever)
        {
            $id                  = $obsever->getId();
            $store_id            = Mage::app()->getStore()->getId();
            $type_of_transaction = MW_RewardPoints_Model_Type::POSTING_TESTIMONIAL;

            $transactions = Mage::getModel('rewardpoints/rewardpointshistory')->getCollection()
                ->addFieldToFilter('type_of_transaction', $type_of_transaction)
                ->addFieldToFilter('transaction_detail', $id)
                ->addFieldToFilter('status', array('in' => array(MW_RewardPoints_Model_Status::PENDING)));
            foreach ($transactions as $transaction)
            {
                $status      = MW_RewardPoints_Model_Status::COMPLETE;
                $customer_id = $transaction->getCustomerId();
                $customer    = Mage::getModel('rewardpoints/customer')->load($customer_id);

                $customer->addRewardPoint($transaction->getAmount());
                $expired_day     = $transaction->getExpiredDay();
                $results         = Mage::helper('rewardpoints/data')->getTransactionByExpiredDayAndPoints((int)$transaction->getAmount(), $expired_day);
                $expired_time    = $results[0];
                $point_remaining = $results[1];
                $transaction->setExpiredTime($expired_time)->setPointRemaining($point_remaining);

                $transaction->setBalance($customer->getRewardPoint())->setTransactionTime(Mage::getSingleton('core/date')->gmtDate());
                $transaction->setStatus($status)->save();

                // send mail when points changed
                $historyData = array('type_of_transaction' => $transaction->getTypeOfTransaction(),
                                     'amount'              => (int)$transaction->getAmount(),
                                     'balance'             => $transaction->getBalance(),
                                     'transaction_detail'  => $transaction->getTransactionDetail(),
                                     'transaction_time'    => $transaction->getTransactionTime(),
                                     'status'              => $transaction->getStatus());

                Mage::helper('rewardpoints')->sendEmailCustomerPointChanged($customer_id, $historyData, $store_id);

            }
        }

        public function expirationEmail()
        {
            $store_id                = Mage::app()->getStore()->getId();
            $day                     = (int)Mage::helper('rewardpoints/data')->getExpirationDaysEmail($store_id);
            $to                      = time() + $day * 24 * 3600;
            $array_add_point         = Mage::getModel('rewardpoints/type')->getAddPointArray();
            $transaction_collections = Mage::getModel('rewardpoints/rewardpointshistory')->getCollection()
                ->addFieldToFilter('type_of_transaction', array('in' => array($array_add_point)))
                ->addFieldToFilter('status', MW_RewardPoints_Model_Status::COMPLETE)
                ->addFieldToFilter('DATE(expired_time)', array('eq' => date('Y-m-d', $to)))
                ->addFieldToFilter('point_remaining', array('gt' => 0))
                ->setOrder('expired_time', 'DESC')
                ->setOrder('history_id', 'ASC');
            //echo $transaction_collections->getSelect();die();
            foreach ($transaction_collections as $transaction_collection)
            {

                $history_id = $transaction_collection->getHistoryId();
                //echo $history_id;die();
                $customer_id                 = $transaction_collection->getCustomerId();
                $store_id                    = Mage::getModel('customer/customer')->load($customer_id)->getStoreId();
                $point_remaining             = $transaction_collection->getPointRemaining();
                $expired_time                = $transaction_collection->getExpiredTime();
                $_customer                   = Mage::getModel('rewardpoints/customer')->load($customer_id);
                $subscribed_point_expiration = $_customer->getSubscribedPointExpiration();

                if($subscribed_point_expiration && $subscribed_point_expiration == 1)
                {
                    $historyData = array('type_of_transaction' => MW_RewardPoints_Model_Type::EXPIRED_POINTS,
                                         'amount'              => (int)$point_remaining,
                                         'balance'             => $_customer->getMwRewardPoint(),
                                         'transaction_detail'  => $history_id,
                                         'transaction_time'    => $expired_time,
                                         'status'              => MW_RewardPoints_Model_Status::COMPLETE);


                    Mage::helper('rewardpoints')->sendEmailCustomerPointExpiration($customer_id, $historyData, $store_id);
                }

            }

        }

        public function expiredPoint()
        {
            $array_add_point         = Mage::getModel('rewardpoints/type')->getAddPointArray();
            $transaction_collections = Mage::getModel('rewardpoints/rewardpointshistory')->getCollection()
                ->addFieldToFilter('type_of_transaction', array('in' => array($array_add_point)))
                ->addFieldToFilter('status', MW_RewardPoints_Model_Status::COMPLETE)
                ->addFieldToFilter('expired_time', array('neq' => null))
                ->addFieldToFilter('point_remaining', array('gt' => 0))
                ->addFieldToFilter('expired_time', array('to' => time(), 'date' => true))
                ->setOrder('expired_time', 'ASC')
                ->setOrder('history_id', 'DESC');
            //echo $transaction_collections->getSelect();die();
            foreach ($transaction_collections as $transaction_collection)
            {

                $history_id = $transaction_collection->getHistoryId();
                //echo $history_id;die();
                $customer_id     = $transaction_collection->getCustomerId();
                $store_id        = Mage::getModel('customer/customer')->load($customer_id)->getStoreId();
                $point_remaining = $transaction_collection->getPointRemaining();
                $_customer       = Mage::getModel('rewardpoints/customer')->load($customer_id);

                $_customer->addRewardPoint(-$point_remaining);
                $historyData = array('type_of_transaction' => MW_RewardPoints_Model_Type::EXPIRED_POINTS,
                                     'amount'              => (int)$point_remaining,
                                     'balance'             => $_customer->getMwRewardPoint(),
                                     'transaction_detail'  => $history_id,
                                     'transaction_time'    => Mage::getSingleton('core/date')->gmtDate(),
                                     'status'              => MW_RewardPoints_Model_Status::COMPLETE);

                $_customer->saveTransactionHistory($historyData);

                $transaction_collection->setPointRemaining(0)->setExpiredTime(null)->save();

                Mage::helper('rewardpoints')->sendEmailCustomerPointChanged($_customer->getId(), $historyData, $store_id);

            }

        }

        public function expiredPointOld()
        {
            $store_id = Mage::app()->getStore()->getId();
            //strtotime
            $day   = 0;
            $limit = 0;
            $day   = (int)Mage::helper('rewardpoints/data')->getExpirationDaysPoint($store_id);
            $limit = (int)Mage::helper('rewardpoints/data')->getQtyCustomerRunCron($store_id);
            if($limit <= 0)
            {
                $limit = 100;
            }
            if($day > 0)
            {
                $time_day                = $day * 24 * 3600;
                $time_condition          = time() - $time_day;
                $time_condition_sql      = date("Y-m-d H:i:s", $time_condition);
                $array_add_point         = Mage::getModel('rewardpoints/type')->getAddPointArray();
                $array_sub_point         = Mage::getModel('rewardpoints/type')->getSubtractPointArray();
                $array_add_point_sql     = implode(',', $array_add_point);
                $transaction_collections = Mage::getModel('rewardpoints/rewardpointshistory')->getCollection()
                    ->addFieldToFilter('status', MW_RewardPoints_Model_Status::COMPLETE)
                    ->addFieldToFilter('check_time', array('neq' => MW_RewardPoints_Model_Checktime::CHECK))
                    ->addFieldToFilter('transaction_time', array('to' => $time_condition, 'date' => true));


                $transaction_collections->getSelect()->group(array('customer_id'))->limit($limit);
                //echo $transaction_collections->getSelect();die();
                $array_customer = array();
                foreach ($transaction_collections as $transaction_collection)
                {
                    $customer_id = $transaction_collection->getCustomerId();

                    $_customer = Mage::getModel('rewardpoints/customer')->load($customer_id);
                    $point     = 0;
                    $point     = (int)$_customer->getMwRewardPoint();
                    if($point > 0 && in_array($customer_id, $array_customer) == false)
                    {
                        $addpoint_collections = Mage::getModel('rewardpoints/rewardpointshistory')->getCollection()
                            ->addFieldToFilter('customer_id', $customer_id)
                            ->addFieldToFilter('type_of_transaction', array('in' => array($array_add_point)))
                            ->addFieldToFilter('status', MW_RewardPoints_Model_Status::COMPLETE)
                            ->addFieldToFilter('transaction_time', array('from' => $time_condition, 'date' => true));

                        // echo  $addpoint_collections->getSelect();die();

                        $addpoint_collections->addExpressionFieldToSelect('total_add_amount', 'sum(amount)', 'amount');
                        $addpoint_collections->getSelect()->group(array('customer_id'));

                        $_total_add_amount = 0;
                        $_total_add_amount = $addpoint_collections->getFirstItem()->getTotalAddAmount();
                        // echo $_total_add_amount;die();

                        // tong so point trong thoi gian khoang 30 ngay gan nhat (a)
                        // tong so point hien tai (b)
                        // neu b > a tuc' la co point het han
                        $result = $point - $_total_add_amount;

                        if($result > 0)
                        {
                            //echo $result.'customer_id '. $customer_id.'<br></br>';
                            //Subtract reward points of customer
                            $_customer->addRewardPoint(-$result);
                            $historyData = array('type_of_transaction' => MW_RewardPoints_Model_Type::EXPIRED_POINTS,
                                                 'amount'              => (int)$result,
                                                 'balance'             => $_customer->getMwRewardPoint(),
                                                 'transaction_detail'  => '',
                                                 'transaction_time'    => Mage::getSingleton('core/date')->gmtDate(),
                                                 'status'              => MW_RewardPoints_Model_Status::COMPLETE);

                            $_customer->saveTransactionHistory($historyData);

                            $check    = MW_RewardPoints_Model_Checktime::CHECK;
                            $resource = Mage::getSingleton('core/resource');
                            $status   = MW_RewardPoints_Model_Status::COMPLETE;

                            $sql = "UPDATE `{$resource->getTableName('rewardpoints/rewardpointshistory')}` set `check_time`='" . $check . "'
		           		            where `customer_id`= '" . $customer_id . "' and `type_of_transaction` in (" . ($array_add_point_sql) . ") and  `status`='" . $status . "'
		           		            and  `transaction_time`<= '" . $time_condition_sql . "'; ";

                            //echo $sql;die();

                            $conn = Mage::getModel('core/resource')->getConnection('core_write');
                            $conn->query($sql);

                            Mage::helper('rewardpoints')->sendEmailCustomerPointChanged($_customer->getId(), $historyData, $store_id);
                        }

                    }
                    $array_customer[] = $customer_id;
                }
            }

        }

        public function salesConvertQuoteItemToOrderItem(Varien_Event_Observer $observer)
        {
            $quoteItem = $observer->getItem();
            if($additionalOptions = $quoteItem->getOptionByCode('additional_options'))
            {
                $orderItem                     = $observer->getOrderItem();
                $options                       = $orderItem->getProductOptions();
                $options['additional_options'] = unserialize($additionalOptions->getValue());
                $orderItem->setProductOptions($options);
            }
        }

        public function checkoutCartProductUpdateComplete($observer)
        {
            $store_id = Mage::app()->getStore()->getId();
            $item     = $observer->getItem();
            $_product = $item->getProduct();

            $product = Mage::getModel('catalog/product')->load($_product->getId());
            switch ($_product->getTypeId())
            {
                case 'bundle':
                    if(!$product->getData('mw_reward_point_sell_product'))
                    {
                        /** If product do not set sell point, then check sell for each item in product */
                        # GET children products of bundle
                        $items = Mage::getSingleton('checkout/session')->getQuote()->getAllItems();
                        foreach ($items as $item)
                        {
                            if($item->getProductId() == $_product->getId())
                            {
                                foreach ($item->getChildren() as $bundle_item)
                                {
                                    $child_product = Mage::getModel('catalog/product')->load($bundle_item->getProductId());
                                    $this->addCustomOptionPoint($child_product, $bundle_item);
                                    $bundle_item->save();
                                }
                            }
                        }
                    }
                    else
                    {
                        $this->addCustomOptionPoint($product, $item);
                    }
                    break;
                case 'simple':
                case 'virtual':
                case 'downloadable':
                    $this->addCustomOptionPoint($product, $item);
                    break;
                case 'configurable':
                    if(!$product->getData('mw_reward_point_sell_product'))
                    {
                        /** If product do not set sell point, then check sell for each item in product */
                        # GET children products of bundle
                        $items = Mage::getSingleton('checkout/session')->getQuote()->getAllItems();

                        if($info = $item->getProduct()->getCustomOption('info_buyRequest'))
                        {
                            $infoArr = unserialize($info->getValue());
                        }
                        $total_sellpoints = 0;
                        $model            = Mage::getModel('rewardpoints/productsellpoint');
                        foreach ($infoArr['super_attribute'] as $attr_id => $val)
                        {
                            $collection = $model->getCollection()
                                ->addFieldToFilter('product_id', $_product->getId())
                                ->addFieldToFilter('option_id', $val)
                                ->addFieldToFilter('option_type_id', $attr_id)
                                ->addFieldToFilter('type_id', 'super_attribute')
                                ->getFirstItem();

                            $total_sellpoints += intval($collection->getSellPoint());
                        }

                        foreach ($items as $_item)
                        {
                            if($_item->getProductId() == $_product->getId())
                            {
                                foreach ($_item->getChildren() as $conf_item)
                                {
                                    $child_product = Mage::getModel('catalog/product')->load($conf_item->getProductId());
                                    $this->addCustomOptionPoint($child_product, $conf_item, $total_sellpoints);
                                    $conf_item->save();
                                }
                            }
                        }
                        $item->save();
                        Mage::getSingleton('checkout/session')->getQuote()->save();
                    }
                    else
                    {
                        $this->addCustomOptionPoint($product, $item);
                    }
                    break;
            }
        }

        public function checkoutCartProductAddAfter($observer)
        {
            $store_id = Mage::app()->getStore()->getId();
            $_product = $observer->getProduct();
            $item     = $observer->getQuoteItem();

            $product = Mage::getModel('catalog/product')->load($_product->getId());
            switch ($_product->getTypeId())
            {
                case 'bundle':
                    if(!$product->getData('mw_reward_point_sell_product'))
                    {
                        /** If product do not set sell point, then check sell for each item in product */
                        # GET children products of bundle
                        $items = Mage::getSingleton('checkout/session')->getQuote()->getAllItems();
                        foreach ($items as $item)
                        {
                            if($item->getProductId() == $_product->getId())
                            {
                                foreach ($item->getChildren() as $bundle_item)
                                {
                                    $child_product = Mage::getModel('catalog/product')->load($bundle_item->getProductId());
                                    $this->addCustomOptionPoint($child_product, $bundle_item);
                                }
                            }
                        }
                    }
                    else
                    {
                        $this->addCustomOptionPoint($product, $item);
                    }
                    break;
                case 'simple':
                case 'virtual':
                case 'downloadable':
                    $this->addCustomOptionPoint($product, $item);
                    break;
                case 'configurable':
                    if(!$product->getData('mw_reward_point_sell_product'))
                    {
                        /** If product do not set sell point, then check sell for each item in product */
                        # GET children products of bundle
                        $items = Mage::getSingleton('checkout/session')->getQuote()->getAllItems();

                        if($info = $item->getProduct()->getCustomOption('info_buyRequest'))
                        {
                            $infoArr = unserialize($info->getValue());
                        }
                        $total_sellpoints = 0;
                        $model            = Mage::getModel('rewardpoints/productsellpoint');
                        foreach ($infoArr['super_attribute'] as $attr_id => $val)
                        {
                            $collection = $model->getCollection()
                                ->addFieldToFilter('product_id', $_product->getId())
                                ->addFieldToFilter('option_id', $val)
                                ->addFieldToFilter('option_type_id', $attr_id)
                                ->addFieldToFilter('type_id', 'super_attribute')
                                ->getFirstItem();
                            #var_dump((string)$collection->getSelect());
                            #exit;

                            $total_sellpoints += intval($collection->getSellPoint());
                        }

                        foreach ($items as $_item)
                        {
                            if($_item->getProductId() == $_product->getId())
                            {
                                foreach ($_item->getChildren() as $conf_item)
                                {
                                    $child_product = Mage::getModel('catalog/product')->load($conf_item->getProductId());
                                    $this->addCustomOptionPoint($child_product, $conf_item, $total_sellpoints);
                                }
                            }
                        }
                    }
                    else
                    {
                        $this->addCustomOptionPoint($product, $item);
                    }
                    break;
            }
        }

        protected function addCustomOptionPoint($product, $item, $point = 0)
        {
            $store_id        = Mage::app()->getStore()->getId();
            $mw_reward_point = ($point) ? $point : $product->getData('mw_reward_point_sell_product');

            if($mw_reward_point > 0)
            {
                $infoArr = array();

                if($info = $item->getProduct()->getCustomOption('info_buyRequest'))
                {
                    $infoArr = unserialize($info->getValue());
                }

                $additionalOptions = array(array(
                    'code'        => 'sell_in_point',
                    'label'       => Mage::helper('rewardpoints')->__('Sell in Points'),
                    'value'       => Mage::helper('rewardpoints')->formatPoints($mw_reward_point, $store_id),
                    'print_value' => Mage::helper('rewardpoints')->formatPoints($mw_reward_point, $store_id),
                    'orgi_value'  => $mw_reward_point,
                ));

                $item->addOption(array(
                    'code'  => 'additional_options',
                    'value' => serialize($additionalOptions),
                ));
                // Add replacement additional option for reorder (see above)
                $infoArr['additional_options'] = $additionalOptions;

                $info->setValue(serialize($infoArr));
                $item->addOption($info);
            }
        }

        public function setFinalPriceProduct($observer)
        {
            $product    = $observer->getProduct();
            $product_id = $product->getId();
            $points     = 0;
            $points     = Mage::getModel('catalog/product')->load($product_id)->getData('reward_point_product');
            if($points > 0)
            {
                $product->setFinalPrice(0);
            }
            /*
            $product_ids = Mage::getModel('catalog/product')->getCollection()->addAttributeToFilter('mw_reward_point_sell_product',array('gt' => 0))->getAllIds();
            $product = $observer->getProduct();

            if(in_array($product->getId(), $product_ids)  ){
                $product->setFinalPrice(0);
            }

            $product = $observer->getProduct();
            //zend_debug::dump()
            if($product->getData('reward_point_product')>0 ){
                //zend_debug::dump($product->getData('reward_point_product'));die();
                $product->setFinalPrice(10);
            }
             return $this;
            */
        }

        public function setFinalPriceProductCollection($observer)
        {
            $_product_collections = $observer->getCollection()->addAttributeToFilter('mw_reward_point_sell_product', array('gt' => 0));
            $product_ids          = $_product_collections->getAllIds();
            //zend_debug::dump($_product_collections->getAllIds());die();
            foreach ($_product_collections as $product)
            {
                if(in_array($product->getId(), $product_ids))
                {
                    $product->setFinalPrice(0);
                }

            }

        }

        public function rewardForSpecialEvents()
        {
            $rule_id             = 0;
            $type_of_transaction = MW_RewardPoints_Model_Type::SPECIAL_EVENTS;
            $month               = date('m', Mage::getModel('core/date')->timestamp(time()));
            $day                 = date('d', Mage::getModel('core/date')->timestamp(time()));
            $year                = date('Y', Mage::getModel('core/date')->timestamp(time()));
            $active_points       = Mage::getModel('rewardpoints/activerules')->getCollection()
                ->addFieldToFilter('type_of_transaction', $type_of_transaction)
                ->addFieldToFilter('date_event', array('like' => '%' . $year . '-' . $month . '-' . $day))
                ->addFieldToFilter('status', MW_RewardPoints_Model_Statusrule::ENABLED);
            if(sizeof($active_points) > 0)
            {
                foreach ($active_points as $active_point)
                {
                    $points = (int)$active_point->getRewardPoint();
                    if($points > 0)
                    {
                        $rule_id = $active_point->getRuleId();
                    }
                    break;
                }
            }
            if($rule_id != 0)
            {
                $model_active       = Mage::getModel('rewardpoints/activerules')->load($rule_id);
                $reward_point       = (int)$model_active->getRewardPoint();
                $store_view         = $model_active->getStoreView();
                $comment            = $model_active->getComment();
                $customer_group_ids = $model_active->getCustomerGroupIds();

                $customer = Mage::getModel("customer/customer")->getCollection();
                $customer->addFieldToFilter('group_id', array('in' => array($customer_group_ids)));
                $items = $customer->getItems();
                foreach ($items as $item)
                {
                    $expired_time     = null;
                    $point_remaining  = 0;
                    $expired_day      = 0;
                    $store_id         = $item->getStoreId();
                    $customer_id      = $item->getEntityId();
                    $check_store_view = $this->checkActiveRulesStoreView($store_view, $store_id);
                    $default_expired  = $active_point->getDefaultExpired();
                    $expired_day      = $active_point->getExpiredDay();
                    if($default_expired == 1)
                    {
                        $expired_day = (int)Mage::helper('rewardpoints/data')->getExpirationDaysPoint($store_id);
                    }
                    if($expired_day > 0)
                    {
                        $expired_time    = time() + $expired_day * 24 * 3600;
                        $point_remaining = $reward_point;
                    }

                    if($check_store_view && $reward_point > 0 && Mage::helper('rewardpoints')->checkCustomerMaxBalance($customer_id, $store_id, $reward_point))
                    {
                        Mage::helper('rewardpoints/data')->checkAndInsertCustomerId($customer_id, 0);
                        $_customer = Mage::getModel('rewardpoints/customer')->load($customer_id);
                        $_customer->addRewardPoint($reward_point);
                        $historyData = array('type_of_transaction' => $type_of_transaction,
                                             'amount'              => (int)$reward_point,
                                             'balance'             => $_customer->getMwRewardPoint(),
                                             'transaction_detail'  => $comment,
                                             'transaction_time'    => Mage::getSingleton('core/date')->gmtDate(),
                                             'expired_day'         => $expired_day,
                                             'expired_time'        => $expired_time,
                                             'point_remaining'     => $point_remaining,
                                             'status'              => MW_RewardPoints_Model_Status::COMPLETE);

                        $_customer->saveTransactionHistory($historyData);

                        // send mail when points changed
                        Mage::helper('rewardpoints')->sendEmailCustomerPointChanged($_customer->getId(), $historyData, $store_id);
                    }
                }
            }
        }

        public function checkActiveRulesStoreView($store_view, $store_id = null)
        {
            if($store_id == null)
            {
                $store_id = Mage::app()->getStore()->getId();
            }
            //$store_views = explode(',',$store_view);
            if(in_array($store_id, $store_view) OR $store_view[0] == '0')
            {
                return true;
            }
            else
            {
                return false;
            }

        }

        public function rewardForBirthdayPoint()
        {
            //this collection get all users which have birthday on today
            $month    = date('m', Mage::getModel('core/date')->timestamp(time()));
            $day      = date('d', Mage::getModel('core/date')->timestamp(time()));
            $year     = date('Y', Mage::getModel('core/date')->timestamp(time()));
            $customer = Mage::getModel("customer/customer")->getCollection();
            $customer->addFieldToFilter('dob', array('like' => '%' . $month . '-' . $day . ' 00:00:00'));
            //$customer->addNameToSelect();
            $items = $customer->getItems();
            foreach ($items as $item)
            {
                $store_id            = $item->getStoreId();
                $customer_id         = $item->getEntityId();
                $customer_group_id   = $item->getGroupId();
                $type_of_transaction = MW_RewardPoints_Model_Type::CUSTOMER_BIRTHDAY;
                //$points = (double)Mage::getModel('rewardpoints/activerules')->getPointActiveRules($type_of_transaction,$customer_group_id,$store_id);
                $results         = Mage::getModel('rewardpoints/activerules')->getResultActiveRulesExpiredPoints($type_of_transaction, $customer_group_id, $store_id);
                $points          = $results[0];
                $expired_day     = $results[1];
                $expired_time    = $results[2];
                $point_remaining = $results[3];
                if($points > 0 && Mage::helper('rewardpoints')->checkCustomerMaxBalance($customer_id, $store_id, $points))
                {
                    $transactions = Mage::getModel('rewardpoints/rewardpointshistory')->getCollection()
                        ->addFieldToFilter('customer_id', $customer_id)
                        ->addFieldToFilter('type_of_transaction', $type_of_transaction)
                        ->addFieldToFilter('transaction_detail', $year)
                        ->addFieldToFilter('status', array('in' => array(MW_RewardPoints_Model_Status::COMPLETE)));
                    if(sizeof($transactions) == 0)
                    {
                        Mage::helper('rewardpoints/data')->checkAndInsertCustomerId($customer_id, 0);
                        $_customer = Mage::getModel('rewardpoints/customer')->load($customer_id);
                        $_customer->addRewardPoint($points);
                        $historyData = array('type_of_transaction' => $type_of_transaction,
                                             'amount'              => (int)$points,
                                             'balance'             => $_customer->getMwRewardPoint(),
                                             'transaction_detail'  => $year,
                                             'transaction_time'    => Mage::getSingleton('core/date')->gmtDate(),
                                             'expired_day'         => $expired_day,
                                             'expired_time'        => $expired_time,
                                             'point_remaining'     => $point_remaining,
                                             'status'              => MW_RewardPoints_Model_Status::COMPLETE);

                        $_customer->saveTransactionHistory($historyData);

                        // send mail when points changed
                        Mage::helper('rewardpoints')->sendEmailCustomerPointChanged($_customer->getId(), $historyData, $store_id);

                        // send mail when customer birthday
                        Mage::helper('rewardpoints')->sendEmailCustomerPointBirthday($_customer->getId(), $historyData, $store_id);
                    }

                }
            }
        }

        // run cron every day or apply rule manual
        public function applyRules()
        {
            $catalogrules = $this->getCatalogRulesByEnable();
            $catalogrules = $this->getCatalogRulesByTime($catalogrules);
            $catalogrules = $this->getCatalogRulesByPostion($catalogrules);

            $catalogrules = implode(",", $catalogrules);

            if($catalogrules == '')
            {
                $catalogrules = 0;
            }
            $resource = Mage::getSingleton('core/resource');
            //$sql = "DELETE FROM `".$resource->getTableName('rewardpoints/productpoint')."`  ";

            $sql  = "DELETE FROM `" . $resource->getTableName('rewardpoints/productpoint') . "` WHERE `rule_id` not in (" . $catalogrules . ")";
            $conn = Mage::getModel('core/resource')->getConnection('core_write');
            $conn->query($sql);

            Mage::getModel('core/config')->saveConfig('mw_reward_last_id', 0);
            Mage::getConfig()->reinit();

            $this->applyRulesCronEvery();

        }

        public function applyRulesCronEvery()
        {
            $limit    = 500;
            $store_id = Mage::app()->getStore()->getId();
            if(Mage::getStoreConfig('mw_reward_last_id') != -1)
            {
                $value           = 0;
                $last_product_id = 0;
                if(Mage::getStoreConfig('mw_reward_last_id'))
                {
                    $last_product_id = (int)Mage::getStoreConfig('mw_reward_last_id');
                }

                $collection_products = Mage::getModel('catalog/product')->getCollection()
                    ->addFieldToFilter('entity_id', array('gt' => $last_product_id))
                    ->setOrder('entity_id', 'ASC');
                $collection_products->getSelect()->limit($limit);

                if(sizeof($collection_products) > 0)
                {
                    $catalogrules = $this->getCatalogRulesByEnable();
                    $catalogrules = $this->getCatalogRulesByTime($catalogrules);
                    $catalogrules = $this->getCatalogRulesByPostion($catalogrules);
                    //var_dump($catalogrules);die();
                    if(sizeof($catalogrules) > 0)
                    {
                        $catalogrules_select = array();
                        foreach ($catalogrules as $catalogrule_id)
                        {
                            $catalogrules_select[] = $catalogrule_id;
                            $catalog_rule          = Mage::getModel('rewardpoints/catalogrules')->load($catalogrule_id);
                            $reward_point          = (int)$catalog_rule->getRewardPoint();
                            $simple_action         = (int)$catalog_rule->getSimpleAction();
                            $reward_step           = (int)$catalog_rule->getRewardStep();
                            $stop_rule             = (int)$catalog_rule->getStopRulesProcessing();
                            $rule_id               = $catalog_rule->getRuleId();
                            if($reward_point > 0)
                            {
                                foreach ($collection_products as $product)
                                {

                                    $product_id      = $product->getId();
                                    $last_product_id = $product_id;
                                    $product         = Mage::getModel('catalog/product')->load($product_id);

                                    $check_inserts = Mage::getModel('rewardpoints/productpoint')->getCollection()
                                        ->addFieldToFilter('product_id', $product_id)
                                        ->addFieldToFilter('rule_id', $rule_id);
                                    if($catalog_rule->getConditions()->validate($product))
                                    {
                                        $data               = array();
                                        $data['product_id'] = $product_id;
                                        $data['rule_id']    = $rule_id;
                                        if($simple_action == MW_RewardPoints_Model_Typerule::FIXED)
                                        {
                                            $data['reward_point'] = $reward_point;
                                        }
                                        else
                                        {
                                            $final_price            = $product->getFinalPrice();
                                            $finalPriceExcludingTax = Mage::helper('tax')->getPrice($product, $product->getFinalPrice());
                                            $data['reward_point']   = 0;
                                            if($reward_step > 0)
                                            {
                                                $applyRewardPointsTax = (int)Mage::helper('rewardpoints')->getAppyRewardPointsTax($store_id);
                                                if($applyRewardPointsTax == MW_RewardPoints_Model_Appyrewardtax::BEFORE)
                                                {
                                                    $data['reward_point'] = (int)($finalPriceExcludingTax * $reward_point) / $reward_step;
                                                }
                                                else
                                                {
                                                    $data['reward_point'] = (int)($final_price * $reward_point) / $reward_step;
                                                }
                                            }
                                        }
                                        if(sizeof($check_inserts) == 0)
                                        {
                                            if($data['reward_point'] > 0)
                                            {
                                                Mage::getModel('rewardpoints/productpoint')->setData($data)->save();
                                            }
                                        }
                                        else
                                        {
                                            foreach ($check_inserts as $check_insert)
                                            {
                                                if($data['reward_point'] > 0)
                                                {
                                                    $check_insert->setRewardPoint($data['reward_point'])->save();
                                                }
                                                else if($data['reward_point'] == 0)
                                                {
                                                    $check_insert->delete();
                                                }
                                            }
                                        }

                                    }
                                    else
                                    {
                                        foreach ($check_inserts as $check_insert)
                                        {
                                            $check_insert->delete();
                                        }
                                    }
                                }
                            }
                            if($stop_rule)
                            {

                                $catalogrules_selects = implode(",", $catalogrules_select);

                                if($catalogrules_selects == '')
                                {
                                    $catalogrules_selects = 0;
                                }

                                $resource = Mage::getSingleton('core/resource');
                                $sql      = "DELETE FROM `" . $resource->getTableName('rewardpoints/productpoint') . "` WHERE `rule_id` not in (" . $catalogrules_selects . ")";
                                $conn     = Mage::getModel('core/resource')->getConnection('core_write');
                                $conn->query($sql);
                                break;
                            }
                        }

                        Mage::getModel('core/config')->saveConfig('mw_reward_last_id', $last_product_id);
                        Mage::getConfig()->reinit();

                    }
                }
            }
            if(sizeof(Mage::getModel('catalog/product')->getCollection()) <= $limit)
            {
                Mage::getModel('core/config')->saveConfig('mw_reward_last_id', -1);
                Mage::getConfig()->reinit();
            }

        }

        public function getCatalogRulesByEnable()
        {
            $catalogrules            = array();
            $collection_catalogrules = Mage::getModel('rewardpoints/catalogrules')->getCollection()
                ->addFieldToFilter('status', MW_RewardPoints_Model_Statusrule::ENABLED);
            if(sizeof($collection_catalogrules) > 0)
            {
                foreach ($collection_catalogrules as $collection_catalogrule)
                {
                    $catalogrules[] = $collection_catalogrule->getRuleId();
                }
            }

            return $catalogrules;

        }

        public function getCatalogRulesByTime($catalogrules)
        {
            $catalog_rule_ids = array();
            foreach ($catalogrules as $catalogrule)
            {
                $start_date = Mage::getModel('rewardpoints/catalogrules')->load($catalogrule)->getStartDate();
                $end_date   = Mage::getModel('rewardpoints/catalogrules')->load($catalogrule)->getEndDate();
                if(Mage::app()->getLocale()->isStoreDateInInterval(null, $start_date, $end_date))
                {
                    $catalog_rule_ids[] = $catalogrule;
                }

            }

            return $catalog_rule_ids;
        }

        public function getCatalogRulesByPostion($catalogrules)
        {
            $catalog_rule_ids = array();
            $array_position   = array();
            foreach ($catalogrules as $catalogrule)
            {
                $rule_position = (int)Mage::getModel('rewardpoints/catalogrules')->load($catalogrule)->getRulePosition();

                $array_position[]   = $rule_position;
                $catalog_rule_ids[] = $catalogrule;

            }
            if(sizeof($catalog_rule_ids) > 0)
            {
                array_multisort($array_position, $catalog_rule_ids);
            }

            return $catalog_rule_ids;
        }

        public function catalogProductSaveBefore($observer)
        {
            $product = $observer->getProduct();
            $data    = Mage::app()->getRequest()->getPost();

            if(isset($data['mw_reward_point_sell_product']) || isset($data['reward_point_product']))
            {
                try
                {
                    if(count($data['mw_reward_point_sell_product']) > 0)
                    {
                        foreach ($data['mw_reward_point_sell_product'] as $key => $value)
                        {
                            if(substr_count($key, '_') == 1)
                            {
                                $product    = explode('mw_', $key);
                                $product_id = $product[1];
                                if(!$value || $value == 0)
                                {
                                    $value = 0;
                                }
                                $attributesData = array('mw_reward_point_sell_product' => $value);

                                Mage::getSingleton('catalog/product_action')->updateAttributes(array($product_id), $attributesData, 0);
                            }
                            else
                            {
                                if(strpos($key, 'super_attribute') > -1)
                                {
                                    $product        = explode('_', $key);
                                    $product_id     = $product[2];
                                    $option_id      = $product[3];
                                    $option_type_id = $product[4];
                                    $type           = 'super_attribute';
                                }
                                else
                                {
                                    $product        = explode('_', $key);
                                    $product_id     = $product[1];
                                    $option_id      = $product[2];
                                    $option_type_id = $product[3];
                                    $type           = 'custom_option';
                                }

                                $model = Mage::getModel('rewardpoints/productsellpoint');

                                $collection = $model->getCollection()
                                    ->addFieldToFilter('product_id', $product_id)
                                    ->addFieldToFilter('option_id', $option_id)
                                    ->addFieldToFilter('option_type_id', $option_type_id)
                                    ->addFieldToFilter('type_id', $type)
                                    ->getFirstItem();

                                if($collection->getData('id') > 0)
                                {
                                    try
                                    {
                                        if(is_numeric($value))
                                        {
                                            $resource        = Mage::getSingleton('core/resource');
                                            $writeConnection = $resource->getConnection('core_write');
                                            $table           = $resource->getTableName('rewardpoints/productsellpoint');

                                            $query = "UPDATE {$table} SET sell_point = {$value} WHERE product_id = {$product_id} AND option_id = {$option_id} AND option_type_id = {$option_type_id} AND type_id = '{$type}'";
                                            #echo $query . "<br>";
                                            $writeConnection->query($query);
                                        }

                                    }
                                    catch (Exception $e)
                                    {
                                        Mage::log($e->getMessage());
                                    }
                                }
                                else
                                {
                                    try
                                    {
                                        if(is_numeric($value))
                                        {
                                            $resource        = Mage::getSingleton('core/resource');
                                            $writeConnection = $resource->getConnection('core_write');
                                            $table           = $resource->getTableName('rewardpoints/productsellpoint');

                                            $query = "INSERT INTO {$table} SET sell_point = {$value}, product_id = {$product_id}, option_id = {$option_id}, option_type_id = {$option_type_id}, type_id = '{$type}'";
                                            #echo $query . "<br>";
                                            $writeConnection->query($query);
                                        }
                                    }
                                    catch (Exception $e)
                                    {
                                        Mage::log($e->getMessage());
                                    }
                                }
                            }
                        }
                    }

                    if(count($data['reward_point_product']))
                    {
                        foreach ($data['reward_point_product'] as $key => $value)
                        {
                            if(!$value || $value == 0)
                            {
                                $value = 0;
                            }
                            if(substr_count($key, '_') == 1)
                            {
                                $product        = explode('mw_', $key);
                                $product_id     = $product[1];
                                $attributesData = array('reward_point_product' => $value);

                                Mage::getSingleton('catalog/product_action')->updateAttributes(array($product_id), $attributesData, 0);
                            }
                            else
                            {
                                if(strpos($key, 'super_attribute') > -1)
                                {
                                    $product        = explode('_', $key);
                                    $product_id     = $product[2];
                                    $option_id      = $product[3];
                                    $option_type_id = $product[4];
                                    $type           = 'super_attribute';
                                }
                                else
                                {
                                    $product        = explode('_', $key);
                                    $product_id     = $product[1];
                                    $option_id      = $product[2];
                                    $option_type_id = $product[3];
                                    $type           = 'custom_option';
                                }

                                $model = Mage::getModel('rewardpoints/productsellpoint');

                                $collection = $model->getCollection()
                                    ->addFieldToFilter('product_id', $product_id)
                                    ->addFieldToFilter('option_id', $option_id)
                                    ->addFieldToFilter('option_type_id', $option_type_id)
                                    ->addFieldToFilter('type_id', $type)
                                    ->getFirstItem();

                                if($collection->getData('id') > 0)
                                {
                                    try
                                    {
                                        $resource        = Mage::getSingleton('core/resource');
                                        $writeConnection = $resource->getConnection('core_write');
                                        $table           = $resource->getTableName('rewardpoints/productsellpoint');

                                        $query = "UPDATE {$table} SET earn_point = {$value} WHERE product_id = {$product_id} AND option_id = {$option_id} AND option_type_id = {$option_type_id} AND type_id = '{$type}'";
                                        #echo $query . "<br>";
                                        $writeConnection->query($query);
                                    }
                                    catch (Exception $e)
                                    {
                                        Mage::log($e->getMessage());
                                    }
                                }
                                else
                                {
                                    try
                                    {
                                        $resource        = Mage::getSingleton('core/resource');
                                        $writeConnection = $resource->getConnection('core_write');
                                        $table           = $resource->getTableName('rewardpoints/productsellpoint');

                                        $query = "INSERT INTO {$table} SET earn_point = {$value}, product_id = {$product_id}, option_id = {$option_id}, option_type_id = {$option_type_id}, type_id = '{$type}'";
                                        #echo $query . "<br>";
                                        $writeConnection->query($query);
                                    }
                                    catch (Exception $e)
                                    {
                                        Mage::log($e->getMessage());
                                    }
                                }
                            }
                        }
                    }
                    Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('rewardpoints')->__('The reward points has been saved successfully!'));
                    Mage::getSingleton('adminhtml/session')->setFormData(false);

                    return;
                }
                catch (Exception $e)
                {
                    Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                    Mage::getSingleton('adminhtml/session')->setFormData($data);

                    return;
                }
            }
        }

        public function afterRemoveProductInQuote($observer)
        {
            Mage::helper('rewardpoints')->setEmptyRewardpoint();
        }
        public function afterUpdateCheckoutCart($observer)
        {
            Mage::helper('rewardpoints')->setEmptyRewardpoint();
        }
		// ptg code
		public function alertExpiredPoint($observer){
			$the_day = 'P10D';
			$curr_datetime = date("Y-m-d H:i:s", Mage::getModel('core/date')->timestamp(time()));
			$date = new DateTime($curr_datetime);
			$date->add(new DateInterval($the_day));
			$expired_start =  $date->format('Y-m-d');
			$array_add_point = Mage::getModel('rewardpoints/type')->getAddPointArray();
			$transaction_collections = Mage::getModel('rewardpoints/rewardpointshistory')->getCollection()
				->addFieldToFilter('type_of_transaction', array('in' => array($array_add_point)))
				->addFieldToFilter('status', MW_RewardPoints_Model_Status::COMPLETE)
				->addFieldToFilter('expired_time', array('neq' => null))
				->addFieldToFilter('status_check_alert_expire', array('eq' => "0"))
				->addFieldToFilter('point_remaining', array('gt' => 0))
				->addFieldToFilter('expired_time', array('like' => "%$expired_start%"))
				->setOrder('history_id', 'ASC');
			foreach ($transaction_collections as $transaction_collection) {

				$history_id = $transaction_collection->getHistoryId();
				/* echo $history_id;die(); */
				$customer_id = $transaction_collection->getCustomerId();
				$store_id = Mage::getModel('customer/customer')->load($customer_id)->getStoreId();
				$point_remaining = $transaction_collection->getPointRemaining();
				$expired_time = $transaction_collection->getExpiredTime();
				$_customer = Mage::getModel('rewardpoints/customer')->load($customer_id);

				
				$historyData = array(
					'type_of_transaction' => MW_RewardPoints_Model_Type::EXPIRED_POINTS,
					'amount' => (int)$point_remaining,
					'balance' => $_customer->getMwRewardPoint(),
					'transaction_detail' => $history_id,
					'transaction_time' => $expired_time,
					'status' => MW_RewardPoints_Model_Status::COMPLETE
				);
				print_r($historyData);die;
				Mage::helper('rewardpoints')->sendEmailCustomerPointExpiration($customer_id, $historyData, $store_id);
			}
		}
		public function expirationAlertEmail()
		{
			$store_id = Mage::app()->getStore()->getId();
			$day = (int)Mage::helper('rewardpoints/data')->getAlertExpirationDaysEmail($store_id);
			$to = time() + $day * 24 * 3600;
			$array_add_point = Mage::getModel('rewardpoints/type')->getAddPointArray();
			$transaction_collections = Mage::getModel('rewardpoints/rewardpointshistory')->getCollection()
				->addFieldToFilter('type_of_transaction', array('in' => array($array_add_point)))
				->addFieldToFilter('status', MW_RewardPoints_Model_Status::COMPLETE)
				->addFieldToFilter('DATE(expired_time)', array('eq' => date('Y-m-d', $to)))
				->addFieldToFilter('point_remaining', array('gt' => 0))
				->addFieldToFilter('status_check_alert_expire', array('eq' => "0"))
				->setOrder('expired_time', 'DESC')
				->setOrder('history_id', 'ASC');
			//echo $transaction_collections->getSelect();die();
			foreach ($transaction_collections as $transaction_collection) {

				$history_id = $transaction_collection->getHistoryId();
				//echo $history_id;die();
				$customer_id = $transaction_collection->getCustomerId();
				$store_id = Mage::getModel('customer/customer')->load($customer_id)->getStoreId();
				$point_remaining = $transaction_collection->getPointRemaining();
				$expired_time = $transaction_collection->getExpiredTime();
				$_customer = Mage::getModel('rewardpoints/customer')->load($customer_id);
				$subscribed_point_expiration = $_customer->getSubscribedPointExpiration();

				if ($subscribed_point_expiration) {
					$historyData = array('type_of_transaction' => MW_RewardPoints_Model_Type::EXPIRED_POINTS,
						'amount' => (int)$point_remaining,
						'balance' => $_customer->getMwRewardPoint(),
						'transaction_detail' => $history_id,
						'transaction_time' => $expired_time,
						'status' => MW_RewardPoints_Model_Status::COMPLETE);
					$transaction_collection->setStatusCheckAlertExpire(1)->save();


					Mage::helper('rewardpoints')->sendEmailCustomerPointExpiration($customer_id, $historyData, $store_id);
				}

			}

		}
    }