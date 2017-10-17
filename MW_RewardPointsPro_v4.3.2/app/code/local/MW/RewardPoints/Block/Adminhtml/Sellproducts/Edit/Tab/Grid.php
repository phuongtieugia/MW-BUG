<?php
    class MW_Rewardpoints_Block_Adminhtml_Sellproducts_Edit_Tab_Grid extends Mage_Adminhtml_Block_Widget_Grid
    {
        protected $_single = false;
        protected $productId = null;

        public function __construct()
        {
            parent::__construct();
            $this->setId('productGrid');
            $this->setDefaultSort('entity_id');
            $this->setDefaultDir('desc');
            $this->setSaveParametersInSession(true);
            $this->setUseAjax(true);
            $this->setVarNameFilter('product_filter');
            if($this->getRequest()->getActionName() == 'edit' && $this->getRequest()->getControllerName() == 'catalog_product')
            {
                $this->setFilterVisibility(false);
                $this->setPagerVisibility(false);
            }
        }

        protected function _getStore()
        {
            $storeId = (int)$this->getRequest()->getParam('store', 0);

            return Mage::app()->getStore($storeId);
        }

        public function setSingle($flag)
        {
            $this->_single = $flag;
        }

        public function setFilterVisible($flag)
        {
            $this->setFilterVisibility($flag);
        }

        public function setPagerVisible($flag)
        {
            $this->setPagerVisibility($flag);
        }

        public function setProductId($productId)
        {
            $this->productId = $productId;
        }

        protected function _prepareCollection()
        {
            if($this->productId != null)
            {
                $product = Mage::getModel('catalog/product')->load($this->productId);
                switch ($product->getTypeId())
                {
                    case 'bundle':
                        $bundled_product = new Mage_Catalog_Model_Product();
                        $bundled_product->load($product->getId());
                        $selectionCollection = $bundled_product->getTypeInstance(true)->getSelectionsCollection(
                            $bundled_product->getTypeInstance(true)->getOptionsIds($bundled_product), $bundled_product
                        )
                            ->addAttributeToSelect('sku')
                            ->addAttributeToSelect('mw_reward_point_sell_product')
                            ->addAttributeToSelect('name')
                            ->addAttributeToSelect('attribute_set_id')
                            ->addAttributeToSelect('type_id');

                        $this->setCollection($selectionCollection);

                        parent::_prepareCollection();
                        break;
                }
            }
            else
            {
                $store      = $this->_getStore();
                $collection = Mage::getModel('catalog/product')->getCollection()
                    ->addAttributeToSelect('sku')
                    ->addAttributeToSelect('mw_reward_point_sell_product')
                    ->addAttributeToSelect('name')
                    ->addAttributeToSelect('attribute_set_id')
                    ->addAttributeToSelect('type_id')
                    ->joinField('qty',
                        'cataloginventory/stock_item',
                        'qty',
                        'product_id=entity_id',
                        '{{table}}.stock_id=1',
                        'left');
                if($store->getId())
                {
                    //$collection->setStoreId($store->getId());
                    $adminStore = Mage_Core_Model_App::ADMIN_STORE_ID;
                    $collection->addStoreFilter($store);
                    $collection->joinAttribute('name', 'catalog_product/name', 'entity_id', null, 'inner', $adminStore);
                    $collection->joinAttribute('custom_name', 'catalog_product/name', 'entity_id', null, 'inner', $store->getId());
                    $collection->joinAttribute('status', 'catalog_product/status', 'entity_id', null, 'inner', $store->getId());
                    $collection->joinAttribute('visibility', 'catalog_product/visibility', 'entity_id', null, 'inner', $store->getId());
                    $collection->joinAttribute('price', 'catalog_product/price', 'entity_id', null, 'left', $store->getId());
                }
                else
                {
                    $collection->addAttributeToSelect('price');
                    $collection->joinAttribute('status', 'catalog_product/status', 'entity_id', null, 'inner');
                    $collection->joinAttribute('visibility', 'catalog_product/visibility', 'entity_id', null, 'inner');
                }

                // $collection ->addAttributeToFilter('type_id','simple');
                $this->setCollection($collection);

                parent::_prepareCollection();
                $this->getCollection()->addWebsiteNamesToResult();
            }

            #die(var_dump((string)$collection->getSelect()));
            return $this;
        }

        protected function _addColumnFilterToCollection($column)
        {
            if($this->_single == false)
            {
                if($this->getCollection())
                {
                    if($column->getId() == 'websites')
                    {
                        $this->getCollection()->joinField('websites',
                            'catalog/product_website',
                            'website_id',
                            'product_id=entity_id',
                            null,
                            'left');
                    }
                }

                return parent::_addColumnFilterToCollection($column);
            }
        }

        protected function _prepareColumns()
        {
            $this->addColumn('entity_id',
                array(
                    'header'   => Mage::helper('catalog')->__('ID'),
                    'width'    => '50px',
                    'type'     => 'number',
                    'index'    => 'entity_id',
                    'sortable' => !$this->_single ? true : false,
                ));
            $this->addColumn('name',
                array(
                    'header'   => Mage::helper('catalog')->__('Name'),
                    'index'    => 'name',
                    'sortable' => !$this->_single ? true : false,
                ));

            $store = $this->_getStore();
            if($store->getId())
            {
                $this->addColumn('custom_name',
                    array(
                        'header' => Mage::helper('catalog')->__('Name in %s', $store->getName()),
                        'index'  => 'custom_name',
                    ));
            }

            if($this->_single == false)
            {
                $this->addColumn('type',
                    array(
                        'header'  => Mage::helper('catalog')->__('Type'),
                        'width'   => '60px',
                        'index'   => 'type_id',
                        'type'    => 'options',
                        'options' => Mage::getSingleton('catalog/product_type')->getOptionArray(),
                        //'filter'=> !$this->_single ? true : false,
                    ));

                $sets = Mage::getResourceModel('eav/entity_attribute_set_collection')
                    ->setEntityTypeFilter(Mage::getModel('catalog/product')->getResource()->getTypeId())
                    ->load()
                    ->toOptionHash();

                $this->addColumn('set_name',
                    array(
                        'header'  => Mage::helper('catalog')->__('Attrib. Set Name'),
                        'width'   => '100px',
                        'index'   => 'attribute_set_id',
                        'type'    => 'options',
                        'options' => $sets,
                        //'filter'=> !$this->_single ? true : false,
                    ));
            }
            $this->addColumn('sku',
                array(
                    'header'   => Mage::helper('catalog')->__('SKU'),
                    'width'    => '80px',
                    'index'    => 'sku',
                    'sortable' => !$this->_single ? true : false,
                ));

            $store = $this->_getStore();
            $this->addColumn('price',
                array(
                    'header'        => Mage::helper('catalog')->__('Price'),
                    'type'          => 'price',
                    'currency_code' => $store->getBaseCurrency()->getCode(),
                    'index'         => 'price',
                    'sortable'      => !$this->_single ? true : false,
                ));

            /* $this->addColumn('qty',
                 array(
                     'header'=> Mage::helper('catalog')->__('Qty'),
                     'width' => '100px',
                     'type'  => 'number',
                     'index' => 'qty',
             ));


             $this->addColumn('visibility',
                 array(
                     'header'=> Mage::helper('catalog')->__('Visibility'),
                     'width' => '70px',
                     'index' => 'visibility',
                     'type'  => 'options',
                     'options' => Mage::getModel('catalog/product_visibility')->getOptionArray(),
             ));
             */
            $this->addColumn('status',
                array(
                    'header'   => Mage::helper('catalog')->__('Status'),
                    'width'    => '70px',
                    'index'    => 'status',
                    'type'     => 'options',
                    'options'  => Mage::getSingleton('catalog/product_status')->getOptionArray(),
                    'sortable' => !$this->_single ? true : false,
                ));
            if($this->_single == false)
            {
                if(!Mage::app()->isSingleStoreMode())
                {
                    $this->addColumn('websites',
                        array(
                            'header'   => Mage::helper('catalog')->__('Websites'),
                            'width'    => '100px',
                            'sortable' => false,
                            'index'    => 'websites',
                            'type'     => 'options',
                            'options'  => Mage::getModel('core/website')->getCollection()->toOptionHash(),
                            //'filter'=> !$this->_single ? true : false,
                        ));
                }
            }

            $this->addColumn('mw_reward_point_sell_product',
                array(
                    'header'         => Mage::helper('rewardpoints')->__('Set Reward Points'),
                    'width'          => '50px',
                    'type'           => 'number',
                    'validate_class' => 'validate-number validate-digits',
                    'index'          => 'mw_reward_point_sell_product',
                    'edit_only'      => true,
                    'editable'       => true,
                    'sortable'       => false,
                    'renderer'       => 'rewardpoints/adminhtml_renderer_sellproduct',
                    //'filter'=> !$this->_single ? true : false,

                ));
            if($this->_single == false)
            {
                $this->addColumn('action',
                    array(
                        'header'   => Mage::helper('catalog')->__('Action'),
                        'width'    => '50px',
                        'type'     => 'action',
                        'getter'   => 'getId',
                        'actions'  => array(
                            array(
                                'caption' => Mage::helper('catalog')->__('Edit'),
                                'target'  => '_blank',
                                'url'     => array(
                                    'base'   => 'adminhtml/catalog_product/edit',
                                    'params' => array('store' => $this->getRequest()->getParam('store'))
                                ),
                                'field'   => 'id'
                            )
                        ),
                        'filter'   => false,
                        'sortable' => false,
                        'index'    => 'stores',
                    ));
            }


            $this->addRssList('rss/catalog/notifystock', Mage::helper('catalog')->__('Notify Low Stock RSS'));

            return parent::_prepareColumns();
        }

        public function getGridUrl()
        {
            return $this->_getData('grid_url') ? $this->_getData('grid_url') : $this->getUrl('*/*/sellProductGrid', array('_current' => true));
        }

        /* public function getRowUrl($row)
         {
             return $this->getUrl('adminhtml/catalog_product/edit', array(
                 'store'=>$this->getRequest()->getParam('store'),
                 'id'=>$row->getId())
             );
         }*/

    }