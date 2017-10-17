<?php

    class MW_RewardPoints_Model_Api_Api extends Mage_Api_Model_Resource_Abstract
    {
        public function getcustomeridbyemail($data)
        {
            if(sizeof($data) >= 1 && sizeof($data) <= 2)
            {
                $website_id = Mage::getModel('core/website')->load('base', 'code')->getId();
                if(is_array($data))
                {
                    $customer_email = $data[0];
                    if(sizeof($data) == 2)
                    {
                        if($data[1] != '')
                        {
                            $website_id = $data[1];
                        }
                    }
                }
                else
                {
                    $customer_email = $data;
                }

                $customer = Mage::getModel('customer/customer')->setWebsiteId($website_id);
                $customer = $customer->loadByEmail($customer_email);
                if($customer->getId())
                {
                    return Mage::helper('rewardpoints')->__('Customer email (%s) have customer id = %s', $customer_email, $customer->getId());

                }
                else
                {
                    return Mage::helper('rewardpoints')->__('Customer email (%s) is not avaiable', $customer_email);
                }

            }
            else
            {
                return Mage::helper('rewardpoints')->__('data (%s) is not avaiable', $data);
            }
        }

        public function getbalancebyemail($data)
        {
            if(sizeof($data) >= 1 && sizeof($data) <= 2)
            {
                $website_id = Mage::getModel('core/website')->load('base', 'code')->getId();
                if(is_array($data))
                {
                    $customer_email = $data[0];
                    if(sizeof($data) == 2)
                    {
                        if($data[1] != '')
                        {
                            $website_id = $data[1];
                        }
                    }
                }
                else
                {
                    $customer_email = $data;
                }

                $customer = Mage::getModel('customer/customer')->setWebsiteId($website_id);
                $customer = $customer->loadByEmail($customer_email);
                if($customer->getId())
                {
                    $_customer = Mage::getModel('rewardpoints/customer')->load($customer->getId());
                    $email     = Mage::getModel('customer/customer')->load($customer->getId())->getEmail();
                    if($_customer->getId())
                    {
                        return Mage::helper('rewardpoints')->__('Customer email (%s) have %s reward points', $email, $_customer->getMwRewardPoint());
                    }
                    else
                    {
                        return Mage::helper('rewardpoints')->__('Customer email (%s) have %s reward points', $email, 0);
                    }
                }
                else
                {
                    return Mage::helper('rewardpoints')->__('Customer email (%s) is not avaiable', $customer_email);
                }

            }
            else
            {
                return Mage::helper('rewardpoints')->__('data (%s) is not avaiable', $data);
            }

        }

        public function updatepoints($data)
        {
            if(sizeof($data) == 3)
            {
                $customer_id = (int)$data[0];
                $customer    = Mage::getModel('customer/customer')->load($customer_id);
                if($customer->getId())
                {
                    Mage::helper('rewardpoints/data')->checkAndInsertCustomerId($customer->getId(), 0);
                    $_customer = Mage::getModel('rewardpoints/customer')->load($customer->getId());
                    $points    = (int)$data[1];
                    if(is_numeric($points))
                    {
                        $oldPoints = $_customer->getMwRewardPoint();
                        $newPoints = $oldPoints + $points;

                        if($newPoints < 0)
                        {
                            $newPoints = 0;
                        }
                        $amount = abs($newPoints - $oldPoints);

                        if($amount > 0)
                        {
                            $detail = '';
                            $detail = $data[2];
                            $_customer->setData('mw_reward_point', $newPoints);
                            $_customer->save();
                            $balance     = $_customer->getMwRewardPoint();
                            $historyData = array('type_of_transaction' => ($points > 0) ? MW_RewardPoints_Model_Type::ADMIN_ADDITION : MW_RewardPoints_Model_Type::ADMIN_SUBTRACT,
                                                 'amount'              => $amount,
                                                 'balance'             => $balance,
                                                 'transaction_detail'  => $detail,
                                                 'transaction_time'    => Mage::getSingleton('core/date')->gmtDate(),
                                                 'status'              => MW_RewardPoints_Model_Status::COMPLETE);
                            $_customer->saveTransactionHistory($historyData);

                            // send mail when points changed
                            $store_id = Mage::getModel('customer/customer')->load($_customer->getId())->getStoreId();
                            Mage::helper('rewardpoints')->sendEmailCustomerPointChanged($_customer->getId(), $historyData, $store_id);

                            $email = Mage::getModel('customer/customer')->load($_customer->getId())->getEmail();

                            return Mage::helper('rewardpoints')->__('The customer id (%s) (%s) updates point successfully. Current balance: %s reward points', $customer_id, $email, $balance);
                        }
                    }
                    else
                    {
                        return Mage::helper('rewardpoints')->__('%s reward points must be numeric', $points);
                    }
                }
                else
                {
                    return Mage::helper('rewardpoints')->__('Customer id (%s) is not avaiable', $customer_id);
                }
            }
            else
            {
                return Mage::helper('rewardpoints')->__('data is not avaiable');
            }


        }

        public function getbalancebyid($data)
        {
            if($data)
            {
                $customer_id = (int)$data;
                $customer    = Mage::getModel('customer/customer')->load($customer_id);
                if($customer->getId())
                {
                    $_customer = Mage::getModel('rewardpoints/customer')->load($customer->getId());
                    $email     = Mage::getModel('customer/customer')->load($customer->getId())->getEmail();
                    if($_customer->getId())
                    {
                        return Mage::helper('rewardpoints')->__('Customer id (%s) (%s) have %s reward points', $customer->getId(), $email, $_customer->getMwRewardPoint());
                    }
                    else
                    {
                        return Mage::helper('rewardpoints')->__('Customer id (%s) (%s) have %s reward points', $customer->getId(), $email, 0);
                    }
                }
                else
                {
                    return Mage::helper('rewardpoints')->__('Customer id (%s) is not avaiable', $data);
                }

            }
            else
            {
                return Mage::helper('rewardpoints')->__('data (%s) is not avaiable', $data);
            }

        }

        public function getproductrewardpoints($data)
        {
            if($data)
            {
                $sku        = trim($data);
                $product_id = Mage::getModel('catalog/product')->getIdBySku($sku);
                if($product_id)
                {
                    $mw_reward_point = (int)Mage::getModel('rewardpoints/catalogrules')->getPointCatalogRulue($product_id);

                    return Mage::helper('rewardpoints')->__('Product sku (%s) have %s reward points', $sku, $mw_reward_point);

                }
                else
                {
                    return Mage::helper('rewardpoints')->__('sku (%s) is not avaiable', $data);
                }

            }
            else
            {
                return Mage::helper('rewardpoints')->__('data (%s) is not avaiable', $data);
            }

        }
    }