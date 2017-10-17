<?php

    class MW_RewardPoints_Block_Adminhtml_Member_Grid extends Mage_Adminhtml_Block_Widget_Grid
    {
        public function __construct()
        {
            parent::__construct();
            $this->setId('member_grid');
            $this->setDefaultSort('customer_id');
            $this->setDefaultDir('ASC');
            $this->setSaveParametersInSession(true);
            //$this->setRowClickCallback(null);
        }

        protected function _prepareCollection()
        {
            /*
            $resource = Mage::getModel('core/resource');
            $customer_table = $resource->getTableName('customer/entity');
          $collection = Mage::getModel('rewardpoints/customer')->getCollection();
          //$collection->join('customer/entity','customer/entity.entity_id = main_table.customer_id');
          $collection->getSelect()->join(
            array('customer_entity'=>$customer_table),'main_table.customer_id = customer_entity.entity_id');
          $this->setCollection($collection);
          */

            $collection     = Mage::getResourceModel('customer/customer_collection')
                ->addNameToSelect()
                ->addAttributeToSelect('email');
            $resource       = Mage::getModel('core/resource');
            $customer_table = $resource->getTableName('rewardpoints/customer');

            $collection->getSelect()->joinLeft(
                array('reward_customer_entity' => $customer_table), 'e.entity_id = reward_customer_entity.customer_id', array('mw_reward_point'));

            // echo $collection->getSelect();die();
            $this->setCollection($collection);

            return parent::_prepareCollection();
        }

        protected function _prepareColumns()
        {
            /*
            $this->addColumn('customer_id', array(
                'header'    => Mage::helper('rewardpoints')->__('ID'),
                'align'     =>'right',
                'width'     => '100px',
                'index'     => 'customer_id',
            ));
             $this->addColumn('customer_name', array(
                'header'    => Mage::helper('rewardpoints')->__('Customer Name'),
                'align'     =>'left',
                'index'     => 'customer_id',
                  'renderer'  => 'rewardpoints/adminhtml_renderer_name',
                  'filter_condition_callback' => array($this, '_filterReferralnameCondition'),
            ));
            $this->addColumn('reward_point', array(
                'header'    => Mage::helper('rewardpoints')->__('Balance'),
                'align'     => 'right',
                'width'     => '80px',
                'index'     => 'mw_reward_point',
                'type'      => 'number',
            ));
            */
            $this->addColumn('entity_id', array(
                'header' => Mage::helper('rewardpoints')->__('ID'),
                'align'  => 'right',
                'width'  => '100px',
                'index'  => 'entity_id',
            ));
            $this->addColumn('name', array(
                'header' => Mage::helper('rewardpoints')->__('Customer Name'),
                'index'  => 'name'
            ));
            $this->addColumn('email', array(
                'header' => Mage::helper('rewardpoints')->__('Customer Email'),
                'align'  => 'left',
                'index'  => 'email',
            ));

            $this->addColumn('reward_point', array(
                'header'                    => Mage::helper('rewardpoints')->__('Balance'),
                'align'                     => 'right',
                'width'                     => '80px',
                'index'                     => 'mw_reward_point',
                'type'                      => 'number',
                'renderer'                  => 'rewardpoints/adminhtml_renderer_point',
                'filter_condition_callback' => array($this, '_filterPointCondition'),
                //'filter'    =>false
            ));

            $this->addColumn('action',
                array(
                    'header'    => Mage::helper('rewardpoints')->__('Action (Manage Points)'),
                    'width'     => '80px',
                    'type'      => 'action',
                    'getter'    => 'getId',
                    'actions'   => array(
                        array(
                            'caption' => Mage::helper('rewardpoints')->__('View'),
                            'url'     => array('base' => '*/*/edit'),
                            'field'   => 'id'
                        )
                    ),
                    'filter'    => false,
                    'sortable'  => false,
                    'index'     => 'stores',
                    'is_system' => true,
                ));

            $this->addExportType('*/*/exportCsv', Mage::helper('rewardpoints')->__('CSV'));
            $this->addExportType('*/*/exportXml', Mage::helper('rewardpoints')->__('XML'));

            return parent::_prepareColumns();
        }

        protected function _filterPointCondition($collection, $column)
        {
            if(!$value = $column->getFilter()->getValue())
            {
                return;
            }

            if(isset($value['from']) && $value['from'] != '' && $value['from'] != 0)
            {
                $this->getCollection()->getSelect()->where("reward_customer_entity.mw_reward_point >= ? ", $value['from']);
            }
            if(isset($value['to']) && $value['to'] != '')
            {
                $this->getCollection()->getSelect()->where("reward_customer_entity.mw_reward_point <= ?", $value['to']);
            }
            //echo $this->getCollection()->getSelect();die();
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

        public function getRowUrl($row)
        {
            return $this->getUrl('*/*/edit', array('id' => $row->getId()));
        }

    }