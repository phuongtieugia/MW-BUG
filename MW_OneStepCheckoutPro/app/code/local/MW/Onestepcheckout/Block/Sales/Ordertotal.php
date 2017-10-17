<?php
/**
 * Created by PhpStorm.
 * User: manhnt
 * Date: 10/22/14
 * Time: 2:27 PM
 */

class MW_Onestepcheckout_Block_Sales_Ordertotal extends Mage_Sales_Block_Order_Totals
{
    public function getGiftwrapDiscount(){
        $order = $this->getOrder();
        return $order->getGiftwrapDiscount();
    }

    public function getBaseGiftwrapDiscount(){
        $order = $this->getOrder();
        return $order->getBaseGiftwrapDiscount();
    }

    public function initTotals(){
        $amount = $this->getGiftwrapDiscount();
        if(floatval($amount)){
            $total = new Varien_Object();
            $total->setCode('Gift wrap');
            $total->setValue($amount);
            $total->setBaseValue($this->getBaseGiftwrapDiscount());
            $total->setLabel('Gift wrap');
            $parent = $this->getParentBlock();
            $parent->addTotal($total,'subtotal');
        }
    }

    public function getOrder(){
        if(!$this->hasData('order')){
            $order = $this->getParentBlock()->getOrder();
            $this->setData('order',$order);
        }
        return $this->getData('order');
    }
}