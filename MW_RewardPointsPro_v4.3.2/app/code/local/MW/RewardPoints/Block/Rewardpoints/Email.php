<?php
    class MW_RewardPoints_Block_Rewardpoints_Email extends Mage_Core_Block_Template
    {
        protected function _getCustomer()
        {
            return Mage::getModel('rewardpoints/customer')->load(Mage::getSingleton("customer/session")->getCustomer()->getId());
        }

        public function getSubscribedBalanceUpdate()
        {
            return $this->_getCustomer()->getSubscribedBalanceUpdate();
        }

        public function getSubscribedPointExpiration()
        {
            return $this->_getCustomer()->getSubscribedPointExpiration();
        }


    }