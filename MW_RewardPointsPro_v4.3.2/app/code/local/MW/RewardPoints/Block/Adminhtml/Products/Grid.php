<?php
    /**
     * Magento
     *
     * NOTICE OF LICENSE
     *
     * This source file is subject to the Open Software License (OSL 3.0)
     * that is bundled with this package in the file LICENSE.txt.
     * It is also available through the world-wide-web at this URL:
     * http://opensource.org/licenses/osl-3.0.php
     * If you did not receive a copy of the license and are unable to
     * obtain it through the world-wide-web, please send an email
     * to license@magentocommerce.com so we can send you a copy immediately.
     *
     * DISCLAIMER
     *
     * Do not edit or add to this file if you wish to upgrade Magento to newer
     * versions in the future. If you wish to customize Magento for your
     * needs please refer to http://www.magentocommerce.com for more information.
     *
     * @category    Mage
     * @package     Mage_Adminhtml
     * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
     * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
     */

    /**
     * Adminhtml customer grid block
     *
     * @category   Mage
     * @package    Mage_Adminhtml
     * @author      Magento Core Team <core@magentocommerce.com>
     */
    class MW_Rewardpoints_Block_Adminhtml_Products_Grid extends Mage_Adminhtml_Block_Widget_Grid
    {

        public function __construct()
        {
            parent::__construct();
            $this->setId('productGrid');
            $this->setDefaultSort('entity_id');
            $this->setDefaultDir('desc');
            $this->setSaveParametersInSession(true);
            $this->setUseAjax(true);
            $this->setVarNameFilter('product_filter');

        }

        protected function _getStore()
        {
            $storeId = (int)$this->getRequest()->getParam('store', 0);

            return Mage::app()->getStore($storeId);
        }

        protected function _prepareCollection()
        {
            $store      = $this->_getStore();
            $collection = Mage::getModel('catalog/product')->getCollection()
                ->addAttributeToSelect('sku')
                ->addAttributeToSelect('reward_point_product')
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

            $this->setCollection($collection);

            parent::_prepareCollection();
            $this->getCollection()->addWebsiteNamesToResult();

            return $this;
        }

        protected function _addColumnFilterToCollection($column)
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

        protected function _prepareColumns()
        {
            $this->addColumn('entity_id',
                array(
                    'header' => Mage::helper('catalog')->__('ID'),
                    'width'  => '50px',
                    'type'   => 'number',
                    'index'  => 'entity_id',
                ));
            $this->addColumn('name',
                array(
                    'header' => Mage::helper('catalog')->__('Name'),
                    'index'  => 'name',
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

            $this->addColumn('type',
                array(
                    'header'  => Mage::helper('catalog')->__('Type'),
                    'width'   => '60px',
                    'index'   => 'type_id',
                    'type'    => 'options',
                    'options' => Mage::getSingleton('catalog/product_type')->getOptionArray(),
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
                ));

            $this->addColumn('sku',
                array(
                    'header' => Mage::helper('catalog')->__('SKU'),
                    'width'  => '80px',
                    'index'  => 'sku',
                ));

            $store = $this->_getStore();
            $this->addColumn('price',
                array(
                    'header'        => Mage::helper('catalog')->__('Price'),
                    'type'          => 'price',
                    'currency_code' => $store->getBaseCurrency()->getCode(),
                    'index'         => 'price',
                ));

            $this->addColumn('qty',
                array(
                    'header' => Mage::helper('catalog')->__('Qty'),
                    'width'  => '100px',
                    'type'   => 'number',
                    'index'  => 'qty',
                ));


            $this->addColumn('visibility',
                array(
                    'header'  => Mage::helper('catalog')->__('Visibility'),
                    'width'   => '70px',
                    'index'   => 'visibility',
                    'type'    => 'options',
                    'options' => Mage::getModel('catalog/product_visibility')->getOptionArray(),
                ));

            $this->addColumn('status',
                array(
                    'header'  => Mage::helper('catalog')->__('Status'),
                    'width'   => '70px',
                    'index'   => 'status',
                    'type'    => 'options',
                    'options' => Mage::getSingleton('catalog/product_status')->getOptionArray(),
                ));

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
                    ));
            }
            $this->addColumn('reward_point_product',
                array(
                    'header'         => Mage::helper('catalog')->__('Reward points'),
                    'width'          => '100px',
                    'type'           => 'number',
                    'validate_class' => 'validate-number validate-digits',
                    'index'          => 'reward_point_product',
                    'edit_only'      => true,
                    'editable'       => true,

                ));

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

            $this->addRssList('rss/catalog/notifystock', Mage::helper('catalog')->__('Notify Low Stock RSS'));

            return parent::_prepareColumns();
        }

        public function getGridUrl()
        {
            return $this->_getData('grid_url') ? $this->_getData('grid_url') : $this->getUrl('*/*/productGrid', array('_current' => true));
        }

        public function getRowUrl($row)
        {
            return $this->getUrl('adminhtml/catalog_product/edit', array(
                    'store' => $this->getRequest()->getParam('store'),
                    'id'    => $row->getId())
            );
        }
    }
