<?php

class MW_Ddate_Block_Adminhtml_Dtime extends Mage_Adminhtml_Block_Widget_Grid_Container
{
	public function __construct()
	{
		$this->_controller = 'adminhtml_dtime';
		$this->_blockGroup = 'ddate';
		$this->_headerText = Mage::helper('ddate')->__('Delivery Slots Time Manager');
		$this->_addButtonLabel = Mage::helper('ddate')->__('Add Item');
		parent::__construct();
	}
}
