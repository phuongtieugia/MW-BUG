<?php

class MW_Ddate_Block_Adminhtml_Ddate_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('ddateGrid');
        $this->setDefaultSort('ddate_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('ddate/ddate')->getCollection();
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('ddate_id', array(
            'header' => Mage::helper('ddate')->__('ID'),
            'align' => 'right',
            'width' => '50px',
            'index' => 'ddate_id',
            'type' => 'number'
        ));
        
        $this->addColumn('ddate', array(
            'header' => Mage::helper('ddate')->__('Delivery Date'),
            'align' => 'left',
            'index' => 'ddate',
            'format' => Mage::helper('ddate')->php_date_format_M("/"),
            'type' => 'date',
            'filter_condition_callback' => array(
                $this,
                '_myDdateFilter'
            )
        ));
        
        $this->addColumn('dtimetext', array(
            'header' => Mage::helper('ddate')->__('Delivery Time'),
            'align' => 'center',
            'width' => '100px',
            'index' => 'dtimetext',
            'type' => 'text'
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
                    'url' => array(
                        'base' => '*/*/edit'
                    ),
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
    
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('ddate_id');
        $this->getMassactionBlock()->setFormFieldName('ddate_id');

        $statuses = Mage::getSingleton('ddate/status')->getOptionArray();
        array_unshift($statuses, array(
            'label' => '',
            'value' => ''
        ));

        $this->getMassactionBlock()->addItem('status', array(
            'label' => Mage::helper('ddate')->__('Change day off'),
            'url' => $this->getUrl('*/*/massStatus', array(
                '_current' => true
            )),
            'additional' => array(
                'visibility' => array(
                    'name' => 'status',
                    'type' => 'select',
                    'class' => 'required-entry',
                    'label' => Mage::helper('ddate')->__('Day Off'),
                    'values' => $statuses
                )
            )
        ));
        
        return $this;
    }
    
    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array(
            'ddate_id' => $row->getDdateId()
        ));
    }

    /*Only use for filtering delivery schedules base on date*/
    protected function _myDdateFilter($collection, $column)
    {
        /*if (!$value = $column->getFilter()->getValue()) {
            return $this;
        }
        $from = $filters['from'];
        $to   = $filters['to'];
        if ($from) {
            $this->getCollection()->addFieldToFilter('main_table.ddate', array(
                'gteq' => $from->toString('yyyy-MM-dd')
            ));
        }
        if ($to) {
            $this->getCollection()->addFieldToFilter('main_table.ddate', array(
                'lteq' => $to->toString('yyyy-MM-dd')
            ));
        }*/
        if (!$value = $column->getFilter()->getValue()) {
            return $this;
        }
        $filters = $column->getFilter()->getValue();

        $from = $filters['from'];
        $to = $filters['to'];
        if($from)
            $this->getCollection()->addFieldToFilter('main_table.ddate', array('gteq' => $from->toString('yyyy-MM-dd')));
        if($to)
            $this->getCollection()->addFieldToFilter('main_table.ddate', array('lteq' => $to->toString('yyyy-MM-dd')));
    }
}
