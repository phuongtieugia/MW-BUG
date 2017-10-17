<?php
class MW_OneStepCheckOut_Block_Totals_Invoice_Discount extends Mage_Core_Block_Template
{
    public function initTotals()
    {
        $totalsBlock = $this->getParentBlock();
        $invoice = $totalsBlock->getInvoice();

        if ($invoice->getCustomDiscount()) {
            $totalsBlock->addTotal(new Varien_Object(array(
                'code' => 'onestepcheckout',
                'label' => $this->__('Custom Discount'),
                'value' => $invoice->getCustomDiscount(),
            )), 'subtotal');
        }
    }
}