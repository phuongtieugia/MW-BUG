<?php
class MW_OneStepCheckOut_Block_Adminhtml_System_Config_Form_Button extends Mage_Adminhtml_Block_System_Config_Form
{
    protected  function  _construct(){
        parent::_construct();
        //$this->setTemplate('mw_onestepcheckout/system/config/button.phtml');
    }

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element){
        return $this->_toHtml();
    }

    public function getAjaxCheckUrl(){
        return Mage::helper('adminhtml')->getUrl('adminhtml/onestepcheckout_onstepcheckout/check');
    }
}