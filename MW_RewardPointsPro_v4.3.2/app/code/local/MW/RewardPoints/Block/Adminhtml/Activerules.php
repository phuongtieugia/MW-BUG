<?php
    class MW_RewardPoints_Block_Adminhtml_Activerules extends Mage_Adminhtml_Block_Widget_Grid_Container
    {
        public function __construct()
        {
            $this->_controller = 'adminhtml_activerules';
            $this->_blockGroup = 'rewardpoints';
            $this->_headerText = Mage::helper('rewardpoints')->__('Manage Customer Behavior Rules');
            parent::__construct();

        }
    }