<?php

    class MW_RewardPoints_Model_Newsletter_Subscriber extends Mage_Core_Model_Abstract
    {
        public function newletterSaveBefore($argv)
        {
            $subscriber          = $argv->getSubscriber();
            $store_id            = Mage::app()->getStore()->getId();
            $customer_group_id   = Mage::getModel('customer/customer')->load($subscriber->getCustomerId())->getGroupId();
            $type_of_transaction = MW_RewardPoints_Model_Type::SIGNING_UP_NEWLETTER;
            // $rewardpoints = (double)Mage::getModel('rewardpoints/activerules')->getPointActiveRules($type_of_transaction,$customer_group_id,$store_id);
            $results = Mage::getModel('rewardpoints/activerules')->getResultActiveRulesExpiredPoints($type_of_transaction, $customer_group_id, $store_id);
            if($subscriber->getCustomerId() && Mage::helper('rewardpoints')->checkCustomerMaxBalance($subscriber->getCustomerId(), $store_id, $results[0]))
            {
                $rewardpoints    = $results[0];
                $expired_day     = $results[1];
                $expired_time    = $results[2];
                $point_remaining = $results[3];
                Mage::helper('rewardpoints/data')->checkAndInsertCustomerId($subscriber->getCustomerId(), 0);
                $_customer = Mage::getModel('rewardpoints/customer')->load($subscriber->getCustomerId());
                if($subscriber->getId())
                {
                    $old_subscriber = Mage::getModel('newsletter/subscriber')->load($subscriber->getId());
                    if(($old_subscriber->getStatus() == Mage_Newsletter_Model_Subscriber::STATUS_NOT_ACTIVE) && ($subscriber->getStatus() == Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED))
                    {
                        //$rewardpoints = Mage::getStoreConfig('rewardpoints/config/reward_point_for_registering_subscriber');
                        if($rewardpoints)
                        {
                            $_customer->addRewardPoint($rewardpoints);
                            $historyData = array('type_of_transaction' => MW_RewardPoints_Model_Type::SIGNING_UP_NEWLETTER,
                                                 'amount'              => (int)$rewardpoints,
                                                 'balance'             => $_customer->getMwRewardPoint(),
                                                 'transaction_detail'  => '',
                                                 'transaction_time'    => Mage::getSingleton('core/date')->gmtDate(),
                                                 'expired_day'         => $expired_day,
                                                 'expired_time'        => $expired_time,
                                                 'point_remaining'     => $point_remaining,
                                                 'status'              => MW_RewardPoints_Model_Status::COMPLETE);
                            $_customer->saveTransactionHistory($historyData);

                            // send mail when points changed
                            Mage::helper('rewardpoints')->sendEmailCustomerPointChanged($_customer->getId(), $historyData, $store_id);
                        }
                    }
                }
                else
                {
                    if(($subscriber->getStatus() == Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED))
                    {
                        //$rewardpoints = Mage::getStoreConfig('rewardpoints/config/reward_point_for_registering_subscriber');
                        if($rewardpoints)
                        {
                            $_customer->addRewardPoint($rewardpoints);
                            $historyData = array('type_of_transaction' => MW_RewardPoints_Model_Type::SIGNING_UP_NEWLETTER,
                                                 'amount'              => (int)$rewardpoints,
                                                 'balance'             => $_customer->getMwRewardPoint(),
                                                 'transaction_detail'  => '',
                                                 'transaction_time'    => Mage::getSingleton('core/date')->gmtDate(),
                                                 'expired_day'         => $expired_day,
                                                 'expired_time'        => $expired_time,
                                                 'point_remaining'     => $point_remaining,
                                                 'status'              => MW_RewardPoints_Model_Status::COMPLETE);
                            $_customer->saveTransactionHistory($historyData);

                            // send mail when points changed
                            Mage::helper('rewardpoints')->sendEmailCustomerPointChanged($_customer->getId(), $historyData, $store_id);
                        }
                    }
                }
            }
        }
    }
