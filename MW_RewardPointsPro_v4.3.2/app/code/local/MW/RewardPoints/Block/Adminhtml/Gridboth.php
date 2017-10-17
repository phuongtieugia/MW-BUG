<?php
    /**
     * User: Anh TO
     * Date: 6/11/14
     * Time: 9:52 AM
     */

    class MW_RewardPoints_Block_Adminhtml_Gridboth extends Mage_Adminhtml_Block_Widget_Grid
    {
        protected $isConfigurable = false;
        protected $_product = null;

        public function __construct()
        {
            parent::__construct();
            $this->setId('productGridBoth');
            $this->setDefaultSort('entity_id');
            $this->setDefaultDir('desc');
            $this->setSaveParametersInSession(true);
            $this->setUseAjax(true);
            $this->setVarNameFilter('product_filter');
            $this->setFilterVisibility(false);
            $this->setPagerVisibility(false);
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

        public function setIsConfigurable()
        {
            $this->isConfigurable = true;
        }

        public function setProduct($product)
        {
            $this->_product = $product;
        }

        protected function _prepareCollection()
        {

            if($this->productId != null)
            {
                $product = Mage::getModel('catalog/product')->load($this->productId);

                switch ($this->_product->getTypeId())
                {
                    case 'grouped':
                        $grouped_products = $this->getProductsInGrouped($product);
                        $collection              = new Varien_Data_Collection();
                        if(count($grouped_products) > 0){
                            foreach($grouped_products as $productId => $_product)
                            {
                                $grouped_product = Mage::getModel('catalog/product')->load($productId);

                                $rowObj = new Mage_Catalog_Model_Product();
                                $rowObj->setData(array(
                                    'entity_id'                    => $productId,
                                    'entity_type_id'               => 'n/a',
                                    'attribute_set_id'             => 'n/a',
                                    'type_id'                      => 'n/a',
                                    'sku'                          => $grouped_product->getSku(),
                                    'created_at'                   => 'n/a',
                                    'updated_at'                   => 'n/a',
                                    'has_options'                  => false,
                                    'required_options'             => false,
                                    'name'                         => $grouped_product->getName(),
                                    'is_saleable'                  => true,
                                    'inventory_in_stock'           => true,
                                    'shirt_size'                   => 'n/a',
                                    'price'                        => $grouped_product->getPrice(),
                                    'stock_item'                   => new Varien_Object(),
                                    'reward_point_product'         => $grouped_product->getData('reward_point_product'),
                                    'mw_reward_point_sell_product' => $grouped_product->getData('mw_reward_point_sell_product')
                                ));
                                $collection->addItem($rowObj);
                            }

                            $this->setCollection($collection);
                        }
                        break;
                    case 'bundle':
                        $bundled_product = new Mage_Catalog_Model_Product();
                        $bundled_product->load($this->_product->getId());
                        $selectionCollection = $bundled_product->getTypeInstance(true)->getSelectionsCollection(
                            $bundled_product->getTypeInstance(true)->getOptionsIds($bundled_product), $bundled_product
                        )
                            ->addAttributeToSelect('sku')
                            ->addAttributeToSelect('mw_reward_point_sell_product')
                            ->addAttributeToSelect('reward_point_product')
                            ->addAttributeToSelect('name')
                            ->addAttributeToSelect('attribute_set_id')
                            ->addAttributeToSelect('type_id');

                        $this->setCollection($selectionCollection);

                        parent::_prepareCollection();
                        break;
                    case 'configurable':
                        $productAttributeOptions = $product->getTypeInstance(true)->getConfigurableAttributesAsArray($product);
                        $collection              = new Varien_Data_Collection();
                        $custom_product          = array();

                        if(is_array($productAttributeOptions) && count($productAttributeOptions) > 0)
                        {
                            foreach ($productAttributeOptions as $attr)
                            {
                                foreach ($attr['values'] as $opv)
                                {
                                    $custom_product[] = time() + $opv['value_index'] + $attr['id'];
                                    $rowObj           = new Mage_Catalog_Model_Product();
                                    $rowObj->setData(array(
                                        'entity_id'           => $opv['value_index'],
                                        'entity_type_id'      => 0,
                                        'option_id'           => $product->getId(),
                                        'custom_attribute_id' => $attr['attribute_id'],
                                        'super_attribute'     => true,
                                        'attribute_set_id'    => 'n/a',
                                        'type_id'             => 'n/a',
                                        'sku'                 => 'n/a',
                                        'created_at'          => 'n/a',
                                        'updated_at'          => 'n/a',
                                        'has_options'         => false,
                                        'required_options'    => false,
                                        'name'                => $product->getName() . " - " . $attr['label'] . " - " . $opv['default_label'],
                                        'is_saleable'         => true,
                                        'inventory_in_stock'  => true,
                                        'shirt_size'          => 'n/a',
                                        'price'               => 0,
                                        'stock_item'          => new Varien_Object()
                                    ));
                                    $collection->addItem($rowObj);
                                }
                            }
                        }

                        if($product->getOptions())
                        {
                            foreach ($product->getOptions() as $option)
                            {
                                foreach ($option->getValues() as $opv)
                                {
                                    $rowObj = new Mage_Catalog_Model_Product();
                                    $rowObj->setData(array(
                                        'entity_id'          => $opv->getOptionTypeId() + $option->getOptionId(),
                                        'entity_type_id'     => $opv->getOptionTypeId(),
                                        'option_id'          => $option->getOptionId(),
                                        'custom_option'      => true,
                                        'attribute_set_id'   => 'n/a',
                                        'type_id'            => 'n/a',
                                        'sku'                => $opv->getSku(),
                                        'created_at'         => 'n/a',
                                        'updated_at'         => 'n/a',
                                        'has_options'        => false,
                                        'required_options'   => false,
                                        'name'               => $option->getTitle() . " - " . $opv->getTitle(),
                                        'is_saleable'        => true,
                                        'inventory_in_stock' => true,
                                        'shirt_size'         => 'n/a',
                                        'price'              => $opv->getPrice(),
                                        'stock_item'         => new Varien_Object()
                                    ));
                                    $collection->addItem($rowObj);
                                }
                            }
                        }
                        $this->setCollection($collection);
                        break;
                }
            }

            return $this;
        }
        /**
         * Retrieve grouped products
         *
         * @return array
         */
        public function getProductsInGrouped($product)
        {
            $associatedProducts = Mage::registry('current_product')->getTypeInstance(true)
                ->getAssociatedProducts($product);
            $products = array();
            foreach ($associatedProducts as $product) {
                $products[$product->getId()] = array(
                    'qty'       => $product->getQty(),
                    'position'  => $product->getPosition()
                );
            }

            return $products;
        }
        protected function _prepareColumns()
        {
            $this->addColumn('entity_id',
                array(
                    'header'   => Mage::helper('catalog')->__('ID'),
                    'width'    => '1px',
                    'type'     => 'number',
                    'index'    => 'entity_id',
                    'sortable' => !$this->_single ? true : false,
                ));
            $this->addColumn('name',
                array(
                    'header'   => Mage::helper('catalog')->__('Name'),
                    'index'    => 'name',
                    'width'    => '300px',
                    'sortable' => !$this->_single ? true : false,
                ));

            $this->addColumn('sku',
                array(
                    'header'   => Mage::helper('catalog')->__('SKU'),
                    'width'    => '60px',
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

            if($this->isConfigurable == false)
            {
                $this->addColumn('status',
                    array(
                        'header'   => Mage::helper('catalog')->__('Status'),
                        'width'    => '1px',
                        'index'    => 'status',
                        'type'     => 'options',
                        'options'  => Mage::getSingleton('catalog/product_status')->getOptionArray(),
                        'sortable' => !$this->_single ? true : false,
                    ));
            }

            $this->addColumn('reward_point_product',
                array(
                    'header'         => Mage::helper('rewardpoints')->__('Set Points Earned'),
                    'width'          => '100px',
                    'type'           => 'number',
                    'validate_class' => 'validate-number validate-digits',
                    'index'          => 'reward_point_product',
                    'edit_only'      => true,
                    'editable'       => true,
                    'sortable'       => false,
                    'renderer'       => 'rewardpoints/adminhtml_renderer_rewardpoints',

                ));
            $this->addColumn('mw_reward_point_sell_product',
                array(
                    'header'         => Mage::helper('rewardpoints')->__('Set Sell Points'),
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

            return parent::_prepareColumns();
        }
    }