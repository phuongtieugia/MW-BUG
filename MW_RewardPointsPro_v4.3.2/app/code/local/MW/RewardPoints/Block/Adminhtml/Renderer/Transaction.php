<?php

    class MW_Rewardpoints_Block_Adminhtml_Renderer_Transaction extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
    {

        public function render(Varien_Object $row)
        {
            if(empty($row['history_id']))
            {
                return '';
            }
            $result_mini      = '';
            $history          = Mage::getModel('rewardpoints/rewardpointshistory')->load($row['history_id']);
            $point_remaining  = $history->getPointRemaining();
            $status           = $history->getStatus();
            $array_add_point  = Mage::getModel('rewardpoints/type')->getAddPointArray();
            $type_transaction = $history->getTypeOfTransaction();
            $point_use        = $history->getAmount() - $point_remaining;

            $expired_time = Mage::helper('core')->formatDate($history->getExpiredTime(), 'medium') . " " . Mage::helper('core')->formatTime($history->getExpiredTime());

            if(in_array($type_transaction, $array_add_point) && $point_remaining > 0 && $status == MW_RewardPoints_Model_Status::COMPLETE && $point_use != 0)
            {
                //$result_mini = Mage::helper('rewardpoints')->__('%s points will be expired on %s',$point_remaining,$expired_time);
                $result_mini = Mage::helper('rewardpoints')->__('%s points are available (Used %s points)', $point_remaining, $point_use);
            }

            $result = '';
            $br     = '<br>';
            if($type_transaction == MW_RewardPoints_Model_Type::CHECKOUT_ORDER_NEW)
            {
                $br = '';
            }
            $result = Mage::getModel('rewardpoints/type')->getTransactionDetail($history->getTypeOfTransaction(), $history->getTransactionDetail(), $history->getStatus(), true);
            if($result_mini != '')
            {
                $result = $result . $br . '<span style="font-size: 11px; color:#808080; font-weight: bold;">' . $result_mini . '</span>';
            }

            return $result;
        }

    }