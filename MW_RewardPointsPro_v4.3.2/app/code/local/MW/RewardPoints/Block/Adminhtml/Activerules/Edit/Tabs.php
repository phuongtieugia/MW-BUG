<?php

    class MW_RewardPoints_Block_Adminhtml_Activerules_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
    {

        public function __construct()
        {
            parent::__construct();
            $this->setId('rules_tabs');
            $this->setDestElementId('edit_form');
            $this->setTitle(Mage::helper('rewardpoints')->__('Customer Behavior Rule'));
        }

        protected function _beforeToHtml()
        {

            $this->addTab('form_rules_detail', array(
                'label'   => Mage::helper('rewardpoints')->__('General information'),
                'title'   => Mage::helper('rewardpoints')->__('General information'),
                'content' => $this->getLayout()->createBlock('rewardpoints/adminhtml_activerules_edit_tab_form')->toHtml(),
            ));


            return parent::_beforeToHtml();
        }
    }