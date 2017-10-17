<?php
    class MW_RewardPoints_Block_Checkout_Total extends Mage_Checkout_Block_Total_Default
    {
        protected $_template = 'mw_rewardpoints/checkout/total.phtml';

        protected function _construct()
        {
            $this->_store = Mage::app()->getStore();
        }
    }
