<?php

    class MW_RewardPoints_Block_Adminhtml_Activerules_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
    {
        protected function _prepareForm()
        {
            $form_detail = new Varien_Data_Form(array(
                    'id'      => 'edit_form',
                    'action'  => $this->getUrl('*/*/save', array('id' => $this->getRequest()->getParam('id'))),
                    'method'  => 'post',
                    'enctype' => 'multipart/form-data'
                )
            );

            $form_detail->setUseContainer(true);
            $this->setForm($form_detail);

            return parent::_prepareForm();
        }
    }