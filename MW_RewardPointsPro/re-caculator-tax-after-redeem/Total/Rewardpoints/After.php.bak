<?php
    /**
     * User: Anh TO
     * Date: 5/23/14
     * Time: 12:32 PM
     */
    class MW_RewardPoints_Model_Quote_Address_Total_Rewardpoints_After extends Mage_Sales_Model_Quote_Address_Total_Abstract
    {
        protected $_itemTotals;
        protected $array_whole_cart = array();
        protected $array_whole_cart_x_y = array();
        protected $array_rule_active = array();

        public function __construct()
        {
            $this->setCode('reward_points_after');
        }

        public function collect(Mage_Sales_Model_Quote_Address $address)
        {
            parent::collect($address);
            if(Mage::app()->getRequest()->getControllerName() == "multishipping")
            {
                return $this;
            }

            $store_id = Mage::app()->getStore()->getId();
            $quote    = $address->getQuote();
            if((int)Mage::helper('rewardpoints')->getRedeemPointsOnTax($store_id) == MW_RewardPoints_Model_Redeemtax::AFTER)
            {
                $tax            = 0;
                $shipping       = 0;
                $tax_new        = 0;
                $shipping_new   = 0;
                $check_tax      = Mage::helper('rewardpoints')->getRedeemedTaxConfig($store_id);
                $check_shipping = Mage::helper('rewardpoints')->getRedeemedShippingConfig($store_id);
                if($check_tax)
                {
                    $tax = $address->getBaseTaxAmount();
                }
                if($check_shipping)
                {
                    $shipping = $address->getBaseShippingInclTax();
                }
                if(!$check_tax)
                {
                    $tax_new = $address->getBaseTaxAmount();
                }
                if(!$check_shipping)
                {
                    $shipping_new = $address->getBaseShippingInclTax();
                }

                // function check reward admin
                $customer_id = $quote->getCustomerId();
                if($customer_id)
                {

                    $baseGrandTotal = $address->getBaseGrandTotal() - $tax_new - $shipping_new;

                    if($quote->getMwRewardpointDiscount() > $baseGrandTotal && $baseGrandTotal > 0)
                    {
                        $quote->setMwRewardpointDiscount($baseGrandTotal);
                        $points = Mage::helper('rewardpoints')->exchangeMoneysToPoints($baseGrandTotal, $store_id);
                        $quote->setMwRewardpoint(Mage::helper('rewardpoints')->roundPoints($points, $store_id))->save();
                    }
                }

                $items = $address->getAllVisibleItems();
                if(!count($items))
                {
                    return $this;
                }
                $appy_reward          = ( int )Mage::helper('rewardpoints')->getAppyRewardPoints($store_id);
                $applyRewardPointsTax = (int)Mage::helper('rewardpoints')->getAppyRewardPointsTax($store_id);
                if($appy_reward == MW_RewardPoints_Model_Appyreward::BEFORE)
                {
                    if($applyRewardPointsTax == MW_RewardPoints_Model_Appyrewardtax::BEFORE)
                    {
                        $baseSubtotalWithDiscount = $address->getData('base_subtotal');
                    }
                    else
                    {
                        $baseSubtotalWithDiscount = $address->getData('subtotal_incl_tax');
                    }
                }
                else
                {
                    if($applyRewardPointsTax == MW_RewardPoints_Model_Appyrewardtax::BEFORE)
                    {
                        $baseSubtotalWithDiscount = $address->getData('base_subtotal') + $address->getData('base_discount_amount');
                    }
                    else
                    {
                        $baseSubtotalWithDiscount = $address->getData('subtotal_incl_tax') + $address->getData('base_discount_amount');
                    }
                }
                // spend point---------------------
                $spend_reward_point_cart = 0;
                $spend_sub_point         = 0;
                $spend_sub_money         = 0;

                $spend_programs = $this->getSpendProgramResult();
                // earn point------------------
                $earn_reward_point      = 0;
                $earn_reward_point_cart = 0;
                $programs               = $this->getEarnProgramResult();

                //$discountAmountTiem = $baseTotalDiscountAmount;
                $product_sell_point  = 0;
                $reward_point_detail = array();
                $array_rule_show     = array();
                $product_sell_point  = 0;
                foreach ($items as $item)
                {
                    $reward_point    = 0;
                    $mw_reward_point = 0;
                    $product_id      = $item->getProductId();
                    $qty             = $item->getQty();

                    $product = Mage::getModel('catalog/product')->load($product_id);
                    switch ($product->getTypeId())
                    {
                        case 'simple':
                        case 'virtual':
                        case 'downloadable':
                            $mw_reward_point_sell = $product->getData('mw_reward_point_sell_product');
                        if($mw_reward_point_sell > 0)
                        {
                            $product_sell_point = $product_sell_point + $qty * $mw_reward_point_sell;
                        }
                            break;
                        case 'bundle':
                            $mw_reward_point_sell = $product->getData('mw_reward_point_sell_product');
                            if($mw_reward_point_sell > 0)
                            {
                                $product_sell_point = $product_sell_point + $qty * $mw_reward_point_sell;
                            }
                            else
                            {
                                foreach ($item->getChildren() as $bundle_item)
                                {
                                    $child_product    = Mage::getModel('catalog/product')->load($bundle_item->getProductId());
                                    $child_point_sell = $child_product->getData('mw_reward_point_sell_product');

                                    if($child_point_sell > 0)
                                    {
                                        $product_sell_point = $product_sell_point + $bundle_item->getQty() * $child_point_sell;
                                    }
                                }
                            }

                            break;
                        case 'configurable':
                            $mw_reward_point_sell = $product->getData('mw_reward_point_sell_product');

                            if($mw_reward_point_sell > 0)
                            {
                                $product_sell_point = $product_sell_point + $qty * $mw_reward_point_sell;
                            }
                            else
                            {
                                if($info = $item->getProduct()->getCustomOption('info_buyRequest'))
                                {
                                    $infoArr = unserialize($info->getValue());
                                }
                                $total_sellpoints = 0;

                                $model = Mage::getModel('rewardpoints/productsellpoint');
                                foreach ($infoArr['super_attribute'] as $attr_id => $val)
                                {
                                    $collection = $model->getCollection()
                                        ->addFieldToFilter('product_id', $product->getId())
                                        ->addFieldToFilter('option_id', $val)
                                        ->addFieldToFilter('option_type_id', $attr_id)
                                        ->addFieldToFilter('type_id', 'super_attribute')
                                        ->getFirstItem();

                                    $product_sell_point += intval($collection->getSellPoint());
                                }
                            }
                            break;
                    }

                    $price_withdiscount = $item->getBasePrice() - $item->getBaseDiscountAmount() / $qty;

                    if($appy_reward == MW_RewardPoints_Model_Appyreward::BEFORE)
                    {
                        if($applyRewardPointsTax == MW_RewardPoints_Model_Appyrewardtax::BEFORE)
                        {
                            $price = Mage::helper('checkout')->getPriceInclTax($item);
                        }
                        else
                        {
                            $price = $item->getBasePrice();
                        }
                    }
                    else
                    {
                        if($applyRewardPointsTax == MW_RewardPoints_Model_Appyrewardtax::BEFORE)
                        {
                            $price = Mage::helper('checkout')->getPriceInclTax($item) - $item->getBaseDiscountAmount() / $qty;
                        }
                        else
                        {
                            $price = $price_withdiscount;
                        }
                    }

                    $mw_reward_point = $qty * Mage::getModel('rewardpoints/catalogrules')->getPointCatalogRulue($product_id);
                    if($item->getHasChildren() && $item->isChildrenCalculated())
                    {
                        foreach ($item->getChildren() as $child)
                        {

                            $qty_child                  = $child->getQty();
                            $price_product_withdiscount = $child->getBasePrice() - $child->getBaseDiscountAmount() / $qty_child;

                            if($appy_reward == MW_RewardPoints_Model_Appyreward::BEFORE)
                            {
                                if($applyRewardPointsTax == MW_RewardPoints_Model_Appyrewardtax::BEFORE)
                                {
                                    $price_product = Mage::helper('checkout')->getPriceInclTax($child);
                                }
                                else
                                {
                                    $price_product = $child->getBasePrice();
                                }
                            }
                            else
                            {
                                if($applyRewardPointsTax == MW_RewardPoints_Model_Appyrewardtax::BEFORE)
                                {
                                    $price_product = Mage::helper('checkout')->getPriceInclTax($child) - $child->getBaseDiscountAmount() / $qty_child;
                                }
                                else
                                {
                                    $price_product = $price_product_withdiscount;
                                }
                            }

                            $array_spend_reward_point = $this->SpendProcessRule($child, $spend_programs, $qty_child, $price_product_withdiscount);

                            if(( int )$array_spend_reward_point [1] > 0)
                            {
                                $spend_sub_point = $spend_sub_point + $qty * $array_spend_reward_point [1];
                            }
                            if(( double )$array_spend_reward_point [2] > 0)
                            {
                                $spend_sub_money = $spend_sub_money + $qty * $array_spend_reward_point [2];
                            }

                            $reward_point_array = $this->processRuleConfigurableProduct($child, $programs, $qty_child, $price_product, $qty, $baseSubtotalWithDiscount);
                            $reward_point       = $reward_point_array [1];
                            $rule_details       = $reward_point_array [2];

                            foreach ($rule_details as $key => $rule_detail)
                            {
                                if(!isset ($reward_point_detail [$key]))
                                {
                                    $reward_point_detail [$key] = 0;
                                }
                                $reward_point_detail [$key] = $reward_point_detail [$key] + $rule_detail;
                            }
                            $earn_reward_point      = $earn_reward_point + $reward_point + $mw_reward_point;
                            $earn_reward_point_cart = $earn_reward_point_cart + $reward_point;

                        }
                    }
                    else
                    {

                        $array_spend_reward_point = $this->SpendProcessRule($item, $spend_programs, $qty, $price_withdiscount);

                        if(( int )$array_spend_reward_point [1] > 0)
                        {
                            $spend_sub_point = $spend_sub_point + $array_spend_reward_point [1];
                        }
                        if(( double )$array_spend_reward_point [2] > 0)
                        {
                            $spend_sub_money = $spend_sub_money + $array_spend_reward_point [2];
                        }

                        $reward_point_array = $this->processRule($item, $programs, $qty, $price, $baseSubtotalWithDiscount);

                        $reward_point = $reward_point_array [1];
                        $rule_details = $reward_point_array [2];

                        foreach ($rule_details as $key => $rule_detail)
                        {
                            if(!isset ($reward_point_detail [$key]))
                            {
                                $reward_point_detail [$key] = 0;
                            }
                            $reward_point_detail [$key] = $reward_point_detail [$key] + $rule_detail;
                        }
                        $earn_reward_point      = $earn_reward_point + $reward_point + $mw_reward_point;
                        $earn_reward_point_cart = $earn_reward_point_cart + $reward_point;
                    }
                }
                Mage::getSingleton('checkout/session')->setQuoteIdSession("");
                //dungdk per order
                $array_rule_show = array_diff($programs, $this->array_rule_active);
                //zend_debug::dump($array_rule_show);die();
                $spend_sub_money_to_point = 0;

                if($spend_sub_money >= 0)
                {
                    $spend_sub_money          = $spend_sub_money + $tax + $shipping;
                    $spend_sub_money_to_point = Mage::helper('rewardpoints')->exchangeMoneysToPoints($spend_sub_money, $store_id);
                    //echo $spend_sub_money.'aaaaaa'.$spend_sub_point.'bbbb'.$spend_sub_money_to_point;
                }
                $spend_reward_point_cart = Mage::helper('rewardpoints')->roundPoints($spend_sub_point + $spend_sub_money_to_point, $store_id, false);
                $point_checkout          = Mage::helper('rewardpoints')->exchangeMoneysToPoints($address->getBaseGrandTotal() - $tax_new - $shipping_new, $store_id);
                if($point_checkout < 0)
                {
                    $point_checkout = 0;
                }
                if($spend_reward_point_cart > $point_checkout)
                {
                    $spend_reward_point_cart = $point_checkout;
                }
                if($quote->getMwRewardpoint() == $point_checkout)
                {
                    $quote->setMwRewardpointDiscount($address->getBaseGrandTotal() - $tax_new - $shipping_new)->save();
                }
                
                /* Fix for multi currency */
                $mwRewardpointDiscount = $quote->getMwRewardpointDiscount();
                $totalDiscountAmount = Mage::helper('core')->currency($mwRewardpointDiscount, false, false);
                $baseTotalDiscountAmount = $mwRewardpointDiscount;
                $quote->setMwRewardpointDiscount($mwRewardpointDiscount);
                $quote->setMwRewardpointDiscountShow($totalDiscountAmount);
                /* Fix for multi currency */

                if($customer_id)
                {
                    if(!Mage::helper('rewardpoints')->checkCustomerMaxBalance($customer_id, $store_id, $earn_reward_point))
                    {
                        $earn_reward_point      = 0;
                        $earn_reward_point_cart = 0;
                        $reward_point_detail    = array();
                    }
                }

                // add option allow using reward points and coupon code at the same time
                if(!Mage::helper('rewardpoints')->getCouponRwpConfig($store_id))
                {
                    if($quote->getCouponCode() != '')
                    {
                        $quote->setSpendRewardpointCart(0)->save();
                    }

                    else if($quote->getMwRewardpoint() > 0)
                    {
                        $quote->setCouponCode('');
                    }
                    else
                    {
                        $quote->setSpendRewardpointCart($spend_reward_point_cart)->save();
                    }
                }
                else
                {
                    $quote->setSpendRewardpointCart($spend_reward_point_cart)->save();
                }

                $quote->setEarnRewardpoint($earn_reward_point)->save();
                $quote->setEarnRewardpointCart($earn_reward_point_cart)->save();
                $quote->setMwRewardpointSellProduct($product_sell_point)->save();
                $quote->setMwRewardpointDetail(serialize($reward_point_detail))->save();
                $quote->setMwRewardpointRuleMessage(serialize($array_rule_show))->save();

                if($quote->getMwRewardpoint() <= 0 || $totalDiscountAmount <= 0 || $baseTotalDiscountAmount <= 0)
                {
                    $address->setMwRewardpoint(0);
                    $address->setMwRewardpointDiscountShow(0);
                    $address->setMwRewardpointDiscount(0);
                }
                else
                {
                    $address->setMwRewardpoint($quote->getMwRewardpoint());
                    $address->setMwRewardpointDiscountShow($totalDiscountAmount);
                    $address->setMwRewardpointDiscount($baseTotalDiscountAmount);
                }
                Mage::getSingleton('core/session')->setMwRewardpointDiscountShowTotal($address->getMwRewardpointDiscountShow());
                Mage::getSingleton('core/session')->setMwRewardpointDiscountTotal($address->getMwRewardpointDiscount());
                $address->setGrandTotal($address->getGrandTotal() - $address->getMwRewardpointDiscountShow());
                $address->setBaseGrandTotal($address->getBaseGrandTotal() - ($address->getMwRewardpointDiscount()));
                $address->setMWRewardpointDefaultAddress($address->getAddressType());

                return $this;
            }
            else if((int)Mage::helper('rewardpoints')->getRedeemPointsOnTax($store_id) == MW_RewardPoints_Model_Redeemtax::BEFORE)
            {
                if($address->getSubtotal() > 0)
                {
                    $subtotalExlTax    = $address->getSubtotal();
                    $taxAmount         = $address->getTaxAmount();
                    $grandTotalExlTax  = $address->getGrandTotal() - $taxAmount;
                    $allShippingAmount = $grandTotalExlTax - $subtotalExlTax;

                    $mwRewardpointDiscount = Mage::helper('core')->currency($quote->getMwRewardpointDiscount(), false, false);
                    $baseMwRewardpointDiscount = $quote->getMwRewardpointDiscount();
                    $totalDiscountAmount = $subtotalExlTax - $mwRewardpointDiscount + $allShippingAmount + $taxAmount;
                    $baseTotalDiscountAmount = $subtotalExlTax - $baseMwRewardpointDiscount + $allShippingAmount + $taxAmount;

                    if($quote->getMwRewardpoint() <= 0 || $totalDiscountAmount <= 0 || $baseTotalDiscountAmount <= 0)
                    {
                        $address->setMwRewardpoint(0);
                        $address->setMwRewardpointDiscountShow(0);
                        $address->setMwRewardpointDiscount(0);
                    }
                    else
                    {
                        $address->setMwRewardpoint($quote->getMwRewardpoint());
                        $address->setMwRewardpointDiscountShow($mwRewardpointDiscount);
                        $address->setMwRewardpointDiscount($baseMwRewardpointDiscount);
                    }

                    Mage::getSingleton('core/session')->setMwRewardpointDiscountShowTotal($address->getMwRewardpointDiscountShow());
                    Mage::getSingleton('core/session')->setMwRewardpointDiscountTotal($address->getMwRewardpointDiscount());

                    $address->setSubtotalInclTax($address->getSubtotalInclTax() - $mwRewardpointDiscount);
                    $address->setBaseSubtotalTotalInclTax($address->getBaseSubtotalInclTax() - $baseMwRewardpointDiscount);
                    $address->setGrandTotal($address->getGrandTotal() - $mwRewardpointDiscount);
                    $address->setBaseGrandTotal($address->getBaseGrandTotal() - $baseMwRewardpointDiscount);
                }
            }
        }

        public function fetch(Mage_Sales_Model_Quote_Address $address)
        {
            $store_id = Mage::app()->getStore()->getId();
            if(
                (int)Mage::helper('rewardpoints')->getRedeemPointsOnTax($store_id) == MW_RewardPoints_Model_Redeemtax::AFTER
            )
            {
                if(Mage::app()->getRequest()->getControllerName() == "multishipping" && Mage::app()->getRequest()->getActionName() == "overview")
                {
                    $addresss = Mage::getSingleton('checkout/session')->getQuote()->getAllShippingAddresses();
                }
                else
                {
                    $amount     = $address->getMwRewardpointDiscountShow();
                    $point      = $address->getMwRewardpoint();
                    $point_show = Mage::helper('rewardpoints')->formatPoints($point, $store_id);
                    if($amount != 0)
                    {
                        //$title = Mage::helper('sales')->__('Discount (Reward Points)');
                        $title = Mage::helper('rewardpoints')->__('You Redeem (%s)', $point_show);
                        if(Mage::app()->getStore()->isAdmin())
                        {
                            $title = Mage::helper('rewardpoints')->__('You Redeem (%s points)', $point);
                        }
                        $address->addTotal(array(
                            'code'   => $this->getCode(),
                            'title'  => $title,
                            'value'  => -$amount,
                            'strong' => false
                        )); //'area'    => '1'
                    }
                }
            }

            return $this;
        }

        public function getSpendProgramResult()
        {
            $spend_programs = array();
            $model_spend    = Mage::getModel('rewardpoints/spendcartrules');
            $spend_programs = $this->getAllProgram($model_spend);
            $spend_programs = $this->getProgramByCustomerGroup($spend_programs, $model_spend);
            if(!Mage::app()->isSingleStoreMode())
            {
                $spend_programs = $this->getProgramByStoreView($spend_programs, $model_spend);
            }
            $spend_programs = $this->getProgramByEnable($spend_programs, $model_spend);
            $spend_programs = $this->getProgramByTime($spend_programs, $model_spend);
            $spend_programs = $this->getProgramByPostion($spend_programs, $model_spend);

            return $spend_programs;
        }

        public function getEarnProgramResult()
        {
            $programs   = array();
            $model_earn = Mage::getModel('rewardpoints/cartrules');
            $programs   = $this->getAllProgram($model_earn);
            $programs   = $this->getProgramByCustomerGroup($programs, $model_earn);
            if(!Mage::app()->isSingleStoreMode())
            {
                $programs = $this->getProgramByStoreView($programs, $model_earn);
            }
            $programs = $this->getProgramByEnable($programs, $model_earn);
            $programs = $this->getProgramByTime($programs, $model_earn);
            $programs = $this->getProgramByPostion($programs, $model_earn);

            return $programs;
        }

        public function getAllProgram($model)
        {
            $programs            = array();
            $program_collections = $model->getCollection();
            foreach ($program_collections as $program_collection)
            {
                $programs [] = $program_collection->getRuleId();
            }

            return $programs;
        }

        public function getProgramByCustomerGroup($programs, $model)
        {
            $program_ids = array();
            if(Mage::app()->getStore()->isAdmin())
            {
                $quote = Mage::getSingleton('adminhtml/sales_order_create')->getQuote();
                if(!is_null($quote))
                {
                    $group_id = $quote->getCustomerGroupId();
                }
                else
                {
                    $group_id = Mage::getSingleton("customer/session")->getCustomerGroupId();
                }
            }
            else
            {
                $group_id = Mage::getSingleton("customer/session")->getCustomerGroupId();
            }
            foreach ($programs as $program)
            {

                $customer_group_id  = $model->load($program)->getCustomerGroupIds();
                $customer_group_ids = explode(',', $customer_group_id);
                if(in_array($group_id, $customer_group_ids))
                {
                    $program_ids [] = $program;
                }

            }

            return $program_ids;
        }

        public function getProgramByStoreView($programs, $model)
        {
            $program_ids = array();
            $store_id    = Mage::app()->getStore()->getId();
            foreach ($programs as $program)
            {

                $store_view  = $model->load($program)->getStoreView();
                $store_views = explode(',', $store_view);
                if(in_array($store_id, $store_views) or $store_views [0] == '0')
                {
                    $program_ids [] = $program;
                }

            }

            return $program_ids;
        }

        public function getProgramByEnable($programs, $model)
        {
            $program_ids = array();
            foreach ($programs as $program)
            {
                $status = $model->load($program)->getStatus();
                if($status == MW_RewardPoints_Model_Statusrule::ENABLED)
                {
                    $program_ids [] = $program;
                }

            }

            return $program_ids;
        }

        public function getProgramByTime($programs, $model)
        {
            $program_ids = array();
            foreach ($programs as $program)
            {
                $cartrules  = $model->load($program);
                $start_date = $cartrules->getStartDate();
                $end_date   = $cartrules->getEndDate();
                if(Mage::app()->getLocale()->isStoreDateInInterval(null, $start_date, $end_date))
                {
                    $program_ids [] = $program;
                }

            }

            return $program_ids;
        }

        public function getProgramByPostion($programs, $model)
        {
            $program_ids            = array();
            $array_program_position = array();
            foreach ($programs as $program)
            {
                $rule_position             = ( int )$model->load($program)->getRulePosition();
                $array_program_position [] = $rule_position;
                $program_ids []            = $program;

            }
            if(sizeof($program_ids) > 0)
            {
                array_multisort($array_program_position, $program_ids);
            }

            return $program_ids;
        }

        public function processRule(Mage_Sales_Model_Quote_Item_Abstract $item, $programs, $qty, $price, $baseSubtotalWithDiscount)
        {
            $result              = array();
            $result_reward_point = 0;
            $_result_detail      = array();
            $_program_rule       = array();
            foreach ($programs as $program)
            {
                $_program_rule [] = $program;
                $rule             = Mage::getModel('rewardpoints/cartrules')->load($program);
                $reward_point     = ( int )$rule->getRewardPoint();
                $simple_action    = ( int )$rule->getSimpleAction();
                $reward_step      = ( int )$rule->getRewardStep();
                $stop_rule        = ( int )$rule->getStopRulesProcessing();
                $rule->afterLoad();
                $address = $this->getAddress_new($item);
                if(($rule->validate($address)) && ($rule->getActions()->validate($item)))
                {
                    //$program_ids[] = $program;
                    if($simple_action == MW_RewardPoints_Model_Typerule::FIXED)
                    {
                        $result_reward_point = $result_reward_point + $reward_point;
                        if(!isset ($_result_detail [$program]))
                        {
                            $_result_detail [$program] = 0;
                        }
                        $_result_detail [$program] = $_result_detail [$program] + $reward_point;
                    }
                    else if($simple_action == MW_RewardPoints_Model_Typerule::FIXED_WHOLE_CART)
                    {

                        if(!(isset ($this->array_whole_cart [$program]) && $this->array_whole_cart [$program] == 1))
                        {
                            $this->array_whole_cart [$program] = 1;
                            $result_reward_point               = $result_reward_point + $reward_point;
                            if(!isset ($_result_detail [$program]))
                            {
                                $_result_detail [$program] = 0;
                            }
                            $_result_detail [$program] = $_result_detail [$program] + $reward_point;
                        }
                    }
                    else if($simple_action == MW_RewardPoints_Model_Typerule::BUY_X_GET_Y_WHOLE_CART)
                    {
                        if($reward_step > 0)
                        {
                            if(!(isset ($this->array_whole_cart_x_y [$program]) && $this->array_whole_cart_x_y [$program] == 1))
                            {
                                $this->array_whole_cart_x_y [$program] = 1;
                                $result_reward_point                   = $result_reward_point + ( int )($baseSubtotalWithDiscount / $reward_step) * $reward_point;
                                if(!isset ($_result_detail [$program]))
                                {
                                    $_result_detail [$program] = 0;
                                }
                                $_result_detail [$program] = $_result_detail [$program] + ( int )($baseSubtotalWithDiscount / $reward_step) * $reward_point;
                            }
                        }

                    }
                    else
                    {
                        if($reward_step > 0)
                        {
                            $result_reward_point = $result_reward_point + ( int )(($qty * $price) / $reward_step) * $reward_point;
                            if(!isset ($_result_detail [$program]))
                            {
                                $_result_detail [$program] = 0;
                            }
                            $_result_detail [$program] = $_result_detail [$program] + ( int )(($qty * $price) / $reward_step) * $reward_point;
                        }
                    }

                    if(!in_array($program, $this->array_rule_active))
                    {
                        $this->array_rule_active [] = $program;
                    }
                    if($stop_rule)
                    {
                        foreach (array_diff($programs, $_program_rule) as $_program_rule_value)
                        {
                            if(!in_array($_program_rule_value, $this->array_rule_active))
                            {
                                $this->array_rule_active [] = $_program_rule_value;
                            }
                        }
                        break;
                    }
                }

            }
            $result [1] = $result_reward_point;
            $result [2] = $_result_detail;

            //return  $result_reward_point;
            return $result;
        }

        public function processRuleConfigurableProduct(Mage_Sales_Model_Quote_Item_Abstract $item, $programs, $qty, $price, $qty_parent, $baseSubtotalWithDiscount)
        {
            $result              = array();
            $result_reward_point = 0;
            $_result_detail      = array();
            $_program_rule       = array();
            foreach ($programs as $program)
            {
                $_program_rule [] = $program;
                $rule             = Mage::getModel('rewardpoints/cartrules')->load($program);
                $reward_point     = ( int )$rule->getRewardPoint();
                $simple_action    = ( int )$rule->getSimpleAction();
                $reward_step      = ( int )$rule->getRewardStep();
                $stop_rule        = ( int )$rule->getStopRulesProcessing();
                $rule->afterLoad();
                $address = $this->getAddress_new($item);
                if(($rule->validate($address)) && ($rule->getActions()->validate($item)))
                {
                    //$program_ids[] = $program;
                    if($simple_action == MW_RewardPoints_Model_Typerule::FIXED)
                    {
                        $result_reward_point = $result_reward_point + $reward_point;
                        if(!isset ($_result_detail [$program]))
                        {
                            $_result_detail [$program] = 0;
                        }
                        $_result_detail [$program] = $_result_detail [$program] + $reward_point;
                    }
                    else if($simple_action == MW_RewardPoints_Model_Typerule::FIXED_WHOLE_CART)
                    {

                        if(!(isset ($this->array_whole_cart [$program]) && $this->array_whole_cart [$program] == 1))
                        {
                            $this->array_whole_cart [$program] = 1;
                            $result_reward_point               = $result_reward_point + $reward_point;
                            if(!isset ($_result_detail [$program]))
                            {
                                $_result_detail [$program] = 0;
                            }
                            $_result_detail [$program] = $_result_detail [$program] + $reward_point;
                        }
                    }
                    else if($simple_action == MW_RewardPoints_Model_Typerule::BUY_X_GET_Y_WHOLE_CART)
                    {
                        if($reward_step > 0)
                        {
                            if(!(isset ($this->array_whole_cart_x_y [$program]) && $this->array_whole_cart_x_y [$program] == 1))
                            {
                                $this->array_whole_cart_x_y [$program] = 1;
                                $result_reward_point                   = $result_reward_point + ( int )($baseSubtotalWithDiscount / $reward_step) * $reward_point;
                                if(!isset ($_result_detail [$program]))
                                {
                                    $_result_detail [$program] = 0;
                                }
                                $_result_detail [$program] = $_result_detail [$program] + ( int )($baseSubtotalWithDiscount / $reward_step) * $reward_point;
                            }
                        }

                    }
                    else
                    {
                        if($reward_step > 0)
                        {
                            $result_reward_point = $result_reward_point + ( int )(($qty * $price * $qty_parent) / $reward_step) * $reward_point;
                            if(!isset ($_result_detail [$program]))
                            {
                                $_result_detail [$program] = 0;
                            }
                            $_result_detail [$program] = $_result_detail [$program] + ( int )(($qty * $price * $qty_parent) / $reward_step) * $reward_point;
                        }
                    }

                    if(!in_array($program, $this->array_rule_active))
                    {
                        $this->array_rule_active [] = $program;
                    }
                    if($stop_rule)
                    {
                        foreach (array_diff($programs, $_program_rule) as $_program_rule_value)
                        {
                            if(!in_array($_program_rule_value, $this->array_rule_active))
                            {
                                $this->array_rule_active [] = $_program_rule_value;
                            }
                        }
                        break;
                    }
                }

            }
            $result [1] = $result_reward_point;
            $result [2] = $_result_detail;

            //return  $result_reward_point;
            return $result;
        }

        public function SpendProcessRule(Mage_Sales_Model_Quote_Item_Abstract $item, $programs, $qty, $price)
        {
            $array_result     = array();
            $array_result [1] = 0;
            $array_result [2] = $qty * $price;
            foreach ($programs as $program)
            {
                $rule          = Mage::getModel('rewardpoints/spendcartrules')->load($program);
                $reward_point  = ( int )$rule->getRewardPoint();
                $stop_rule     = ( int )$rule->getStopRulesProcessing();
                $simple_action = ( int )$rule->getSimpleAction();
                $reward_step   = ( int )$rule->getRewardStep();
                $rule->afterLoad();
                $address = $this->getAddress_new($item);

                if(($rule->validate($address)) && ($rule->getActions()->validate($item)))
                {
                    if($simple_action == MW_RewardPoints_Model_Typerulespend::FIXED)
                    {
                        //dungdk per order
                        if(Mage::getSingleton('checkout/session')->getQuoteIdSession() == $item->getQuoteId())
                        {
                            $array_result [1] = 0;
                            $array_result [2] = 0;
                        }
                        else
                        {
                            $array_result [1] = $array_result [1] + $reward_point;
                            $array_result [2] = 0;
                        }
                        Mage::getSingleton('checkout/session')->setQuoteIdSession($item->getQuoteId());

                    }
                    else if($simple_action == MW_RewardPoints_Model_Typerulespend::BUY_X_USE_Y)
                    {

                        if($reward_step > 0)
                        {
                            $array_result [1] = $array_result [1] + ( int )(($qty * $price) / $reward_step) * $reward_point;
                        }
                        $array_result [2] = 0;

                    }
                    else if($simple_action == MW_RewardPoints_Model_Typerulespend::USE_UNLIMIT_POINTS)
                    {
                        $array_result [1] = 0;
                        $array_result [2] = $qty * $price;

                    }
                    else if($simple_action == MW_RewardPoints_Model_Typerulespend::NOT_ALLOW_USE_POINTS)
                    {
                        $array_result [1] = 0;
                        $array_result [2] = 0;
                    };
                }
                if($stop_rule)
                {
                    break;
                }
            }

            return $array_result;
        }

        protected function getAddress_new(Mage_Sales_Model_Quote_Item_Abstract $item)
        {
            if($item instanceof Mage_Sales_Model_Quote_Address_Item)
            {
                $address = $item->getAddress();
            }
            elseif($item->getQuote()->isVirtual())
            {
                $address = $item->getQuote()->getBillingAddress();
            }
            else
            {
                $address = $item->getQuote()->getShippingAddress();
            }

            return $address;
        }
    }