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
    class MW_RewardPoints_Block_Adminhtml_Report_Redeemed_Grid extends Mage_Adminhtml_Block_Report_Grid
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
            $this->setId('gridrewardpointsReportRedeemed');
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
                ->initReport('rewardpoints/rewardpointshistory_redeemed_collection');
            return $this;
        }

        /**
         * Prepare Grid columns
         *
         * @return Mage_Adminhtml_Block_Report_Product_Sold_Grid
         */
        protected function _prepareColumns()
        {
            $this->addColumn('total_redeemed_sum', array(
                'header' => Mage::helper('rewardpoints')->__('Total Redeemed'),
                'align'  => 'left',
                'index'  => 'total_redeemed_sum',
                'width'  => '250px',
                'type'   => 'text',
            ));

            $this->addColumn('order_id_count', array(
                'header' => Mage::helper('rewardpoints')->__('Number of Orders'),
                'width'  => '150px',
                'align'  => 'left',
                'index'  => 'order_id_count',
            ));

            $this->addColumn('avg_redeemed_per_order', array(
                'header' => Mage::helper('rewardpoints')->__('Avg Redeemed per Order'),
                'width'  => '150px',
                'align'  => 'left',
                'index'  => 'avg_redeemed_per_order',
            ));

            $this->addColumn('total_sales_sum', array(
                'header' => Mage::helper('rewardpoints')->__('Total Sales'),
                'width'  => '150px',
                'align'  => 'left',
                'index'  => 'total_sales_sum',
            ));

            $this->addColumn('total_point_discount_sum', array(
                'header' => Mage::helper('rewardpoints')->__('Total Discount'),
                'width'  => '150px',
                'align'  => 'left',
                'index'  => 'total_point_discount_sum',
            ));


            $this->addExportType('*/*/exportRedeemedCsv', Mage::helper('reports')->__('CSV'));
            $this->addExportType('*/*/exportRedeemedExcel', Mage::helper('reports')->__('Excel XML'));

            return parent::_prepareColumns();
        }
    }
