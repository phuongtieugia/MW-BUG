<?php
class MW_Onestepcheckout_Model_Sales_Order extends Mage_Sales_Model_Order
{	
	public function sendNewOrderEmail()
    {
   try
	 {
		 $storeId = $this->getStore()->getId();

        if (!Mage::helper('sales')->canSendNewOrderEmail($storeId)) {
            return $this;
        }
        // Get the destination email addresses to send copies to
        $copyTo = $this->_getEmails(self::XML_PATH_EMAIL_COPY_TO);
        $copyMethod = Mage::getStoreConfig(self::XML_PATH_EMAIL_COPY_METHOD, $storeId);

        // Start store emulation process
        $appEmulation = Mage::getSingleton('core/app_emulation');
        $initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($storeId);

        try {
            // Retrieve specified view block from appropriate design package (depends on emulated store)
            $paymentBlock = Mage::helper('payment')->getInfoBlock($this->getPayment())
                ->setIsSecureMode(true);
            $paymentBlock->getMethod()->setStore($storeId);
            $paymentBlockHtml = $paymentBlock->toHtml();
        } catch (Exception $exception) {
            // Stop store emulation process
            $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);
            throw $exception;
        }

        // Stop store emulation process
        $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);

        // Retrieve corresponding email template id and customer name
        if ($this->getCustomerIsGuest()) {
            $templateId = Mage::getStoreConfig(self::XML_PATH_EMAIL_GUEST_TEMPLATE, $storeId);
            $customerName = $this->getBillingAddress()->getName();
        } else {
            $templateId = Mage::getStoreConfig(self::XML_PATH_EMAIL_TEMPLATE, $storeId);
            $customerName = $this->getCustomerName();
        }

        $mailer = Mage::getModel('core/email_template_mailer');
        $emailInfo = Mage::getModel('core/email_info');
        $emailInfo->addTo($this->getCustomerEmail(), $customerName);
        if ($copyTo && $copyMethod == 'bcc') {
            // Add bcc to customer email
            foreach ($copyTo as $email) {
                $emailInfo->addBcc($email);
            }
        }
        $mailer->addEmailInfo($emailInfo);

        // Email copies are sent as separated emails if their copy method is 'copy'
        if ($copyTo && $copyMethod == 'copy') {
            foreach ($copyTo as $email) {
                $emailInfo = Mage::getModel('core/email_info');
                $emailInfo->addTo($email);
                $mailer->addEmailInfo($emailInfo);
            }
        }

        // Set all required params and send emails
        $mailer->setSender(Mage::getStoreConfig(self::XML_PATH_EMAIL_IDENTITY, $storeId));
        $mailer->setStoreId($storeId);
        $mailer->setTemplateId($templateId);        
        
        // frontend
		if(Mage::getSingleton('core/session')->getDeliveryInforEmail())
				{
					$deliveryInfo = Mage::getSingleton('core/session')->getDeliveryInforEmail();
						$this->setMwCustomercommentInfo($deliveryInfo[0]);						
						if($deliveryInfo[1]=="late")
						{
							$dates = explode("/",$deliveryInfo[2]);
							$newdate = $dates[2]."-".$dates[1]."-".$dates[0];
							if($dates[1] > 12){
								$newdate = $dates[2]."-".$dates[0]."-".$dates[1];
							}
							$this->setDeliverystatus('')
								->setMwdeliverydate($newdate)
								-> setMwdeliverytime($deliveryInfo[3]);
						}
						else 
						{
							$this->setDeliverystatus('As soon as possible')
								->setMwdeliverydate('')
								-> setMwdeliverytime('');
						}
						
						Mage::getSingleton('core/session')->unsDeliveryInforEmail();	
							//Zend_Debug::dump($deliveryInfo);
							//die();
				}
		// 
	/* only fix for sending email in backend */
	 $onstep_order=Mage::getModel('onestepcheckout/onestepcheckout')->getCollection()->addFieldToFilter('sales_order_id',$this->getId())->getFirstItem();
	  if(!empty($onstep_order)){
		$this->setMwCustomercommentInfo($onstep_order->getMwCustomercommentInfo())
				->setMwdeliverydate($onstep_order->getMwDeliverydateDate())
				->setMwdeliverytime($onstep_order->getMwDeliverydateTime());
		};	
   
        $mailer->setTemplateParams(array(
                'order'        => $this,
                'billing'      => $this->getBillingAddress(),
                'payment_html' => $paymentBlockHtml
            )
        );
        $mailer->send();

        $this->setEmailSent(true);
        $this->_getResource()->saveAttribute($this, 'email_sent');
		}
		catch(Exception $e)
		{
			Mage::log($e);		
		}

        return $this;
    }
  /**
     * Save order related objects
     *
     * @return Mage_Sales_Model_Order
     */
    protected function _afterSave()
    { 
		$delivery_infor=Mage::getSingleton('customer/session')->getDeliveryInfor();	
		$saleorderid = $this->getId();	
		$row_saleorderid = Mage::getModel('onestepcheckout/onestepcheckout')->getCollection()
											->addFieldToFilter('sales_order_id',$saleorderid);		
		if(!empty($delivery_infor)){	
				if(!$row_saleorderid->getSize())
				{		
					$onestep=Mage::getModel('onestepcheckout/onestepcheckout');		
					$onestep->setSalesOrderId($this->getId());
					$onestep->setMwCustomercommentInfo($delivery_infor[1]);
					if($delivery_infor[2]=="late"){				
						$onestep->setMwDeliverydateDate($delivery_infor[3]);
						$onestep->setMwDeliverydateTime($delivery_infor[4]);
					};
					$onestep->save();
				}
		Mage::getSingleton('customer/session')->setDeliveryInfor(array());		
		};
        if (null !== $this->_addresses) {
            $this->_addresses->save();
            $billingAddress = $this->getBillingAddress();
            $attributesForSave = array();
            if ($billingAddress && $this->getBillingAddressId() != $billingAddress->getId()) {
                $this->setBillingAddressId($billingAddress->getId());
                $attributesForSave[] = 'billing_address_id';
            }

            $shippingAddress = $this->getShippingAddress();
            if ($shippingAddress && $this->getShippigAddressId() != $shippingAddress->getId()) {
                $this->setShippingAddressId($shippingAddress->getId());
                $attributesForSave[] = 'shipping_address_id';
            }

            if (!empty($attributesForSave)) {
                $this->_getResource()->saveAttribute($this, $attributesForSave);
            }

        }
        if (null !== $this->_items) {
            $this->_items->save();
        }
        if (null !== $this->_payments) {
            $this->_payments->save();
        }
        if (null !== $this->_statusHistory) {
            $this->_statusHistory->save();
        }
        foreach ($this->getRelatedObjects() as $object) {
            $object->save();
        }
        return parent::_afterSave();
    }


}
