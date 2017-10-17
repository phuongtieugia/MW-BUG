<?php

class MW_Ddate_Model_Type_Multishipping extends Mage_Checkout_Model_Type_Multishipping
{
    /**
    * Create orders per each quote address
    *
    * @return Mage_Checkout_Model_Type_Multishipping
    */
    public function createOrders()
    {
        $orderIds = array();
        $this->_validate();
        $shippingAddresses = $this->getQuote()->getAllShippingAddresses();
        $orders = array();

        if ($this->getQuote()->hasVirtualItems()) {
            $shippingAddresses[] = $this->getQuote()->getBillingAddress();
        }

        try {
            foreach ($shippingAddresses as $address) {
                $order = $this->_prepareOrder($address);
				//Ddate code
				$delivery = Mage::helper('ddate')->find_delivery_info($address->getId());
				$order->setDdate($delivery['ddate']);
				$order->setDdateComment($delivery['mwcomment']);
				$order->setDtime($delivery['dtime']);
				//end

                $orders[] = $order;
                Mage::dispatchEvent(
                    'checkout_type_multishipping_create_orders_single',
                    array('order'=>$order, 'address'=>$address)
                );
            }

            foreach ($orders as $order) {
                $order->place();
                $order->save();
				$this->saveDdate($order->getDdate(),$order->getDtime(),$order->getDdateComment(),$order->getIncrementId(), $order->getId());
				$order->setDtime(Mage::getModel('ddate/dtime')->load($order->getDtime())->getDtime());
                if ($order->getCanSendNewEmailFlag()) {
                    $order->sendNewOrderEmail();
                }
                $orderIds[$order->getId()] = $order->getIncrementId();
            }
			//DDate code detroy session
			Mage::getSingleton('customer/session')->unsDdateinfo();

            Mage::getSingleton('core/session')->setOrderIds($orderIds);
            Mage::getSingleton('checkout/session')->setLastQuoteId($this->getQuote()->getId());

            $this->getQuote()->setIsActive(false)->save();

            Mage::dispatchEvent('checkout_submit_all_after', array('orders' => $orders, 'quote' => $this->getQuote()));

            return $this;
        } catch (Exception $e) {
            Mage::dispatchEvent('checkout_multishipping_refund_all', array('orders' => $orders));
            throw $e;
        }
    }

	/*
	* $ddate array()  Delivery infor array
	* $id order id
	* return true|false
	*/
	public function saveDdate($ddate,$dtime,$ddate_comment,$id,$sales_order_id)
    {		
        $ddates = Mage::getModel('ddate/ddate')->getCollection()
                ->addFieldToFilter('ddate', array('like' => $ddate . '%'))
                ->addFieldToFilter('dtime', $dtime);
        if ($ddates->count() > 0) {
            foreach ($ddates as $ddate1) {
                $ddate1->setOrdered($ddate1->getOrdered() + 1);
                $ddate1->setIncrementId($id);
                $ddate1->setDdateComment($ddate_comment);
                $ddate1->setSalesOrderId($sales_order_id);
                $ddate1->save();
                break;
            }
        } else {
            $_ddate = Mage::getModel('ddate/ddate');
            $_ddate->setDdate($ddate);
            $_ddate->setDtime($dtime);
            $_ddate->setOrdered(1);
            $_ddate->setIncrementId($id);
            $_ddate->setDdateComment($ddate_comment);
            $_ddate->setSalesOrderId($sales_order_id);
            $_ddate->save();
        }
	}
}
