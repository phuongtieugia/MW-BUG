<?php

    class MW_RewardPoints_Block_Adminhtml_Sellproducts_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
    {
        public function __construct()
        {
            parent::__construct();

            $this->_objectId   = 'product_id';
            $this->_blockGroup = 'rewardpoints';
            $this->_controller = 'adminhtml_sellproducts';

            $this->_updateButton('save', 'label', Mage::helper('rewardpoints')->__('Save'));
            $this->_removeButton('delete');
            $this->_removeButton('back');

        }

        public function getHeaderText()
        {
            return Mage::helper('rewardpoints')->__('Sell Products in Points');

        }
    }