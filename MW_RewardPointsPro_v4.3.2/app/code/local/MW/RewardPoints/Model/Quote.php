<?php

    class MW_RewardPoints_Model_Quote extends Mage_Core_Model_Abstract
    {
        protected $array_whole_cart = array();
        protected $array_whole_cart_x_y = array();
        protected $array_rule_active = array();

        protected function resetCount()
        {
            $this->array_whole_cart     = array();
            $this->array_whole_cart_x_y = array();
            $this->array_rule_active    = array();
        }

        protected function _getSession()
        {
            return Mage::getSingleton('checkout/session');
        }

        protected function _getCustomer()
        {
            return Mage::getModel('rewardpoints/customer')->load(Mage::getSingleton('customer/session')->getCustomer()->getId());
        }

        public function collectTotalBefore($argv)
        {
            if(Mage::app()->getRequest()->getControllerName() == "multishipping")
            {
                return true;
            }
            if(Mage::helper('rewardpoints')->moduleEnabled())
            {
                $store_id = Mage::app()->getStore()->getId();
                $quote    = $argv->getQuote();

                $address  = $quote->isVirtual() ? $quote->getBillingAddress() : $quote->getShippingAddress();
                $subtotal = $address->getBaseSubtotal();
                $subtotal += $address->getBaseDiscountAmount();
                $spend_point     = $quote->getSpendRewardpointCart();
                $mw_rewardpoints = (int)$quote->getMwRewardpoint();
                $min             = (int)Mage::helper('rewardpoints/data')->getMinPointCheckoutStore($store_id);
                //if($min > 0 && $mw_rewardpoints < $min && $min <= $spend_point){
                if($min > 0 && $mw_rewardpoints < $min)
                {
                    $quote->setMwRewardpoint(0);
                    $quote->setMwRewardpointDiscount(0)->save();
                }

                //$spend_point = (int)Mage::helper('rewardpoints')->getMaxPointToCheckOut();
                $max_points_discount = Mage::helper('rewardpoints')->exchangePointsToMoneys($spend_point, $store_id);
                if($max_points_discount < 0)
                {
                    $max_points_discount = 0;
                }
                //echo $max_points_discount.'aaaaaaaa'.$spend_point;die();
                $rewardpoint_discount = (double)$quote->getMwRewardpointDiscount();
                //$subtotal_after_rewardpoint = $subtotal + $rewardpoint_discount;
                $baseGrandTotal_after_rewardpoint = $quote->getBaseGrandTotal() + $rewardpoint_discount;

                if($rewardpoint_discount > $baseGrandTotal_after_rewardpoint)
                {
                    $quote->setMwRewardpointDiscount($baseGrandTotal_after_rewardpoint);
                    $points = Mage::helper('rewardpoints')->exchangeMoneysToPoints($baseGrandTotal_after_rewardpoint, $store_id);
                    $quote->setMwRewardpoint(Mage::helper('rewardpoints')->roundPoints($points, $store_id))->save();
                }

                if($rewardpoint_discount > $max_points_discount)
                {
                    $quote->setMwRewardpointDiscount($max_points_discount);
                    $quote->setMwRewardpoint(Mage::helper('rewardpoints')->roundPoints($spend_point, $store_id))->save();
                    if($max_points_discount > $baseGrandTotal_after_rewardpoint)
                    {
                        $quote->setMwRewardpointDiscount($baseGrandTotal_after_rewardpoint);
                        $points = Mage::helper('rewardpoints')->exchangeMoneysToPoints($baseGrandTotal_after_rewardpoint, $store_id);
                        $quote->setMwRewardpoint(Mage::helper('rewardpoints')->roundPoints($points, $store_id))->save();
                    }
                }

                if($customer_id = $quote->getCustomerId())
                {
                    $customer_rewarpoint = Mage::getModel('rewardpoints/customer')->load($customer_id)->getMwRewardPoint();
                    $product_sell_point  = 0;
                    foreach ($quote->getAllItems() as $item)
                    {
                        $product_id = $item->getProductId();
                        $qty        = $item->getQty();
                        $product    = Mage::getModel('catalog/product')->load($product_id);

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
                        }
                    }
                    if($product_sell_point > 0 && $customer_rewarpoint < $product_sell_point + $quote->getMwRewardpoint())
                    {
                        //$quote->removeItem($item->getId())->save();
                        $quote->setMwRewardpointDiscount(0)->setMwRewardpoint(0)->save();
                        Mage::getSingleton('checkout/session')->getMessages(true);
                        Mage::getSingleton('checkout/session')->setAllowCheckout(false);
                        $quote->setHasError(true);
                        Mage::getSingleton('checkout/session')->addError(Mage::helper('rewardpoints')->__('You do not have enough points for product in cart.'));
                    }
                    else
                    {
                        Mage::getSingleton('checkout/session')->setAllowCheckout(true);
                    }
                }
                else
                {
                    if(!Mage::getSingleton('customer/session')->isLoggedIn())
                    {
                        Mage::getSingleton('checkout/session')->getMessages(true);
                        Mage::getSingleton('checkout/session')->addNotice(Mage::helper('rewardpoints')->__('For using points to checkout order, please login!'));
                    }
                }

            }
            else
            {
                $quote = $argv->getQuote();
                $quote->setMwRewardpointDiscount(0);
                $quote->setMwRewardpoint(0)->save();
            }
        }

        public function collectTotalAfter($argv)
        {
            if(Mage::helper('rewardpoints')->moduleEnabled())
            {
                $quote   = $argv->getQuote();
                $address = $quote->isVirtual() ? $quote->getBillingAddress() : $quote->getShippingAddress();

                $controller_name = Mage::app()->getRequest()->getControllerName();
                $action_name     = Mage::app()->getRequest()->getActionName();
                $store_id        = Mage::app()->getStore()->getId();
                if($controller_name == "multishipping" && ($action_name == "overview" || $action_name == "overviewPost"))
                {
                    $tax            = 0;
                    $shipping       = 0;
                    $tax_new        = 0;
                    $shipping_new   = 0;
                    $check_tax      = Mage::helper('rewardpoints')->getRedeemedTaxConfig($store_id);
                    $check_shipping = Mage::helper('rewardpoints')->getRedeemedShippingConfig($store_id);

                    $shipping_address = $quote->getAllShippingAddresses();

                    $amount = Mage::getSingleton('core/session')->getMwRewardpointDiscountShowTotal();

                    $point = $quote->getMwRewardpoint();
                    if($amount != 0)
                    {
                        $amount_per_address = $amount / count($shipping_address);
                        $point_per_address  = $point / count($shipping_address);
                    }

                    $total_amount_dec = 0;
                    if($amount != 0)
                    {
                        $max_subtotal = array();
                        foreach ($shipping_address as $address)
                        {
                            $max_subtotal[] = array(
                                'address_id' => $address->getAddressId(),
                                'subtotal'   => $address->getSubtotal()
                            );
                        }
                        $max = $max_subtotal[0];

                        for ($i = 1; $i < count($max_subtotal); $i++)
                        {
                            if($max["subtotal"] < $max_subtotal[$i]["subtotal"])
                            {
                                $max = $max_subtotal[$i];
                            }
                        }

                        foreach ($shipping_address as $address)
                        {
                            $last_memory = 0;
                            $subTotal    = $address->getSubtotal();
                            if($amount_per_address > $subTotal)
                            {
                                $last_memory         = $amount_per_address - $subTotal;
                                $amount_this_address = $subTotal;
                                $amount_per_address += $last_memory;

                            }
                            else
                            {
                                $amount_this_address = $amount_per_address;

                            }
                            $total_amount_dec += $amount_this_address;
                        }
                        /** This value will be added to address have the highest subtotal */
                        $amount_residual    = $amount - $total_amount_dec;
                        $total_amount_dec   = 0;
                        $amount_per_address = $amount / count($shipping_address);
                        $address_to_session = array();
                    }

                    $programs = $this->getEarnProgramResult();
                    foreach ($shipping_address as $address)
                    {
                        /** for earn point */

                        if(!$check_tax)
                        {
                            $tax_new = $address->getBaseTaxAmount();
                        }
                        if(!$check_shipping)
                        {
                            $shipping_new = $address->getBaseShippingInclTax();
                        }

                        $customer_id = $quote->getCustomerId();

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

                        // earn point------------------
                        $earn_reward_point      = 0;
                        $earn_reward_point_cart = 0;

                        $product_sell_point  = 0;
                        $reward_point_detail = array();
                        $array_rule_show     = array();
                        $items               = $address->getAllVisibleItems();
                        foreach ($items as $item)
                        {
                            $reward_point    = 0;
                            $mw_reward_point = 0;
                            $product_id      = $item->getProductId();
                            $qty             = $item->getQty();

                            $mw_reward_point_sell = Mage::getModel('catalog/product')->load($product_id)->getData('mw_reward_point_sell_product');
                            if($mw_reward_point_sell > 0)
                            {
                                $product_sell_point = $product_sell_point + $qty * $mw_reward_point_sell;
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
                                /*foreach ($item->getChildren() as $child) {
                                    $qty_child                  = $child->getQty();
                                    $price_product_withdiscount = $child->getBasePrice() - $child->getBaseDiscountAmount() / $qty_child;

                                    if($appy_reward == MW_RewardPoints_Model_Appyreward::BEFORE) {
                                        if($applyRewardPointsTax == MW_RewardPoints_Model_Appyrewardtax::BEFORE)
                                            $price_product = Mage::helper('checkout')->getPriceInclTax($child);
                                        else
                                            $price_product = $child->getBasePrice();
                                    } else {
                                        if($applyRewardPointsTax == MW_RewardPoints_Model_Appyrewardtax::BEFORE)
                                            $price_product = Mage::helper('checkout')->getPriceInclTax($child) - $child->getBaseDiscountAmount() / $qty_child;
                                        else
                                            $price_product = $price_product_withdiscount;
                                    }

                                    if(( int )$array_spend_reward_point [1] > 0)
                                        $spend_sub_point = $spend_sub_point + $qty * $array_spend_reward_point [1];
                                    if(( double )$array_spend_reward_point [2] > 0)
                                        $spend_sub_money = $spend_sub_money + $qty * $array_spend_reward_point [2];

                                    $reward_point_array = $this->processRuleConfigurableProduct($child, $programs, $qty_child, $price_product, $qty, $baseSubtotalWithDiscount);
                                    $reward_point       = $reward_point_array [1];
                                    $rule_details       = $reward_point_array [2];

                                    foreach ($rule_details as $key => $rule_detail) {
                                        if(!isset ($reward_point_detail [$key]))
                                            $reward_point_detail [$key] = 0;
                                        $reward_point_detail [$key] = $reward_point_detail [$key] + $rule_detail;
                                    }
                                    $earn_reward_point      = $earn_reward_point + $reward_point + $mw_reward_point;
                                    $earn_reward_point_cart = $earn_reward_point_cart + $reward_point;
                                }*/
                            }
                            else
                            {
                                $reward_point_array = $this->processRule($item, $programs, $qty, $price, $baseSubtotalWithDiscount);
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
                                $earn_reward_point      = $earn_reward_point + $mw_reward_point;
                                $earn_reward_point_cart = $reward_point;
                            }
                        }
                        $earn_point_total = $earn_reward_point + $earn_reward_point_cart;
                        $title            = Mage::helper('rewardpoints')->__('You Earn');
                        if($earn_point_total > 0)
                        {
                            $address->addTotal(array(
                                'code'   => "earn_points",
                                'title'  => $title,
                                'value'  => "NO_FORMAT",
                                'text'   => $earn_point_total,
                                'strong' => false
                            ), 'subtotal');
                        }

                        $earnpoint_to_session[$address->getAddressId()] = array(
                            'address_id' => $address->getAddressId(),
                            'earnpoints' => $earn_point_total,
                        );
                        /** [end] earn point */
                        if($amount != 0)
                        {
                            $amount_this_address = 0;
                            $last_memory         = 0;
                            $grandTotal          = $address->getGrandTotal();
                            $subTotal            = $address->getSubtotal();
                            if($amount_per_address > $subTotal)
                            {
                                $last_memory         = $amount_per_address - $subTotal;
                                $amount_this_address = $subTotal;
                                $amount_per_address += $last_memory;

                            }
                            else
                            {
                                $amount_this_address = $amount_per_address;
                            }

                            if($max['address_id'] == $address->getAddressId())
                            {
                                $amount_this_address = $amount_residual + $amount_this_address; //  - $tax_new - $shipping_new;
                            }
                            /** [start] re-collect for each subtotal per address */
                            /** at the moment, then no need, no support */
                            /** [end] re-collect */
                            $point_show = Mage::helper('rewardpoints')->exchangeMoneysToPoints($amount_this_address, $store_id);

                            $title = Mage::helper('rewardpoints')->__('You Redeem (%s)', $point_show);
                            $address->addTotal(array(
                                'code'   => "reward_points",
                                'title'  => $title,
                                'value'  => -$amount_this_address,
                                'strong' => false
                            ));

                            $total_amount_dec += $amount_this_address;
                            $address_to_session[$address->getAddressId()] = array(
                                'address_id'            => $address->getAddressId(),
                                'rewardpoints'          => $point_show,
                                'rewardpoints_discount' => $amount_this_address, //
                            );
                            $address->setMwRewardpoint($point_show);
                            $address->setMwRewardpointDiscountShow($amount_this_address);
                            $address->setMwRewardpointDiscount($amount_this_address);
                            $address->setGrandTotal((float)$address->getGrandTotal() - $amount_this_address);
                            $address->setBaseGrandTotal((float)$address->getBaseGrandTotal() - $amount_this_address);
                        }
                    }

                    if($action_name == "overview")
                    {
                        if($amount != 0)
                        {
                            Mage::getSingleton('core/session')->setQuoteAddressRewardpoint($address_to_session);
                        }
                        Mage::getSingleton('core/session')->setQuoteAddressEarnpoint($earnpoint_to_session);

                    }
                    if($amount != 0)
                    {
                        $quote->setGrandTotal((float)$quote->getGrandTotal() - $total_amount_dec)->save();
                        $quote->setBaseGrandTotal((float)$quote->getBaseGrandTotal() - $total_amount_dec)->save();
                    }
                }
                else
                {
                }
            }
        }

        protected function processRuleConfigurableProduct(Mage_Sales_Model_Quote_Item_Abstract $item, $programs, $qty, $price, $qty_parent, $baseSubtotalWithDiscount)
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
            $this->resetCount();
            $result [1] = $result_reward_point;
            $result [2] = $_result_detail;

            //return  $result_reward_point;
            return $result;
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
            $this->resetCount();
            $result [1] = $result_reward_point;
            $result [2] = $_result_detail;

            //return  $result_reward_point;
            return $result;
        }

        protected function getEarnProgramResult()
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

        protected function getAllProgram($model)
        {
            $programs            = array();
            $program_collections = $model->getCollection();
            foreach ($program_collections as $program_collection)
            {
                $programs [] = $program_collection->getRuleId();
            }

            return $programs;
        }

        protected function getProgramByCustomerGroup($programs, $model)
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

        protected function getProgramByStoreView($programs, $model)
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

        protected function getProgramByEnable($programs, $model)
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

        protected function getProgramByTime($programs, $model)
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

        protected function getProgramByPostion($programs, $model)
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