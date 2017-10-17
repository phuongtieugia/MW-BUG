<?php

class MW_Ddate_Block_Sales_Order_Print extends Mage_Sales_Block_Order_Print
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('ddate/sales/order/print.phtml');
    }
}
