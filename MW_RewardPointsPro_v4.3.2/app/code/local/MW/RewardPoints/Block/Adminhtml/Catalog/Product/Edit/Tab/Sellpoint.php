<?php
    /**
     * User: Anh TO
     * Date: 6/9/14
     * Time: 3:52 PM
     */

    class MW_RewardPoints_Block_Adminhtml_Catalog_Product_Edit_Tab_Sellpoint extends Mage_Adminhtml_Block_Widget_Form
    {
        protected function _prepareForm()
        {
            $storeId = $this->getRequest()->getParam('store');
            if(!$storeId)
            {
                $storeId = 0;
            }

            $form     = new Varien_Data_Form();
            $fieldset = $form->addFieldset('sizechart_form', array('legend' => 1));


            $this->setForm($form);

            return parent::_prepareForm();
        }

    }