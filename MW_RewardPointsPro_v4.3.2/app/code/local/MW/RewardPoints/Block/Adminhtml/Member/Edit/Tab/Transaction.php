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
     * @copyright   Copyright (c) 2009 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
     * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
     */

    /**
     * Adminhtml customer grid block
     *
     * @category   Mage
     * @package    Mage_Adminhtml
     * @author      Magento Core Team <core@magentocommerce.com>
     */
    class MW_Rewardpoints_Block_Adminhtml_Member_Edit_Tab_Transaction extends Mage_Adminhtml_Block_Widget_Grid
    {

        public function __construct()
        {
            parent::__construct();
            $this->setId('Rewardpoints_Grid');
            $this->setDefaultSort('history_id');
            $this->setDefaultDir('desc');

            $this->setUseAjax(true);
            //$this->setTemplate('mw_rewardpoints/grid.phtml');
            $this->setEmptyText(Mage::helper('rewardpoints')->__('No Transaction Found'));
        }

        public function getGridUrl()
        {
            return $this->getUrl('adminhtml/rewardpoints_member/transaction', array('id' => $this->getRequest()->getParam('id')));

        }

        protected function _prepareCollection()
        {
            $collection = Mage::getResourceModel('rewardpoints/rewardpointshistory_collection')
                ->addFieldToFilter('customer_id', $this->getRequest()->getParam('id'));

            $this->setCollection($collection);

            return parent::_prepareCollection();
        }

        protected function _prepareColumns()
        {
            $this->addColumn('history_id', array(
                'header' => Mage::helper('rewardpoints')->__('ID'),
                'align'  => 'left',
                'index'  => 'history_id',
                'width'  => 10
            ));
            /*
            $this->addColumn('transaction_time', array(
                'header'    =>  Mage::helper('rewardpoints')->__('Transaction Time'),
                'type'      =>  'datetime',
                'align'     =>  'center',
                'index'     =>  'transaction_time',
                'gmtoffset' => true,
                'default'   =>  ' ---- '
            ));
            */
            $this->addColumn('transaction_time', array(
                'header'   => Mage::helper('rewardpoints')->__('Transaction Time'),
                'type'     => 'datetime',
                'align'    => 'center',
                'index'    => 'transaction_time',
                'renderer' => 'rewardpoints/adminhtml_renderer_time',
            ));
            $this->addColumn('amount', array(
                'header'   => Mage::helper('rewardpoints')->__('Amount'),
                'align'    => 'left',
                'index'    => 'amount',
                'type'     => 'number',
                'renderer' => 'rewardpoints/adminhtml_renderer_amount',
            ));

            $this->addColumn('balance', array(
                'header' => Mage::helper('rewardpoints')->__('Customer Balance'),
                'align'  => 'left',
                'index'  => 'balance',
                'type'   => 'number',
            ));
            $this->addColumn('transaction_detail', array(
                'header'   => Mage::helper('rewardpoints')->__('Transaction Details'),
                'align'    => 'left',
                'width'    => 400,
                'index'    => 'transaction_detail',
                'renderer' => 'rewardpoints/adminhtml_renderer_transaction',
            ));
            $this->addColumn('status', array(
                'header'  => Mage::helper('rewardpoints')->__('Status'),
                'align'   => 'center',
                'index'   => 'status',
                'type'    => 'options',
                'options' => Mage::getSingleton('rewardpoints/status')->getOptionArray(),
            ));

            return parent::_prepareColumns();
        }

    }
