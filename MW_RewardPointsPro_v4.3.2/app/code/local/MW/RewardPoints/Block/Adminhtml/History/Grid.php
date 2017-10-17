<?php

    class MW_Rewardpoints_Block_Adminhtml_History_Grid extends Mage_Adminhtml_Block_Widget_Grid
    {

        public function __construct()
        {
            parent::__construct();
            $this->setId('history_Grid');
            $this->setDefaultSort('history_id');
            $this->setDefaultDir('desc');

            //$this->setUseAjax(true);
            //$this->setTemplate('mw_rewardpoints/grid.phtml');
            $this->setEmptyText(Mage::helper('rewardpoints')->__('No Transaction Found'));
        }

        protected function _prepareCollection()
        {
            //$customer = Mage::getModel('customer/customer')->getCollection();
            $resource       = Mage::getModel('core/resource');
            $customer_table = $resource->getTableName('customer/entity');
            $collection     = Mage::getResourceModel('rewardpoints/rewardpointshistory_collection')
                ->setOrder('transaction_time', 'DESC')
                ->setOrder('history_id', 'DESC');

            $collection->getSelect()->join(
                array('customer_entity' => $customer_table), 'main_table.customer_id = customer_entity.entity_id', array('email'));
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
                'header'    =>  Mage::helper('rewardpoints')->__('Created Time'),
                'type'      =>  'datetime',
                'align'     =>  'center',
                'index'     =>  'transaction_time',
                'gmtoffset' => true,
                'default'   =>  ' ---- '
            ));*/
            $this->addColumn('transaction_time', array(
                'header'   => Mage::helper('rewardpoints')->__('Created Time'),
                'type'     => 'datetime',
                'align'    => 'center',
                'index'    => 'transaction_time',
                'renderer' => 'rewardpoints/adminhtml_renderer_time',
            ));
            $this->addColumn('customer_name', array(
                'header'                    => Mage::helper('rewardpoints')->__('Customer Name'),
                'align'                     => 'left',
                'index'                     => 'customer_id',
                'renderer'                  => 'rewardpoints/adminhtml_renderer_name',
                'filter_condition_callback' => array($this, '_filterReferralnameCondition'),
            ));
            $this->addColumn('email', array(
                'header' => Mage::helper('rewardpoints')->__('Customer Email'),
                'align'  => 'left',
                'index'  => 'email',
            ));
            $this->addColumn('amount', array(
                'header'   => Mage::helper('rewardpoints')->__('Amount'),
                'align'    => 'right',
                'index'    => 'amount',
                'type'     => 'number',
                'renderer' => 'rewardpoints/adminhtml_renderer_amount',
            ));

            $this->addColumn('balance', array(
                'header' => Mage::helper('rewardpoints')->__('Customer Balance'),
                'align'  => 'right',
                'index'  => 'balance',
                'type'   => 'number',
            ));
            $this->addColumn('transaction_detail', array(
                'header'   => Mage::helper('rewardpoints')->__('Transaction Detail'),
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


            $this->addExportType('*/*/exportCsv', Mage::helper('rewardpoints')->__('CSV'));
            $this->addExportType('*/*/exportXml', Mage::helper('rewardpoints')->__('XML'));

            return parent::_prepareColumns();
        }

        protected function _filterReferralnameCondition($collection, $column)
        {
            if(!$value = $column->getFilter()->getValue())
            {
                return;
            }
            $customer_ids = array();
            $value        = '%' . $value . '%';
            // $customer_collections =  Mage::getModel('customer/customer')->getCollection()->addFieldToFilter('firstname',array('like' => $value));
            $customer_collections = Mage::getModel('customer/customer')->getCollection()
                ->addAttributeToFilter(array(
                    array(
                        'attribute' => 'firstname',
                        array('like' => $value),
                    ),
                    array(
                        'attribute' => 'lastname',
                        array('like' => $value),
                    ),
                ));
            foreach ($customer_collections as $customer_collection)
            {
                $customer_ids[] = $customer_collection->getId();
            }
            $this->getCollection()->getSelect()->where("main_table.customer_id in (?)", $customer_ids);
        }

        public function getCsv()
        {
            $csv             = '';
            $this->_isExport = true;
            $this->_prepareGrid();
            $this->getCollection()->getSelect()->limit();
            $this->getCollection()->setPageSize(0);
            $this->getCollection()->load();
            $this->_afterLoadCollection();

            $data = array();
            foreach ($this->_columns as $column)
            {
                if(!$column->getIsSystem())
                {
                    $data[] = '"' . $column->getExportHeader() . '"';
                }
            }
            $csv .= implode(',', $data) . "\n";

            foreach ($this->getCollection() as $item)
            {
                $data = array();
                foreach ($this->_columns as $col_id => $column)
                {
                    if(!$column->getIsSystem())
                    {
                        if($col_id == 'transaction_detail')
                        {
                            $transactionDetail = MW_RewardPoints_Model_Typecsv::getTransactionDetail($item->getTypeOfTransaction(), $item->getTransactionDetail(), $item->getStatus(), true);
                            $data[]            = '"' . str_replace(array('"', '\\'), array('""', '\\\\'), $transactionDetail) . '"';
                            //zend_debug::dump($item->getOrderId());die();
                        }
                        else
                        {
                            $data[] = '"' . str_replace(array('"', '\\'), array('""', '\\\\'), $column->getRowFieldExport($item)) . '"';
                        }
                    }
                }
                $csv .= implode(',', $data) . "\n";
            }

            if($this->getCountTotals())
            {
                $data = array();
                foreach ($this->_columns as $column)
                {
                    if(!$column->getIsSystem())
                    {
                        $data[] = '"' . str_replace(array('"', '\\'), array('""', '\\\\'), $column->getRowFieldExport($this->getTotals())) . '"';
                    }
                }
                $csv .= implode(',', $data) . "\n";
            }

            return $csv;
        }

    }
