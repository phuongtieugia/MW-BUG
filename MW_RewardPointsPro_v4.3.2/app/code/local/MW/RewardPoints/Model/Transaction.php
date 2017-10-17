<?php
    class MW_RewardPoints_Model_Transaction extends Varien_Object
    {
        public function update($argv)
        {
            $customer     = Mage::getModel('rewardpoints/customer')->load($argv->getModel()->getId());
            $store_id     = Mage::getModel('customer/customer')->load($customer->getId())->getStoreId();
            $transactions = Mage::getModel('rewardpoints/rewardpointshistory')->getCollection()
                ->addFieldToFilter('customer_id', $customer->getId())
                ->addFieldToFilter('status', MW_RewardPoints_Model_Status::PENDING)
                ->addOrder('transaction_time', 'ASC')
                ->addOrder('history_id', 'ASC');
            //because select by current customer so have no record
            foreach ($transactions as $transaction)
            {
                switch ($transaction->getTypeOfTransaction())
                {

                    case MW_RewardPoints_Model_Type::SEND_TO_FRIEND:
                        //if the time is expired add reward points back to customer
                        $oldtime     = strtotime($transaction->getTransactionTime());
                        $currentTime = strtotime(Mage::getSingleton('core/date')->gmtDate());
                        $hour        = ($currentTime - $oldtime) / (60 * 60);
                        $hourConfig  = Mage::helper('rewardpoints/data')->timeLifeSendRewardPointsToFriend($store_id);
                        if($hourConfig && ($hour > $hourConfig))
                        {
                            $friend_id = Mage::getModel('core/cookie')->get('friend');
                            Mage::helper('rewardpoints/data')->checkAndInsertCustomerId($customer->getId(), $friend_id);
                            $customer->addRewardPoint($transaction->getAmount());

                            $results         = Mage::helper('rewardpoints/data')->getTransactionExpiredPoints($transaction->getAmount(), $store_id);
                            $expired_day     = $results[0];
                            $expired_time    = $results[1];
                            $point_remaining = $results[2];

                            $historyData = array('type_of_transaction' => MW_RewardPoints_Model_Type::SEND_TO_FRIEND_EXPIRED,
                                                 'amount'              => (int)$transaction->getAmount(),
                                                 'balance'             => $customer->getMwRewardPoint(),
                                                 'transaction_detail'  => $transaction->getData('transaction_detail'),
                                                 'transaction_time'    => Mage::getSingleton('core/date')->gmtDate(),
                                                 'expired_day'         => $expired_day,
                                                 'expired_time'        => $expired_time,
                                                 'point_remaining'     => $point_remaining,
                                                 'status'              => MW_RewardPoints_Model_Status::COMPLETE);

                            $customer->saveTransactionHistory($historyData);
                            $transaction->setStatus(MW_RewardPoints_Model_Status::UNCOMPLETE);
                            $transaction->save();

                            // send mail when points changed
                            Mage::helper('rewardpoints')->sendEmailCustomerPointChanged($customer->getId(), $historyData, $store_id);
                        }
                        break;
                }
            }


            $_transactions = Mage::getModel('rewardpoints/rewardpointshistory')->getCollection()
                ->addFieldToFilter('transaction_detail', $customer->getCustomerModel()->getEmail())
                ->addFieldToFilter('type_of_transaction', MW_RewardPoints_Model_Type::SEND_TO_FRIEND)
                ->addFieldToFilter('status', MW_RewardPoints_Model_Status::PENDING);

            if(sizeof($_transactions))
            {
                foreach ($_transactions as $_transaction)
                {
                    $friend_id = Mage::getModel('core/cookie')->get('friend');
                    Mage::helper('rewardpoints/data')->checkAndInsertCustomerId($customer->getId(), $friend_id);
                    $customer->addRewardPoint($_transaction->getAmount());

                    $results         = Mage::helper('rewardpoints/data')->getTransactionExpiredPoints($_transaction->getAmount(), $store_id);
                    $expired_day     = $results[0];
                    $expired_time    = $results[1];
                    $point_remaining = $results[2];

                    $historyData = array('type_of_transaction' => MW_RewardPoints_Model_Type::RECIVE_FROM_FRIEND,
                                         'amount'              => $_transaction->getAmount(),
                                         'balance'             => $customer->getMwRewardPoint(),
                                         'transaction_detail'  => $_transaction->getCustomerId(),
                                         'transaction_time'    => Mage::getSingleton('core/date')->gmtDate(),
                                         'expired_day'         => $expired_day,
                                         'expired_time'        => $expired_time,
                                         'point_remaining'     => $point_remaining,
                                         'status'              => MW_RewardPoints_Model_Status::COMPLETE);
                    $customer->saveTransactionHistory($historyData);
                    $_transaction->setStatus(MW_RewardPoints_Model_Status::COMPLETE)->setTransactionDetail($customer->getCustomerId())->save();

                    // send mail when points changed
                    Mage::helper('rewardpoints')->sendEmailCustomerPointChanged($customer->getId(), $historyData, $store_id);
                }
            }
        }

        public function updateNew($argv)
        {
            $customer     = Mage::getModel('rewardpoints/customer')->load($argv->getCustomer()->getId());
            $store_id     = Mage::getModel('customer/customer')->load($customer->getId())->getStoreId();
            $transactions = Mage::getModel('rewardpoints/rewardpointshistory')->getCollection()
                ->addFieldToFilter('customer_id', $customer->getId())
                ->addFieldToFilter('status', MW_RewardPoints_Model_Status::PENDING)
                ->addOrder('transaction_time', 'ASC')
                ->addOrder('history_id', 'ASC');
            //because select by current customer so have no record
            foreach ($transactions as $transaction)
            {
                switch ($transaction->getTypeOfTransaction())
                {

                    case MW_RewardPoints_Model_Type::SEND_TO_FRIEND:
                        //if the time is expired add reward points back to customer
                        $oldtime     = strtotime($transaction->getTransactionTime());
                        $currentTime = strtotime(Mage::getSingleton('core/date')->gmtDate());
                        $hour        = ($currentTime - $oldtime) / (60 * 60);
                        $hourConfig  = Mage::helper('rewardpoints/data')->timeLifeSendRewardPointsToFriend($store_id);
                        if($hourConfig && ($hour > $hourConfig))
                        {
                            $friend_id = Mage::getModel('core/cookie')->get('friend');
                            Mage::helper('rewardpoints/data')->checkAndInsertCustomerId($customer->getId(), $friend_id);
                            $customer->addRewardPoint($transaction->getAmount());

                            $results         = Mage::helper('rewardpoints/data')->getTransactionExpiredPoints($transaction->getAmount(), $store_id);
                            $expired_day     = $results[0];
                            $expired_time    = $results[1];
                            $point_remaining = $results[2];

                            $historyData = array('type_of_transaction' => MW_RewardPoints_Model_Type::SEND_TO_FRIEND_EXPIRED,
                                                 'amount'              => (int)$transaction->getAmount(),
                                                 'balance'             => $customer->getMwRewardPoint(),
                                                 'transaction_detail'  => $transaction->getData('transaction_detail'),
                                                 'transaction_time'    => Mage::getSingleton('core/date')->gmtDate(),
                                                 'expired_day'         => $expired_day,
                                                 'expired_time'        => $expired_time,
                                                 'point_remaining'     => $point_remaining,
                                                 'status'              => MW_RewardPoints_Model_Status::COMPLETE);

                            $customer->saveTransactionHistory($historyData);
                            $transaction->setStatus(MW_RewardPoints_Model_Status::UNCOMPLETE);
                            $transaction->save();

                            // send mail when points changed
                            Mage::helper('rewardpoints')->sendEmailCustomerPointChanged($customer->getId(), $historyData, $store_id);
                        }
                        break;

                }
            }


            $_transactions = Mage::getModel('rewardpoints/rewardpointshistory')->getCollection()
                ->addFieldToFilter('transaction_detail', $customer->getCustomerModel()->getEmail())
                ->addFieldToFilter('type_of_transaction', MW_RewardPoints_Model_Type::SEND_TO_FRIEND)
                ->addFieldToFilter('status', MW_RewardPoints_Model_Status::PENDING);

            if(sizeof($_transactions))
            {
                foreach ($_transactions as $_transaction)
                {
                    $friend_id = Mage::getModel('core/cookie')->get('friend');
                    Mage::helper('rewardpoints/data')->checkAndInsertCustomerId($customer->getId(), $friend_id);
                    $customer->addRewardPoint($_transaction->getAmount());

                    $results         = Mage::helper('rewardpoints/data')->getTransactionExpiredPoints($_transaction->getAmount(), $store_id);
                    $expired_day     = $results[0];
                    $expired_time    = $results[1];
                    $point_remaining = $results[2];

                    $historyData = array('type_of_transaction' => MW_RewardPoints_Model_Type::RECIVE_FROM_FRIEND,
                                         'amount'              => $_transaction->getAmount(),
                                         'balance'             => $customer->getMwRewardPoint(),
                                         'transaction_detail'  => $_transaction->getCustomerId(),
                                         'transaction_time'    => Mage::getSingleton('core/date')->gmtDate(),
                                         'expired_day'         => $expired_day,
                                         'expired_time'        => $expired_time,
                                         'point_remaining'     => $point_remaining,
                                         'status'              => MW_RewardPoints_Model_Status::COMPLETE);
                    $customer->saveTransactionHistory($historyData);
                    $_transaction->setStatus(MW_RewardPoints_Model_Status::COMPLETE)->setTransactionDetail($customer->getCustomerId())->save();

                    // send mail when points changed
                    Mage::helper('rewardpoints')->sendEmailCustomerPointChanged($customer->getId(), $historyData, $store_id);
                }
            }
        }

    }