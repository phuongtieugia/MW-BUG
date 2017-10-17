<?php

    class MW_Rewardpoints_Block_Adminhtml_Renderer_Time extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
    {

        public function render(Varien_Object $row)
        {
            if(empty($row['history_id']))
            {
                return '';
            }

            $transaction      = Mage::getModel('rewardpoints/rewardpointshistory')->load($row['history_id']);
            $result           = '';
            $status           = $transaction->getStatus();
            $point_remaining  = $transaction->getPointRemaining();
            $array_add_point  = Mage::getModel('rewardpoints/type')->getAddPointArray();
            $type_transaction = $transaction->getTypeOfTransaction();
            $result           = Mage::helper('core')->formatDate($transaction->getTransactionTime(), 'medium') . " " . Mage::helper('core')->formatTime($transaction->getTransactionTime());

            $result_mini  = '';
            $expired_time = Mage::helper('core')->formatDate($transaction->getExpiredTime(), 'medium') . " " . Mage::helper('core')->formatTime($transaction->getExpiredTime());
            if(in_array($type_transaction, $array_add_point) && $point_remaining > 0 && $status == MW_RewardPoints_Model_Status::COMPLETE)
            {
                $result_mini = Mage::helper('rewardpoints')->__('Expires on %s', $expired_time);
            }
            if($result_mini != '')
            {
                $result = $result . '<br><span style="font-size: 11px; color:#808080; font-weight: bold;">' . $result_mini . '</span>';
            }

            return $result;
        }

    }