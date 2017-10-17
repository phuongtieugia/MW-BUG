<?php

    class MW_RewardPoints_Model_Invitation extends Mage_Core_Model_Abstract
    {
        public function dispathClickLink($observer)
        {
            $invite = Mage::app()->getRequest()->getParam('mw_reward');
            if($invite)
            {
                Mage::dispatchEvent('invitation_referral_link_click', array('invite' => $invite, 'request' => Mage::app()->getRequest()));
                Mage::getSingleton('core/session')->addSuccess(Mage::helper('rewardpoints')->__('Thank you for visiting our site'));
            }
        }

        public function referralLinkClick($argv)
        {
            $invite = $argv->getInvite();
            //$referral_by = $argv->getReferralBy();
            $request = $argv->getRequest();
            //$customer = Mage::getModel('customer/customer');
            $customer = Mage::getModel('customer/customer')->setWebsiteId(Mage::app()->getStore()->getWebsiteId())->getCollection();
            $customer->getSelect()->where("md5(email)='" . $invite . "'");
            $customer_id = $customer->getFirstItem()->getId();

            /*
            switch ($referral_by){
                case "1":
                    $customer->load($invite);
                    break;
                case "2":
                    $customer->setWebsiteId(Mage::app()->getStore()->getWebsiteId())->loadByEmail($invite);
                    break;
            }
            */
            //$customers->getSelect()->where("md5(email)='".$invite."'");

            if($customer_id)
            {
                if(method_exists($request, 'getClientIp'))
                {
                    $clientIP = $request->getClientIp(true);
                }
                else
                {
                    $clientIP = $request->getServer('REMOTE_ADDR');
                }

                $transactions = Mage::getModel('rewardpoints/rewardpointshistory')->getCollection()
                    ->addFieldToFilter('transaction_detail', $clientIP)
                    ->addFieldToFilter('customer_id', $customer_id);

                if(!sizeof($transactions))
                {
                    Mage::helper('rewardpoints/data')->checkAndInsertCustomerId($customer_id, 0);
                    $_customer = Mage::getModel('rewardpoints/customer')->load($customer_id);
                    //$points = Mage::getStoreConfig('rewardpoints/config/reward_point_for_invite_friend');
                    $customer_group_id   = Mage::getModel('customer/customer')->load($customer_id)->getGroupId();
                    $store_id            = Mage::app()->getStore()->getId();
                    $type_of_transaction = MW_RewardPoints_Model_Type::INVITE_FRIEND;
                    //$points = (int)Mage::getModel('rewardpoints/activerules')->getPointActiveRules($type_of_transaction,$customer_group_id,$store_id);
                    $results         = Mage::getModel('rewardpoints/activerules')->getResultActiveRulesExpiredPoints($type_of_transaction, $customer_group_id, $store_id);
                    $points          = $results[0];
                    $expired_day     = $results[1];
                    $expired_time    = $results[2];
                    $point_remaining = $results[3];
                    if($points)
                    {
                        $_customer->addRewardPoint($points);
                        $historyData = array('type_of_transaction' => MW_RewardPoints_Model_Type::INVITE_FRIEND,
                                             'amount'              => $points,
                                             'balance'             => $_customer->getMwRewardPoint(),
                                             'transaction_detail'  => $clientIP,
                                             'transaction_time'    => Mage::getSingleton('core/date')->gmtDate(),
                                             'expired_day'         => $expired_day,
                                             'expired_time'        => $expired_time,
                                             'point_remaining'     => $point_remaining,
                                             'status'              => MW_RewardPoints_Model_Status::COMPLETE);
                        $_customer->saveTransactionHistory($historyData);

                        // send mail when points changed
                        Mage::helper('rewardpoints')->sendEmailCustomerPointChangedNew($_customer->getId(), $historyData, $store_id);
                    }
                }
                Mage::getModel('core/cookie')->set('friend', $customer_id, 3600 * 24);
            }

        }
    }