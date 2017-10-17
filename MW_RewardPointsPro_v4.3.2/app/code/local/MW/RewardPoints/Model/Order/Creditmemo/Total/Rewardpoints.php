<?php
    class MW_RewardPoints_Model_Order_Creditmemo_Total_Rewardpoints extends Mage_Sales_Model_Order_Creditmemo_Total_Abstract
    {
        public function collect(Mage_Sales_Model_Order_Creditmemo $creditmemo)
        {

            $order = $creditmemo->getOrder();

            $totalDiscountAmount     = $order->getMwRewardpointDiscountShow();
            $baseTotalDiscountAmount = $order->getMwRewardpointDiscount();

            $creditmemo->setGrandTotal($creditmemo->getGrandTotal() - $totalDiscountAmount);
            $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() - $baseTotalDiscountAmount);

            $creditmemo->setMwRewardpoint($order->getMwRewardpoint());
            $creditmemo->setMwRewardpointDiscountShow($totalDiscountAmount);
            $creditmemo->setMwRewardpointDiscount($baseTotalDiscountAmount);

            return $this;
        }
    }
