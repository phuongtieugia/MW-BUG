<?php

    class MW_Rewardpoints_Block_Adminhtml_Renderer_Amount extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
    {

        public function render(Varien_Object $row)
        {
            if(empty($row['history_id']))
            {
                return '';
            }
            $history = Mage::getModel('rewardpoints/rewardpointshistory')->load($row['history_id']);
            $result  = '';
            $result  = Mage::getModel('rewardpoints/type')->getAmountWithSign($history->getAmount(), $history->getTypeOfTransaction());

            return $result;
        }

    }