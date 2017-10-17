<?php
    class MW_RewardPoints_Model_Poll_Vote extends Mage_Poll_Model_Poll_Vote
    {
        protected $_eventPrefix = 'poll_vote';
        protected $_eventObject = 'vote';

        protected function _construct()
        {
            return parent::_construct();
        }

        public function voteAfterSave($argv)
        {
            $vote     = $argv->getVote();
            $store_id = Mage::app()->getStore()->getId();
            //$points = Mage::getStoreConfig('rewardpoints/config/reward_point_for_submit_poll');
            $customer_group_id   = Mage::getModel('customer/customer')->load($vote->getCustomerId())->getGroupId();
            $type_of_transaction = MW_RewardPoints_Model_Type::SUBMIT_POLL;
            //$points = (double)Mage::getModel('rewardpoints/activerules')->getPointActiveRules($type_of_transaction,$customer_group_id,$store_id);
            $results = Mage::getModel('rewardpoints/activerules')->getResultActiveRulesExpiredPoints($type_of_transaction, $customer_group_id, $store_id);
            if($vote->getCustomerId() && Mage::helper('rewardpoints')->checkCustomerMaxBalance($vote->getCustomerId(), $store_id, $results[0]))
            {
                $points          = $results[0];
                $expired_day     = $results[1];
                $expired_time    = $results[2];
                $point_remaining = $results[3];
                Mage::helper('rewardpoints/data')->checkAndInsertCustomerId($vote->getCustomerId(), 0);
                if($points)
                {
                    $_customer = Mage::getModel("rewardpoints/customer")->load($vote->getCustomerId());
                    $_customer->addRewardPoint($points);

                    $historyData = array('type_of_transaction' => MW_RewardPoints_Model_Type::SUBMIT_POLL,
                                         'amount'              => (int)$points,
                                         'balance'             => $_customer->getMwRewardPoint(),
                                         'transaction_detail'  => $vote->getPollId(),
                                         'transaction_time'    => Mage::getSingleton('core/date')->gmtDate(),
                                         'expired_day'         => $expired_day,
                                         'expired_time'        => $expired_time,
                                         'point_remaining'     => $point_remaining,
                                         'status'              => MW_RewardPoints_Model_Status::COMPLETE);

                    $_customer->saveTransactionHistory($historyData);

                    // send mail when points changed
                    Mage::helper('rewardpoints')->sendEmailCustomerPointChanged($_customer->getId(), $historyData, $store_id);

                    Mage::getSingleton('core/session')->addSuccess(Mage::helper("rewardpoints")->__("You have been rewarded %s %s for submitting poll", $points, Mage::helper('rewardpoints')->getPointCurency($store_id)));
                }
            }
        }
    }
