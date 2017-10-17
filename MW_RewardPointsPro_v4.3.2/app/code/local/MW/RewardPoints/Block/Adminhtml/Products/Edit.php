<?php

    class MW_RewardPoints_Block_Adminhtml_Products_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
    {
        public function __construct()
        {
            parent::__construct();

            $this->_objectId   = 'product_id';
            $this->_blockGroup = 'rewardpoints';
            $this->_controller = 'adminhtml_products';

            $this->_updateButton('save', 'label', Mage::helper('rewardpoints')->__('Save Product Reward Points'));
            $this->_removeButton('delete');
            $this->_removeButton('back');
            $this->_addButton('import', array(
                'label'   => Mage::helper('rewardpoints')->__('Import Product Reward Points'),
                'onclick' => 'setLocation(\'' . $this->getUrl('*/*/importProductPoints') . '\')',
                'class'   => 'add',
            ));

        }

        public function getHeaderText()
        {
            //return Mage::helper('rewardpoints')->__('Product Reward Points');
            return Mage::helper('rewardpoints')->__('Individual Reward Points <br /><div style="width: 700px; font-size: 11px;">Reward Points for products take priority over catalog rules. (Shopping cart rules may still apply)</div>');

        }
    }