<?php 
	public function getReferralCodeByCheckout()
    {
    	$customer_id = (int)Mage::getSingleton("customer/session")->getCustomer()->getId();
    	$customer_invited = Mage::getModel('affiliate/affiliatecustomers')->load($customer_id)->getCustomerInvited();
    	$affiliate_customers = Mage::getModel('affiliate/affiliatecustomers')->load($customer_invited);
    	if($affiliate_customers->getReferralCode()!=""){
    		Mage::getSingleton('checkout/session')->setReferralCode($affiliate_customers->getReferralCode());
    	}
    	return Mage::getSingleton('checkout/session')->getReferralCode();
    }
    