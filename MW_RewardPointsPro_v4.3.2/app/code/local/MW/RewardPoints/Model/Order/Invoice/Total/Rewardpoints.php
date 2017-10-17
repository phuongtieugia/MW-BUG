<?php
    class MW_RewardPoints_Model_Order_Invoice_Total_Rewardpoints extends Mage_Sales_Model_Order_Invoice_Total_Abstract
    {
        public function collect(Mage_Sales_Model_Order_Invoice $invoice)
        {
            $order = $invoice->getOrder();

            $totalDiscountAmount     = $order->getMwRewardpointDiscountShow();
            $baseTotalDiscountAmount = $order->getMwRewardpointDiscount();

            $invoice->setGrandTotal($invoice->getGrandTotal() - $totalDiscountAmount);
            $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() - $baseTotalDiscountAmount);

            $invoice->setMwRewardpoint($order->getMwRewardpoint());
            $invoice->setMwRewardpointDiscountShow($totalDiscountAmount);
            $invoice->setMwRewardpointDiscount($baseTotalDiscountAmount);

            return $this;
        }


    }
