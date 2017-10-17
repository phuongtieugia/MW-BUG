<?php
class MW_Ddate_Block_Config_Delivery
    extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    public function _prepareToRender()
    {
        $this->addColumn('dpostfrom', array(
            'label' => Mage::helper('ddate')->__('From'),
            'style' => 'width:100px',
        ));
        $this->addColumn('dpostto', array(
            'label' => Mage::helper('ddate')->__('To'),
            'style' => 'width:100px',
        ));
		$this->addColumn('ddays', array(
            'label' => Mage::helper('ddate')->__('The day'),
            'style' => 'width:100px',
        ));

        $this->_addAfter = false;
        $this->_addButtonLabel = Mage::helper('ddate')->__('Add');
    }
}