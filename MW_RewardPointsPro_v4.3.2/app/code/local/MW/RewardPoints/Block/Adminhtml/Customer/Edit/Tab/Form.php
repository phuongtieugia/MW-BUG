<?php
    class MW_Rewardpoints_Block_Adminhtml_Customer_Edit_Tab_Form extends Mage_Adminhtml_Block_Template
    {
        protected function _construct()
        {
            parent::_construct();
            $this->setTemplate('mw_rewardpoints/customer/edit/form.phtml');
        }

        protected function _prepareLayout()
        {
            $mw_form = $this->getLayout()
                ->createBlock('rewardpoints/adminhtml_customer_edit_tab_rewardpoints_form');

            $this->setChild('mw_rewardpoints_form', $mw_form);

            return parent::_prepareLayout();
        }
    }
	