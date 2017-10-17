<?php
class MW_Ddate_Model_Total_Address_Mwcustomfee extends Mage_Sales_Model_Quote_Address_Total_Abstract
{
     public function __construct()
     {
         $this->setCode('mwfee_amount');
     }

     public function  collect(Mage_Sales_Model_Quote_Address $address)
     {

         $quote = $address->getQuote();
         if(!$quote->isVirtual() && $address->getAddressType() == 'billing')
         {
             return $this;
         }

         $items = $address->getAllItems();


         //$mwfee = count($items) * 5;

         $ismwfee = Mage::getSingleton('core/session')->getDeliveryMwfee();


         if(!$ismwfee){
             return $this;
         }
		 if($ismwfee == 0){
			 return $this;
		 }

         $discount = Mage::app()->getStore()->convertPrice($ismwfee);
         $address->setBaseMwfeeAmount(+$ismwfee);
         $address->setMwfeeAmount(+$discount);
         

         $address->setBaseGrandTotal($address->getBaseGrandTotal() + $ismwfee);
         $address->setGrandTotal($address->getGrandTotal() + $ismwfee);


         return $this;
     }

    public function fetch(Mage_Sales_Model_Quote_Address $address){

		$ismwfee = Mage::getSingleton('core/session')->getDeliveryMwfee();


        if(!$ismwfee){
             return $this;
         }
		 if($ismwfee == 0){
			 return $this;
		 }

        $amount = $address->getMwfeeAmount();
        /* $title = Mage::helper('ddate')->__('Additional fee'); */
        $title = Mage::getStoreConfig("ddate/info/dtax");

        if($amount!=0){
            $address->addTotal(array(
                'code'  =>$this->getCode(),
                'title' =>$title,
                'value' => $amount,
            ));
        }

        return $this;
    }
}
