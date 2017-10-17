<?php
    /**
     * User: Anh TO
     * Date: 7/1/14
     * Time: 5:14 PM
     */
    class MW_RewardPoints_Helper_Rewrite_Catalog_Product_Configuration extends Mage_Catalog_Helper_Product_Configuration
    {
        /**
         * Retrieves configuration options for configurable product
         *
         * @param Mage_Catalog_Model_Product_Configuration_Item_Interface $item
         * @return array
         */
        public function getConfigurableOptions(Mage_Catalog_Model_Product_Configuration_Item_Interface $item)
        {
            $product = $item->getProduct();
            $typeId  = $product->getTypeId();
            if($typeId != Mage_Catalog_Model_Product_Type_Configurable::TYPE_CODE)
            {
                Mage::throwException($this->__('Wrong product type to extract configurable options.'));
            }
            $attributes = $product->getTypeInstance(true)
                ->getSelectedAttributesInfo($product);
            $total_points = 0;
            foreach ($item->getChildren() as $conf_item)
            {
                $_params = unserialize($conf_item->getOptionByCode('info_buyRequest')->getValue());
                if(isset($_params['additional_options']))
                {
                    try
                    {
                        $add_opts = end($_params['additional_options']);

                        $total_points += intval($add_opts['orgi_value']) * $conf_item->getQty();
                    }
                    catch (Exception $e)
                    {
                        Mage::log($e->getMessage());
                    }
                }
            }
            if($total_points > 0){
                $attributes = array_merge(
                    array(array(
                        'label' => Mage::helper('rewardpoints')->__('Sell in Points'),
                        'value' => Mage::helper('rewardpoints')->formatPoints($total_points, $store_id)
                    )), $attributes
                );
            }

            return array_merge($attributes, $this->getCustomOptions($item));
        }
    }