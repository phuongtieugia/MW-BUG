<?php
class MW_OneStepCHeckOut_Block_Adminhtml_System_Config_Resetconfig extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected  function  _construct(){
        parent::_construct();
        $this->setTemplate('mw_onestepcheckout/system/config/button.phtml');
    }

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element){
        return $this->_toHtml();
    }

    public function getAjaxCheckUrl(){
        return Mage::helper('adminhtml')->getUrl('adminhtml/onestepcheckout_onestepcheckout/check');
    }

    public function getButtonHtml(){
        $button = $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setData(array(
                'id' => 'onestepcheckout_button',
                'label' => $this->helper('adminhtml')->__('Reset To Default Config'),
                'onclick' => 'javascript:check(); return false;'
            ));
        return $button->toHtml();
    }
}