<?php

    class MW_Rewardpoints_Block_Adminhtml_Renderer_Point extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
    {

        public function render(Varien_Object $row)
        {
            if(empty($row['entity_id']))
            {
                return '0';
            }
            $point = (int)Mage::getModel('rewardpoints/customer')->load($row['entity_id'])->getMwRewardPoint();

            if($point == 0)
            {
                $point = '0';
            }

            return $point;
        }

    }