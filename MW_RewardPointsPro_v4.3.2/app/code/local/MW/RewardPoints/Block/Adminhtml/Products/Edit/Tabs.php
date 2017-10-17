<?php

    class MW_RewardPoints_Block_Adminhtml_Products_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
    {

        public function __construct()
        {
            parent::__construct();
            $this->setId('products_tabs');
            $this->setDestElementId('edit_form');
            $this->setTitle(Mage::helper('rewardpoints')->__('Individual Reward Points'));
        }

        protected function _beforeToHtml()
        {

            $this->addTab('form_products', array(
                'label'   => Mage::helper('rewardpoints')->__('Products'),
                'title'   => Mage::helper('rewardpoints')->__('Products'),
                'content' => $this->getLayout()->createBlock('rewardpoints/adminhtml_products_edit_tab_grid')->toHtml(),
            ));


            return parent::_beforeToHtml();
        }
    }