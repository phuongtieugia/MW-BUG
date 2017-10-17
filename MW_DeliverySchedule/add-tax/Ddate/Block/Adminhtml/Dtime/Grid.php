<?php

class MW_Ddate_Block_Adminhtml_Dtime_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('ddate/dtime/grid.phtml');
        $this->setId('dtimeGrid');
        $this->setDefaultSort('dtime_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }
    
    protected function _prepareCollection()
    {
        $collection = Mage::getModel('ddate/dtime')->getCollection();
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->setTemplate('ddate/dtime/grid.phtml');
        $this->addColumn('dtime_id', array(
            'header' => Mage::helper('ddate')->__('ID'),
            'align' => 'right',
            'width' => '50px',
            'index' => 'dtime_id'
        ));
        
        $this->addColumn('dtime', array(
            'header' => Mage::helper('ddate')->__('Delivery Slot Time'),
            'align' => 'left',
            'index' => 'dtime'
        ));

        $this->addColumn('dtimesort', array(
            'header' => Mage::helper('ddate')->__('Slot time Sorting'),
            'align' => 'left',
            'index' => 'dtimesort'
        ));
		
		$this->addColumn('dtime_tax', array(
            'header' => Mage::getStoreConfig("ddate/info/dtax"),
            'align' => 'left',
            'index' => 'dtime_tax'
        ));

        if (!Mage::app()->isSingleStoreMode()) {
            $this->addColumn('store_id', array(
                'header' => Mage::helper('ddate')->__('Store Views'),
                'index' => 'store_id',
                'width' => '180px',
                'type' => 'store',
                'store_all' => true
            ));
        }

        $this->addColumn('status', array(
            'header' => Mage::helper('ddate')->__('Status'),
            'align' => 'left',
            'width' => '80px',
            'index' => 'status',
            'type' => 'options',
            'options' => array(
                1 => 'Enabled',
                0 => 'Disabled'
            )
        ));
        
        $this->addColumn('action', array(
            'header' => Mage::helper('ddate')->__('Action'),
            'width' => '100',
            'type' => 'action',
            'align' => 'center',
            'getter' => 'getDtimeId',
            'actions' => array(
                array(
                    'caption' => Mage::helper('ddate')->__('Edit'),
                    'url' => array('base' => '*/*/edit'),
                    'field' => 'dtime_id'
                )
            ),
            'filter' => false,
            'sortable' => false,
            'index' => 'stores',
            'is_system' => true
        ));
        
        $this->addExportType('*/*/exportCsv', Mage::helper('ddate')->__('CSV'));
        $this->addExportType('*/*/exportXml', Mage::helper('ddate')->__('XML'));
        
        return parent::_prepareColumns();
    }
    
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('dtime_id');
        $this->getMassactionBlock()->setFormFieldName('dtime_id');
        
        $this->getMassactionBlock()->addItem('delete', array(
            'label' => Mage::helper('ddate')->__('Delete'),
            'url' => $this->getUrl('*/*/massDelete'),
            'confirm' => Mage::helper('ddate')->__('Are you sure to delete?')
        ));
        
        $statuses = Mage::getSingleton('ddate/status')->getOptionArray();
        array_unshift($statuses, array(
            'label' => '',
            'value' => ''
        ));
        
        $this->getMassactionBlock()->addItem('status', array(
            'label' => Mage::helper('ddate')->__('Change status'),
            'url' => $this->getUrl('*/*/massStatus', array(
                '_current' => true
            )),
            'additional' => array(
                'visibility' => array(
                    'name' => 'status',
                    'type' => 'select',
                    'class' => 'required-entry',
                    'label' => Mage::helper('ddate')->__('Status'),
                    'values' => $statuses
                )
            ),
            'confirm' => Mage::helper('ddate')->__('Are you sure to change status?')
        ));
        
        return $this;
    }
    
    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array(
            'dtime_id' => $row->getDtimeId()
        ));
    }
    
    protected function _afterLoadCollection()
    {
        $this->getCollection()->walk('afterLoad');
        parent::_afterLoadCollection();
    }
    
    protected function _filterStoreCondition($collection, $column)
    {
        if (!$value = $column->getFilter()->getValue()) {
            return;
        }

        $this->getCollection()->addStoreFilter($value);
    }
}
