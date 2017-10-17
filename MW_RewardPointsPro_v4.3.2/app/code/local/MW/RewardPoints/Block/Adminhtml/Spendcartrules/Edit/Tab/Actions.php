<?php
    /**
     * Magento
     *
     * NOTICE OF LICENSE
     *
     * This source file is subject to the Open Software License (OSL 3.0)
     * that is bundled with this package in the file LICENSE.txt.
     * It is also available through the world-wide-web at this URL:
     * http://opensource.org/licenses/osl-3.0.php
     * If you did not receive a copy of the license and are unable to
     * obtain it through the world-wide-web, please send an email
     * to license@magentocommerce.com so we can send you a copy immediately.
     *
     * DISCLAIMER
     *
     * Do not edit or add to this file if you wish to upgrade Magento to newer
     * versions in the future. If you wish to customize Magento for your
     * needs please refer to http://www.magentocommerce.com for more information.
     *
     * @category    Mage
     * @package     Mage_Adminhtml
     * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
     * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
     */

    /**
     * description
     *
     * @category    Mage
     * @category   Mage
     * @package    Mage_Adminhtml
     * @author      Magento Core Team <core@magentocommerce.com>
     */
    class MW_RewardPoints_Block_Adminhtml_Spendcartrules_Edit_Tab_Actions extends Mage_Adminhtml_Block_Widget_Form
    {
        /**
         * Prepare content for tab
         *
         * @return string
         */

        protected function _prepareForm()
        {
            $model = Mage::registry('data_cart_rules');
            //$model = Mage::getModel('salesrule/rule');
            $form = new Varien_Data_Form();

            $form->setHtmlIdPrefix('rule_');

            $fieldset = $form->addFieldset('action_fieldset', array('legend' => Mage::helper('rewardpoints')->__('Allow reward points using the following information')));

            $fieldset->addField('simple_action', 'select', array(
                'label'   => Mage::helper('rewardpoints')->__('Apply'),
                'class'   => 'required-entry validate-digits',
                'name'    => 'simple_action',
                'options' => MW_RewardPoints_Model_Typerulespend::getOptionArray()
            ));
            $fieldset->addField('reward_point', 'text', array(
                'label'    => Mage::helper('rewardpoints')->__('Reward Points (X)'),
                'class'    => 'required-entry validate-digits',
                'required' => true,
                'name'     => 'reward_point',
            ));
            $fieldset->addField('reward_step', 'text', array(
                'label' => Mage::helper('rewardpoints')->__('Per (Y) dollars Spent'),
                'class' => 'validate-digits',
                'name'  => 'reward_step',
                'note'  => Mage::helper('rewardpoints')->__('Skip if Fixed Reward Points chosen')
            ));

            $fieldset->addField('stop_rules_processing', 'select', array(
                'label'   => Mage::helper('rewardpoints')->__('Stop Further Rules Processing'),
                'title'   => Mage::helper('rewardpoints')->__('Stop Further Rules Processing'),
                'name'    => 'stop_rules_processing',
                'options' => array(
                    '1' => Mage::helper('rewardpoints')->__('Yes'),
                    '0' => Mage::helper('rewardpoints')->__('No'),
                ),
                'note'    => Mage::helper('rewardpoints')->__("Set priority under 'Rule Information'")
            ));

            $renderer = Mage::getBlockSingleton('adminhtml/widget_form_renderer_fieldset')
                ->setTemplate('promo/fieldset.phtml')
                ->setNewChildUrl($this->getUrl('adminhtml/promo_quote/newActionHtml/form/rule_actions_fieldset'));
            //echo $this->getUrl('adminhtml/promo_quote/newConditionHtml/form/rule_conditions_fieldset');

            $fieldset = $form->addFieldset('actions_fieldset', array(
                'legend' => Mage::helper('rewardpoints')->__('Apply the rule only to <u>cart items</u> matching the following conditions (leave blank for all items)')
            ))->setRenderer($renderer);

            $fieldset->addField('actions', 'text', array(
                'name'     => 'actions',
                'label'    => Mage::helper('rewardpoints')->__('Apply To'),
                'title'    => Mage::helper('rewardpoints')->__('Apply To'),
                'required' => true,
            ))->setRule($model)->setRenderer(Mage::getBlockSingleton('rule/actions'));

            $form->setValues($model->getData());

            //$form->setUseContainer(true);
            $this->setForm($form);

            return parent::_prepareForm();
        }
    }
