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
//class MW_Test_Block_Adminhtml_Test_Edit_Tab_Conditions
    // extends Mage_Adminhtml_Block_Widget_Form
    //implements Mage_Adminhtml_Block_Widget_Tab_Interface
    class MW_RewardPoints_Block_Adminhtml_Spendcartrules_Edit_Tab_Conditions extends Mage_Adminhtml_Block_Widget_Form
    {
        /**
         * Prepare content for tab
         *
         * @return string
         */
//    public function getTabLabel()
//    {
//        return Mage::helper('salesrule')->__('Conditions');
//    }

        /**
         * Prepare title for tab
         *
         * @return string
         */
//    public function getTabTitle()
//    {
//        return Mage::helper('salesrule')->__('Conditions');
//    }

        /**
         * Returns status flag about this tab can be showen or not
         *
         * @return true
         */
//    public function canShowTab()
//    {
//        return true;
//    }

        /**
         * Returns status flag about this tab hidden or not
         *
         * @return true
         */
//    public function isHidden()
//    {
//        return false;
//    }

        protected function _prepareForm()
        {
            $model = Mage::registry('data_cart_rules');
            //$model = Mage::getModel('salesrule/rule');
            $form = new Varien_Data_Form();

            $form->setHtmlIdPrefix('rule_');

            $renderer = Mage::getBlockSingleton('adminhtml/widget_form_renderer_fieldset')
                ->setTemplate('promo/fieldset.phtml')
                ->setNewChildUrl($this->getUrl('adminhtml/promo_quote/newConditionHtml/form/rule_conditions_fieldset'));
            //echo $this->getUrl('adminhtml/promo_quote/newConditionHtml/form/rule_conditions_fieldset');

            $fieldset = $form->addFieldset('conditions_fieldset', array(
                'legend' => Mage::helper('rewardpoints')->__('Apply the rule only if the following conditions are met for the entire cart (leave blank for all products)')
            ))->setRenderer($renderer);

            $fieldset->addField('conditions', 'text', array(
                'name'  => 'conditions',
                'label' => Mage::helper('rewardpoints')->__('Conditions'),
                'title' => Mage::helper('rewardpoints')->__('Conditions'),
            ))->setRule($model)->setRenderer(Mage::getBlockSingleton('rule/conditions'));

            $form->setValues($model->getData());

            //$form->setUseContainer(true);

            $this->setForm($form);

            return parent::_prepareForm();
        }
    }
