<?php
    class MW_RewardPoints_Block_Adminhtml_Cartrules extends Mage_Adminhtml_Block_Widget_Grid_Container
    {
        public function __construct()
        {
            $this->_controller     = 'adminhtml_cartrules';
            $this->_blockGroup     = 'rewardpoints';
            $this->_headerText     = Mage::helper('rewardpoints')->__('Shopping Cart Earning Rule');
            $this->_addButtonLabel = Mage::helper('rewardpoints')->__('Add New Rule');
            parent::__construct();
        }
    }