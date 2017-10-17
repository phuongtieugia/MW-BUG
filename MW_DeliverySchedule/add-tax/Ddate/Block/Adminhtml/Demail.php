<?php

class MW_Ddate_Block_Adminhtml_Demail extends Mage_Adminhtml_Block_Widget_Grid_Container
{
	public function __construct()
	{
		$this->_controller = 'adminhtml_demail';
		$this->_blockGroup = 'ddate';
		$this->_headerText = Mage::helper('ddate')->__('1 Day Delivery Email Manager');
		$this->_addButtonLabel = Mage::helper('ddate')->__('Add Item');
		parent::__construct();
	}
}
