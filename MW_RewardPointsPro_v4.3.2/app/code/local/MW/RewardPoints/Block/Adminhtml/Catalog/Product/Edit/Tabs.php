<?php
    /**
     * User: Anh TO
     * Date: 6/9/14
     * Time: 3:02 PM
     */

    class MW_RewardPoints_Block_Adminhtml_Catalog_Product_Edit_Tabs extends Mage_Adminhtml_Block_Catalog_Product_Edit_Tabs
    {
        protected function _prepareLayout()
        {
            $params = $this->getRequest()->getParams();

            if($params['id'])
            {
                $product = false; //Mage::getModel('catalog/product')->load($params['id']);
                if($product)
                {
                    switch ($product->getTypeId())
                    {
                        case 'simple':
                        case 'virtual':
                        case 'downloadable':
                            break;
                        case 'bundle':
                            $block = $this->getLayout()->createBlock('rewardpoints/adminhtml_sellproducts_edit_tab_grid');
                            $block->setSingle(true);
                            $block->setProductId($product->getId());
                            $this->addTabAfter('sell_points', array(
                                'label'   => Mage::helper('catalog')->__('Sell in Points'),
                                'content' =>
                                    $block->toHtml()
                            ));
                        case 'grouped':
                            break;
                    }

                }

            }

            parent::_prepareLayout();
        }
    }