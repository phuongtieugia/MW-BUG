<?php

    class MW_RewardPoints_Block_Adminhtml_Sellproducts_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
    {

        public function __construct()
        {
            parent::__construct();
            $this->setId('products_tabs');
            $this->setDestElementId('edit_form');
            $this->setTitle(Mage::helper('rewardpoints')->__('Sell Products in Points'));
        }

        protected function _beforeToHtml()
        {

            $this->addTab('form_products', array(
                'label'   => Mage::helper('rewardpoints')->__('Products'),
                'title'   => Mage::helper('rewardpoints')->__('Products'),
                'content' =>
                    $this->getLayout()->createBlock('rewardpoints/adminhtml_sellproducts_edit_tab_grid')->toHtml() .
                    $this->getLayout()->createBlock('rewardpoints/adminhtml_sellproducts_edit_tab_headjs')->setTemplate('mw_rewardpoints/sellproducts/edit/tab/headjs.phtml')->toHtml(),
            ));


            return parent::_beforeToHtml();
        }
    }