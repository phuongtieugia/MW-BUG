<?php

    class MW_RewardPoints_Block_Adminhtml_Rewardpoints_Grid extends Mage_Adminhtml_Block_Widget_Grid
    {
        public function __construct()
        {
            parent::__construct();
            $this->setId('rewardpointsGrid');
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
           $collection->getSelect()->join(
             array('customer_entity'=>$customer_table),'main_table.customer_id = customer_entity.entity_id');

         */
            $collection     = Mage::getResourceModel('customer/customer_collection')
                ->addNameToSelect()
                ->addAttributeToSelect('email');
            $resource       = Mage::getModel('core/resource');
            $customer_table = $resource->getTableName('rewardpoints/customer');

            $collection->getSelect()->joinLeft(
                array('reward_customer_entity' => $customer_table), 'e.entity_id = reward_customer_entity.customer_id', array('mw_reward_point'));

            $this->setCollection($collection);

            return parent::_prepareCollection();
        }

        protected function _prepareColumns()
        {
            /*
            $this->addColumn('customer_id', array(
                'header'    => Mage::helper('rewardpoints')->__('ID'),
                'align'     =>'center',
                'width'     => '100px',
                'index'     => 'customer_id',
            ));

            $this->addColumn('email', array(
                'header'    => Mage::helper('rewardpoints')->__('Email'),
                'align'     =>'left',
                'index'     => 'email',
            ));

            $this->addColumn('reward_point', array(
                'header'    => Mage::helper('rewardpoints')->__('Reward Points'),
                'align'     => 'left',
                'width'     => '80px',
                'index'     => 'mw_reward_point',
                'type'      => 'text',
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
                    'header'    => Mage::helper('rewardpoints')->__('Action'),
                    'width'     => '80px',
                    'type'      => 'action',
                    'getter'    => 'getId',
                    'actions'   => array(
                        array(
                            'caption' => Mage::helper('rewardpoints')->__('View'),
                            'url'     => array('base' => '*/adminhtml_member/edit'),
                            'field'   => 'id'
                        )
                    ),
                    'filter'    => false,
                    'sortable'  => false,
                    'index'     => 'stores',
                    'is_system' => true,
                ));

            $this->addExportType('*/*/exportCsv', Mage::helper('rewardpoints')->__('CSV'));

            //$this->addExportType('*/*/exportXml', Mage::helper('rewardpoints')->__('XML'));

            return parent::_prepareColumns();
        }

        protected function _prepareMassaction()
        {

            return $this;
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

        public function getRowUrl($row)
        {
            return $this->getUrl('adminhtml/rewardpoints_member/edit', array('id' => $row->getId()));
        }

    }