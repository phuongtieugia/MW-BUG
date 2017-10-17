<?php

class MW_Ddate_Block_Adminhtml_Pickup extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        parent::__construct();
        $this->_controller = 'adminhtml_pickup';
        $this->_blockGroup = 'ddate';
        $this->_headerText = Mage::helper('ddate')->__('Pickup Schedules Manager');
        $this->removeButton('add');
    }
}
