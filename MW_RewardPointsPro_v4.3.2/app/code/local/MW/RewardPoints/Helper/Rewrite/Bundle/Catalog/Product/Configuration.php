<?php
    /**
     * User: Anh TO
     * Date: 6/12/14
     * Time: 10:51 AM
     */

    class MW_RewardPoints_Helper_Rewrite_Bundle_Catalog_Product_Configuration extends Mage_Bundle_Helper_Catalog_Product_Configuration
    {
        /**
         * Get bundled selections (slections-products collection)
         *
         * Returns array of options objects.
         * Each option object will contain array of selections objects
         *
         * @return array
         */
        public function getBundleOptions(Mage_Catalog_Model_Product_Configuration_Item_Interface $item)
        {
            $options = array();
            $product = $item->getProduct();

            /**
             * @var Mage_Bundle_Model_Product_Type
             */
            $typeInstance = $product->getTypeInstance(true);

            // get bundle options
            $optionsQuoteItemOption = $item->getOptionByCode('bundle_option_ids');

            $additional_options = array();
            $total_points       = 0;
            foreach ($item->getChildren() as $bundle_item)
            {
                $_params = unserialize($bundle_item->getOptionByCode('info_buyRequest')->getValue());
                if(isset($_params['additional_options']))
                {
                    try
                    {
                        $add_opts                                         = end($_params['additional_options']);
                        $additional_options[$bundle_item->getProductId()] = $add_opts;

                        $total_points += intval($add_opts['orgi_value']);
                    }
                    catch (Exception $e)
                    {
                        Mage::log($e->getMessage());
                    }
                }
            }
            $store_id         = Mage::app()->getStore()->getId();
            if($total_points > 0){
                $options[]        = array(
                    'label' => Mage::helper('rewardpoints')->__('Sell in Points'),
                    'value' => array(
                        Mage::helper('rewardpoints')->formatPoints($total_points, $store_id)
                    ),
                );
            }

            $bundleOptionsIds = $optionsQuoteItemOption ? unserialize($optionsQuoteItemOption->getValue()) : array();

            if($bundleOptionsIds)
            {
                /**
                 * @var Mage_Bundle_Model_Mysql4_Option_Collection
                 */
                $optionsCollection = $typeInstance->getOptionsByIds($bundleOptionsIds, $product);

                // get and add bundle selections collection
                $selectionsQuoteItemOption = $item->getOptionByCode('bundle_selection_ids');

                $bundleSelectionIds = unserialize($selectionsQuoteItemOption->getValue());
                if(!empty($bundleSelectionIds))
                {
                    $selectionsCollection = $typeInstance->getSelectionsByIds(
                        unserialize($selectionsQuoteItemOption->getValue()),
                        $product
                    );

                    $bundleOptions = $optionsCollection->appendSelections($selectionsCollection, true);
                    foreach ($bundleOptions as $bundleOption)
                    {
                        if($bundleOption->getSelections())
                        {
                            $option = array(
                                'label' => $bundleOption->getTitle(),
                                'value' => array()
                            );

                            $bundleSelections = $bundleOption->getSelections();

                            foreach ($bundleSelections as $bundleSelection)
                            {
                                $qty = $this->getSelectionQty($product, $bundleSelection->getSelectionId()) * 1;
                                if($qty)
                                {
                                    $option['value'][] = $qty . ' x ' . $this->escapeHtml($bundleSelection->getName())
                                        . ' ' . Mage::helper('core')->currency(
                                            $this->getSelectionFinalPrice($item, $bundleSelection)
                                        );
                                    if(isset($additional_options[$bundleSelection->getId()]) && $additional_options[$bundleSelection->getId()])
                                    {
                                        /*$option['value'][] = "<dt>".$additional_options[$bundleSelection->getId()]['label'] . "-" ."</dt><dd>".$additional_options[$bundleSelection->getId()]['value']."</dd>";*/
                                    }
                                }
                            }

                            if($option['value'])
                            {
                                $options[] = $option;
                            }
                        }
                    }
                }
            }

            return $options;
        }
    }