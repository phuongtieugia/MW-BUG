<?php

class MW_Ddate_Block_Adminhtml_Ddate_Edit_Tab_Order extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('sales_order_grid');
        $this->setUseAjax(true);
        $this->setDefaultSort('created_at');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
    	$ddate_id = "";
    	if(Mage::registry('ddate_data')) {
    		$ddate_id = Mage::registry('ddate_data')->getDdateId();
    	} else {
    		$ddate_id = $this->getRequest()->getParam('ddate_id');
    	}
    	
        //TODO: add full name logic
        $collection = Mage::getResourceModel('sales/order_grid_collection');
        $ddate = Mage::getModel('ddate/ddate')->getCollection();
        $collection->getSelect()
                ->join(
                	array('ddate' => $ddate->getTable('ddate_store')),
                	'ddate.increment_id = main_table.increment_id',
                	array('ddate.ddate_id')
                )
                ->where('ddate.ddate_id = ?',$ddate_id);
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('real_order_id', array(
            'header'    => Mage::helper('ddate')->__('Order #'),
            'width'     => '80px',
            'type'      => 'text',
            'index'     => 'increment_id',
        ));

        if (!Mage::app()->isSingleStoreMode()) {
            $this->addColumn('store_id', array(
                'header'    => Mage::helper('ddate')->__('Purchased from (store)'),
                'index'     => 'store_id',
                'type'      => 'store',
                'store_view'=> true,
                'display_deleted' => true,
            ));
        }

        $this->addColumn('created_at', array(
            'header'    => Mage::helper('ddate')->__('Purchased On'),
            'index'     => 'created_at',
            'type'      => 'datetime',
            'width'     => '100px',
        ));

        $this->addColumn('billing_name', array(
            'header'    => Mage::helper('ddate')->__('Bill to Name'),
            'index'     => 'billing_name',
        ));
     
        $this->addColumn('shipping_address', array(
          'header'      => Mage::helper('ddate')->__('Shipping Address'),
          'align'       =>'left',
          'index'       => 'increment_id',
      	  'renderer'    => 'ddate/adminhtml_renderer_shippingaddress',
        ));
		
        $this->addColumn('shipping_name', array(
            'header'    => Mage::helper('ddate')->__('Ship to Name'),
            'index'     => 'shipping_name',
        ));

        $this->addColumn('base_grand_total', array(
            'header'    => Mage::helper('sales')->__('G.T. (Base)'),
            'index'     => 'base_grand_total',
            'type'      => 'currency',
            'currency'  => 'base_currency_code',
        ));

        $this->addColumn('grand_total', array(
            'header'    => Mage::helper('ddate')->__('G.T. (Purchased)'),
            'index'     => 'grand_total',
            'type'      => 'currency',
            'currency'  => 'order_currency_code',
        ));

        $this->addColumn('status', array(
            'header'    => Mage::helper('ddate')->__('Status'),
            'index'     => 'status',
            'type'      => 'options',
            'width'     => '70px',
            'options'   => Mage::getSingleton('sales/order_config')->getStatuses(),
        ));

        if (Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/view')) {
            $this->addColumn('action',
                array(
                    'header'    => Mage::helper('ddate')->__('Action'),
                    'width'     => '50px',
                    'type'      => 'action',
                    'getter'    => 'getId',
                    'actions'   => array(
                        array(
                            'caption' => Mage::helper('ddate')->__('View'),
                            'url'     => array('base'=>'adminhtml/sales_order/view'),
                            'field'   => 'order_id'
                        )
                    ),
                    'filter'    => false,
                    'sortable'  => false,
                    'index'     => 'stores',
                    'is_system' => true,
            ));
        }
        $this->addRssList('rss/order/new', Mage::helper('ddate')->__('New Order RSS'));

        return parent::_prepareColumns();
    }

    public function getRowUrl($row)
    {
        if (Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/view')) {
            return $this->getUrl('adminhtml/sales_order/view', array('order_id' => $row->getId()));
        }

        return false;
    }

    public function getGridUrl()
    {
        return $this->getUrl('ddate_admin/adminhtml_ddate/grid', array('ddate_id'=>$this->getRequest()->getParam('ddate_id')));
    }
}
