<?php

    class MW_RewardPoints_Block_Adminhtml_Member_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
    {

        public function __construct()
        {
            parent::__construct();
            $this->setId('member_tabs');
            $this->setDestElementId('edit_form');
            $this->setTitle(Mage::helper('rewardpoints')->__('Rewardpoints Member Information'));
        }

        protected function _beforeToHtml()
        {

            $this->addTab('form_member_detail', array(
                'label'   => Mage::helper('rewardpoints')->__('General information'),
                'title'   => Mage::helper('rewardpoints')->__('General information'),
                'content' => $this->getLayout()->createBlock('rewardpoints/adminhtml_member_edit_tab_form')->toHtml(),
            ));

            $this->addTab('form_member_transaction', array(
                'label'   => Mage::helper('rewardpoints')->__('Transaction History'),
                'title'   => Mage::helper('rewardpoints')->__('Transaction History'),
                'content' => $this->getLayout()->createBlock('rewardpoints/adminhtml_member_edit_tab_transaction')->toHtml(),
            ));

            /* if(Mage::helper('rewardpoints')->getCreditModule()){
                 $this->addTab('credit', array(
                     'label'     => Mage::helper('credit')->__('Credit'),
                     'content'   => $this->getLayout()->createBlock('credit/adminhtml_customer_edit_tab_credit')->initForm()->toHtml()
                 ));
             }*/

            return parent::_beforeToHtml();
        }
    }