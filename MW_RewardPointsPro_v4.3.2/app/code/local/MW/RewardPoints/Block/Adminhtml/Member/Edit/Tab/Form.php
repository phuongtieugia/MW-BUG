<?php

    class MW_RewardPoints_Block_Adminhtml_Member_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
    {
        protected function _prepareForm()
        {
            $form_member_detail = new Varien_Data_Form();
            $this->setForm($form_member_detail);

            $fieldset1 = $form_member_detail->addFieldset('base_fieldset', array('legend' => Mage::helper('rewardpoints')->__('Reward Points Information')));

            $fieldset = $form_member_detail->addFieldset('rewardpoints_form', array('legend' => Mage::helper('rewardpoints')->__('Manually Adjust Reward Point Balance')));

            $customer_id    = $this->getRequest()->getParam('id');
            $customer       = Mage::getModel('customer/customer')->load($customer_id);
            $customer_email = $customer->getEmail();
            $points         = Mage::getModel('rewardpoints/customer')->load($customer_id)->getData('mw_reward_point');

            $fieldset1->addField('rewardpoints', 'note', array(
                'label' => Mage::helper('rewardpoints')->__('Reward Points'),
                'name'  => 'mw_reward_points',
                'text'  => $points,
            ));

            $fieldset1->addField('customer_email', 'note', array(
                'label' => Mage::helper('rewardpoints')->__('Customer Email'),
                'text'  => Mage::helper('rewardpoints')->getLinkCustomer($customer_id, $customer_email),
            ));
            $fieldset->addField('amount', 'text',
                array(
                    'label' => Mage::helper('rewardpoints')->__('Amount'),
                    'name'  => 'reward_points_amount',
                    'class' => 'validate-digits'
                )
            );

            $fieldset->addField('action', 'select',
                array(
                    'label'   => Mage::helper('rewardpoints')->__('Action'),
                    'name'    => 'reward_points_action',
                    'options' => Mage::getModel('rewardpoints/action')->getOptionArray()
                )
            );

            $fieldset->addField('comment', 'textarea',
                array(
                    'label' => Mage::helper('rewardpoints')->__('Comment'),
                    'name'  => 'reward_points_comment',
                    'style' => 'height:100px'
                )
            );
            $form_member_detail->getElement('action')->setValue(1);

            return parent::_prepareForm();
        }

    }