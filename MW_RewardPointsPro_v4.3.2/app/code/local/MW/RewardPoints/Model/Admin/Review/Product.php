<?php
    class MW_RewardPoints_Model_Admin_Review_Product
    {
        public function save($argv)
        {
            $review              = $argv->getObject();
            $store_id            = $review->getStoreId();
            $customer_group_id   = Mage::getModel('customer/customer')->load($review->getData('customer_id'))->getGroupId();
            $type_of_transaction = MW_RewardPoints_Model_Type::SUBMIT_PRODUCT_REVIEW;
            //$points = Mage::getStoreConfig('rewardpoints/config/reward_point_for_submit_review');
            //$points = (double)Mage::getModel('rewardpoints/activerules')->getPointActiveRules($type_of_transaction,$customer_group_id,$store_id);
            $results = Mage::getModel('rewardpoints/activerules')->getResultActiveRulesExpiredPoints($type_of_transaction, $customer_group_id, $store_id);
            if(Mage::helper('rewardpoints')->moduleEnabled() && $review->getData('customer_id') && Mage::helper('rewardpoints')->checkCustomerMaxBalance($review->getData('customer_id'), $store_id, $results[0]))
            {
                $transactions = Mage::getResourceModel('rewardpoints/rewardpointshistory_collection')
                    ->addFieldToFilter('type_of_transaction', MW_RewardPoints_Model_Type::SUBMIT_PRODUCT_REVIEW)
                    ->addFieldToFilter('transaction_detail', $review->getId() . "|" . $review->getEntityPkValue());
                if(!sizeof($transactions))
                {

                    Mage::helper('rewardpoints/data')->checkAndInsertCustomerId($review->getData('customer_id'), 0);
                    $_customer       = Mage::getModel('rewardpoints/customer')->load($review->getData('customer_id'));
                    $points          = $results[0];
                    $expired_day     = $results[1];
                    $expired_time    = $results[2];
                    $point_remaining = $results[3];
                    if($review->getStatusId() == Mage_Review_Model_Review::STATUS_APPROVED && $points)
                    {
                        $status = MW_RewardPoints_Model_Status::COMPLETE;
                        $_customer->addRewardPoint($points);

                        $historyData = array('type_of_transaction' => MW_RewardPoints_Model_Type::SUBMIT_PRODUCT_REVIEW,
                                             'amount'              => $points,
                                             'balance'             => $_customer->getMwRewardPoint(),
                                             'transaction_detail'  => $review->getId() . "|" . $review->getEntityPkValue(),
                                             'transaction_time'    => Mage::getSingleton('core/date')->gmtDate(),
                                             'expired_day'         => $expired_day,
                                             'expired_time'        => $expired_time,
                                             'point_remaining'     => $point_remaining,
                                             'status'              => $status);
                        $_customer->saveTransactionHistory($historyData);

                        // send mail when points changed
                        Mage::helper('rewardpoints')->sendEmailCustomerPointChanged($_customer->getId(), $historyData, $store_id);
                    }
                }
            }
        }
    }