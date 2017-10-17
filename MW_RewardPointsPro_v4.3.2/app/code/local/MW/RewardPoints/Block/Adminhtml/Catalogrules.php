<?php
    class MW_RewardPoints_Block_Adminhtml_Catalogrules extends Mage_Adminhtml_Block_Widget_Grid_Container
    {
        public function __construct()
        {
            $this->_controller = 'adminhtml_catalogrules';
            $this->_blockGroup = 'rewardpoints';
            $this->_addButton('apply_rules', array(
                'label'   => Mage::helper('rewardpoints')->__('Apply Rules'),
                'onclick' => "location.href='" . $this->getUrl('*/*/applyRules') . "'",
                'class'   => '',
            ));
            $this->_headerText     = Mage::helper('rewardpoints')->__('Catalog Reward Rules');
            $this->_addButtonLabel = Mage::helper('rewardpoints')->__('Add New Rule');
            parent::__construct();
        }
    }