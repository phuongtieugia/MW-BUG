<?php

    class MW_Rewardpoints_Block_Adminhtml_Renderer_Name extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
    {

        public function render(Varien_Object $row)
        {
            if(empty($row['customer_id']))
            {
                return '';
            }

            return Mage::getModel('customer/customer')->load($row['customer_id'])->getName();
        }

    }