<?php
    class MW_RewardPoints_Block_Adminhtml_Sales_Order_Totals extends Mage_Adminhtml_Block_Sales_Order_Totals
    {

        protected function _initTotals()
        {
            parent::_initTotals();
            $rewardpoints = Mage::getModel('rewardpoints/rewardpointsorder')->load($this->getOrder()->getId());

            $baseCurrencyCode    = Mage::getModel('sales/order')->loadByIncrementId($this->getOrder()->getIncrementId())->getData('base_currency_code');
            $currentCurrencyCode = Mage::getModel('sales/order')->loadByIncrementId($this->getOrder()->getIncrementId())->getData('order_currency_code');
            $store_id            = Mage::getModel('sales/order')->load($this->getOrder()->getId())->getStoreId();

            $earn_rewardpoint      = (int)$rewardpoints->getEarnRewardpoint();
            $total_rewardpoint_use = $rewardpoints->getRewardPoint() + $rewardpoints->getRewardpointSellProduct();

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
            if($total_rewardpoint_use > 0)
            {
                $total = new Varien_Object(array(
                    'code'        => 'rewardpoints',
                    'value'       => Mage::helper('rewardpoints')->formatPoints($total_rewardpoint_use, $store_id),
                    'base_value'  => Mage::helper('rewardpoints')->formatPoints($total_rewardpoint_use, $store_id),
                    'label'       => Mage::helper('rewardpoints')->__('Total Points'),
                    'strong'      => true,
                    'is_formated' => true,
                ));
                $this->addTotal($total, 'last');

            }
            if($rewardpoints->getMoney())
            {
                $rewardpoints_show = Mage::helper('directory')->currencyConvert($rewardpoints->getMoney(), $baseCurrencyCode, $currentCurrencyCode);
                $total1            = new Varien_Object(array(
                    'code'       => 'rewardpoints_discount',
                    'value'      => $rewardpoints_show,
                    'base_value' => $rewardpoints->getMoney(),
                    'label'      => Mage::helper('rewardpoints')->__('Discount'),
                ));
                $this->addTotal($total1, 'subtotal');
            }

            return $this;
        }
    }
