<?php
    class MW_RewardPoints_Block_Rewardpoints_Info extends Mage_Core_Block_Template
    {
        protected function _getCustomer()
        {
            return Mage::getModel('rewardpoints/customer')->load(Mage::getSingleton("customer/session")->getCustomer()->getId());
        }

        public function getRewardPoints()
        {
            return $this->_getCustomer()->getRewardPoint();
        }

        public function getPointPerMoney($store_id)
        {
            $config = Mage::helper('rewardpoints')->getPointMoneyRateConfig($store_id);
            $rate   = explode("/", $config);

            return $rate;
        }

        public function getPointPerCredit($store_id)
        {
            $config = Mage::helper('rewardpoints')->pointCreditRate($store_id);
            $rate   = explode("/", $config);

            return $rate;
        }

        public function formatMoney($money)
        {
            return Mage::helper('rewardpoints')->formatMoney($money);
        }

        public function getMoney($store_id)
        {
            return $this->formatMoney(Mage::helper('rewardpoints')->exchangePointsToMoneys($this->getRewardPoints(), $store_id));
        }

        public function canExchangeToCredit($store_id)
        {
            return Mage::helper('rewardpoints')->allowExchangePointToCredit($store_id) && Mage::helper('rewardpoints')->getCreditModule();
        }
        /*
        public function getPointCurency()
        {
            return Mage::helper('rewardpoints')->getPointCurency();
        }*/
    }