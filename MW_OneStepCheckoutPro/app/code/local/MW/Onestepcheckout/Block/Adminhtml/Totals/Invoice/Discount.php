<?php
class MW_OneStepCheckOut_Block_Adminhtml_Totals_Invoice_Discount extends Mage_Adminhtml_Block_Sales_Order_Totals_Item
{
    public function initTotals(){
        $totalsBlock = $this->getParentBlock();
        $invoice = $totalsBlock->getInvoice();
        if($invoice->getCustomDiscount()){
            $totalsBlock->addTotal(new Varien_Object(array(
                'code' => 'onestepcheckout',
                'label'=>$this->__('Gift Wrap'),
                'value'=> $invoice->getCustomDiscount(),
            )),'subtotal');
        }
    }
}