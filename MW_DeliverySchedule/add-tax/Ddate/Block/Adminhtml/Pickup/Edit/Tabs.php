<?php

class MW_Ddate_Block_Adminhtml_Pickup_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('pickup_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(Mage::helper('ddate')->__('Item Information'));
    }

    protected function _beforeToHtml()
    {
        $this->addTab('form_section', array(
            'label' => Mage::helper('ddate')->__('Delivery Information'),
            'title' => Mage::helper('ddate')->__('Delivery Information'),
            'content' => $this->getLayout()->createBlock('ddate/adminhtml_pickup_edit_tab_form')->toHtml()
        ));
        
        return parent::_beforeToHtml();
    }
}
