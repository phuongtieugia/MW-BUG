<?php

    class MW_RewardPoints_Block_Adminhtml_Activerules_Grid extends Mage_Adminhtml_Block_Widget_Grid
    {
        public function __construct()
        {
            parent::__construct();
            $this->setId('activerules_grid');
            $this->setDefaultSort('rule_id');
            $this->setDefaultDir('ASC');
            $this->setSaveParametersInSession(true);
            //$this->setRowClickCallback(null);
        }

        protected function _prepareCollection()
        {
            $collections = Mage::getModel('rewardpoints/activerules')->getCollection();

            /*foreach ($collections as $collection) {
               $storeview = $collection ->getStoreView();// chuoi storeview
               $store = explode(",", $storeview);// 1,2 => array(1,2)
               $collection->setData('store_view',$store);
           }*/
            $this->setCollection($collections);

            return parent::_prepareCollection();
        }

        protected function _prepareColumns()
        {
            $this->addColumn('rule_id', array(
                'header' => Mage::helper('rewardpoints')->__('ID'),
                'align'  => 'center',
                'width'  => '120px',
                'index'  => 'rule_id',
            ));
            $this->addColumn('rule_name', array(
                'header' => Mage::helper('rewardpoints')->__('Rule name'),
                'align'  => 'left',
                'index'  => 'rule_name',
                'type'   => 'text',
            ));
            $this->addColumn('type_of_transaction', array(
                'header'  => Mage::helper('rewardpoints')->__('Transaction Type'),
                'align'   => 'left',
                'index'   => 'type_of_transaction',
                'type'    => 'options',
                'options' => MW_RewardPoints_Model_Type::getTypeReward(),
            ));

            if(!Mage::app()->isSingleStoreMode())
            {
                $this->addColumn('store_view', array(
                    'header'                    => Mage::helper('rewardpoints')->__('Store View'),
                    'index'                     => 'store_view',
                    'type'                      => 'store',
                    'store_all'                 => true,
                    'store_view'                => true,
                    'sortable'                  => false,
                    'filter_condition_callback' => array($this, '_filterStoreCondition'),
                ));
            }

            $this->addColumn('reward_point', array(
                'header' => Mage::helper('rewardpoints')->__('Set Reward Points'),
                'align'  => 'left',
                'index'  => 'reward_point',
                'type'   => 'text',
                'width'  => '150px',
            ));

            $this->addColumn('status', array(
                'header'  => Mage::helper('rewardpoints')->__('Status'),
                'align'   => 'left',
                'width'   => '120px',
                'index'   => 'status',
                'type'    => 'options',
                'options' => array(
                    1 => 'Enabled',
                    2 => 'Disabled',
                ),
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

        protected function _prepareMassaction()
        {
            $this->setMassactionIdField('rule_id');
            $this->getMassactionBlock()->setFormFieldName('activerules_grid');

            $this->getMassactionBlock()->addItem('delete', array(
                'label'   => Mage::helper('rewardpoints')->__('Delete'),
                'url'     => $this->getUrl('*/*/massDelete'),
                'confirm' => Mage::helper('rewardpoints')->__('Are you sure?')
            ));

            $statuses = array(
                array(
                    'value' => 1,
                    'label' => Mage::helper('rewardpoints')->__('Enabled'),
                ),

                array(
                    'value' => 2,
                    'label' => Mage::helper('rewardpoints')->__('Disabled'),
                ),
            );

            array_unshift($statuses, array('label' => '', 'value' => ''));
            $this->getMassactionBlock()->addItem('status', array(
                'label'      => Mage::helper('rewardpoints')->__('Change status'),
                'url'        => $this->getUrl('*/*/massStatus', array('_current' => true)),
                'additional' => array(
                    'visibility' => array(
                        'name'   => 'status',
                        'type'   => 'select',
                        'class'  => 'required-entry',
                        'label'  => Mage::helper('rewardpoints')->__('Status'),
                        'values' => $statuses
                    )
                )
            ));

            return $this;
        }

        protected function _filterStoreCondition($collection, $column)
        {
            if(!$value = $column->getFilter()->getValue())
            {
                return;
            }
            //$this->getCollection()->addFieldToFilter('store_view', array('like' => '%'.$value.'%'));
            $this->getCollection()->getSelect()->where("main_table.store_view like '%" . $value . "%' or main_table.store_view = '0'");
            //echo $this->getCollection()->getSelect();die();
        }

        public function getRowUrl($row)
        {
            return $this->getUrl('*/*/edit', array('id' => $row->getId()));
        }

    }