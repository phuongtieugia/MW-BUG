<?php
    class MW_RewardPoints_Model_Tag_Product
    {
        public function tagSubmit($argv)
        {
            $tag         = $argv->getEvent()->getObject();
            $store_id    = $tag->getFirstStoreId();
            $customer_id = $tag->getFirstCustomerId();
            $tag_id      = $tag->getId();

            $_product            = Mage::getResourceModel('tag/product_collection')
                ->addAttributeToSelect('sku')
                ->addAttributeToSelect('name')
                ->addTagFilter($tag_id)
                ->getFirstItem();
            $product_id          = $_product->getProductId();
            $customer_group_id   = Mage::getModel('customer/customer')->load($customer_id)->getGroupId();
            $type_of_transaction = MW_RewardPoints_Model_Type::TAGGING_PRODUCT;

            $results = Mage::getModel('rewardpoints/activerules')->getResultActiveRulesExpiredPoints($type_of_transaction, $customer_group_id, $store_id);
            if(Mage::helper('rewardpoints')->moduleEnabled() && $customer_id && $product_id && Mage::helper('rewardpoints')->checkCustomerMaxBalance($customer_id, $store_id, $results[0]))
            {
                $transactions = Mage::getResourceModel('rewardpoints/rewardpointshistory_collection')
                    ->addFieldToFilter('type_of_transaction', MW_RewardPoints_Model_Type::TAGGING_PRODUCT)
                    ->addFieldToFilter('customer_id', $customer_id)
                    ->addFieldToFilter('transaction_detail', $product_id);
                if(!sizeof($transactions))
                {
                    Mage::helper('rewardpoints/data')->checkAndInsertCustomerId($customer_id, 0);
                    $_customer = Mage::getModel('rewardpoints/customer')->load($customer_id);

                    $points          = $results[0];
                    $expired_day     = $results[1];
                    $expired_time    = $results[2];
                    $point_remaining = $results[3];

                    if($tag->getApprovedStatus() == $tag->getStatus() && $points)
                    {
                        $status = MW_RewardPoints_Model_Status::COMPLETE;
                        $_customer->addRewardPoint($points);

                        $historyData = array('type_of_transaction' => MW_RewardPoints_Model_Type::TAGGING_PRODUCT,
                                             'amount'              => $points,
                                             'balance'             => $_customer->getMwRewardPoint(),
                                             'transaction_detail'  => $product_id,
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