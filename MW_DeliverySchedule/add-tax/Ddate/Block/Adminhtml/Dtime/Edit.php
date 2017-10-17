<?php

class MW_Ddate_Block_Adminhtml_Dtime_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();

        $this->_objectId = 'dtime_id';
        $this->_blockGroup = 'ddate';
        $this->_controller = 'adminhtml_dtime';

        $this->_updateButton('save', 'label', Mage::helper('ddate')->__('Save Item'));
        $this->_updateButton('delete', 'label', Mage::helper('ddate')->__('Delete Item'));

        $this->_addButton('saveandcontinue', array(
            'label'     => Mage::helper('adminhtml')->__('Save And Continue Edit'),
            'onclick'   => 'saveAndContinueEdit()',
            'class'     => 'save',
        ), -100);

        $this->_formScripts[] = "
            function toggleEditor() {
                if (tinyMCE.getInstanceById('dtime_content') == null) {
                    tinyMCE.execCommand('mceAddControl', false, 'dtime_content');
                } else {
                    tinyMCE.execCommand('mceRemoveControl', false, 'dtime_content');
                }
            }

            function saveAndContinueEdit(){
                editForm.submit($('edit_form').action+'back/edit/');
            }
        ";
    }

    public function getHeaderText()
    {
        if( Mage::registry('dtime_data') && Mage::registry('dtime_data')->getDtimeId() ) {
            return Mage::helper('ddate')->__("Edit Item '%s'", $this->htmlEscape(Mage::registry('dtime_data')->getDtimeId()));
        } else {
            return Mage::helper('ddate')->__('Add Item');
        }
    }
}
