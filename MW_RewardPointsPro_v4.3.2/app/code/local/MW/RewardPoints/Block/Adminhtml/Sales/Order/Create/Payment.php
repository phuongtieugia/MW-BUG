<?php

    class MW_RewardPoints_Block_Adminhtml_Sales_Order_Create_Payment extends Mage_Core_Block_Template
    {

        protected function _getOrderCreateModel()
        {
            return Mage::getSingleton('adminhtml/sales_order_create');
        }

        public function getStoreId()
        {
            $quote = $this->_getOrderCreateModel()->getQuote();;

            return $quote->getStore()->getId();
        }

        public function getRewardPointsRule()
        {
            $quote    = $this->_getOrderCreateModel()->getQuote();
            $store_id = $quote->getStore()->getId();

            return Mage::helper('rewardpoints')->getCheckoutRewardPointsRule($quote, $store_id);
        }

        public function getCurrentRewardPoints()
        {
            $quote       = $this->_getOrderCreateModel()->getQuote();
            $customer_id = $quote->getCustomerId();
            $store_id    = $quote->getStore()->getId();
            $customer    = Mage::getModel('rewardpoints/customer')->load($customer_id);
            $point       = (int)$customer->getMwRewardPoint();
            $point_show  = Mage::helper('rewardpoints')->formatPoints($point, $store_id);

            return $point_show;

        }

        public function getRate($store_id)
        {
            $config = Mage::helper('rewardpoints')->getPointMoneyRateConfig($store_id);
            $rate   = explode("/", $config);

            return $rate;
        }

        public function formatMoney($money)
        {
            return Mage::helper('core')->currency($money);
        }

        public function getEarnPointShow()
        {
            $quote                 = $this->_getOrderCreateModel()->getQuote();
            $store_id              = $quote->getStore()->getId();
            $earn_rewardpoint      = (int)$quote->getEarnRewardpoint();
            $earn_rewardpoint_show = Mage::helper('rewardpoints')->formatPoints($earn_rewardpoint, $store_id);

            return $earn_rewardpoint_show;
        }

        public function getRewardPoints()
        {
            $quote  = $this->_getOrderCreateModel()->getQuote();
            $points = $quote->getMwRewardpoint();

            return $points;
        }

        public function getMaxPointToCheckOut()
        {
            $quote = $this->_getOrderCreateModel()->getQuote();
            $quote->collectTotals()->save();
            $store_id         = $quote->getStore()->getId();
            $customer_id      = $quote->getCustomerId();
            $baseGrandTotal   = $quote->getBaseGrandTotal();
            $spend_point      = Mage::helper('rewardpoints')->getMaxPointToCheckOut($quote, $customer_id, $store_id, $baseGrandTotal);
            $spend_point_show = Mage::helper('rewardpoints')->formatPoints($spend_point, $store_id);

            return $spend_point_show;
        }
    }
