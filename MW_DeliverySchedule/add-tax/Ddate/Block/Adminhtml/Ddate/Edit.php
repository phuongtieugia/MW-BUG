<?php

class MW_Ddate_Block_Adminhtml_Ddate_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();
                 
        $this->_objectId = 'ddate_id';
        $this->_blockGroup = 'ddate';
        $this->_controller = 'adminhtml_ddate';
        
        $this->_updateButton('save', 'label', Mage::helper('ddate')->__('Save Item'));
        $this->_updateButton('delete', 'label', Mage::helper('ddate')->__('Delete Item'));
		
        $this->_addButton('saveandcontinue', array(
            'label'     => Mage::helper('adminhtml')->__('Save And Continue Edit'),
            'onclick'   => 'saveAndContinueEdit()',
            'class'     => 'save',
        ), -100);

        $this->_formScripts[] = "
            function toggleEditor() {
                if (tinyMCE.getInstanceById('ddate_content') == null) {
                    tinyMCE.execCommand('mceAddControl', false, 'ddate_content');
                } else {
                    tinyMCE.execCommand('mceRemoveControl', false, 'ddate_content');
                }
            }

            function saveAndContinueEdit(){
                editForm.submit($('edit_form').action+'back/edit/');
            }
        ";
    }

    public function getHeaderText()
    {
        if(Mage::registry('ddate_data') && Mage::registry('ddate_data')->getDdateId()) {
            return Mage::helper('ddate')->__("Edit Item '%s'", $this->htmlEscape(Mage::registry('ddate_data')->getDdateId()));
        } else {
            return Mage::helper('ddate')->__('Add Item');
        }
    }
}
