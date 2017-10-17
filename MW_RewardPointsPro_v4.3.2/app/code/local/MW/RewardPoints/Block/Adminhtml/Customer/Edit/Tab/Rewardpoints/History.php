<?php
    class MW_Rewardpoints_Block_Adminhtml_Customer_Edit_Tab_Rewardpoints_History
        extends Mage_Adminhtml_Block_Template
    {
        /**
         * Internal constructor
         */
        protected function _construct()
        {
            parent::_construct();
            $this->setTemplate('mw_rewardpoints/customer/edit/history.phtml');
        }

        /**
         * Prepare layout.
         * Create history grid block
         *
         * @return Enterprise_Reward_Block_Adminhtml_Customer_Edit_Tab_Reward_History
         */
        protected function _prepareLayout()
        {
            $grid = $this->getLayout()
                ->createBlock('rewardpoints/adminhtml_customer_edit_tab_rewardpoints_history_grid')
                ->setCustomerId($this->getCustomerId());
            $this->setChild('grid', $grid);

            return parent::_prepareLayout();
        }
    }
