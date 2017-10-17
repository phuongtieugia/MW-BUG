<?php

    class MW_RewardPoints_Block_Adminhtml_Cartrules_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
    {
        protected function _prepareForm()
        {
            $form_program_detail = new Varien_Data_Form();
            $this->setForm($form_program_detail);
            $fieldset = $form_program_detail->addFieldset('cart_rules_form', array('legend' => Mage::helper('rewardpoints')->__('Rule Information')));

            $fieldset->addField('name', 'text', array(
                'label'    => Mage::helper('rewardpoints')->__('Rule Name'),
                'class'    => 'required-entry',
                'required' => true,
                'name'     => 'name',
            ));
            $fieldset->addField('description', 'textarea', array(
                'name'     => 'description',
                'label'    => Mage::helper('rewardpoints')->__('Description'),
                'class'    => 'required-entry',
                'required' => true,
                'title'    => Mage::helper('rewardpoints')->__('Description'),
            ));
            $fieldset->addField('promotion_message', 'textarea', array(
                'name'  => 'promotion_message',
                'label' => Mage::helper('rewardpoints')->__('Promotional Message'),
                'title' => Mage::helper('rewardpoints')->__('Promotional Message'),
                'note'  => Mage::helper('rewardpoints')->__('Choose Yes under Configuration / Display Configuration to display')
            ));

            $fieldset->addField('promotion_image', 'image', array(
                'label'    => Mage::helper('rewardpoints')->__('Promotional Banner'),
                'required' => false,
                'name'     => 'promotion_image',
                'note'     => Mage::helper('rewardpoints')->__('Choose Yes under Configuration / Display Configuration to display')
            ));
            $fieldset->addField('status', 'select', array(
                'label'  => Mage::helper('rewardpoints')->__('Status'),
                'name'   => 'status',
                'values' => array(
                    array(
                        'value' => 1,
                        'label' => Mage::helper('rewardpoints')->__('Enabled'),
                    ),

                    array(
                        'value' => 2,
                        'label' => Mage::helper('rewardpoints')->__('Disabled'),
                    ),
                ),
                'note'   => Mage::helper('rewardpoints')->__('Enable and Save rule to activate')
            ));
            if(!Mage::app()->isSingleStoreMode())
            {
                $fieldset->addField('store_view', 'multiselect', array(
                    'name'     => 'store_view[]',
                    'label'    => Mage::helper('rewardpoints')->__('Store View'),
                    'title'    => Mage::helper('rewardpoints')->__('Store View'),
                    'required' => true,
                    'values'   => Mage::getSingleton('adminhtml/system_store')->getStoreValuesForForm(false, true),
                    //'disabled'  => $isElementDisabled                
                ));
            }
            else
            {
                $fieldset->addField('store_view', 'hidden', array(
                    'name'  => 'store_view[]',
                    'value' => Mage::app()->getStore(true)->getId()
                ));
                // $model->setStoreId(Mage::app()->getStore(true)->getId());
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
                'label'    => Mage::helper('catalogrule')->__('Customer Groups'),
                'title'    => Mage::helper('catalogrule')->__('Customer Groups'),
                'required' => true,
                'values'   => $customerGroups,
            ));
            $fieldset->addField('start_date', 'date', array(
                'label'  => Mage::helper('rewardpoints')->__('Start Date'),
                'name'   => 'start_date',
                'image'  => $this->getSkinUrl('images/grid-cal.gif'),
                'format' => 'yyyy-MM-dd',
            ));
            $fieldset->addField('end_date', 'date', array(
                'label'  => Mage::helper('rewardpoints')->__('End Date'),
                'name'   => 'end_date',
                'image'  => $this->getSkinUrl('images/grid-cal.gif'),
                'format' => 'yyyy-MM-dd',
                'note'   => Mage::helper('rewardpoints')->__('Leave blank for no time restriction'),
            ));
            $fieldset->addField('rule_position', 'text', array(
                'label' => Mage::helper('rewardpoints')->__('Priority'),
                'name'  => 'rule_position',
                'class' => 'validate-digits',
                'note'  => Mage::helper('rewardpoints')->__("'Set Further Rules Processing' under 'Actions'"),
            ));

            if(Mage::getSingleton('adminhtml/session')->getDataCartRules())
            {
                $form_program_detail->setValues(Mage::getSingleton('adminhtml/session')->getDataCartRules());
                Mage::getSingleton('adminhtml/session')->setDataCartRules(null);
            }
            elseif(Mage::registry('data_cart_rules'))
            {
                //Zend_Debug::dump(Mage::registry('affiliate_data_program')->getData());die();
                $form_program_detail->setValues(Mage::registry('data_cart_rules')->getData());

            }

            return parent::_prepareForm();
        }
    }