<?php
    class MW_RewardPoints_Block_Adminhtml_Report_Overview extends Mage_Adminhtml_Block_Widget_Grid_Container
    {
        public function __construct()
        {
            $this->_controller = 'adminhtml_report_overview';
            $this->_headerText = Mage::helper('rewardpoints')->__('Rewardpoints Overview');
            $this->_blockGroup = 'rewardpoints';
            parent::__construct();
            $this->_removeButton('add');
        }
    }