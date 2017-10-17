<?php

class MW_Ddate_Model_Paypal_Express_Checkout extends Mage_Paypal_Model_Express_Checkout
{
    public function place($token, $ddate = null, $shippingMethodCode = null)
    {
        if ($shippingMethodCode) {
            $this->updateShippingMethod($shippingMethodCode);
        }

        $isNewCustomer = false;
        switch ($this->getCheckoutMethod()) {
            case Mage_Checkout_Model_Type_Onepage::METHOD_GUEST:
                $this->_prepareGuestQuote();
                break;
            case Mage_Checkout_Model_Type_Onepage::METHOD_REGISTER:
                $this->_prepareNewCustomerQuote();
                $isNewCustomer = true;
                break;
            default:
                $this->_prepareCustomerQuote();
                break;
        }

        $this->_ignoreAddressValidation();
        $this->_quote->collectTotals();
        $service = Mage::getModel('sales/service_quote', $this->_quote);
        $service->submitAll();
        $this->_quote->save();

        if ($isNewCustomer) {
            try {
                $this->_involveNewCustomer();
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }

        $this->_recurringPaymentProfiles = $service->getRecurringPaymentProfiles();
        // TODO: send recurring profile emails

        $order = $service->getOrder();
        if (!$order) {
            return;
        }

    	if ($order) {
			if ($ddate) {
				$ddatePost = $ddate;
				$ddate = $ddatePost['date'];
				$dtime = $ddatePost['dtime'];
				$ddateComment = $ddatePost['ddate_comment'];
				$this->_getQuote()->setDdate($ddate);
				$this->_getQuote()
						->setDtime(Mage::getModel('ddate/dtime')
						->load($dtime)->getDtime());
				$this->_getQuote()->setDdateComment($ddateComment);
				$this->_getQuote()->save();

				$order->setDdate($ddate);
				$order->setDdateComment($ddateComment);
				$order->setDtime(Mage::getModel('ddate/dtime')->load($dtime)->getDtime());
				$order->save();
				
				$ddates = Mage::getModel('ddate/ddate')->getCollection()
						->addFieldToFilter('ddate', array('like' => $ddate . '%'))
						->addFieldToFilter('dtime', $dtime);
				if ($ddates->count() > 0) {
                    foreach ($ddates as $ddate1) {
                        $ddate1->setOrdered($ddate1->getOrdered() + 1);
                        $ddate1->setIncrementId($order->getIncrementId());
                        $ddate1->setDdateComment($ddateComment);
                        $ddate1->save();
                        break;
                    }
                } else {
                    $_ddate = Mage::getModel('ddate/ddate');
                    $_ddate->setDdate($ddate);
                    $_ddate->setDtime($dtime);
                    $_ddate->setOrdered(1);
                    $_ddate->setIncrementId($order->getIncrementId());
                    $_ddate->setDdateComment($ddateComment);
                    $_ddate->save();
                }
			}
		}        

        $this->_billingAgreement = $order->getPayment()->getBillingAgreement();

        // commence redirecting to finish payment, if paypal requires it
        if ($order->getPayment()->getAdditionalInformation(
            Mage_Paypal_Model_Express_Checkout::PAYMENT_INFO_TRANSPORT_REDIRECT
        )) {
            $this->_redirectUrl = $this->_config->getExpressCheckoutCompleteUrl($token);
        }
        switch ($order->getState()) {
            // even after placement paypal can disallow to authorize/capture, but will wait until bank transfers money
            case Mage_Sales_Model_Order::STATE_PENDING_PAYMENT:
                // TODO
                break;
            // regular placement, when everything is ok
            case Mage_Sales_Model_Order::STATE_PROCESSING:
            case Mage_Sales_Model_Order::STATE_COMPLETE:
            case Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW:
                $order->sendNewOrderEmail();
                break;
        }

        $this->_order = $order;
    }	
    
    private function _ignoreAddressValidation()
    {
        $this->_quote->getBillingAddress()->setShouldIgnoreValidation(true);
        if (!$this->_quote->getIsVirtual()) {
            $this->_quote->getShippingAddress()->setShouldIgnoreValidation(true);
            if (!$this->_config->requireBillingAddress && !$this->_quote->getBillingAddress()->getEmail()) {
                $this->_quote->getBillingAddress()->setSameAsBilling(1);
            }
        }
    }  

    public function _getQuote()
    {
        if (!$this->_quote) {
            $this->_quote = $this->_getCheckoutSession()->getQuote();
        }

        return $this->_quote;
    }    
}
