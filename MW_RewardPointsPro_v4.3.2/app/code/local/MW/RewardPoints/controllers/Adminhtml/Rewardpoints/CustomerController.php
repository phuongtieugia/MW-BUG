<?php
    /**
     * Magento Enterprise Edition
     *
     * NOTICE OF LICENSE
     *
     * This source file is subject to the Magento Enterprise Edition License
     * that is bundled with this package in the file LICENSE_EE.txt.
     * It is also available through the world-wide-web at this URL:
     * http://www.magentocommerce.com/license/enterprise-edition
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
     * @category    Enterprise
     * @package     Enterprise_Reward
     * @copyright   Copyright (c) 2012 Magento Inc. (http://www.magentocommerce.com)
     * @license     http://www.magentocommerce.com/license/enterprise-edition
     */


    /**
     * Reward admin customer controller
     *
     * @category    Enterprise
     * @package     Enterprise_Reward
     * @author      Magento Core Team <core@magentocommerce.com>
     */
    class MW_RewardPoints_Adminhtml_Rewardpoints_CustomerController extends Mage_Adminhtml_Controller_Action
    {
        /**
         * Check if module functionality enabled
         *
         * @return Enterprise_Reward_Adminhtml_Reward_RateController
         */
        public function preDispatch()
        {
            parent::preDispatch();
            if(!Mage::helper('rewardpoints')->moduleEnabled() && $this->getRequest()->getActionName() != 'noroute')
            {
                $this->_forward('noroute');
            }

            return $this;
        }

        /**
         * History Ajax Action
         */
        public function historyAction()
        {
            $customerId = $this->getRequest()->getParam('id', 0);
            $history    = $this->getLayout()
                ->createBlock('rewardpoints/adminhtml_customer_edit_tab_rewardpoints_history', '',
                    array('customer_id' => $customerId));
            $this->getResponse()->setBody($history->toHtml());
        }

        /**
         * History Grid Ajax Action
         *
         */
        public function historyGridAction()
        {
            $customerId = $this->getRequest()->getParam('id', 0);
            $history    = $this->getLayout()
                ->createBlock('rewardpoints/adminhtml_customer_edit_tab_rewardpoints_history_grid', '',
                    array('customer_id' => $customerId));
            $this->getResponse()->setBody($history->toHtml());
        }

        protected function _isAllowed()
        {
            return true;
        }
    }
