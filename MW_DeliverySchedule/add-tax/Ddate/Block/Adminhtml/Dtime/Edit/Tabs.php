<?php

class MW_Ddate_Block_Adminhtml_Dtime_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('dtime_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(Mage::helper('ddate')->__('Delivery Time Information'));
    }
    
    protected function _beforeToHtml()
    {
        $this->addTab('form_section', array(
            'label' => Mage::helper('ddate')->__('General'),
            'title' => Mage::helper('ddate')->__('General'),
            'content' => $this->getLayout()->createBlock('ddate/adminhtml_dtime_edit_tab_form')->toHtml()
        ));

        $this->addTab('days_section', array(
            'label' => Mage::helper('ddate')->__('Delivery Days'),
            'title' => Mage::helper('ddate')->__('Delivery Days'),
            'content' => $this->getLayout()->createBlock('ddate/adminhtml_dtime_edit_tab_days')->toHtml()
        ));

        return parent::_beforeToHtml();
    }
}
