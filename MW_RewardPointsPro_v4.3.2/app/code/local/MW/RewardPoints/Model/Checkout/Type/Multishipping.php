<?php
    /**
     * User: Anh TO
     * Date: 4/9/14
     * Time: 5:46 PM
     */

    class MW_RewardPoints_Model_Checkout_Type_Multishipping extends Mage_Checkout_Model_Type_Multishipping
    {
        /**
         * Prepare order based on quote address
         *
         * @param   Mage_Sales_Model_Quote_Address $address
         * @return  Mage_Sales_Model_Order
         * @throws  Mage_Checkout_Exception
         */
        protected function _prepareOrder(Mage_Sales_Model_Quote_Address $address)
        {
            $quote = $this->getQuote();
            $quote->unsReservedOrderId();
            $quote->reserveOrderId();
            $quote->collectTotals();

            $convertQuote = Mage::getSingleton('sales/convert_quote');
            $order        = $convertQuote->addressToOrder($address);
            $order->setQuote($quote);
            $order->setBillingAddress(
                $convertQuote->addressToOrderAddress($quote->getBillingAddress())
            );
            $order->setQuoteAdresssId($address->getAddressId());
            if($address->getAddressType() == 'billing')
            {
                $order->setIsVirtual(1);
            }
            else
            {
                $order->setShippingAddress($convertQuote->addressToOrderAddress($address));
            }
            $order->setPayment($convertQuote->paymentToOrderPayment($quote->getPayment()));
            if(Mage::app()->getStore()->roundPrice($address->getGrandTotal()) == 0)
            {
                $order->getPayment()->setMethod('free');
            }

            foreach ($address->getAllItems() as $item)
            {
                $_quoteItem = $item->getQuoteItem();
                if(!$_quoteItem)
                {
                    throw new Mage_Checkout_Exception(Mage::helper('checkout')->__('Item not found or already ordered'));
                }
                $item->setProductType($_quoteItem->getProductType())
                    ->setProductOptions(
                        $_quoteItem->getProduct()->getTypeInstance(true)->getOrderOptions($_quoteItem->getProduct())
                    );
                $orderItem = $convertQuote->itemToOrderItem($item);
                if($item->getParentItem())
                {
                    $orderItem->setParentItem($order->getItemByQuoteItemId($item->getParentItem()->getId()));
                }
                $order->addItem($orderItem);
            }

            return $order;
        }
    }