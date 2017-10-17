<?php
    class MW_RewardPoints_RewardpointsController extends Mage_Core_Controller_Front_Action
    {
        //const EMAIL_TO_SEMDER_TEMPLATE_XML_PATH 	= 'rewardpoints/email_notifications/sender_template';
        const EMAIL_TO_RECIPIENT_TEMPLATE_XML_PATH = 'rewardpoints/email_notifications/recipient_template';
        const XML_PATH_EMAIL_IDENTITY              = 'rewardpoints/email_notifications/email_sender';

        /**
         * Retrieve customer session model object
         *
         * @return Mage_Customer_Model_Session
         */
        protected function _getSession()
        {
            return Mage::getSingleton('customer/session');
        }

        protected function _getHelper()
        {
            return Mage::helper('rewardpoints');
        }

        /**
         * Action predispatch
         *
         * Check customer authentication for some actions
         */
        public function preDispatch()
        {
            // a brute-force protection here would be nice

            parent::preDispatch();

            if(!$this->getRequest()->isDispatched())
            {
                return;
            }

            $action = $this->getRequest()->getActionName();
            if(!preg_match('/^(create|login|logoutSuccess|forgotpassword|forgotpasswordpost|confirm|confirmation)/i', $action))
            {
                if(!$this->_getSession()->authenticate($this))
                {
                    $this->setFlag('', 'no-dispatch', true);
                }
            }
            else
            {
                $this->_getSession()->setNoReferer(true);
            }
        }

        public function test1Action()
        {
            echo Mage::getStoreConfig('mw_reward_last_id') . 'aaaaaaaaa';
            //Mage::getModel('rewardpoints/obsever')->applyRulesCronEvery();
            echo 'test';
            die();

        }

        public function testAction()
        {
            Mage::getModel('rewardpoints/obsever')->expiredPoint();
            Mage::getModel('rewardpoints/obsever')->rewardForSpecialEvents();
            Mage::getModel('rewardpoints/obsever')->rewardForBirthdayPoint();
            echo 'test';
            die();

        }

        public function indexAction()
        {
            if(!(Mage::helper('rewardpoints')->moduleEnabled()))
            {
                $this->norouteAction();

                return;
            }
            //Check invition information if exist add reward point to friend
            $friend_id   = Mage::getModel('core/cookie')->get('friend');
            $customer_id = $this->_getSession()->getCustomer()->getId();
            Mage::helper('rewardpoints/data')->checkAndInsertCustomerId($customer_id, $friend_id);

            $this->loadLayout();
            $this->_initLayoutMessages('customer/session');
            $this->_initLayoutMessages('checkout/session');
            $this->getLayout()->getBlock('head')->setTitle($this->__('My Reward Points'));

            $this->renderLayout();
        }

        public function couponAction()
        {
            $store_id = Mage::app()->getStore()->getId();
            if(!(Mage::helper('rewardpoints')->moduleEnabled()))
            {
                $this->norouteAction();

                return;
            }
            $check       = 0;
            $coupon_code = trim($this->getRequest()->getPost("coupon_code"));
            $rule_id     = Mage::getModel('rewardpoints/activerules')->getRuleIdbyCouponCode($coupon_code);
            $customer_id = Mage::getSingleton('customer/session')->getCustomer()->getId();
            if($customer_id && $rule_id)
            {
                $type_of_transaction = MW_RewardPoints_Model_Type::CUSTOM_RULE;
                $transactions        = Mage::getModel('rewardpoints/rewardpointshistory')->getCollection()
                    ->addFieldToFilter('customer_id', $customer_id)
                    ->addFieldToFilter('type_of_transaction', $type_of_transaction)
                    ->addFieldToFilter('transaction_detail', $rule_id)
                    ->addFieldToFilter('status', MW_RewardPoints_Model_Status::COMPLETE);

                if(!sizeof($transactions))
                {
                    Mage::helper('rewardpoints/data')->checkAndInsertCustomerId($customer_id, 0);
                    $_customer         = Mage::getModel('rewardpoints/customer')->load($customer_id);
                    $customer_group_id = Mage::getModel('customer/customer')->load($customer_id)->getGroupId();
                    $store_id          = Mage::app()->getStore()->getId();

                    $results         = Mage::getModel('rewardpoints/activerules')->getPointByRuleId($rule_id, $customer_group_id, $store_id);
                    $points          = $results[0];
                    $expired_day     = $results[1];
                    $expired_time    = $results[2];
                    $point_remaining = $results[3];
                    if($points)
                    {
                        $check = 1;

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
                        $this->_getSession()->addSuccess(Mage::helper('rewardpoints')->__('Congratulation! %s Reward Points have been added to your account', $points));
                    }
                }
            }
            if($check == 0)
            {
                $this->_getSession()->addError(Mage::helper('rewardpoints')->__('Coupon code %s invalid', $coupon_code));
            }
            $this->_redirect('rewardpoints/rewardpoints/index');
        }

        public function setpointsfblikeAction()
        {
            $customer_id         = $this->_getSession()->getCustomer()->getId();
            $url_like            = trim($_POST["pageurl"]);
            $url_fix             = Mage::helper('rewardpoints')->getLinkShareFacebook($url_like);
            $store_id            = Mage::app()->getStore()->getId();
            $type_of_transaction = MW_RewardPoints_Model_Type::LIKE_FACEBOOK;
            $customer_group_id   = Mage::helper('rewardpoints/data')->getCustomerGroupIdFontend();
            $results             = Mage::getModel('rewardpoints/activerules')->getResultActiveRulesExpiredPoints($type_of_transaction, $customer_group_id, $store_id);
            if($customer_id && Mage::helper('rewardpoints')->checkCustomerMaxBalance($customer_id, $store_id, $results[0]))
            {
                //$points = (int)Mage::getModel('rewardpoints/activerules')->getPointActiveRules($type_of_transaction,$customer_group_id,$store_id);
                $points          = $results[0];
                $expired_day     = $results[1];
                $expired_time    = $results[2];
                $point_remaining = $results[3];
                if($points > 0)
                {

                    $transactions = Mage::getModel('rewardpoints/rewardpointshistory')->getCollection()
                        ->addFieldToFilter('customer_id', $customer_id)
                        ->addFieldToFilter('type_of_transaction', $type_of_transaction)
                        ->addFieldToFilter('transaction_detail', $url_fix)
                        ->addFieldToFilter('status', array('in' => array(MW_RewardPoints_Model_Status::COMPLETE)));
                    if(sizeof($transactions) == 0)
                    {
                        Mage::helper('rewardpoints/data')->checkAndInsertCustomerId($customer_id, 0);
                        $_customer = Mage::getModel("rewardpoints/customer")->load($customer_id);
                        $_customer->addRewardPoint($points);
                        $historyData = array('type_of_transaction' => $type_of_transaction,
                                             'amount'              => (int)$points,
                                             'balance'             => $_customer->getMwRewardPoint(),
                                             'transaction_detail'  => $url_fix,
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

        public function setpointsfbsendAction()
        {
            $customer_id         = $this->_getSession()->getCustomer()->getId();
            $url_send            = trim($_POST["pageurl"]);
            $url_fix             = Mage::helper('rewardpoints')->getLinkShareFacebook($url_send);
            $store_id            = Mage::app()->getStore()->getId();
            $customer_group_id   = Mage::helper('rewardpoints/data')->getCustomerGroupIdFontend();
            $type_of_transaction = MW_RewardPoints_Model_Type::SEND_FACEBOOK;
            $results             = Mage::getModel('rewardpoints/activerules')->getResultActiveRulesExpiredPoints($type_of_transaction, $customer_group_id, $store_id);
            if($customer_id && Mage::helper('rewardpoints')->checkCustomerMaxBalance($customer_id, $store_id, $results[0]))
            {
                $points          = $results[0];
                $expired_day     = $results[1];
                $expired_time    = $results[2];
                $point_remaining = $results[3];
                if($points > 0)
                {

                    $transactions = Mage::getModel('rewardpoints/rewardpointshistory')->getCollection()
                        ->addFieldToFilter('customer_id', $customer_id)
                        ->addFieldToFilter('type_of_transaction', $type_of_transaction)
                        ->addFieldToFilter('transaction_detail', $url_fix)
                        ->addFieldToFilter('status', array('in' => array(MW_RewardPoints_Model_Status::COMPLETE)));
                    if(sizeof($transactions) == 0)
                    {
                        Mage::helper('rewardpoints/data')->checkAndInsertCustomerId($customer_id, 0);
                        $_customer = Mage::getModel("rewardpoints/customer")->load($customer_id);
                        $_customer->addRewardPoint($points);
                        $historyData = array('type_of_transaction' => $type_of_transaction,
                                             'amount'              => (int)$points,
                                             'balance'             => $_customer->getMwRewardPoint(),
                                             'transaction_detail'  => $url_fix,
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

        public function emailAction()
        {
            $store_id = Mage::app()->getStore()->getId();
            if(!(Mage::helper('rewardpoints')->moduleEnabled()))
            {
                $this->norouteAction();

                return;
            }

            $subscribed_balance_update   = $this->getRequest()->getPost("subscribed_balance_update");
            $subscribed_point_expiration = $this->getRequest()->getPost("subscribed_point_expiration");

            if($subscribed_balance_update == null)
            {
                $subscribed_balance_update = 0;
            }
            if($subscribed_point_expiration == null)
            {
                $subscribed_point_expiration = 0;
            }

            $customer = Mage::getModel('rewardpoints/customer')->load(Mage::getSingleton("customer/session")->getCustomer()->getId());
            $customer->setSubscribedBalanceUpdate($subscribed_balance_update)->setSubscribedPointExpiration($subscribed_point_expiration)->save();

            $this->_getSession()->addSuccess($this->__("The Email Notification has been saved."));

            $this->_redirect('rewardpoints/rewardpoints/index');
        }

        public function sendAction()
        {
            $store_id = Mage::app()->getStore()->getId();
            if(!(Mage::helper('rewardpoints')->moduleEnabled()))
            {
                $this->norouteAction();

                return;
            }

            if(!Mage::helper('rewardpoints')->allowSendRewardPointsToFriend($store_id))
            {
                $this->norouteAction();

                return;
            }
            $this->_initLayoutMessages('customer/session');
            $this->_initLayoutMessages('checkout/session');

            if($this->getRequest()->getPost())
            {
                /*if($this->_getHelper()->enabledCapcha($store_id)){
                    $require = dirname(dirname(__FILE__))."/Helper/Capcha/Securimage.php";
                    require($require);
                      $img = new Securimage();
                      $valid = $img->check($this->getRequest()->getPost("code"));
                }else{
                    $valid = true;
                }*/
                $valid = true;
                if($valid)
                {
                    $_customer = Mage::getModel('rewardpoints/customer')->load($this->_getSession()->getCustomer()->getId()); //current customer
                    $point     = $this->getRequest()->getPost("amount");
                    if($point < 0)
                    {
                        $point = -$point;
                    }
                    if($_customer->getMwRewardPoint() >= $point)
                    {
                        //send reward point
                        $website_id = Mage::getModel('core/store')->load($store_id)->getWebsiteId();
                        $customer   = Mage::getModel('customer/customer')->setWebsiteId($website_id)->loadByEmail($this->getRequest()->getPost("email"));
                        if($customer->getId() != $_customer->getId())
                        {
                            if($customer->getId())
                            {
                                //Add reward points to friend
                                Mage::helper('rewardpoints/data')->checkAndInsertCustomerId($customer->getId(), 0);
                                $mwCustomer = Mage::getModel('rewardpoints/customer')->load($customer->getId());
                                $mwCustomer->addRewardPoint($point);

                                $results         = Mage::helper('rewardpoints/data')->getTransactionExpiredPoints($point, $store_id);
                                $expired_day     = $results[0];
                                $expired_time    = $results[1];
                                $point_remaining = $results[2];

                                $historyData = array('type_of_transaction' => MW_RewardPoints_Model_Type::RECIVE_FROM_FRIEND,
                                                     'amount'              => $point,
                                                     'balance'             => $mwCustomer->getMwRewardPoint(),
                                                     'transaction_detail'  => $_customer->getId(),
                                                     'transaction_time'    => Mage::getSingleton('core/date')->gmtDate(),
                                                     'expired_day'         => $expired_day,
                                                     'expired_time'        => $expired_time,
                                                     'point_remaining'     => $point_remaining,
                                                     'status'              => MW_RewardPoints_Model_Status::COMPLETE);

                                $mwCustomer->saveTransactionHistory($historyData);

                                // send mail when points changed
                                Mage::helper('rewardpoints')->sendEmailCustomerPointChanged($customer->getId(), $historyData, $store_id);

                                //Subtract reward points of current customer
                                $_customer->addRewardPoint(-$point);
                                $historyData = array('type_of_transaction' => MW_RewardPoints_Model_Type::SEND_TO_FRIEND,
                                                     'amount'              => $point,
                                                     'balance'             => $_customer->getMwRewardPoint(),
                                                     'transaction_detail'  => $customer->getId(),
                                                     'transaction_time'    => Mage::getSingleton('core/date')->gmtDate(),
                                                     'status'              => MW_RewardPoints_Model_Status::COMPLETE);
                                $_customer->saveTransactionHistory($historyData);

                                // process expired points when spent point
                                Mage::helper('rewardpoints/data')->processExpiredPointsWhenSpentPoints($_customer->getId(), $point);

                                // send mail when points changed
                                Mage::helper('rewardpoints')->sendEmailCustomerPointChanged($_customer->getId(), $historyData, $store_id);

                                $this->_getSession()->addSuccess($this->__("Your reward points were sent successfuly"));
                                $this->_redirect('rewardpoints/rewardpoints/index');
                            }
                            else
                            {
                                //Subtract reward points of current customer
                                $_customer->addRewardPoint(-$point);
                                $historyData = array('type_of_transaction' => MW_RewardPoints_Model_Type::SEND_TO_FRIEND,
                                                     'amount'              => $point,
                                                     'balance'             => $_customer->getMwRewardPoint(),
                                                     'transaction_detail'  => $this->getRequest()->getPost("email"),
                                                     'transaction_time'    => Mage::getSingleton('core/date')->gmtDate(),
                                                     'status'              => MW_RewardPoints_Model_Status::PENDING);

                                $_customer->saveTransactionHistory($historyData);

                                // process expired points when spent point
                                Mage::helper('rewardpoints/data')->processExpiredPointsWhenSpentPoints($_customer->getId(), $point);

                                // send mail when points changed
                                Mage::helper('rewardpoints')->sendEmailCustomerPointChanged($_customer->getId(), $historyData, $store_id);

                                //customer dose not exist
                                $this->_getSession()->addSuccess($this->__("Your reward points were sent successfully"));
                            }

                            if(Mage::helper('rewardpoints')->allowSendEmailNotifications($store_id))
                            {
                                //Send mail to frend
                                $store_name = Mage::getStoreConfig('general/store_information/name', $store_id);
                                $sender     = Mage::getStoreConfig(self::XML_PATH_EMAIL_IDENTITY, $store_id);
                                $mailto     = $this->getRequest()->getPost('email');
                                $name       = $this->getRequest()->getPost('name');
                                $template   = self::EMAIL_TO_RECIPIENT_TEMPLATE_XML_PATH;
                                $postObject = new Varien_Object();
                                $postObject->setData($this->getRequest()->getPost());
                                $postObject->setSender($_customer->getCustomerModel());
                                $postObject->setData('login_link', Mage::app()->getStore($store_id)->getUrl('customer/account/login'));
                                $postObject->setData('customer_link', Mage::app()->getStore($store_id)->getUrl('rewardpoints/rewardpoints/index'));
                                $postObject->setData('register_link', Mage::app()->getStore($store_id)->getUrl('customer/account/create'));
                                $postObject->setStoreName($store_name);
                                Mage::helper('rewardpoints')->_sendEmailTransaction($sender, $mailto, $name, $template, $postObject->getData(), $store_id);
                            }
                            /*
                            if(Mage::helper('rewardpoints')->allowSendEmailToSender($store_id))
                            {
                                //Send mail to sender
                                $sender = Mage::getStoreConfig(self::XML_PATH_EMAIL_IDENTITY, $store_id);
                                $mailto = $_customer->getCustomerModel()->getEmail();
                                $name = $_customer->getCustomerModel()->getName();
                                $template = self::EMAIL_TO_SEMDER_TEMPLATE_XML_PATH;
                                $postObject = new Varien_Object();
                                $postObject->setData('amount',$this->getRequest()->getPost('amount'));
                                $postObject->setData('name',$name);
                                Mage::helper('rewardpoints')->_sendEmailTransaction($sender,$mailto, $name, $template, $postObject->getData(),$store_id);
                            }*/
                        }
                        else
                        {
                            $this->_getSession()->addError($this->__("You can not send reward points to yourself"));
                        }
                    }
                    else
                    {
                        //Current total reward points do not enought to send
                        $this->_getSession()->addError($this->__("You do not have enough points to send to your friend"));
                    }
                }
                else
                {
                    //return error
                    $this->_getSession()->addError($this->__("Your security code is incorrect"));
                }
            }
            else
            {
                $this->_getSession()->addError($this->__("You do not have permission!"));
            }
            $this->_redirect('rewardpoints/rewardpoints/index');
        }

        public function exchangeAction()
        {
            if(!(Mage::helper('rewardpoints')->moduleEnabled()))
            {
                $this->norouteAction();

                return;
            }
            $store_id = Mage::app()->getStore()->getId();
            if(!Mage::helper('rewardpoints')->allowExchangePointToCredit($store_id))
            {
                $this->norouteAction();

                return;
            }
            $points    = $this->getRequest()->getPost('exchange_points');
            $_customer = Mage::getModel('rewardpoints/customer')->load($this->_getSession()->getCustomerId());
            if($points > $_customer->getRewardPoint())
            {
                $this->_getSession()->addError($this->__("You do not enought points to exchange"));

                return;
            }

            if(Mage::helper('rewardpoints')->getCreditModule())
            {
                $exchangeRate = explode('/', Mage::helper('rewardpoints')->pointCreditRate($store_id));
                if(sizeof($exchangeRate) == 2)
                {
                    if($points < 0)
                    {
                        $points = -$points;
                    }
                    $credit = ($points * $exchangeRate[1] * 1.0) / $exchangeRate[0];
                    //add credit to customer
                    $customerCredit = Mage::getSingleton('credit/creditcustomer')->load($this->_getSession()->getCustomer()->getId());
                    $oldCredit      = $customerCredit->getCredit();
                    $newCredit      = $oldCredit + $credit;
                    $customerCredit->setCredit($newCredit)->save();
                    $historyData = array('type_transaction'      => MW_Credit_Model_TransactionType::SEND_TO_FRIEND,
                                         'transaction_detail'    => $points,
                                         'amount'                => $credit,
                                         'beginning_transaction' => $oldCredit,
                                         'end_transaction'       => $newCredit,
                                         'created_time'          => Mage::getSingleton('core/date')->gmtDate());
                    Mage::getModel("credit/credithistory")->saveTransactionHistory($historyData);
                    //Subtract points

                    $customerRewardPoints = Mage::getModel('rewardpoints/customer')->load($this->_getSession()->getCustomer()->getId());
                    $customerRewardPoints->addRewardPoint(-$points);
                    $historyData = array('type_of_transaction' => MW_RewardPoints_Model_Type::EXCHANGE_TO_CREDIT,
                                         'amount'              => $points,
                                         'balance'             => $customerRewardPoints->getMwRewardPoint(),
                                         'transaction_detail'  => $credit,
                                         'transaction_time'    => Mage::getSingleton('core/date')->gmtDate(),
                                         'status'              => MW_RewardPoints_Model_Status::COMPLETE);
                    $customerRewardPoints->saveTransactionHistory($historyData);

                    $this->_getSession()->addSuccess($this->__("Your reward points was exchanged to credit successfuly"));
                }
                else
                {
                    $this->_getSession()->addError($this->__("There is a system error. Please contact to administrator."));
                }
            }
            else
            {
                $this->_getSession()->addError($this->__("Credit module error or has not been installed yet"));
            }
            $this->_redirect('rewardpoints/rewardpoints/index');
        }

        public function imageAction()
        {
            //require(str_replace("index.php/","",Mage::getBaseDir()).DS.'mw_capcha'.DS.'Securimage.php');
            $store_id = Mage::app()->getStore()->getId();
            if(!Mage::helper('rewardpoints')->enabledCapcha($store_id))
            {
                $this->norouteAction();

                return;
            }
            $require = dirname(dirname(__FILE__)) . "/Helper/Capcha/Securimage.php";
            require($require);
            $hp  = $this->_getHelper();
            $img = new Securimage();

            //Change some settings
            $img->use_wordlist                 = $hp->capchaUseWordList($store_id);
            $img->image_width                  = $hp->getCapchaImageWidth($store_id);
            $img->image_height                 = $hp->getCapchaImageHeight($store_id);
            $img->perturbation                 = $hp->getCapchaPerturbation($store_id);
            $img->code_length                  = $hp->getCapchaCodeLength($store_id);
            $img->image_bg_color               = new Securimage_Color($hp->getCapchaBackgroundColor($store_id));
            $img->use_transparent_text         = $hp->capchaUseTransparentText($store_id);
            $img->text_transparency_percentage = $hp->getCapchaTextTransparencyPercentage($store_id); // 100 = completely transparent
            $img->num_lines                    = $hp->getCapchaNumberLine($store_id);
            $img->text_color                   = new Securimage_Color($hp->getCapchaTextColor($store_id));
            $img->line_color                   = new Securimage_Color($hp->getCapchaLineColor($store_id));
            $backgroundFile                    = $hp->getCapchaBackgroundImage($store_id);
            $img->show($backgroundFile);
        }
    }