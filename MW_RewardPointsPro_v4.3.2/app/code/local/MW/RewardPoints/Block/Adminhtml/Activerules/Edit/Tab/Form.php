<?php

    class MW_RewardPoints_Block_Adminhtml_Activerules_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
    {
        protected function _prepareForm()
        {
            $form_detail = new Varien_Data_Form();
            $this->setForm($form_detail);

            $rule_id = $this->getRequest()->getParam('id');

            $default_expired     = 0;
            $set_default_expired = false;
            $default_expired     = Mage::getModel('rewardpoints/activerules')->load($rule_id)->getDefaultExpired();
            if($default_expired == 1)
            {
                $set_default_expired = true;
            }

            $fieldset = $form_detail->addFieldset('rewardpoints_form', array('legend' => Mage::helper('rewardpoints')->__('Change Reward Points Of Customer')));

            $fieldset->addField('rule_name', 'text', array(
                'label'    => Mage::helper('rewardpoints')->__('Rule name'),
                'required' => true,
                'name'     => 'rule_name',
            ));
            $fieldset->addField('type_of_transaction', 'select', array(
                'label'  => Mage::helper('rewardpoints')->__('Reward for'),
                'class'  => 'required-entry',
                'name'   => 'type_of_transaction',
                'values' => MW_RewardPoints_Model_Type::getTypeReward(),
            ));

            $fieldset->addField('status', 'select', array(
                'label'    => Mage::helper('rewardpoints')->__('Status'),
                'name'     => 'status',
                'required' => true,
                'values'   => array(
                    array(
                        'value' => 1,
                        'label' => Mage::helper('rewardpoints')->__('Enabled'),
                    ),

                    array(
                        'value' => 2,
                        'label' => Mage::helper('rewardpoints')->__('Disabled'),
                    ),
                ),
                'note'     => Mage::helper('rewardpoints')->__('Enable and Save rule to activate'),
            ));

            if(!Mage::app()->isSingleStoreMode())
            {
                $fieldset->addField('store_view', 'multiselect', array(
                    'name'     => 'store_view[]',
                    'label'    => Mage::helper('rewardpoints')->__('Store View'),
                    'title'    => Mage::helper('rewardpoints')->__('Store View'),
                    'required' => true,
                    'values'   => Mage::getSingleton('adminhtml/system_store')->getStoreValuesForForm(false, true),
                ));
            }
            else
            {
                $fieldset->addField('store_view', 'hidden', array(
                    'name' => 'store_view[]',
                    //'value'     => $store_view
                ));
            }
            $customerGroups = Mage::getResourceModel('customer/group_collection')
                ->load()->toOptionArray();
            $found          = false;
            foreach ($customerGroups as $group)
            {
                if($group['value'] == 0)
                {
                    $found = true;
                }
            }
            if(!$found)
            {
                array_unshift($customerGroups, array('value' => 0, 'label' => Mage::helper('catalogrule')->__('NOT LOGGED IN')));
            }

            $fieldset->addField('customer_group_ids', 'multiselect', array(
                'name'     => 'customer_group_ids[]',
                'label'    => Mage::helper('rewardpoints')->__('Customer Groups'),
                'title'    => Mage::helper('rewardpoints')->__('Customer Groups'),
                'required' => true,
                'values'   => $customerGroups,
            ));
            $fieldset->addField('date_event', 'date', array(
                'label'  => Mage::helper('rewardpoints')->__('Date Event'),
                'name'   => 'date_event',
                'image'  => $this->getSkinUrl('images/grid-cal.gif'),
                'format' => 'yyyy-MM-dd',
            ));
            $fieldset->addField('comment', 'textarea',
                array(
                    'label' => Mage::helper('rewardpoints')->__('Comment'),
                    'name'  => 'comment',
                    'style' => 'height:100px'
                )
            );
            $fieldset->addField('default_expired', 'checkbox', array(
                'label'   => Mage::helper('rewardpoints')->__('Use default point expiration time'),
                'onclick' => 'this.value = this.checked ? 1 : 0;',
                'name'    => 'default_expired',
                'checked' => $set_default_expired,
                'note'    => Mage::helper('rewardpoints')->__('Set in Configuration / General Settings')
            ));

            $fieldset->addField('expired_day', 'text', array(
                'label' => Mage::helper('rewardpoints')->__('Reward Points Expire in (days)'),
                //'required'  => true,
                'class' => 'validate-digits',
                'name'  => 'expired_day',
                //'value'     => $reward_point,
                'note'  => Mage::helper('rewardpoints')->__('Insert 0 if no limitation.'),
            ));

            $fieldset->addField('coupon_code', 'text', array(
                'label' => Mage::helper('rewardpoints')->__('Coupon code'),
                // 'required'  => true,
                'class' => 'mw-rewardpoint-validate-coupon-code',
                'name'  => 'coupon_code',
            ));

            $fieldset->addField('reward_point', 'text', array(
                'label'    => Mage::helper('rewardpoints')->__('Reward Points'),
                'required' => true,
                'class'    => 'validate-digits',
                'name'     => 'reward_point',
                //'value'     => $reward_point,
                'note'     => Mage::helper('rewardpoints')->__('Format x (fixed number of points) or x/y (earn x points for every y monetary units spent)'),
            ));
            if($id = $this->getRequest()->getParam('id'))
            {

                $type = Mage::getModel('rewardpoints/activerules')->load($id)->getTypeOfTransaction();
                if($type == MW_RewardPoints_Model_Type::CUSTOM_RULE)
                {
                    $fieldset->addField('custom_rule', 'note', array(
                        'label' => Mage::helper('rewardpoints')->__('Referral Link'),
                        'text'  => Mage::helper('rewardpoints')->getLinkCustomRule($this->getRequest()->getParam('id')),
                        //'note'      => Mage::helper('rewardpoints')->__('Ex. customer_email = test@gmail.com'),
                    ));
                }
            }
            /*
            $fieldset->addField('reward_point_number', 'text', array(
                'label'     => Mage::helper('rewardpoints')->__('Reward Points'),
                'class'     => 'validate-digits',
                'required'  => true,
                'name'      => 'reward_point_number',
                'value'     => $reward_point,
                ));*/

            if(Mage::getSingleton('adminhtml/session')->getDataActiverules())
            {
                $form_detail->setValues(Mage::getSingleton('adminhtml/session')->getDataActiverules());
                Mage::getSingleton('adminhtml/session')->setDataActiverules(null);

            }
            elseif(Mage::registry('data_activerules'))
            {

                $form_detail->setValues(Mage::registry('data_activerules')->getData());
            }

            return parent::_prepareForm();
        }

    }