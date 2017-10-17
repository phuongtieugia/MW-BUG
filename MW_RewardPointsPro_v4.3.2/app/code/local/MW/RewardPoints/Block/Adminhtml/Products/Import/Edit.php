<?php

    class MW_RewardPoints_Block_Adminhtml_Products_Import_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
    {
        public function __construct()
        {
            parent::__construct();

            $this->_objectId   = 'product_import';
            $this->_blockGroup = 'rewardpoints';
            $this->_controller = 'adminhtml_products_import';

            $this->_updateButton('save', 'label', Mage::helper('rewardpoints')->__('Import Product Reward Points'));

            $this->_removeButton('delete');
        }

        public function getHeaderText()
        {
            return Mage::helper('rewardpoints')->__('Import Product Reward Points');
        }
    }