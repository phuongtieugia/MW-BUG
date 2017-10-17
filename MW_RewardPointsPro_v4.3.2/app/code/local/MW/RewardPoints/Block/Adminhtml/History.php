<?php
    class MW_RewardPoints_Block_Adminhtml_History extends Mage_Adminhtml_Block_Widget_Grid_Container
    {
        public function __construct()
        {
            $this->_controller = 'adminhtml_history';
            $this->_blockGroup = 'rewardpoints';
            $this->_headerText = Mage::helper('rewardpoints')->__('All Transaction History');
            parent::__construct();
            $this->_removeButton('add');

        }
    }