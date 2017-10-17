<?php

    class MW_RewardPoints_Block_Adminhtml_Products_Import_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
    {
        protected function _prepareForm()
        {
            $form = new Varien_Data_Form();
            $this->setForm($form);
            $fieldset = $form->addFieldset('rewardpoints_form', array('legend' => Mage::helper('rewardpoints')->__('Import Product Reward Points')));


            $fieldset->addField('website_id', 'select', array(
                'label'    => Mage::helper('rewardpoints')->__('Website'),
                'required' => true,
                'name'     => 'website_id',
                'values'   => Mage::getModel('adminhtml/system_config_source_website')->toOptionArray(),
            ));

            $fieldset->addField('filename', 'image', array(
                'label'    => Mage::helper('rewardpoints')->__('CSV File'),
                'required' => true,
                'name'     => 'filename',
                'note'     => 'CSV (Product Id, SKUs, Reward Points)'
            ));

            return parent::_prepareForm();
        }
    }