<?php
class MW_Onestepcheckout_Model_Total_Address_Mwcustomfee extends Mage_Sales_Model_Quote_Address_Total_Abstract
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


         $mwfee = Mage::getStoreConfig('onestepcheckout/addfield/mw_custom_fee');


         //$mwfee = count($items) * 5;

         $ismwfee = Mage::getSingleton('core/session')->getIsMwfee();


         if(!$ismwfee){
             return $this;
         }

         $discount = Mage::app()->getStore()->convertPrice($mwfee);
         $address->setBaseMwfeeAmount(+$mwfee);
         $address->setMwfeeAmount(+$discount);
         

         $address->setBaseMwfeeAmount($address->getBaseMwfeeAmount() + $mwfee);
         $address->setGrandTotal($address->getGrandTotal() + $mwfee);


         return $this;
     }

    public function fetch(Mage_Sales_Model_Quote_Address $address){

        $ismwfee = Mage::getSingleton('core/session')->getIsMwfee();


        if(!$ismwfee){
            return $this;
        }

        $amount = $address->getMwfeeAmount();
        $title = Mage::helper('onestepcheckout')->__('Additional fee');

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
