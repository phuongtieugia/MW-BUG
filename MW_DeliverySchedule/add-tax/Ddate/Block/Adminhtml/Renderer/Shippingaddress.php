<?php

class MW_Ddate_Block_Adminhtml_Renderer_Shippingaddress extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
    	if (empty($row['increment_id'])) {
    		return '';
    	}

    	return Mage::getModel('sales/order')->loadByIncrementId($row['increment_id'])->getShippingAddress()->format('html');
    }
}