<?php

class MW_Ddate_Block_Adminhtml_Ddate extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        parent::__construct();
        $this->_controller = 'adminhtml_ddate';
        $this->_blockGroup = 'ddate';
        $this->_headerText = Mage::helper('ddate')->__('Delivery Schedules Manager');
        $this->removeButton('add');
    }
}
