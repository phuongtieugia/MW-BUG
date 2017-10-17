<?php

    class MW_RewardPoints_Block_Adminhtml_Products_Import_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
    {

        public function __construct()
        {
            parent::__construct();
            $this->setId('rewardpoints_tabs');
            $this->setDestElementId('edit_form');
            $this->setTitle(Mage::helper('rewardpoints')->__('Product Reward Points'));
        }

        protected function _beforeToHtml()
        {
            $this->addTab('form_section', array(
                'label'   => Mage::helper('rewardpoints')->__('Import Product Reward Points'),
                'title'   => Mage::helper('rewardpoints')->__('Import Product Reward Points'),
                'content' => $this->getLayout()->createBlock('rewardpoints/adminhtml_products_import_edit_tab_form')->toHtml(),
            ));

            return parent::_beforeToHtml();
        }
    }