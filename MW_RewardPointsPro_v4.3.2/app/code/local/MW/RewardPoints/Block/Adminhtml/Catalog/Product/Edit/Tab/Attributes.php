<?php
    /**
     * User: Anh TO
     * Date: 6/11/14
     * Time: 2:42 PM
     */

    class MW_RewardPoints_Block_Adminhtml_Catalog_Product_Edit_Tab_Attributes extends Mage_Adminhtml_Block_Catalog_Product_Edit_Tab_Attributes
    {
        protected function _prepareForm()
        {
            parent::_prepareForm();
            $group = $this->getGroup();
            if($group)
            {
                $point_sell_product = $this->getForm()->getElement('mw_reward_point_sell_product');

                if($point_sell_product)
                {
                    $point_sell_product->setRenderer(
                        $this->getLayout()->createBlock('rewardpoints/adminhtml_renderer_catalog_product_edit_tab_attributes_sell')
                    );
                }

                $product_reward_point = $this->getForm()->getElement('reward_point_product');
                if($product_reward_point)
                {

                    $product_reward_point->setRenderer(
                        $this->getLayout()->createBlock('rewardpoints/adminhtml_renderer_catalog_product_edit_tab_attributes_reward')
                    );
                }
            }
        }
    }