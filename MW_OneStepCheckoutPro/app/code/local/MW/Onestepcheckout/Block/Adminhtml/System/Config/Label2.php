<?php

class MW_Onestepcheckout_Block_Adminhtml_System_Config_Label2 extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {    	    
		$html =  $this->getLayout()->createBlock('onestepcheckout/adminhtml_system_config_label2info')->setTemplate('mw_onestepcheckout/system/config/label2.phtml')->toHtml();
        return $html;
    }
}