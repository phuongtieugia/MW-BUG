<?php

class MW_Ddate_Block_Adminhtml_Pickup_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();
                 
        $this->_objectId = 'pickup_id';
        $this->_blockGroup = 'ddate';
        $this->_controller = 'adminhtml_pickup';
        
        $this->_updateButton('save', 'label', Mage::helper('ddate')->__('Save Item'));
        $this->_updateButton('delete', 'label', Mage::helper('ddate')->__('Delete Item'));
		
        $this->_addButton('saveandcontinue', array(
            'label'     => Mage::helper('adminhtml')->__('Save And Continue Edit'),
            'onclick'   => 'saveAndContinueEdit()',
            'class'     => 'save',
        ), -100);

        $this->_formScripts[] = "
            function toggleEditor() {
                if (tinyMCE.getInstanceById('pickup_content') == null) {
                    tinyMCE.execCommand('mceAddControl', false, 'pickup_content');
                } else {
                    tinyMCE.execCommand('mceRemoveControl', false, 'pickup_content');
                }
            }

            function saveAndContinueEdit(){
                editForm.submit($('edit_form').action+'back/edit/');
            }
        ";
    }

    public function getHeaderText()
    {
        if( Mage::registry('pickup_data') && Mage::registry('pickup_data')->getDdateId()) {
            return Mage::helper('ddate')->__("Edit Item '%s'", $this->htmlEscape(Mage::registry('pickup_data')->getDdateId()));
        } else {
            return Mage::helper('ddate')->__('Add Item');
        }
    }
}
