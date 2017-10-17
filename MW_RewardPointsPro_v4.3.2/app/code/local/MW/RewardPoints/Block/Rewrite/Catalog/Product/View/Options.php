<?php
    /**
     * User: Anh TO
     * Date: 6/20/14
     * Time: 9:21 AM
     */

    class MW_RewardPoints_Block_Rewrite_Catalog_Product_View_Options extends Mage_Catalog_Block_Product_View_Options
    {
        /**
         * Get json representation of
         *
         * @return string
         */
        public function getJsonConfig()
        {
            $config = array();
            $model  = Mage::getModel('rewardpoints/productsellpoint');
            foreach ($this->getOptions() as $option)
            {
                /* @var $option Mage_Catalog_Model_Product_Option */
                $priceValue = 0;
                if($option->getGroupByType() == Mage_Catalog_Model_Product_Option::OPTION_GROUP_SELECT)
                {
                    $_tmpPriceValues = array();
                    foreach ($option->getValues() as $value)
                    {
                        /* @var $value Mage_Catalog_Model_Product_Option_Value */
                        $id                   = $value->getId();
                        $_tmpPriceValues[$id] = $this->_getPriceConfiguration($value);
                        $product_id           = $value->getOptionTypeId() + $value->getOptionId();
                        $option_id            = $value->getOptionId();
                        $option_type_id       = $value->getOptionTypeId();
                        $collection           = $model->getCollection()
                            ->addFieldToFilter('product_id', $product_id)
                            ->addFieldToFilter('option_id', $option_id)
                            ->addFieldToFilter('option_type_id', $option_type_id)
                            ->getFirstItem();

                        $_tmpPriceValues[$id]['sellPoint'] = $collection->getData('sell_point');
                        $_tmpPriceValues[$id]['earnPoint'] = $collection->getData('earn_point');

                    }
                    $priceValue = $_tmpPriceValues;
                }
                else
                {
                    $priceValue = $this->_getPriceConfiguration($option);
                }
                $config[$option->getId()] = $priceValue;
            }

            return Mage::helper('core')->jsonEncode($config);
        }
    }