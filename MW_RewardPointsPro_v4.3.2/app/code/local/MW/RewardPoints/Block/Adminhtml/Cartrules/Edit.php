<?php

    class MW_RewardPoints_Block_Adminhtml_Cartrules_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
    {
        public function __construct()
        {
            parent::__construct();

            $this->_objectId   = 'rule_id';
            $this->_blockGroup = 'rewardpoints';
            $this->_controller = 'adminhtml_cartrules';

            $this->_updateButton('save', 'label', Mage::helper('rewardpoints')->__('Save Rule'));
            $this->_updateButton('delete', 'label', Mage::helper('rewardpoints')->__('Delete Rule'));

            $this->_addButton('saveandcontinue', array(
                'label'   => Mage::helper('adminhtml')->__('Save And Continue Edit'),
                'onclick' => 'saveAndContinueEdit()',
                'class'   => 'save',
            ), -100);

            $this->_formScripts[] = "
            function toggleEditor() {
                if (tinyMCE.getInstanceById('rewardpoints_content') == null) {
                    tinyMCE.execCommand('mceAddControl', false, 'rewardpoints_content');
                } else {
                    tinyMCE.execCommand('mceRemoveControl', false, 'rewardpoints_content');
                }
            }

            function saveAndContinueEdit(){
                editForm.submit($('edit_form').action+'back/edit/');
            }
        ";
        }

        public function getHeaderText()
        {
            if(Mage::registry('data_cart_rules') && Mage::registry('data_cart_rules')->getId())
            {
                return Mage::helper('rewardpoints')->__("Edit Rule '%s'", $this->htmlEscape(Mage::registry('data_cart_rules')->getName()));
            }
            else
            {
                return Mage::helper('rewardpoints')->__('New Rule');
            }
        }
    }