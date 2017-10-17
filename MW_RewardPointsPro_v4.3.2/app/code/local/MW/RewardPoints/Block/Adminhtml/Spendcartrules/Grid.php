<?php

    class MW_RewardPoints_Block_Adminhtml_Spendcartrules_Grid extends Mage_Adminhtml_Block_Widget_Grid
    {
        public function __construct()
        {
            parent::__construct();
            $this->setId('cart_rule_Grid');
            $this->setDefaultSort('rule_id');
            $this->setDefaultDir('DESC');
            $this->setSaveParametersInSession(true);
            $this->setEmptyText(Mage::helper('rewardpoints')->__('No Shopping Cart Spending Rule Found'));
        }

        protected function _prepareCollection()
        {
            $collection = Mage::getModel('rewardpoints/spendcartrules')->getCollection();
            $this->setCollection($collection);

            return parent::_prepareCollection();
        }

        protected function _prepareColumns()
        {
            $this->addColumn('rule_id', array(
                'header' => Mage::helper('rewardpoints')->__('ID'),
                'align'  => 'right',
                'width'  => '50px',
                'index'  => 'rule_id',
            ));

            $this->addColumn('name', array(
                'header' => Mage::helper('rewardpoints')->__('Rule Name'),
                'align'  => 'left',
                'index'  => 'name',
            ));
            $this->addColumn('start_date', array(
                'header' => Mage::helper('rewardpoints')->__('Start Date'),
                'width'  => '150px',
                'index'  => 'start_date',
            ));
            $this->addColumn('end_date', array(
                'header' => Mage::helper('rewardpoints')->__('End Date'),
                'width'  => '150px',
                'index'  => 'end_date',
            ));
            $this->addColumn('rule_position', array(
                'header' => Mage::helper('rewardpoints')->__('Priority'),
                'align'  => 'left',
                'type'   => 'number',
                'index'  => 'rule_position',
            ));
            $this->addColumn('status', array(
                'header'  => Mage::helper('rewardpoints')->__('Status'),
                'align'   => 'left',
                'width'   => '80px',
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
                    'width'     => '100',
                    'type'      => 'action',
                    'getter'    => 'getId',
                    'actions'   => array(
                        array(
                            'caption' => Mage::helper('rewardpoints')->__('Edit'),
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
            $this->getMassactionBlock()->setFormFieldName('cart_rule_Grid');

            $this->getMassactionBlock()->addItem('delete', array(
                'label'   => Mage::helper('rewardpoints')->__('Delete'),
                'url'     => $this->getUrl('*/*/massDelete'),
                'confirm' => Mage::helper('rewardpoints')->__('Are you sure?')
            ));

            $statuses = Mage::getSingleton('rewardpoints/statusrule')->getOptionArray();

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

        public function getRowUrl($row)
        {
            return $this->getUrl('*/*/edit', array('id' => $row->getId()));
        }

    }