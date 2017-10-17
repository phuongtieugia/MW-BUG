<?php
class MW_Ddate_Block_Adminhtml_Ddate_System_Config_Form_Field_Addtimerange extends Mage_Adminhtml_Block_System_Config_Form_Field_Regexceptions
{
    public function __construct()
    {
        $this->addColumn('shipvalue', array(
            'label' => Mage::helper('adminhtml')->__('Shipping method value '),
            'style' => 'width:240px',
        ));       
        $this->_addAfter = false;
        $this->_addButtonLabel = Mage::helper('adminhtml')->__('Add shipping method value');
        Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract::__construct();
    }
}