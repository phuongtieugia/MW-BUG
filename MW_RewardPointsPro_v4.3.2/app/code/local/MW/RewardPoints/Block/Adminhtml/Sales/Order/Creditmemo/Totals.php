<?php
    class MW_RewardPoints_Block_Adminhtml_Sales_Order_Creditmemo_Totals extends Mage_Adminhtml_Block_Sales_Order_Creditmemo_Totals
    {

        protected function _initTotals()
        {
            parent::_initTotals();
            $rewardpoints          = Mage::getModel('rewardpoints/rewardpointsorder')->load($this->getOrder()->getId());
            $store_id              = Mage::getModel('sales/order')->load($this->getOrder()->getId())->getStoreId();
            $total_rewardpoint_use = $rewardpoints->getRewardPoint() + $rewardpoints->getRewardpointSellProduct();

            $earn_rewardpoint = (int)$rewardpoints->getEarnRewardpoint();
            if($earn_rewardpoint > 0)
            {
                $total = new Varien_Object(array(
                    'code'        => 'earn_rewardpoints',
                    'value'       => Mage::helper('rewardpoints')->formatPoints($earn_rewardpoint, $store_id),
                    'base_value'  => Mage::helper('rewardpoints')->formatPoints($earn_rewardpoint, $store_id),
                    'label'       => Mage::helper('rewardpoints')->__('You Earn'),
                    'strong'      => false,
                    'is_formated' => true,
                ));
                $this->addTotal($total, 'first');

            }

            if($total_rewardpoint_use)
            {
                $total = new Varien_Object(array(
                    'code'        => 'rewardpoints_discount',
                    'value'       => Mage::helper('rewardpoints')->formatPoints($total_rewardpoint_use, $store_id),
                    'base_value'  => Mage::helper('rewardpoints')->formatPoints($total_rewardpoint_use, $store_id),
                    'label'       => Mage::helper('rewardpoints')->__('Total Points'),
                    'strong'      => true,
                    'is_formated' => true,
                ));
                $this->addTotal($total, 'last');
            }

            return $this;
        }
    }
