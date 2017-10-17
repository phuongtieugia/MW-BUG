<?php

class MW_Affiliate_Model_Quote_Address_Total_Discount extends Mage_Sales_Model_Quote_Address_Total_Abstract
{
    public function collect(Mage_Sales_Model_Quote_Address $address)
    {
        $session  = Mage::getSingleton('admin/session');
        $store_id = Mage::app()->getStore()->getId();
        if (Mage::helper('affiliate/data')->getEnabledStore($store_id)) {
            $quote = $address->getQuote();
            $items = $address->getAllVisibleItems();
            if (!count($items)) {
                return $this;
            }
            $discountAmount    = 0;
            $referral_code     = Mage::helper('affiliate')->getReferralCodeByCheckout();
            $customer_id       = (int) Mage::getSingleton("customer/session")->getCustomer()->getId();
            $program_priority  = Mage::helper('affiliate/data')->getAffiliatePositionStore($store_id);
            $position_discount = Mage::helper('affiliate/data')->getAffiliateDiscountStore($store_id);
            
            $programs = array();
            $programs = $this->getAllProgram();
            if (!Mage::app()->isSingleStoreMode())
                $programs = $this->getProgramByStoreView($programs);
            $programs  = $this->getProgramByEnable($programs);
            $_programs = $this->getProgramByTime($programs);
            foreach ($items as $item) {
                $product_id = $item->getProductId();
                $qty        = $item->getQty();
                
                if ($position_discount == 1)
                    $price = $item->getBasePrice();
                else
                    $price = $item->getBasePrice() - $item->getBaseDiscountAmount() / $qty;
                
                $programs = $this->processRule($item, $_programs);
                $programs = $this->getProgramByCustomer($programs, $referral_code);
                if (sizeof($programs) >= 2) {
                    $array_customer_inviteds = array_splice($programs, sizeof($programs) - 1, 1);
                    foreach ($array_customer_inviteds as $array_customer_invited) {
                        $customer_invited = $array_customer_invited;
                        break;
                    }
                    if ($program_priority == 1) {
                        $program_id = $this->getProgramByCommission($programs, $qty, $price, $customer_invited);
                    } else if ($program_priority == 2) {
                        $program_id = $this->getProgramByDiscount($programs, $qty, $price, $customer_invited);
                    } else if ($program_priority == 3) {
                        $program_id = $this->getProgramByPosition($programs);
                    }
                    
                    $discount = $this->getDiscountByProgram($program_id, $qty, $price, $customer_invited);
                } else {
                    $discount = 0;
                }
                
                $discount       = round($discount, 2);
                $discountAmount = $discountAmount + $discount;
                $item->setDiscountAmount($item->getDiscountAmount() + Mage::helper('core')->currency($discount, false, false));
                $item->setBaseDiscountAmount($item->getBaseDiscountAmount() + $discount);
                $item->setMwAffiliateDiscount($discount);
            }
            $discountAmount_show = Mage::helper('core')->currency($discountAmount, false, false);
            $address->setBaseDiscountAmount($address->getBaseDiscountAmount() - $discountAmount);
            $address->setAffiliateDiscount($discountAmount_show);
            $address->setBaseAffiliateDiscount($discountAmount);
            $address->setGrandTotal($address->getGrandTotal() - $address->getAffiliateDiscount() + $address->getTaxAmount());
            $address->setBaseGrandTotal($address->getBaseGrandTotal() - $address->getBaseAffiliateDiscount() + $address->getTaxAmount());
            
            return $this;
        }
    }

    public function fetch(Mage_Sales_Model_Quote_Address $address)
    {
        $store_id = Mage::app()->getStore()->getId();
        if (Mage::helper('affiliate/data')->getEnabledStore($store_id)) {
            $amount = $address->getAffiliateDiscount();
            if ($amount != 0) {
                $title = Mage::helper('affiliate')->__('Affiliate Discount');
                $address->addTotal(array(
                    'code' => $this->getCode(),
                    'title' => $title,
                    'value' => -$amount
                ));
            }
            return $this;
        }
    }

    public function getAllProgram()
    {
        $programs            = array();
        $program_collections = Mage::getModel('affiliate/affiliateprogram')->getCollection();
        foreach ($program_collections as $program_collection) {
            $programs[] = $program_collection->getProgramId();
        }
        return $programs;
    }

    public function processRule(Mage_Sales_Model_Quote_Item_Abstract $item, $programs)
    {
        $program_ids = array();
        foreach ($programs as $program) {
            $rule = Mage::getModel('affiliate/affiliateprogram')->load($program);
            $rule->afterLoad();
            $address = $this->getAddress_new($item);
            if (($rule->validate($address)) && ($rule->getActions()->validate($item))) {
                $program_ids[] = $program;
            }
        }
        return $program_ids;
    }
    
    protected function getAddress_new(Mage_Sales_Model_Quote_Item_Abstract $item)
    {
        if ($item instanceof Mage_Sales_Model_Quote_Address_Item) {
            $address = $item->getAddress();
        } elseif ($item->getQuote()->isVirtual()) {
            $address = $item->getQuote()->getBillingAddress();
        } else {
            $address = $item->getQuote()->getShippingAddress();
        }
        return $address;
    }

    public function getProgramByCommission($programs, $qty, $price, $customer_invited)
    {
        $array_commissions = array();
        $max               = 0;
        $program_id        = 0;
        foreach ($programs as $program) {
            $result_commission = 0;
            $commissions       = Mage::getModel('affiliate/affiliateprogram')->load($program)->getCommission();
            if (substr_count($commissions, ',') == 0) {
                $result_commission = $commissions;
            } else if (substr_count($commissions, ',') >= 1) {
                $commission        = explode(",", $commissions);
                $result_commission = $commission[0];
            }
            ;
            if (substr_count($result_commission, '%') == 1) {
                $text                        = explode("%", $result_commission);
                $percent                     = trim($text[0]);
                $array_commissions[$program] = ($percent * $price * $qty) / 100;
                
            } else if (substr_count($result_commission, '%') == 0) {
                $array_commissions[$program] = $result_commission * $qty;
            }
            ;
            if ($max < $array_commissions[$program]) {
                $max        = $array_commissions[$program];
                $program_id = $program;
            }
        }
        if ($program_id == 0)
            $program_id = $this->getProgramByDiscountOld($programs, $qty, $price, $customer_invited);
        return $program_id;
    }

    public function getProgramByCommissionOld($programs, $qty, $price)
    {
        $array_commissions = array();
        $max               = 0;
        $program_id        = 0;
        foreach ($programs as $program) {
            $result_commission = 0;
            $commissions       = Mage::getModel('affiliate/affiliateprogram')->load($program)->getCommission();
            if (substr_count($commissions, ',') == 0) {
                $result_commission = $commissions;
            } else if (substr_count($commissions, ',') >= 1) {
                $commission        = explode(",", $commissions);
                $result_commission = $commission[0];
            }
            ;
            if (substr_count($result_commission, '%') == 1) {
                $text                        = explode("%", $result_commission);
                $percent                     = trim($text[0]);
                $array_commissions[$program] = ($percent * $price * $qty) / 100;
                
            } else if (substr_count($result_commission, '%') == 0) {
                $array_commissions[$program] = $result_commission * $qty;
            }
            ;
            if ($max < $array_commissions[$program]) {
                $max        = $array_commissions[$program];
                $program_id = $program;
            }
        }
        return $program_id;
    }

    public function getProgramByPosition($programs)
    {
        $program_id   = 0;
        $positions    = array();
        $min_position = 0;
        foreach ($programs as $program) {
            
            $min_position = Mage::getModel('affiliate/affiliateprogram')->load($program)->getProgramPosition();
            break;
        }
        foreach ($programs as $program) {
            
            $positions[$program] = Mage::getModel('affiliate/affiliateprogram')->load($program)->getProgramPosition();
            if ($positions[$program] <= $min_position) {
                $min_position = $positions[$program];
                $program_id   = $program;
            }
        }
        
        return $program_id;
    }

    public function getProgramByDiscount($programs, $qty, $price, $customer_invited)
    {
        $customer_id = (int) Mage::getSingleton("customer/session")->getCustomer()->getId();
        if (!$customer_id)
            $customer_id = 0;
        $array_discounts = array();
        $max             = 0;
        $program_id      = 0;
        foreach ($programs as $program) {
            $result_discounts = 0;
            $discounts        = Mage::getModel('affiliate/affiliateprogram')->load($program)->getDiscount();
            if (substr_count($discounts, ',') == 0) {
                $result_discounts = $discounts;
            } else if (substr_count($discounts, ',') >= 1) {
                $discount = explode(",", $discounts);
                if ($customer_id == 0) {
                    $result_discounts = $discount[0];
                } else {
                    $collection = Mage::getModel('affiliate/affiliatehistory')->getCollection()->addFieldToFilter('customer_invited', $customer_invited)->addFieldToFilter('customer_id', $customer_id)->addFieldToFilter('status', array(
                        'nin' => array(
                            MW_Affiliate_Model_Status::CANCELED
                        )
                    ));
                    $collection->getSelect()->group('order_id');
                    $sizeof_discount = sizeof($discount);
                    $sizeo_order     = sizeof($collection);
                    if ($sizeo_order < $sizeof_discount) {
                        $result_discounts = $discount[$sizeo_order];
                    } else if ($sizeo_order >= $sizeof_discount) {
                        $result_discounts = $discount[$sizeof_discount - 1];
                    }
                }
            }
            if (substr_count($result_discounts, '%') == 1) {
                $text                      = explode("%", $result_discounts);
                $percent                   = trim($text[0]);
                $array_discounts[$program] = ($percent * $price * $qty) / 100;
            } else if (substr_count($result_discounts, '%') == 0) {
                $array_discounts[$program] = $result_discounts * $qty;
            }
            if ($max < $array_discounts[$program]) {
                $max        = $array_discounts[$program];
                $program_id = $program;
            }
            
        }
        if ($program_id == 0)
            $program_id = $this->getProgramByCommissionOld($programs, $qty, $price);
        return $program_id;
    }

    public function getProgramByDiscountOld($programs, $qty, $price, $customer_invited)
    {
        $customer_id = (int) Mage::getSingleton("customer/session")->getCustomer()->getId();
        if (!$customer_id)
            $customer_id = 0;
        $array_discounts = array();
        $max             = 0;
        $program_id      = 0;
        foreach ($programs as $program) {
            $result_discounts = 0;
            $discounts        = Mage::getModel('affiliate/affiliateprogram')->load($program)->getDiscount();
            if (substr_count($discounts, ',') == 0) {
                $result_discounts = $discounts;
            } else if (substr_count($discounts, ',') >= 1) {
                $discount = explode(",", $discounts);
                if ($customer_id == 0) {
                    $result_discounts = $discount[0];
                } else {
                    $collection = Mage::getModel('affiliate/affiliatehistory')->getCollection()->addFieldToFilter('customer_invited', $customer_invited)->addFieldToFilter('customer_id', $customer_id)->addFieldToFilter('status', array(
                        'nin' => array(
                            MW_Affiliate_Model_Status::CANCELED
                        )
                    ));
                    $collection->getSelect()->group('order_id');
                    $sizeof_discount = sizeof($discount);
                    $sizeo_order     = sizeof($collection);
                    if ($sizeo_order < $sizeof_discount) {
                        $result_discounts = $discount[$sizeo_order];
                    } else if ($sizeo_order >= $sizeof_discount) {
                        $result_discounts = $discount[$sizeof_discount - 1];
                    }
                }
            }
            if (substr_count($result_discounts, '%') == 1) {
                $text                      = explode("%", $result_discounts);
                $percent                   = trim($text[0]);
                $array_discounts[$program] = ($percent * $price * $qty) / 100;
                
            } else if (substr_count($result_discounts, '%') == 0) {
                $array_discounts[$program] = $result_discounts * $qty;
            }
            if ($max < $array_discounts[$program]) {
                $max        = $array_discounts[$program];
                $program_id = $program;
            }
            
        }
        return $program_id;
    }

    public function getDiscountByProgram($program_id, $qty, $price, $customer_invited)
    {
        $customer_id = (int) Mage::getSingleton("customer/session")->getCustomer()->getId();
        if (!$customer_id)
            $customer_id = 0;
        $mw_discounts = 0;
        $discounts    = Mage::getModel('affiliate/affiliateprogram')->load($program_id)->getDiscount();
        if (substr_count($discounts, ',') == 0) {
            $result_discounts = $discounts;
        } else if (substr_count($discounts, ',') >= 1) {
            $discount = explode(",", $discounts);
            if ($customer_id == 0) {
                $result_discounts = $discount[0];
            } else {
                $collection = Mage::getModel('affiliate/affiliatehistory')->getCollection()->addFieldToFilter('customer_invited', $customer_invited)->addFieldToFilter('customer_id', $customer_id)->addFieldToFilter('status', array(
                    'nin' => array(
                        MW_Affiliate_Model_Status::CANCELED
                    )
                ));
                $collection->getSelect()->group('order_id');
                $sizeof_discount = sizeof($discount);
                $sizeo_order     = sizeof($collection);
                if ($sizeo_order < $sizeof_discount) {
                    $result_discounts = $discount[$sizeo_order];
                } else if ($sizeo_order >= $sizeof_discount) {
                    $result_discounts = $discount[$sizeof_discount - 1];
                }
                ;
            }
            ;
        }
        if (substr_count($result_discounts, '%') == 1) {
            $text         = explode("%", $result_discounts);
            $percent      = trim($text[0]);
            $mw_discounts = ($percent * $price * $qty) / 100;
            
        } else if (substr_count($result_discounts, '%') == 0) {
            $mw_discounts = $result_discounts * $qty;
        }
        return $mw_discounts;
    }

    public function getProgramByTime($programs)
    {
        $program_ids = array();
        foreach ($programs as $program) {
            $start_date = Mage::getModel('affiliate/affiliateprogram')->load($program)->getStartDate();
            $end_date   = Mage::getModel('affiliate/affiliateprogram')->load($program)->getEndDate();
            if (Mage::app()->getLocale()->isStoreDateInInterval(null, $start_date, $end_date))
                $program_ids[] = $program;
            
        }
        return $program_ids;
    }

    public function getProgramByStoreView($programs)
    {
        $program_ids = array();
        $store_id    = Mage::app()->getStore()->getId();
        foreach ($programs as $program) {
            
            $store_view  = Mage::getModel('affiliate/affiliateprogram')->load($program)->getStoreView();
            $store_views = explode(',', $store_view);
            if (in_array($store_id, $store_views) OR $store_views[0] == '0')
                $program_ids[] = $program;
            
        }
        return $program_ids;
    }

    public function getProgramByEnable($programs)
    {
        $program_ids = array();
        foreach ($programs as $program) {
            $status = Mage::getModel('affiliate/affiliateprogram')->load($program)->getStatus();
            if ($status == MW_Affiliate_Model_Statusprogram::ENABLED)
                $program_ids[] = $program;
            
        }
        return $program_ids;
    }
    
    public function getProgramByCustomer($programs, $referral_code)
    {
        $program_ids     = array();
        $program_id_news = array();
        $result_new      = array();
        $result          = array();
        $cokie           = (int) Mage::getModel('core/cookie')->get('customer');
        if (!$cokie)
            $cokie = 0;
        $customer_id = (int) Mage::getSingleton("customer/session")->getCustomer()->getId();
        $check                = Mage::helper('affiliate')->checkCustomer($customer_id);
        $store_id             = Mage::app()->getStore()->getId();
        $affiliate_commission = (int) Mage::helper('affiliate/data')->getAffiliateCommissionbyThemselves($store_id);
        if ($customer_id) {
            $is_rererral_code = 0;
            $customer_invited = 0;
            $customer_id_new  = (int) Mage::helper('affiliate')->getCustomerIdByReferralCode($referral_code, $customer_id);
            if ($customer_id == $customer_id_new) {
                $customer_invited = Mage::getModel('affiliate/affiliatecustomers')->load($customer_id)->getCustomerInvited();
                if (!$customer_invited)
                    $customer_invited = 0;
            } else {
                $customer_invited = $customer_id_new;
                $is_rererral_code = 1;
            }
            
            if ($customer_invited == 0) {
                if ($affiliate_commission == 0)
                    return $result;
                
                if (Mage::helper('affiliate')->getActiveAndEnableAffiliate($customer_id) == 1) {
                    $result = $this->checkThreeCondition($customer_id, $customer_id, $programs);
                    if (sizeof($result) > 0) {
                        $result[] = $customer_id;
                        return $result;
                    } else if (sizeof($result) == 0) {
                        return $result;
                    }
                }
            } else if ($customer_invited != 0) {
                if (Mage::helper('affiliate')->getLockAffiliate($customer_invited) == 1) {
                    if ($affiliate_commission == 0)
                        return $result;
                    
                    if (Mage::helper('affiliate')->getActiveAndEnableAffiliate($customer_id) == 1) {
                        $result = $this->checkThreeCondition($customer_id, $customer_id, $programs);
                        if (sizeof($result) > 0) {
                            $result[] = $customer_id;
                            return $result;
                        } else if (sizeof($result) == 0) {
                            return $result;
                        }
                    }
                }

                else if (Mage::helper('affiliate')->getLockAffiliate($customer_invited) == 0) {
                    if ($is_rererral_code) {
                        $program_id_news = $this->getProgramByCustomerId($customer_invited);
                        $result_new      = array_intersect($program_id_news, $programs);
                    } else {
                        $result_new = $this->checkThreeCondition($customer_id, $customer_invited, $programs);
                    }
                    ;

                    if (sizeof($result_new) > 0) {
                        $result_new[] = $customer_invited;
                        return $result_new;
                    }

                    else if (sizeof($result_new) == 0) {
                        
                        if ($affiliate_commission == 0)
                            return $result;
                        
                        if (Mage::helper('affiliate')->getActiveAndEnableAffiliate($customer_id) == 1) {
                            $result = $this->checkThreeCondition($customer_id, $customer_id, $programs);
                            if (sizeof($result) > 0) {
                                $result[] = $customer_id;
                                return $result;
                            } else if (sizeof($result) == 0) {
                                return $result;
                            }
                        }
                    }
                }
            }
            return $result;
        }
        
        $cokie = Mage::helper('affiliate')->getCustomerIdByReferralCode($referral_code, $cokie);
        if ($cokie) {
            if (Mage::helper('affiliate')->getLockAffiliate($cokie) == 0) {
                $result = $this->checkThreeCondition(0, $cokie, $programs);
                if (sizeof($result) > 0) {
                    $result[] = $cokie;
                    return $result;
                }
            }
            return $result;
        }
        return $result;
    }

    public function checkThreeCondition($customer_id, $customer_invited, $programs)
    {
        $result      = array();
        $program_ids = array();
        
        $group_members             = Mage::getModel('affiliate/affiliategroupmember')->getCollection()->addFieldToFilter('customer_id', $customer_invited);
        $group_id                  = $group_members->getFirstItem()->getGroupId();
        $group_affiliate           = Mage::getModel('affiliate/affiliategroup')->load($group_id);
        $time_day                  = $group_affiliate->getLimitDay();
        $total_order               = $group_affiliate->getLimitOrder();
        $total_commission_customer = $group_affiliate->getLimitCommission();
        $check_customer_time       = $this->checkCustomerInvitedTime($customer_id, $time_day);
        $check_customer_order      = $this->checkCustomerInvitedTotalOrder($customer_id, $customer_invited, $total_order);
        $check_customer_commission = $this->checkCustomerByTotalCommission($customer_id, $customer_invited, $total_commission_customer);

        if ($check_customer_time == 1 && $check_customer_order == 1 && $check_customer_commission == 1) {
            $program_ids = $this->getProgramByCustomerId($customer_invited);
            $result      = array_intersect($program_ids, $programs);
        }
        return $result;
        
    }

    public function checkCustomerInvitedTime($customer_id, $time_day)
    {
        if ($time_day == '')
            return 0;
        $time_day = (int) $time_day;
        if ($customer_id == 0)
            return 1;
        if ($time_day == 0)
            return 1;
        if ($time_day > 0) {
            $time_day_second = $time_day * 24 * 60 * 60;
            $date_now        = Mage::getSingleton('core/date')->timestamp(time());
            $time_register   = Mage::getModel('affiliate/affiliatecustomers')->load($customer_id)->getCustomerTime();
            if (!$time_register)
                return 1;
            $date_register = Mage::getSingleton('core/date')->timestamp($time_register);
            if (($date_register + $time_day_second) > $date_now)
                return 1;
            if (($date_register + $time_day_second) <= $date_now)
                return 0;
        }
        
    }

    public function checkCustomerInvitedTotalOrder($customer_id, $customer_invited, $total_order)
    {
        if ($total_order == '')
            return 0;
        $total_order = (int) $total_order;
        if ($customer_id == 0)
            return 1;
        if ($total_order == 0)
            return 1;
        $collection = Mage::getModel('affiliate/affiliatehistory')->getCollection()->addFieldToFilter('customer_invited', $customer_invited)->addFieldToFilter('customer_id', $customer_id)
            ->addFieldToFilter('status', array(
            'nin' => array(
                MW_Affiliate_Model_Status::CANCELED
            )
        ));
        $collection->getSelect()->group('order_id');
        if (sizeof($collection) < $total_order)
            return 1;
        if (sizeof($collection) >= $total_order)
            return 0;
    }

    public function checkCustomerByTotalCommission($customer_id, $customer_invited, $total_commission_customer)
    {
        if ($total_commission_customer == '')
            return 0;
        $total_commission_customer = (double) $total_commission_customer;
        if ($customer_id == 0)
            return 1;
        if ($total_commission_customer == 0)
            return 1;
        if ($total_commission_customer > 0) {
            $collection = Mage::getModel('affiliate/affiliatehistory')->getCollection()->addFieldToFilter('customer_invited', $customer_invited)->addFieldToFilter('customer_id', $customer_id)->addFieldToFilter('status', MW_Affiliate_Model_Status::COMPLETE);
            $collection->addExpressionFieldToSelect('history_commission_sum', 'sum(history_commission)', 'history_commission_sum');
            $total_commission = (double) $collection->getFirstItem()->getHistoryCommissionSum();
            if ($total_commission >= $total_commission_customer)
                return 0;
        }
        return 1;
    }

    public function getProgramByCustomerId($customer_id)
    {
        $program_ids     = array();
        $customer_groups = Mage::getModel('affiliate/affiliategroupmember')->getCollection()->addFieldToFilter('customer_id', $customer_id);
        if (sizeof($customer_groups) > 0) {
            foreach ($customer_groups as $customer_group) {
                $group_id = $customer_group->getGroupId();
                break;
            }
            $customer_programs = Mage::getModel('affiliate/affiliategroupprogram')->getCollection()->addFieldToFilter('group_id', $group_id);
            foreach ($customer_programs as $customer_program) {
                $program_ids[] = $customer_program->getProgramId();
            }
        }
        
        return $program_ids;
    }   
}