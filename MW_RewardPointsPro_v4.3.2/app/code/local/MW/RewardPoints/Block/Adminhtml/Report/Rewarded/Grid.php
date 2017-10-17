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
     * @copyright   Copyright (c) 2012 Magento Inc. (http://www.magentocommerce.com)
     * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
     */


    /**
     * Report Sold Products Grid Block
     *
     * @category   Mage
     * @package    Mage_Adminhtml
     * @author     Magento Core Team <core@magentocommerce.com>
     */
    class MW_RewardPoints_Block_Adminhtml_Report_Rewarded_Grid extends Mage_Adminhtml_Block_Report_Grid
    {
        /**
         * Sub report size
         *
         * @var int
         */
        protected $_subReportSize = 0;

        /**
         * Initialize Grid settings
         *
         */
        public function __construct()
        {
            parent::__construct();
            $this->setId('gridrewardpointsReportRewarded');
        }

        /**
         * Prepare collection object for grid
         *
         * @return Mage_Adminhtml_Block_Report_Product_Sold_Grid
         */
        protected function _prepareCollection()
        {
            parent::_prepareCollection();
            $this->getCollection()
                ->initReport('rewardpoints/rewardpointshistory_rewarded_collection');
            return $this;
        }

        /**
         * Prepare Grid columns
         *
         * @return Mage_Adminhtml_Block_Report_Product_Sold_Grid
         */
        protected function _prepareColumns()
        {
            $this->addColumn('total_rewarded_sum', array(
                'header' => Mage::helper('rewardpoints')->__('Total Rewarded'),
                'align'  => 'left',
                'index'  => 'total_rewarded_sum',
                'width'  => '150px',
                'type'   => 'text',
            ));

            $this->addColumn('rewarded_on_purchases_sum', array(
                'header' => Mage::helper('rewardpoints')->__('Rewarded on Purchases'),
                'align'  => 'left',
                'index'  => 'rewarded_on_purchases_sum',
                'width'  => '150px',
                'type'   => 'text',
            ));

            $this->addColumn('rewarded_on_sign_up_sum', array(
                'header' => Mage::helper('rewardpoints')->__('Rewarded on Sign-Ups'),
                'width'  => '150px',
                'align'  => 'left',
                'index'  => 'rewarded_on_sign_up_sum',
            ));
            $this->addColumn('rewarded_on_subscribers_sum', array(
                'header' => Mage::helper('rewardpoints')->__('Rewarded on Subscribers'),
                'width'  => '150px',
                'align'  => 'left',
                'index'  => 'rewarded_on_subscribers_sum',
            ));
            $this->addColumn('rewarded_on_reviews_sum', array(
                'header' => Mage::helper('rewardpoints')->__('Rewarded on Reviews'),
                'width'  => '150px',
                'align'  => 'left',
                'index'  => 'rewarded_on_reviews_sum',
            ));
            $this->addColumn('rewarded_on_birthday_sum', array(
                'header' => Mage::helper('rewardpoints')->__('Rewarded on Birthday'),
                'width'  => '150px',
                'align'  => 'left',
                'index'  => 'rewarded_on_birthday_sum',
            ));
            $this->addColumn('rewarded_on_referal_sum', array(
                'header' => Mage::helper('rewardpoints')->__('Rewarded on Referal'),
                'width'  => '150px',
                'align'  => 'left',
                'index'  => 'rewarded_on_referal_sum',
            ));
            /*$this->addColumn('added_by_admin_sum', array(
                'header' => Mage::helper('rewardpoints')->__('Added by Admin'),
                'width'  => '150px',
                'align'  => 'left',
                'index'  => 'added_by_admin_sum',
            ));*/
            $this->addColumn('other_rewards_sum', array(
                'header' => Mage::helper('rewardpoints')->__('Other Rewards'),
                'width'  => '150px',
                'align'  => 'left',
                'index'  => 'other_rewards_sum',
            ));
            $this->addColumn('total_transaction_count', array(
                'header' => Mage::helper('rewardpoints')->__('Total Transactions'),
                'width'  => '150px',
                'align'  => 'left',
                'index'  => 'total_transaction_count',
            ));

            $this->addExportType('*/*/exportRewardedCsv', Mage::helper('reports')->__('CSV'));
            $this->addExportType('*/*/exportRewardedExcel', Mage::helper('reports')->__('Excel XML'));

            return parent::_prepareColumns();
        }
    }
