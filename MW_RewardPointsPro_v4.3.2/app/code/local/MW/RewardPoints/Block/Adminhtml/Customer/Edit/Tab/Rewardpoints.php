<?php
    class MW_Rewardpoints_Block_Adminhtml_Customer_Edit_Tab_Rewardpoints
        extends Mage_Adminhtml_Block_Template
        implements Mage_Adminhtml_Block_Widget_Tab_Interface
    {
        /**
         * Return tab label
         *
         * @return string
         */
        public function getTabLabel()
        {
            return Mage::helper('rewardpoints')->__('Reward Points');
        }

        /**
         * Return tab title
         *
         * @return string
         */
        public function getTabTitle()
        {
            return Mage::helper('rewardpoints')->__('Reward Points');
        }

        /**
         * Check if can show tab
         *
         * @return boolean
         */
        public function canShowTab()
        {
            $customer = Mage::registry('current_customer');

            return $customer->getId()
            && Mage::helper('rewardpoints')->moduleEnabled();
        }

        /**
         * Check if tab hidden
         *
         * @return boolean
         */
        public function isHidden()
        {
            return false;
        }

        /**
         * Prepare layout.
         * Add accordion items
         *
         * @return Enterprise_Reward_Block_Adminhtml_Customer_Edit_Tab_Reward
         */
        protected function _prepareLayout()
        {
            $accordion = $this->getLayout()->createBlock('adminhtml/widget_accordion');
            $accordion->addItem('reward_points_history', array(
                'title'       => Mage::helper('rewardpoints')->__('Reward Points History'),
                'open'        => false,
                'class'       => '',
                'ajax'        => true,
                'content_url' => $this->getUrl('adminhtml/rewardpoints_customer/history', array('_current' => true))
            ));
            $this->setChild('mw_history', $accordion);


            return parent::_prepareLayout();
        }

        /**
         * Precessor tab ID getter
         *
         * @return string
         */
        public function getAfter()
        {
            return 'account';
        }
    }
	