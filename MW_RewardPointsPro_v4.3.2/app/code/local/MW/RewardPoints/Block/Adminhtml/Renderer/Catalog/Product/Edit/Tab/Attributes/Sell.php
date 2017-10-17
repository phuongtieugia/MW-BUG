<?php
    /**
     * User: Anh TO
     * Date: 6/10/14
     * Time: 9:43 AM
     */

    class MW_RewardPoints_Block_Adminhtml_Renderer_Catalog_Product_Edit_Tab_Attributes_Sell extends Mage_Adminhtml_Block_Catalog_Form_Renderer_Fieldset_Element
    {
        public function getElementHtml()
        {
            $element = $this->getElement();

            $params = $this->getRequest()->getParams();
            if($params['id'])
            {
                $product = Mage::getModel('catalog/product')->load($params['id']);
                if($product)
                {
                    switch ($product->getTypeId())
                    {
                        case 'simple':
                        case 'virtual':
                        case 'downloadable':
                            return parent::getElementHtml();
                            break;
                        case 'grouped':
                        case 'bundle':
                        case 'configurable':
                            return parent::getElementHtml() . "<script type='text/javascript'>var mwSetPoints = new MW.ProductView()</script>";

                            /*$block = $this->getLayout()->createBlock('rewardpoints/adminhtml_sellproducts_edit_tab_grid');
                            $block->setSingle(true);
                            $block->setFilterVisible(false);
                            $block->setPagerVisible(false);
                            $block->setProductId($product->getId());

                            $data     = array(
                                'name'    => 'mw_show_hide_sell_point',
                                'class'   => 'mw_show_hide_sell_point',
                                'label'   => 'Show/Hide',
                                'value'   => 1,
                                'checked' => true
                            );
                            $checkbox = new Varien_Data_Form_Element_Checkbox($data);
                            $checkbox->setForm($element->getForm());

                            $script        = "<script type='text/javascript'>var mwViewSellPoint = new MW.ProductView.SellPoint()</script>";
                            $html_renderer = $checkbox->getElementHtml() .
                                $checkbox->getLabelHtml() .
                                "<div class='mw-sell-point-product'>" .
                                $block->toHtml() .
                                "</div>" .
                                $script;

                            return $html_renderer;*/
                            break;
                    }
                }
            }
        }
    }