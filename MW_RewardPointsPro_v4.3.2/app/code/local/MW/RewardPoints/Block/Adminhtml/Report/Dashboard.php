<?php
/**
 * User: Anh TO
 * Date: 8/4/14
 * Time: 3:39 PM
 */

class MW_RewardPoints_Block_Adminhtml_Report_Dashboard extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_controller = 'adminhtml_report_dashboard';
        $this->_headerText = Mage::helper('rewardpoints')->__('Dashboard');
        $this->_blockGroup = 'rewardpoints';
        parent::__construct();
        $this->_removeButton('add');
    }
}