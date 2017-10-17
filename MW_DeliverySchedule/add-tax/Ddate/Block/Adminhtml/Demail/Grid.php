<?php

class MW_Ddate_Block_Adminhtml_Demail_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('demailGrid');
        $this->setDefaultSort('queue_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }
    
    protected function _prepareCollection()
    {
        $collection = Mage::getModel('ddate/demail')->getCollection();
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }
    
    protected function _prepareColumns()
    {
        $this->addColumn('queue_id', array(
            'header' => Mage::helper('ddate')->__('ID'),
            'align' => 'right',
            'width' => '50px',
            'index' => 'queue_id'
        ));
        
        $this->addColumn('create_date', array(
            'header' => Mage::helper('ddate')->__('Create Date'),
            'align' => 'left',
            'width' => '120px',
            'type' => 'datetime',
            'index' => 'create_date',
            'renderer' => 'MW_FollowUpEmail_Block_Adminhtml_Queue_Grid_Column_Emptydate'
        ));

        $this->addColumn('scheduled_at', array(
            'header' => Mage::helper('ddate')->__('Scheduled At'),
            'align' => 'left',
            'width' => '120px',
            'type' => 'datetime',
            'default' => '--',
            'index' => 'scheduled_at',
            'renderer' => 'MW_FollowUpEmail_Block_Adminhtml_Queue_Grid_Column_Emptydate'
        ));

        $this->addColumn('sent_at', array(
            'header' => Mage::helper('ddate')->__('Sent At'),
            'align' => 'left',
            'width' => '120px',
            'type' => 'datetime',
            'default' => '--',
            'index' => 'sent_at',
            'empty_text' => Mage::helper('ddate')->__('Not sent yet'),
            'renderer' => 'MW_FollowUpEmail_Block_Adminhtml_Queue_Grid_Column_Sentatemptydate'
        ));

        $this->addColumn('recipient_name', array(
            'header' => Mage::helper('ddate')->__('Customer Name'),
            'align' => 'left',
            'index' => 'recipient_name'
        ));

        $this->addColumn('recipient_email', array(
            'header' => Mage::helper('ddate')->__('Email'),
            'align' => 'left',
            'index' => 'recipient_email',
            'renderer' => 'MW_FollowUpEmail_Block_Adminhtml_Queue_Grid_Column_Email'
        ));

        $this->addColumn('emailtemplate_id', array(
            'header' => Mage::helper('ddate')->__('Email Template'),
            'align' => 'left',
            'index' => 'emailtemplate_id'
        ));

        $this->addColumn('action', array(
            'header' => Mage::helper('ddate')->__('Action'),
            'width' => '80',
            'type' => 'action',
            'getter' => 'getId',
            'renderer' => 'MW_FollowUpEmail_Block_Adminhtml_Queue_Grid_Column_Actionbystatus',
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
        $this->setMassactionIdField('queue_id');
        $this->getMassactionBlock()->setFormFieldName('emailqueue');

        $this->getMassactionBlock()->addItem('sendnow', array(
            'label' => $this->__('Send now'),
            'url' => $this->getUrl('*/*/massactionSend', array('_current' => true)),
            'confirm' => Mage::helper('ddate')->__('Are you sure you want to do this?')
        ));
        
        $this->getMassactionBlock()->addItem('cancel', array(
            'label' => $this->__('Cancel'),
            'url' => $this->getUrl('*/*/massactionCancel', array('_current' => true)),
            'confirm' => Mage::helper('ddate')->__('Are you sure you want to do this?')
        ));
        
        $this->getMassactionBlock()->addItem('delete', array(
            'label' => $this->__('Delete'),
            'url' => $this->getUrl('*/*/massactionDelete', array('_current' => true)),
            'confirm' => Mage::helper('ddate')->__('Are you sure you want to do this?')
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
