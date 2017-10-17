<?php

    class MW_RewardPoints_Block_Adminhtml_Member_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
    {
        public function __construct()
        {
            parent::__construct();

            $this->_objectId   = 'customer_id';
            $this->_blockGroup = 'rewardpoints';
            $this->_controller = 'adminhtml_member';

            $this->_updateButton('save', 'label', Mage::helper('rewardpoints')->__('Save Member'));
            $this->_removeButton('delete');
            //$this->_updateButton('delete', 'label', Mage::helper('affiliate')->__('Delete Member'));

            $this->_addButton('saveandcontinue', array(
                'label'   => Mage::helper('adminhtml')->__('Save And Continue Edit'),
                'onclick' => 'saveAndContinueEdit()',
                'class'   => 'save',
            ), -100);
            $edit = $this->getUrl('*/*/save', array('id' => $this->getRequest()->getParam('id')));
            //editForm.submit('".$edit."'+'back/edit/');
            $this->_formScripts[] = "
            function toggleEditor() {
                if (tinyMCE.getInstanceById('member_content') == null) {
                    tinyMCE.execCommand('mceAddControl', false, 'member_content');
                } else {
                    tinyMCE.execCommand('mceRemoveControl', false, 'member_content');
                }
            }

            function saveAndContinueEdit(){
                editForm.submit('" . $edit . "'+'back/edit/');
            }
        ";
        }

        public function getHeaderText()
        {
            $customer_id = $this->getRequest()->getParam('id');
            if(isset($customer_id))
            {
                $name = Mage::getModel('customer/customer')->load($customer_id)->getName();

                return Mage::helper('rewardpoints')->__($this->htmlEscape($name));
            }

        }
    }