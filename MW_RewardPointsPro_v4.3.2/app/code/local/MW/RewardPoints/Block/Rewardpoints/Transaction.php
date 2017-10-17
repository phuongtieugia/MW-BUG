<?php
    class MW_RewardPoints_Block_Rewardpoints_Transaction extends Mage_Core_Block_Template
    {
        protected function _prepareLayout()
        {
            $this->setToolbar($this->getLayout()->createBlock('page/html_pager', 'rewardpoints_transaction_toolbar'));
            $this->getToolbar()->setCollection($this->_getTransaction());
        }

        protected function _getCustomer()
        {
            return Mage::getSingleton("customer/session")->getCustomer();
        }

        public function _getTransaction()
        {
            if($this->getPageSize())
            {
                $pagesize = $this->getPage_size();
            }
            $transactions = Mage::getModel('rewardpoints/rewardpointshistory')->getCollection()
                ->addFieldToFilter('customer_id', $this->_getCustomer()->getId())
                ->addFieldToFilter('status', array('in' => array(MW_RewardPoints_Model_Status::COMPLETE, MW_RewardPoints_Model_Status::PENDING)))
                ->addOrder('transaction_time', 'DESC')
                ->addOrder('history_id', 'DESC');

            //if(isset($pagesize)) $transactions->setPageSize($pagesize);
            return $transactions;
        }

        public function getTransaction()
        {
            return $this->getToolbar()->getCollection();
        }

        public function getTypeLabel($type)
        {
            return MW_RewardPoints_Model_Type::getLabel($type);
        }

        public function getTransactionDetail($type, $detail = null, $status = null)
        {
            return MW_RewardPoints_Model_Type::getTransactionDetail($type, $detail, $status);
        }

        public function getFormatDateNew($transaction)
        {
            $result           = '';
            $status           = $transaction->getStatus();
            $point_remaining  = $transaction->getPointRemaining();
            $array_add_point  = Mage::getModel('rewardpoints/type')->getAddPointArray();
            $type_transaction = $transaction->getTypeOfTransaction();
            $result           = Mage::helper('core')->formatDate($transaction->getTransactionTime()) . " " . Mage::helper('core')->formatTime($transaction->getTransactionTime());

            $result_mini  = '';
            $expired_time = Mage::helper('core')->formatDate($transaction->getExpiredTime()) . " " . Mage::helper('core')->formatTime($transaction->getExpiredTime());
            if(in_array($type_transaction, $array_add_point) && $point_remaining > 0 && $status == MW_RewardPoints_Model_Status::COMPLETE)
            {
                $result_mini = Mage::helper('rewardpoints')->__('Expires on %s', $expired_time);
            }
            if($status == MW_RewardPoints_Model_Status::PENDING)
            {
                $status_label = MW_RewardPoints_Model_Status::getLabel($status);
                $result       = $result . '<br><span style="font-size: 11px; color:#808080; font-weight: bold;">' . $status_label . '</span>';
            }
            if($result_mini != '')
            {
                $result = $result . '<br><span style="font-size: 11px; color:#808080; font-weight: bold;">' . $result_mini . '</span>';
            }

            return $result;
        }

        public function getTransactionDetailNew($transaction)
        {
            $type             = $transaction->getTypeOfTransaction();
            $detail           = $transaction->getTransactionDetail();
            $status           = $transaction->getStatus();
            $point_remaining  = $transaction->getPointRemaining();
            $array_add_point  = Mage::getModel('rewardpoints/type')->getAddPointArray();
            $type_transaction = $transaction->getTypeOfTransaction();
            $point_use        = $transaction->getAmount() - $point_remaining;
            //$expired_time = Mage::helper('core')->formatDate($transaction->getExpiredTime())." ".Mage::helper('core')->formatTime($transaction->getExpiredTime());

            $result_mini = '';
            if(in_array($type_transaction, $array_add_point) && $point_remaining > 0 && $status == MW_RewardPoints_Model_Status::COMPLETE && $point_use != 0)
            {
                $result_mini = Mage::helper('rewardpoints')->__('%s points are available (Used %s points)', $point_remaining, $point_use);
            }

            $result = '';
            $result = MW_RewardPoints_Model_Type::getTransactionDetail($type, $detail, $status);
            $br     = '<br>';
            if($type == MW_RewardPoints_Model_Type::CHECKOUT_ORDER_NEW)
            {
                $br = '';
            }
            if($result_mini != '')
            {
                $result = $result . $br . '<span style="font-size: 11px; color:#808080; font-weight: bold;">' . $result_mini . '</span>';
            }

            return $result;
        }

        public function formatAmount($amount, $type)
        {
            return MW_RewardPoints_Model_Type::getAmountWithSign($amount, $type);
        }

        public function getPositiveAmount($amount, $type)
        {
            $result = MW_RewardPoints_Model_Type::getAmountWithSign($amount, $type);

            return $result > 0 ? $result : 0;
        }

        public function getStatusText($status)
        {
            return MW_RewardPoints_Model_Status::getLabel($status);
        }

        public function getToolbarHtml()
        {
            return $this->getToolbar()->toHtml();
        }
    }