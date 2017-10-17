<?php
class MW_Storecreditpro_Model_Quote_Address_Total_Storecredit extends Mage_Sales_Model_Quote_Address_Total_Abstract {	
	
	public function __construct() {
		$this->setCode('mw_storecredit');
	}
	
	public function collect(Mage_Sales_Model_Quote_Address $address) {
		
		parent::collect($address);
		$quote = $address->getQuote();
		
		$this->_setAmount(0);
		$this->_setBaseAmount(0);
		$credit_max_checkout = 0;
		$tax = 0;
		$shipping = 0;
		$mw_credit_buy = 0;
		$credit_min_checkout = 0;
		
		$store_id = $quote->getStore()->getId();
		$customer_id = $quote->getCustomerId();
		$mw_storecredit = $quote->getMwStorecredit();
		
		$items = $address->getAllVisibleItems();
		if (!count($items )) {
			return $this;
		}
		if ($customer_id) {
			
			$check_tax = Mage::helper('storecreditpro')->getRedeemedTaxConfig($store_id );
			$check_shipping = Mage::helper('storecreditpro')->getRedeemedShippingConfig($store_id );
			if(!$check_tax)$tax = $address->getBaseTaxAmount();
			if(!$check_shipping) $shipping = $address->getBaseShippingInclTax();
			
			$baseGrandTotal = $address->getBaseGrandTotal() - $tax - $shipping;
			if ($quote->getMwStorecredit() > $baseGrandTotal && $baseGrandTotal > 0) $quote->setMwStorecredit($baseGrandTotal);
		
			$credit_max_checkout = Mage::helper('storecreditpro')->getMaxCreditToCheckOut($baseGrandTotal,$quote,$customer_id,$store_id);
			$credit_min_checkout = Mage::helper('storecreditpro')->getMinCreditConfig($baseGrandTotal,$store_id);
			
			if ($quote->getMwStorecredit() > $credit_max_checkout) $quote->setMwStorecredit($credit_max_checkout);

			if($quote->getMwStorecredit() < $credit_min_checkout) $quote->setMwStorecredit(0);
			
		}else{
			$quote->setMwStorecredit(0);
		}
		
		foreach ($items as $item) {
			
			$product_id = $item->getProductId ();
			$qty = $item->getQty();
			$buy_credit = (int) $qty * Mage::getModel('catalog/product')->load($product_id)->getData('mw_storecredit');
			$mw_credit_buy += $buy_credit;
		}
		
		$mw_storecredit_discount_show = round(Mage::helper('core')->currency($mw_storecredit, false, false ), 2);
		
		$quote->setMwStorecreditCheckoutMax($credit_max_checkout);
		$quote->setMwStorecreditCheckoutMin($credit_min_checkout);
		$quote->setMwStorecreditBuyCredit($mw_credit_buy);
		$quote->setMwStorecreditDiscount($mw_storecredit);
		$quote->setMwStorecreditDiscountShow($mw_storecredit_discount_show);
		
		$address->setMwStorecredit($mw_storecredit);
		$address->setMwStorecreditBuyCredit($mw_credit_buy);
		$address->setMwStorecreditDiscount($mw_storecredit);
		$address->setMwStorecreditDiscountShow($mw_storecredit_discount_show);
		
		$address->setGrandTotal($address->getGrandTotal() - $address->getMwStorecreditDiscountShow() );
		$address->setBaseGrandTotal($address->getBaseGrandTotal() - ($address->getMwStorecreditDiscount()) );
		
		return $this;
	}
	
	public function fetch(Mage_Sales_Model_Quote_Address $address) {
	
		$items = $address->getAllVisibleItems();
		if (! count($items )) {
			return $this;
		}
		
	   $store_id = Mage::app()->getStore()->getId();
       $amount = $address->getMwStorecreditDiscountShow();
       $credit = $address->getMwStorecredit();

        if ($amount!=0) {
        	$title = Mage::helper('storecreditpro')->__('You Redeemed');
            $address->addTotal(array(
                'code'=> $this->getCode(),
                'title'=>$title,
                'value'=>-$amount,
            	'strong'    => false
            	//'area'    => '1'
            ));
        }
        return $this;
	}

}
