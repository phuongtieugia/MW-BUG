<?php

class MW_Ddate_Block_Adminhtml_Pickup_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('pickupGrid');
        $this->setDefaultSort('ddate');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }
    
    protected function _prepareCollection()
    {
        $collection = Mage::getModel('ddate/pickup')->getCollection();
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }
    
    protected function _prepareColumns()
    {
        $this->addColumn('pickup_id', array(
            'header' => Mage::helper('ddate')->__('ID'),
            'align' => 'right',
            'width' => '50px',
            'index' => 'pickup_id',
            'type' => 'number'
        ));
        
        $this->addColumn('ddate', array(
            'header' => Mage::helper('ddate')->__('Delivery Date'),
            'align' => 'left',
            'index' => 'ddate',
            'type' => 'date'
        ));
        
        $sots      = array();
        $slot_objs = Mage::getModel('ddate/dtime')->getCollection();
        foreach ($slot_objs as $slot) {
            $slots[$slot->getDtimeId()] = $slot->getDtime();
        }
        
        $this->addColumn('dtime', array(
            'header' => Mage::helper('ddate')->__('Delivery Time'),
            'align' => 'center',
            'width' => '100px',
            'index' => 'dtime',
            'type' => 'options',
            'options' => $slots
        ));
        
        $this->addColumn('ordered', array(
            'header' => Mage::helper('ddate')->__('Bookings'),
            'align' => 'right',
            'width' => '100px',
            'index' => 'ordered',
            'type' => 'number'
        ));

        if (Mage::helper('ddate')->getDayoff()) {
            $this->addColumn('holiday', array(
                'header' => Mage::helper('ddate')->__('Day Off'),
                'align' => 'center',
                'width' => '100px',
                'index' => 'holiday',
                'type' => 'options',
                'options' => array(
                    0 => 'No',
                    1 => 'Yes'
                )
            ));
        }
        
        $this->addColumn('action', array(
            'header' => Mage::helper('ddate')->__('Action'),
            'width' => '100',
            'type' => 'action',
            'align' => 'center',
            'getter' => 'getDdateId',
            'actions' => array(
                array(
                    'caption' => Mage::helper('ddate')->__('Edit'),
                    'url' => array('base' => '*/*/edit'),
                    'field' => 'ddate_id'
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
    
    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array(
            'pickup_id' => $row->getPickupId()
        ));
    }

    protected function _filterStoreCondition($collection, $column)
    {
        if (!$value = $column->getFilter()->getValue()) {
            return;
        }

        $this->getCollection()->addStoreFilter($value);
    }
}
