<?php
    /**
     * User: Anh TO
     * Date: 6/11/14
     * Time: 9:04 AM
     */

    class MW_RewardPoints_Block_Adminhtml_Renderer_Catalog_Product_Edit_Tab_Attributes_Reward extends Mage_Adminhtml_Block_Catalog_Form_Renderer_Fieldset_Element
    {
        public function getElementHtml()
        {
            $element = $this->getElement();

            $params = $this->getRequest()->getParams();

            if($params['id'])
            {
                $product = Mage::getModel('catalog/product')->load($params['id']);
                foreach ($product->getOptions() as $option)
                {
                }
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
                            $block = $this->getLayout()->createBlock('rewardpoints/adminhtml_gridboth');
                            $block->setSingle(true);
                            $block->setFilterVisible(false);
                            $block->setPagerVisible(false);
                            $block->setProductId($product->getId());
                            $block->setProduct($product);
                            if($product->getTypeId() == 'configurable')
                            {
                                $block->setIsConfigurable();
                            }
                            $data     = array(
                                'name'    => 'mw_show_hide_set_point',
                                'class'   => 'mw_show_hide_set_point',
                                'label'   => 'Set for this product?',
                                'value'   => 0,
                                'checked' => false
                            );
                            $checkbox = new Varien_Data_Form_Element_Checkbox($data);
                            $checkbox->setForm($element->getForm());

                            $html_renderer = $checkbox->getElementHtml() .
                                "<span id='mw-lb-set-point'>" . $checkbox->getLabelHtml() . "</span>" .
                                "<div class='mw-reward-point-product'>" .
                                $block->toHtml() .
                                "</div>" .
                                "<div class='mw-reward-point-input' style='display:none'>" . parent::getElementHtml() . "</div>";

                            return $html_renderer;
                            break;
                    }
                }
            }
        }
    }