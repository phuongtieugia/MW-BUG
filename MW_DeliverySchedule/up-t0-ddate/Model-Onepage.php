<?php

/**
 * Onepage for Magento version 1.4.1.1
 *
 * */
class MW_Ddate_Model_Type_Onepage extends Mage_Checkout_Model_Type_Onepage {

    /* public function savePayment($data) {
        if (empty($data)) {
            $res = array(
                'error' => -1,
                'message' => Mage::helper('checkout')->__('Invalid data')
            );
            return $res;
        }
        $payment = $this->getQuote()->getPayment();
        $payment->importData($data);

        $this->getQuote()->getShippingAddress()->setPaymentMethod($payment->getMethod());
        $this->getQuote()->collectTotals()->save();

        $this->getCheckout()
                ->setStepData('payment', 'complete', true)
                ->setStepData('ddate', 'allow', true);

        return array();
    } */
	public function saveShippingMethod($shippingMethod)
    {
        if (empty($shippingMethod)) {
            return array('error' => -1, 'message' => Mage::helper('checkout')->__('Invalid shipping method.'));
        }
        $rate = $this->getQuote()->getShippingAddress()->getShippingRateByCode($shippingMethod);
        if (!$rate) {
            return array('error' => -1, 'message' => Mage::helper('checkout')->__('Invalid shipping method.'));
        }
        $this->getQuote()->getShippingAddress()
            ->setShippingMethod($shippingMethod);

        $this->getCheckout()
            ->setStepData('shipping_method', 'complete', true)
            ->setStepData('ddate', 'allow', true);

        return array();
    }

    public function saveDdate($data) {
        if (empty($data)) {
            $res = array(
                'error' => -1,
                'message' => Mage::helper('checkout')->__('Invalid Delivery Date.')
            );
            return $res;
        }

        if (empty($data['date'])) {
            $res = array(
                'error' => -1,
                'message' => Mage::helper('checkout')->__('Please select Delivery Date!')
            );
            return $res;
        }
        /* if (empty($data['ampm'])) {
          $res = array(
          'error' => -1,
          'message' => Mage::helper('checkout')->__('Please select Delivery Date!')
          );
          return $res;
          } */
        if (empty($data['dtime'])) {
            $res = array(
                'error' => -1,
                'message' => Mage::helper('checkout')->__('Please select Delivery Time!')
            );
            return $res;
        }

        $this->getQuote()->setDdate($data['date']);
        $this->getQuote()->setDtime(Mage::getModel('ddate/dtime')->load($data['dtime'])->getDtime());
        $this->getQuote()->setDdateComment($data['ddate_comment']);
        $this->getQuote()->save();

        $_SESSION['ddate'] = $data['date'];
        $_SESSION['dtime'] = $data['dtime'];
        $_SESSION['ddate_comment'] = $data['ddate_comment'];

        $this->getCheckout()
                ->setStepData('ddate', 'complete', true)
                ->setStepData('payment', 'allow', true);

        return array();
    }

    public function saveOrder() {
        $this->validate();
        $isNewCustomer = false;
        switch ($this->getCheckoutMethod()) {
            case self::METHOD_GUEST:
                $this->_prepareGuestQuote();
                break;
            case self::METHOD_REGISTER:
                $this->_prepareNewCustomerQuote();
                $isNewCustomer = true;
                break;
            default:
                $this->_prepareCustomerQuote();
                break;
        }

        $service = Mage::getModel('sales/service_quote', $this->getQuote());
        $service->submitAll();

        if ($isNewCustomer) {
            try {
                $this->_involveNewCustomer();
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }

        $this->_checkoutSession->setLastQuoteId($this->getQuote()->getId())
                ->setLastSuccessQuoteId($this->getQuote()->getId())
                ->clearHelperData()
        ;

        $order = $service->getOrder();
        if ($order) {
            $dtime = (isset($_SESSION['dtime'])) ? $_SESSION['dtime'] : '';
            $ddate = (isset($_SESSION['ddate'])) ? $_SESSION['ddate'] : '';
            $ddate_comment = (isset($_SESSION['ddate_comment'])) ? $_SESSION['ddate_comment'] : '';
            $ddates = Mage::getModel('ddate/ddate')->getCollection()
                    ->addFieldToFilter('ddate', array('like' => $ddate . '%'))
                    ->addFieldToFilter('dtime', $dtime);
            if ($ddates->count() > 0):
                foreach ($ddates as $ddate1) {
                    $ddate1->setOrdered($ddate1->getOrdered() + 1);
                    $ddate1->setIncrementId($order->getIncrementId());
                    $ddate1->setDdateComment($ddate_comment);                   
                    $ddate1->save();
                    break;
                }
            else:
                $_ddate = Mage::getModel('ddate/ddate');
                $_ddate->setDdate($ddate);
                $_ddate->setDtime($dtime);
                $_ddate->setOrdered(1);
                $_ddate->setIncrementId($order->getIncrementId());
                $_ddate->setDdateComment($ddate_comment);
                $_ddate->save();
            endif;

            $order->setDdate($ddate);
            $order->setDdateComment($ddate_comment);
            $order->setDtime(Mage::getModel('ddate/dtime')->load($dtime)->getDtime());
            $order->save();
            $_SESSION['ddate'] = '';
            $_SESSION['dtime'] = '';
            $_SESSION['ddate_comment'] = '';

            Mage::dispatchEvent('checkout_type_onepage_save_order_after', array('order' => $order, 'quote' => $this->getQuote()));

            /**
             * a flag to set that there will be redirect to third party after confirmation
             * eg: paypal standard ipn
             */
            $redirectUrl = $this->getQuote()->getPayment()->getOrderPlaceRedirectUrl();
            /**
             * we only want to send to customer about new order when there is no redirect to third party
             */
            if (!$redirectUrl) {
                try {
                    $order->sendNewOrderEmail();
                } catch (Exception $e) {
                    Mage::logException($e);
                }
            }

            // add order information to the session
            $this->_checkoutSession->setLastOrderId($order->getId())
                    ->setRedirectUrl($redirectUrl)
                    ->setLastRealOrderId($order->getIncrementId());

            // as well a billing agreement can be created
            $agreement = $order->getPayment()->getBillingAgreement();
            if ($agreement) {
                $this->_checkoutSession->setLastBillingAgreementId($agreement->getId());
            }
        }

        // add recurring profiles information to the session
        $profiles = $service->getRecurringPaymentProfiles();
        if ($profiles) {
            $ids = array();
            foreach ($profiles as $profile) {
                $ids[] = $profile->getId();
            }
            $this->_checkoutSession->setLastRecurringProfileIds($ids);
            // TODO: send recurring profile emails
        }

        Mage::dispatchEvent(
                'checkout_submit_all_after', array('order' => $order, 'quote' => $this->getQuote(), 'recurring_profiles' => $profiles)
        );

        return $this;
    }

}

?>