<?php
    class MW_RewardPoints_Model_Order_Pdf_Total_Rewardpoints extends Mage_Sales_Model_Order_Pdf_Total_Default
    {
        /**
         * Get array of arrays with totals information for display in PDF
         * array(
         *  $index => array(
         *      'amount'   => $amount,
         *      'label'    => $label,
         *      'font_size'=> $font_size
         *  )
         * )
         * @return array
         */
        public function getTotalsForDisplay()
        {
            $amount = $this->getOrder()->formatPriceTxt($this->getAmount());
            if($this->getAmountPrefix())
            {
                $amount = $this->getAmountPrefix() . $amount;
            }
            $label    = Mage::helper('sales')->__($this->getTitle()) . ':';
            $fontSize = $this->getFontSize() ? $this->getFontSize() : 7;
            $total    = array(
                'amount'    => $amount,
                'label'     => $label,
                'font_size' => $fontSize
            );

            return array($total);
        }

        /**
         * Get array of arrays with tax information for display in PDF
         * array(
         *  $index => array(
         *      'amount'   => $amount,
         *      'label'    => $label,
         *      'font_size'=> $font_size
         *  )
         * )
         * @return array
         */
        public function getFullTaxInfo()
        {
            $rates    = Mage::getResourceModel('sales/order_tax_collection')->loadByOrder($this->getOrder())->toArray();
            $fullInfo = Mage::getSingleton('tax/calculation')->reproduceProcess($rates['items']);
            $fontSize = $this->getFontSize() ? $this->getFontSize() : 7;
            $tax_info = array();

            if($fullInfo)
            {
                foreach ($fullInfo as $info)
                {
                    if(isset($info['hidden']) && $info['hidden'])
                    {
                        continue;
                    }

                    $_amount = $info['amount'];

                    foreach ($info['rates'] as $rate)
                    {
                        $percent = $rate['percent'] ? ' (' . $rate['percent'] . '%)' : '';

                        $tax_info[] = array(
                            'amount'    => $this->getAmountPrefix() . $this->getOrder()->formatPriceTxt($_amount),
                            'label'     => Mage::helper('tax')->__($rate['title']) . $percent . ':',
                            'font_size' => $fontSize
                        );
                    }
                }
            }

            return $tax_info;
        }

        /**
         * Check if we can display total information in PDF
         *
         * @return bool
         */
        public function canDisplay()
        {
            $amount = $this->getAmount();

            return $this->getDisplayZero() || ($amount != 0);
        }

        /**
         * Get Total amount from source
         *
         * @return float
         */
        public function getAmount()
        {
            $_order              = $this->getOrder();
            $baseCurrencyCode    = Mage::getModel('sales/order')->load($_order->getId())->getData('base_currency_code');
            $currentCurrencyCode = Mage::getModel('sales/order')->load($_order->getId())->getData('order_currency_code');

            $rewardOrder                = Mage::getModel('rewardpoints/rewardpointsorder')->load($_order->getId());
            $reward_point_discount_show = $_order->getMwRewardpointDiscountShow();
            if($reward_point_discount_show == 0)
            {
                $reward_point_discount_show = Mage::helper('directory')->currencyConvert($rewardOrder->getMoney(), $baseCurrencyCode, $currentCurrencyCode);
            }

            return $reward_point_discount_show;

        }

    }
