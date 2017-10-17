<?php

    class MW_Rewardpoints_Block_Adminhtml_Customer_Edit_Tab_Rewardpoints_History_Grid extends Mage_Adminhtml_Block_Widget_Grid
    {

        public function __construct()
        {
            parent::__construct();
            $this->setId('Rewardpoints_Grid');
            $this->setDefaultSort('history_id');
            $this->setDefaultDir('desc');

            $this->setUseAjax(true);
            $this->setEmptyText(Mage::helper('rewardpoints')->__('No Transaction Found'));
        }

        public function getGridUrl()
        {
            return $this->getUrl('adminhtml/rewardpoints_customer/historyGrid', array('_current' => true));
        }

        public function getRowUrl($row)
        {
            return '';
        }

        protected function _prepareCollection()
        {
            $collection = Mage::getResourceModel('rewardpoints/rewardpointshistory_collection')
                ->addFieldToFilter('customer_id', $this->getCustomerId());

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
