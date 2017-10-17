<?php

    class MW_RewardPoints_Model_Activerules extends Mage_Core_Model_Abstract
    {
        public function _construct()
        {
            parent::_construct();
            $this->_init('rewardpoints/activerules');
        }

        public function getRuleIdbyCouponCode($coupon_code)
        {
            $result_reward_point = 0;
            if($coupon_code != '' && isset($coupon_code))
            {
                $active_points = Mage::getModel('rewardpoints/activerules')->getCollection()
                    ->addFieldToFilter('coupon_code', $coupon_code)
                    ->addFieldToFilter('status', MW_RewardPoints_Model_Statusrule::ENABLED);

                $active_point = $active_points->getFirstItem();

                if(sizeof($active_point) > 0)
                {
                    $result_reward_point = $active_point->getRuleId();
                }
            }

            return $result_reward_point;
        }

        public function getPointByRuleIdNotGroup($rule_id, $store_id = null)
        {
            $result_reward_point = 0;

            $active_point = Mage::getModel('rewardpoints/activerules')->load($rule_id);
            if($active_point->getRuleId() && $active_point->getStatus() == MW_RewardPoints_Model_Statusrule::ENABLED)
            {
                $reward_point     = $active_point->getRewardPoint();
                $store_view       = $active_point->getStoreView();
                $check_store_view = $this->checkActiveRulesStoreViewNew($store_view, $store_id);
                if($check_store_view)
                {
                    $result_reward_point = $reward_point;
                }
            }

            return $result_reward_point;
        }

        public function getPointByRuleId($rule_id, $customer_group_id, $store_id = null)
        {
            $result_reward_point = 0;
            $results             = array();
            $expired_day         = 0;
            $expired_time        = null;
            $point_remaining     = 0;
            $active_point        = Mage::getModel('rewardpoints/activerules')->load($rule_id);
            if($active_point->getRuleId() && $active_point->getStatus() == MW_RewardPoints_Model_Statusrule::ENABLED)
            {
                $default_expired      = $active_point->getDefaultExpired();
                $expired_day          = $active_point->getExpiredDay();
                $reward_point         = $active_point->getRewardPoint();
                $store_view           = $active_point->getStoreView();
                $customer_group_ids   = $active_point->getCustomerGroupIds();
                $check_store_view     = $this->checkActiveRulesStoreViewNew($store_view, $store_id);
                $check_customer_group = $this->checkCustomerGroup($customer_group_ids, $customer_group_id);
                if($check_store_view && $check_customer_group)
                {
                    $result_reward_point = $reward_point;
                    if($default_expired == 1)
                    {
                        $expired_day = (int)Mage::helper('rewardpoints/data')->getExpirationDaysPoint($store_id);
                    }
                }
            }

            if($expired_day > 0)
            {
                $expired_time    = time() + $expired_day * 24 * 3600;
                $point_remaining = $result_reward_point;
            }
            $results[0] = $result_reward_point;
            $results[1] = $expired_day;
            $results[2] = $expired_time;
            $results[3] = $point_remaining;

            return $results;
        }

        public function getRuleIdCustomRule($rule_id_md5)
        {
            $result_reward_point = 0;
            $active_points       = Mage::getModel('rewardpoints/activerules')->getCollection()->addFieldToFilter('status', MW_RewardPoints_Model_Statusrule::ENABLED);
            $active_points->getSelect()->where("md5(rule_id)='" . trim($rule_id_md5) . "'");
            $active_point = $active_points->getFirstItem();

            if(sizeof($active_point) > 0)
            {
                $result_reward_point = $active_point->getRuleId();
            }

            return $result_reward_point;
        }

        public function getPointCustomRules($rule_id_md5, $customer_group_id, $store_id = null)
        {
            $results             = array();
            $result_reward_point = 0;
            $expired_day         = 0;
            $expired_time        = null;
            $point_remaining     = 0;
            if($store_id == null)
            {
                $store_id = Mage::app()->getStore()->getId();
            }

            $active_points = Mage::getModel('rewardpoints/activerules')->getCollection()->addFieldToFilter('status', MW_RewardPoints_Model_Statusrule::ENABLED);
            $active_points->getSelect()->where("md5(rule_id)='" . trim($rule_id_md5) . "'");
            $active_point = $active_points->getFirstItem();

            if(sizeof($active_point) > 0)
            {
                $default_expired      = $active_point->getDefaultExpired();
                $expired_day          = $active_point->getExpiredDay();
                $reward_point         = $active_point->getRewardPoint();
                $store_view           = $active_point->getStoreView();
                $customer_group_ids   = $active_point->getCustomerGroupIds();
                $check_store_view     = $this->checkActiveRulesStoreView($store_view, $store_id);
                $check_customer_group = $this->checkCustomerGroup($customer_group_ids, $customer_group_id);
                if($check_store_view && $check_customer_group)
                {
                    $result_reward_point = $reward_point;
                    if($default_expired == 1)
                    {
                        $expired_day = (int)Mage::helper('rewardpoints/data')->getExpirationDaysPoint($store_id);
                    }
                }
            }
            if($expired_day > 0)
            {
                $expired_time    = time() + $expired_day * 24 * 3600;
                $point_remaining = $result_reward_point;
            }
            $results[0] = $result_reward_point;
            $results[1] = $expired_day;
            $results[2] = $expired_time;
            $results[3] = $point_remaining;

            return $results;
        }

        public function getResultActiveRulesExpiredPoints($type_of_transaction, $customer_group_id, $store_id = null)
        {
            $results         = array();
            $result          = array();
            $result          = $this->getPointActiveRulesExpiredPoints($type_of_transaction, $customer_group_id, $store_id);
            $points          = 0;
            $expired_day     = 0;
            $expired_time    = null;
            $point_remaining = 0;
            $points          = $result[0];
            $expired_day     = (int)$result[1];
            if($expired_day > 0)
            {
                $expired_time    = time() + $expired_day * 24 * 3600;
                $point_remaining = $points;
            }
            $results[0] = $points;
            $results[1] = $expired_day;
            $results[2] = $expired_time;
            $results[3] = $point_remaining;

            return $results;
        }

        public function getPointActiveRulesExpiredPoints($type_of_transaction, $customer_group_id, $store_id = null)
        {
            //$result_reward_point = 0;
            $result    = array();
            $result[0] = 0;
            $result[1] = 0;
            if($store_id == null)
            {
                $store_id = Mage::app()->getStore()->getId();
            }
            $active_points = Mage::getModel('rewardpoints/activerules')->getCollection()
                ->addFieldToFilter('type_of_transaction', $type_of_transaction)
                ->addFieldToFilter('status', MW_RewardPoints_Model_Statusrule::ENABLED);
            if(sizeof($active_points) > 0)
            {
                foreach ($active_points as $active_point)
                {
                    $default_expired      = $active_point->getDefaultExpired();
                    $expired_day          = $active_point->getExpiredDay();
                    $reward_point         = $active_point->getRewardPoint();
                    $store_view           = $active_point->getStoreView();
                    $customer_group_ids   = $active_point->getCustomerGroupIds();
                    $check_store_view     = $this->checkActiveRulesStoreView($store_view, $store_id);
                    $check_customer_group = $this->checkCustomerGroup($customer_group_ids, $customer_group_id);
                    if($check_store_view && $check_customer_group)
                    {
                        //$result_reward_point = $reward_point;
                        if($default_expired == 1)
                        {
                            $expired_day = (int)Mage::helper('rewardpoints/data')->getExpirationDaysPoint($store_id);
                        }
                        $result[0] = $reward_point;
                        $result[1] = $expired_day;
                        break;
                    }
                }

            }

            return $result;


        }

        public function getPointActiveRules($type_of_transaction, $customer_group_id, $store_id = null)
        {
            $result_reward_point = 0;
            $active_points       = Mage::getModel('rewardpoints/activerules')->getCollection()
                ->addFieldToFilter('type_of_transaction', $type_of_transaction)
                ->addFieldToFilter('status', MW_RewardPoints_Model_Statusrule::ENABLED);
            if(sizeof($active_points) > 0)
            {
                foreach ($active_points as $active_point)
                {
                    $reward_point         = $active_point->getRewardPoint();
                    $store_view           = $active_point->getStoreView();
                    $customer_group_ids   = $active_point->getCustomerGroupIds();
                    $check_store_view     = $this->checkActiveRulesStoreView($store_view, $store_id);
                    $check_customer_group = $this->checkCustomerGroup($customer_group_ids, $customer_group_id);
                    if($check_store_view && $check_customer_group)
                    {
                        $result_reward_point = $reward_point;
                        break;
                    }
                }

            }

            return $result_reward_point;


        }

        public function checkCustomerGroup($customer_group_ids, $customer_group_id)
        {
            $_customer_group_ids = explode(',', $customer_group_ids);
            if(in_array($customer_group_id, $_customer_group_ids))
            {
                return true;
            }
            else
            {
                return false;
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

        public function checkActiveRulesStoreViewNew($store_view, $store_id = null)
        {

            if($store_id == null)
            {
                $store_id = Mage::app()->getStore()->getId();
            }
            $store_view = explode(',', $store_view);
            if(in_array($store_id, $store_view) OR $store_view[0] == '0')
            {
                return true;
            }
            else
            {
                return false;
            }

        }


    }