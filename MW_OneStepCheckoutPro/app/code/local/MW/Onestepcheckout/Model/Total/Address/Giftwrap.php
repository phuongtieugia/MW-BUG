<?php
class MW_OneStepCheckOut_Model_Total_Address_Giftwrap extends Mage_Sales_Model_Quote_Address_Total_Abstract
{
     public function __construct()
     {
         $this->setCode('giftwrap_discount');
     }

     public function  collect(Mage_Sales_Model_Quote_Address $address)
     {

         $quote = $address->getQuote();
         if(!$quote->isVirtual() && $address->getAddressType() == 'billing')
         {
             return $this;
         }

         $items = $address->getAllItems();


         $baseDiscount = Mage::getStoreConfig('onestepcheckout/addfield/price_gift_wrap');


         //$baseDiscount = count($items) * 5;

         $iswrap = Mage::getSingleton('core/session')->getIsWrap();


         if(!$iswrap){
             return $this;
         }

         $discount = Mage::app()->getStore()->convertPrice($baseDiscount);
         $address->setBaseGiftwrapDiscount(+$baseDiscount);
         $address->setGiftwrapDiscount(+$discount);

         $address->setBaseGrandTotal($address->getBaseGrandTotal() + $baseDiscount);
         $address->setGrandTotal($address->getGrandTotal() + $baseDiscount);


         return $this;
     }

    public function fetch(Mage_Sales_Model_Quote_Address $address){

        $iswrap = Mage::getSingleton('core/session')->getIsWrap();


        if(!$iswrap){
            return $this;
        }

        $amount = $address->getGiftwrapDiscount();
        $title = Mage::helper('onestepcheckout')->__('Gift Wrap Price');

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
