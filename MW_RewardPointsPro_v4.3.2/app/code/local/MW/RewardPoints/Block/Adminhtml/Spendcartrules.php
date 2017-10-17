<?php
    class MW_RewardPoints_Block_Adminhtml_Spendcartrules extends Mage_Adminhtml_Block_Widget_Grid_Container
    {
        public function __construct()
        {
            $this->_controller     = 'adminhtml_spendcartrules';
            $this->_blockGroup     = 'rewardpoints';
            $this->_headerText     = Mage::helper('rewardpoints')->__('Reward Point Spending Rules');
            $this->_addButtonLabel = Mage::helper('rewardpoints')->__('Add New Rule');
            parent::__construct();
        }
    }