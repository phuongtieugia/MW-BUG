<?php

class MW_Ddate_Model_Order extends Mage_Sales_Model_Order
{
    /**
     * Sending email with order data
     *
     * @return Mage_Sales_Model_Order
     */
    public function sendNewOrderEmail()
    {
        //Check OneStepcheckout is running
        if (!Mage::helper('ddate')->isOSCRunning()) {
            if (!Mage::helper('sales')->canSendNewOrderEmail($this->getStore()->getId())) {
                return $this;
            }

            $translate = Mage::getSingleton('core/translate');
            /* @var $translate Mage_Core_Model_Translate */
            $translate->setTranslateInline(false);

            $paymentBlock = Mage::helper('payment')->getInfoBlock($this->getPayment())->setIsSecureMode(true);
            $paymentBlock->getMethod()->setStore($this->getStore()->getId());

            $mailTemplate = Mage::getModel('core/email_template');
            /* @var $mailTemplate Mage_Core_Model_Email_Template */
            $copyTo = $this->_getEmails(self::XML_PATH_EMAIL_COPY_TO);
            $copyMethod = Mage::getStoreConfig(self::XML_PATH_EMAIL_COPY_METHOD, $this->getStoreId());
            if ($copyTo && $copyMethod == 'bcc') {
                foreach ($copyTo as $email) {
                    $mailTemplate->addBcc($email);
                }
            }

            if ($this->getCustomerIsGuest()) {
                $template = Mage::getStoreConfig(self::XML_PATH_EMAIL_GUEST_TEMPLATE, $this->getStoreId());
                $customerName = $this->getBillingAddress()->getName();
            } else {
                $template = Mage::getStoreConfig(self::XML_PATH_EMAIL_TEMPLATE, $this->getStoreId());
                $customerName = $this->getCustomerName();
            }

            $sendTo = array(
                array(
                    'email' => $this->getCustomerEmail(),
                    'name'  => $customerName
                )
            );
            if ($copyTo && $copyMethod == 'copy') {
                foreach ($copyTo as $email) {
                    $sendTo[] = array(
                        'email' => $email,
                        'name'  => null
                    );
                }
            }

            // Check edit order or create new order
            if ($this->getRelationParentRealId()) {
                $incrementId = $this->getRelationParentRealId();
            } else {
                $incrementId = $this->getIncrementId();
            }
            $ddates = Mage::getModel('ddate/ddate')->getCollection();
            $ddates->getSelect()
                ->join(
                    array('date_store'=>$ddates->getTable('ddate_store')),
                    'date_store.ddate_id = main_table.ddate_id',
                    array('date_store.ddate_id','date_store.ddate_comment')
                );
            $ddates->addFieldToFilter('date_store.increment_id', array('eq' => $incrementId));
            if (count($ddates->getData())>0) {
            	foreach($ddates as $ddate) {
            		$this->setDdate(Mage::helper('ddate')->convert_date_format_config($ddate->getDdate()));
            		$this->setDtime(Mage::getModel('ddate/dtime')->load($ddate->getDtime())->getDtime());
            		$this->setDdateComment($ddate->getDdateComment());
            	}
            }
    		$paypalexpress=Mage::registry('paypal_delivery');
			if(!empty($paypalexpress)) {
				$this->setDdate(Mage::helper('ddate')->convert_date_format_config($paypalexpress['date']));
        		$this->setDtime(Mage::getModel('ddate/dtime')->load($paypalexpress['dtime'])->getDtime());
        		$this->setDdateComment($paypalexpress['ddate_comment']);
				Mage::unregister('paypal_delivery');
			}

            foreach ($sendTo as $recipient) {
                $mailTemplate->setDesignConfig(array('area'=>'frontend', 'store'=>$this->getStoreId()))
                    ->sendTransactional(
                        $template,
                        Mage::getStoreConfig(self::XML_PATH_EMAIL_IDENTITY, $this->getStoreId()),
                        $recipient['email'],
                        $recipient['name'],
                        array(
                            'order'         => $this,
                            'billing'       => $this->getBillingAddress(),
                            'payment_html'  => $paymentBlock->toHtml(),
                        )
                    );
            }
            $this->setEmailSent(true);
            $this->_getResource()->saveAttribute($this, 'email_sent');
            $translate->setTranslateInline(true);

            return $this;
        }
    }

    /**
     * Sending email with order update information
     *
     * @return Mage_Sales_Model_Order
     */
    public function sendOrderUpdateEmail($notifyCustomer=true, $comment='')
    {
        // Check OneStepcheckout is running
        if (!Mage::helper('ddate')->isOSCRunning()) {
            if (!Mage::helper('sales')->canSendOrderCommentEmail($this->getStore()->getId())) {
                return $this;
            }

            $copyTo = $this->_getEmails(self::XML_PATH_UPDATE_EMAIL_COPY_TO);
            $copyMethod = Mage::getStoreConfig(self::XML_PATH_UPDATE_EMAIL_COPY_METHOD, $this->getStoreId());
            if (!$notifyCustomer && !$copyTo) {
                return $this;
            }

            // set design parameters, required for email (remember current)
            $currentDesign = Mage::getDesign()->setAllGetOld(array(
                'store'   => $this->getStoreId(),
                'area'    => 'frontend',
                'package' => Mage::getStoreConfig('design/package/name', $this->getStoreId()),
            ));

            $translate = Mage::getSingleton('core/translate');
            /* @var $translate Mage_Core_Model_Translate */
            $translate->setTranslateInline(false);

            $sendTo = array();
            $mailTemplate = Mage::getModel('core/email_template');

            if ($this->getCustomerIsGuest()) {
                $template = Mage::getStoreConfig(self::XML_PATH_UPDATE_EMAIL_GUEST_TEMPLATE, $this->getStoreId());
                $customerName = $this->getBillingAddress()->getName();
            } else {
                $template = Mage::getStoreConfig(self::XML_PATH_UPDATE_EMAIL_TEMPLATE, $this->getStoreId());
                $customerName = $this->getCustomerName();
            }

            if ($notifyCustomer) {
                $sendTo[] = array(
                    'name'  => $customerName,
                    'email' => $this->getCustomerEmail()
                );
                if ($copyTo && $copyMethod == 'bcc') {
                    foreach ($copyTo as $email) {
                        $mailTemplate->addBcc($email);
                    }
                }

            }

            if ($copyTo && ($copyMethod == 'copy' || !$notifyCustomer)) {
                foreach ($copyTo as $email) {
                    $sendTo[] = array(
                        'name'  => null,
                        'email' => $email
                    );
                }
            }
        	$ddates = Mage::getModel('ddate/ddate')->getCollection();
            $ddates->getSelect()
            	->join(
            		array('date_store'=>$ddates->getTable('ddate_store')),
            		'date_store.ddate_id = main_table.ddate_id AND date_store.increment_id='.$this->getIncrementId(),
            		array('date_store.ddate_id','date_store.ddate_comment')
            	);
            if (count($ddates->getData())>0) {
            	foreach($ddates as $ddate){
            		$this->setDDate(Mage::helper('ddate')->convert_date_format_config($ddate->getDdate()));
            		$this->setDtime(Mage::getModel('ddate/dtime')->load($ddate->getDtime())->getDtime());
            		$this->setDdateComment($ddate->getDdateComment());
            	}
            }
            foreach ($sendTo as $recipient) {
                $mailTemplate->setDesignConfig(array('area'=>'frontend', 'store' => $this->getStoreId()))
                    ->sendTransactional(
                        $template,
                        Mage::getStoreConfig(self::XML_PATH_UPDATE_EMAIL_IDENTITY, $this->getStoreId()),
                        $recipient['email'],
                        $recipient['name'],
                        array(
                            'order'     => $this,
                            'billing'   => $this->getBillingAddress(),
                            'comment'   => $comment
                        )
                    );
            }

            $translate->setTranslateInline(true);

            // revert current design
            Mage::getDesign()->setAllGetOld($currentDesign);

            return $this;
        }
    }
}
