<?php
    class MW_RewardPoints_Block_Checkout_Cart_Rewardpoints extends Mage_Core_Block_Template
    {
        public function _prepareLayout()
        {
            return parent::_prepareLayout();
        }

        /**
         * Get checkout session model instance
         *
         * @return Mage_Checkout_Model_Session
         */
        protected function _getSession()
        {
            return Mage::getSingleton('checkout/session');
        }

        protected function _getCustomer()
        {
            return Mage::getSingleton('customer/session')->getCustomer();
        }

        protected function _getQuote()
        {
            return Mage::getSingleton('checkout/session')->getQuote();
        }

        public function getRewardPoints()
        {
            return Mage::getSingleton('checkout/session')->getRewardPoints();
        }

        public function getPointPerMoney($store_id)
        {
            $config = Mage::helper('rewardpoints')->getPointMoneyRateConfig($store_id);
            $rate   = explode("/", $config);

            return $rate;
        }

        public function formatMoney($money)
        {
            return Mage::helper('core')->currency($money);
        }

        public function getCurrentRewardPoints()
        {
            $customer = Mage::getModel('rewardpoints/customer')->load($this->_getCustomer()->getId());

            return $customer->getMwRewardPoint();
        }

        public function getRewardPointsRule()
        {
            return Mage::helper('rewardpoints')->getCheckoutRewardPointsRule($this->_getQuote());
        }

        public function getMaxPointsToCheckout()
        {
            return Mage::helper('rewardpoints')->getMaxPointToCheckOut();

        }

        public function formatNumber($value)
        {
            return Mage::helper('rewardpoints')->formatNumber($value);
        }
    }