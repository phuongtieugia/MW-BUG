<?php

    class MW_Rewardpoints_Block_Adminhtml_Renderer_Rewardpoints extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
    {

        public function render(Varien_Object $row)
        {
            $result = '';

            if(isset($row['custom_option']) && $row['custom_option'])
            {
                $id = $row['entity_id'] . "_" . $row['option_id'] . "_" . $row->getEntityTypeId();
            }
            elseif(isset($row['super_attribute']) && $row['super_attribute'])
            {
                $id = $row['option_id'] . "_" . $row['entity_id'] . "_" . $row['custom_attribute_id'];
            }
            else
            {
                $id = $row['entity_id'];
            }

            //$class = "input-text validate-number validate-digits";
            if(isset($row['super_attribute']) && $row['super_attribute'])
            {
                $name = 'reward_point_product[super_attribute_' . $id . ']';
            }
            else
            {
                $name = 'reward_point_product[mw_' . $id . ']';
            }

            if(isset($row['custom_option']) || isset($row['super_attribute']))
            {
                $model = Mage::getModel('rewardpoints/productsellpoint');

                if(isset($row['custom_option']) && $row['custom_option'])
                {
                    $collection = $model->getCollection()
                        ->addFieldToFilter('product_id', $row['entity_id'])
                        ->addFieldToFilter('option_id', $row['option_id'])
                        ->addFieldToFilter('option_type_id', $row['entity_type_id'])
                        ->addFieldToFilter('type_id', 'custom_option')
                        ->getFirstItem();
                }
                else
                {
                    $collection = $model->getCollection()
                        ->addFieldToFilter('product_id', $row['option_id'])
                        ->addFieldToFilter('option_id', $row['entity_id'])
                        ->addFieldToFilter('option_type_id', $row['custom_attribute_id'])
                        ->addFieldToFilter('type_id', 'super_attribute')
                        ->getFirstItem();
                }
                $value = $collection->getEarnPoint();
            }
            else
            {
                $value = $row['reward_point_product'];
            }

            //$result = "<span style='display: block; margin: 0px 0px 0px 8px;'>".$value."</span><input type=".$type." class=".$class." name=".$name." value=".$value."></input>";
            $result = "  <input type='text' class='input-text validate-number' style='width: 80px !important' name=" . $name . " value=" . $value . "></input>";

            return $result;
        }

    }