<?php
    /**
     * User: Anh TO
     * Date: 5/23/14
     * Time: 9:11 AM
     */
    class MW_RewardPoints_Model_Rewrite_Tax_Sales_Total_Quote_Tax extends Mage_Tax_Model_Sales_Total_Quote_Tax
    {
        /**
         * Calculate address total tax based on address subtotal
         *
         * @param   Mage_Sales_Model_Quote_Address $address
         * @param   Varien_Object $taxRateRequest
         * @return  Mage_Tax_Model_Sales_Total_Quote
         */
        protected function _totalBaseCalculation(Mage_Sales_Model_Quote_Address $address, $taxRateRequest)
        {
            $store_id = Mage::app()->getStore()->getId();
            if((int)Mage::helper('rewardpoints')->getRedeemPointsOnTax($store_id) == MW_RewardPoints_Model_Redeemtax::AFTER)
            {
                parent::_totalBaseCalculation($address, $taxRateRequest);

                return;
            }
            $items               = $this->_getAddressItems($address);
            $store               = $address->getQuote()->getStore();
            $taxGroups           = array();
            $itemTaxGroups       = array();
            $catalogPriceInclTax = $this->_config->priceIncludesTax($store);
            /** [Start] Rewardpoint added */
            $countItem = 0;
            foreach ($items as $item)
            {
                if($item->getParentItem())
                {
                    continue;
                }
                $countItem++;
            }

            $pointToRdeem     = Mage::getSingleton('core/session')->getMwRewardpointAfterDrop();
            $amountRedeem     = Mage::helper('core')->currency(Mage::helper('rewardpoints')->exchangePointsToMoneys($pointToRdeem,$store_id), false, false);
            $amountRedeemItem = $this->_calculator->round($amountRedeem / $countItem);

            $lastMemory      = 0;
            $savedReedemItem = array();
            if($amountRedeem > 0)
            {
                foreach ($items as $item)
                {
                    if($item->getParentItem())
                    {
                        continue;
                    }

                    if($item->getHasChildren() && $item->isChildrenCalculated())
                    {
                        continue;
                    }
                    else
                    {
                        $taxAmount = $item->getTaxableAmount();
                        if($taxAmount < $amountRedeemItem + $lastMemory)
                        {
                            $lastMemory           = $amountRedeemItem + $lastMemory - $taxAmount;
                            $amountRedeemThisItem = $taxAmount;
                        }
                        else
                        {
                            $amountRedeemThisItem = $lastMemory + $amountRedeemItem;
                            $lastMemory           = 0;
                        }
                        $savedReedemItem[$item->getItemId()] = $amountRedeemThisItem;
                    }
                }
                foreach ($items as $item)
                {
                    if($item->getParentItem())
                    {
                        continue;
                    }

                    if($item->getHasChildren() && $item->isChildrenCalculated())
                    {
                        $amountRedeemChildItem = $this->_calculator->round(($amountRedeemItem + $lastMemory) / count($item->getChildren()));
                        $lastMemoryChildren    = 0;
                        foreach ($item->getChildren() as $child)
                        {
                            $taxAmount = $child->getTaxableAmount();
                            if($taxAmount < $amountRedeemChildItem + $lastMemoryChildren)
                            {
                                $lastMemoryChildren    = $amountRedeemChildItem + $lastMemoryChildren - $taxAmount;
                                $amountRedeemThisChild = $taxAmount;
                            }
                            else
                            {
                                $amountRedeemThisChild = $lastMemoryChildren + $amountRedeemChildItem;
                                $lastMemoryChildren    = 0;
                            }
                            $savedReedemItem[$child->getItemId()] = $amountRedeemThisChild;
                        }
                    }
                }
            }
            /** [End] Rewardpoint added */
            foreach ($items as $item)
            {
                if($item->getParentItem())
                {
                    continue;
                }

                if($item->getHasChildren() && $item->isChildrenCalculated())
                {
                    foreach ($item->getChildren() as $child)
                    {
                        $this->_totalBaseProcessItemTax(
                            $child, $taxRateRequest, $taxGroups, $itemTaxGroups, $catalogPriceInclTax, (isset($savedReedemItem[$child->getItemId()])) ? $savedReedemItem[$child->getItemId()] : 0);
                    }
                    $this->_recalculateParent($item);
                }
                else
                {
                    $this->_totalBaseProcessItemTax(
                        $item, $taxRateRequest, $taxGroups, $itemTaxGroups, $catalogPriceInclTax, (isset($savedReedemItem[$item->getItemId()])) ? $savedReedemItem[$item->getItemId()] : 0);
                }
            }

            if($address->getQuote()->getTaxesForItems())
            {
                $itemTaxGroups += $address->getQuote()->getTaxesForItems();
            }
            $address->getQuote()->setTaxesForItems($itemTaxGroups);
            foreach ($taxGroups as $taxId => $data)
            {
                if($catalogPriceInclTax)
                {
                    $rate = (float)$taxId;
                }
                else
                {
                    $rate = $data['applied_rates'][0]['percent'];
                }

                $inclTax = $data['incl_tax'];

                $totalTax = array_sum($data['tax']);

                $baseTotalTax = array_sum($data['base_tax']);

                $this->_addAmount($totalTax);
                $this->_addBaseAmount($baseTotalTax);
                $totalTaxRounded     = $this->_calculator->round($totalTax);
                $baseTotalTaxRounded = $this->_calculator->round($totalTaxRounded);
                $this->_saveAppliedTaxes($address, $data['applied_rates'], $totalTaxRounded, $baseTotalTaxRounded, $rate);
            }

            return $this;
        }

        /**
         *
         * @param Mage_Sales_Model_Quote_Item_Abstract $item
         * @param Varien_Object $taxRateRequest
         * @param array $taxGroups
         * @param array $itemTaxGroups
         * @param boolean $catalogPriceInclTax
         */
        protected function _totalBaseProcessItemTax(
            $item, $taxRateRequest, &$taxGroups, &$itemTaxGroups, $catalogPriceInclTax, $amountReedemItem = 0
        )
        {
            $taxRateRequest->setProductClassId($item->getProduct()->getTaxClassId());
            $rate = $this->_calculator->getRate($taxRateRequest);
            $item->setTaxAmount(0);
            $item->setBaseTaxAmount(0);
            $item->setHiddenTaxAmount(0);
            $item->setBaseHiddenTaxAmount(0);
            $item->setTaxPercent($rate);
            $item->setDiscountTaxCompensation(0);
            $rowTotalInclTax            = $item->getRowTotalInclTax();
            $recalculateRowTotalInclTax = false;
            if(!isset($rowTotalInclTax))
            {
                $item->setRowTotalInclTax($item->getTaxableAmount());
                $item->setBaseRowTotalInclTax($item->getBaseTaxableAmount());
                $recalculateRowTotalInclTax = true;
            }

            $appliedRates = $this->_calculator->getAppliedRates($taxRateRequest);
            if($catalogPriceInclTax)
            {
                $taxGroups[(string)$rate]['applied_rates'] = $appliedRates;
                $taxGroups[(string)$rate]['incl_tax']      = $item->getIsPriceInclTax();
                $this->_aggregateTaxPerRate($item, $rate, $taxGroups, null, false, $amountReedemItem);
            }
            else
            {
                //need to calculate each tax separately
                foreach ($appliedRates as $appliedTax)
                {
                    $taxId                              = $appliedTax['id'];
                    $taxRate                            = $appliedTax['percent'];
                    $taxGroups[$taxId]['applied_rates'] = array($appliedTax);
                    $taxGroups[$taxId]['incl_tax']      = $item->getIsPriceInclTax();
                    $this->_aggregateTaxPerRate($item, $taxRate, $taxGroups, $taxId, $recalculateRowTotalInclTax, $amountReedemItem);
                }
                if(version_compare(Mage::getVersion(), '1.8', '>='))
                {
                    //We need to calculate weeeAmountInclTax using multiple tax rate here
                    //because the _calculateWeeeTax and _calculateRowWeeeTax only take one tax rate
                    if($this->_weeeHelper->isEnabled() && $this->_weeeHelper->isTaxable())
                    {
                        $this->_calculateWeeeAmountInclTax($item, $appliedRates, false);
                        $this->_calculateWeeeAmountInclTax($item, $appliedRates, true);
                    }
                }
            }
            if($rate > 0)
            {
                $itemTaxGroups[$item->getId()] = $appliedRates;
            }

            return;
        }

        /**
         * Aggregate row totals per tax rate in array
         *
         * @param   Mage_Sales_Model_Quote_Item_Abstract $item
         * @param   float $rate
         * @param   array $taxGroups
         * @return  Mage_Tax_Model_Sales_Total_Quote
         */
        protected function _aggregateTaxPerRate(
            $item, $rate, &$taxGroups, $taxId = null, $recalculateRowTotalInclTax = false, $amountReedemItem = 0
        )
        {
            $inclTax         = $item->getIsPriceInclTax();
            $rateKey         = ($taxId == null) ? (string)$rate : $taxId;
            $taxSubtotal     = $subtotal = $item->getTaxableAmount();
            $baseTaxSubtotal = $baseSubtotal = $item->getBaseTaxableAmount();
            if(version_compare(Mage::getVersion(), '1.8', '>='))
            {
                //version is 1.6 or greater
                $isWeeeEnabled = $this->_weeeHelper->isEnabled();
                $isWeeeTaxable = $this->_weeeHelper->isTaxable();
            }

            if(!isset($taxGroups[$rateKey]['totals']))
            {
                $taxGroups[$rateKey]['totals']        = array();
                $taxGroups[$rateKey]['base_totals']   = array();
                $taxGroups[$rateKey]['weee_tax']      = array();
                $taxGroups[$rateKey]['base_weee_tax'] = array();
            }

            $hiddenTax                    = null;
            $baseHiddenTax                = null;
            $weeeTax                      = null;
            $baseWeeeTax                  = null;
            $discount                     = 0;
            $rowTaxBeforeDiscount         = 0;
            $baseRowTaxBeforeDiscount     = 0;
            $weeeRowTaxBeforeDiscount     = 0;
            $baseWeeeRowTaxBeforeDiscount = 0;


            switch ($this->_helper->getCalculationSequence($this->_store))
            {
                case Mage_Tax_Model_Calculation::CALC_TAX_BEFORE_DISCOUNT_ON_EXCL:
                case Mage_Tax_Model_Calculation::CALC_TAX_BEFORE_DISCOUNT_ON_INCL:
                    $rowTaxBeforeDiscount     = $this->_calculator->calcTaxAmount($subtotal - $amountReedemItem, $rate, $inclTax, false);
                    $baseRowTaxBeforeDiscount = $this->_calculator->calcTaxAmount($baseSubtotal - $amountReedemItem, $rate, $inclTax, false);
                    if(version_compare(Mage::getVersion(), '1.8', '>='))
                    {
                        if($isWeeeEnabled && $isWeeeTaxable)
                        {
                            $weeeRowTaxBeforeDiscount     = $this->_calculateRowWeeeTax(0, $item, $rate, false);
                            $baseWeeeRowTaxBeforeDiscount = $this->_calculateRowWeeeTax(0, $item, $rate);
                            $rowTaxBeforeDiscount += $weeeRowTaxBeforeDiscount;
                            $baseRowTaxBeforeDiscount += $baseWeeeRowTaxBeforeDiscount;
                            $taxGroups[$rateKey]['weee_tax'][]      = $this->_deltaRound($weeeRowTaxBeforeDiscount,
                                $rateKey, $inclTax);
                            $taxGroups[$rateKey]['base_weee_tax'][] = $this->_deltaRound($baseWeeeRowTaxBeforeDiscount,
                                $rateKey, $inclTax);
                        }
                    }

                    $taxBeforeDiscountRounded     = $rowTax = $this->_deltaRound($rowTaxBeforeDiscount, $rateKey, $inclTax);
                    $baseTaxBeforeDiscountRounded = $baseRowTax = $this->_deltaRound($baseRowTaxBeforeDiscount,
                        $rateKey, $inclTax, 'base');
                    $item->setTaxAmount($item->getTaxAmount() + max(0, $rowTax));
                    $item->setBaseTaxAmount($item->getBaseTaxAmount() + max(0, $baseRowTax));
                    break;
                case Mage_Tax_Model_Calculation::CALC_TAX_AFTER_DISCOUNT_ON_EXCL:
                case Mage_Tax_Model_Calculation::CALC_TAX_AFTER_DISCOUNT_ON_INCL:
                    if($this->_helper->applyTaxOnOriginalPrice($this->_store))
                    {
                        $discount     = $item->getOriginalDiscountAmount();
                        $baseDiscount = $item->getBaseOriginalDiscountAmount();
                    }
                    else
                    {
                        $discount     = $item->getDiscountAmount();
                        $baseDiscount = $item->getBaseDiscountAmount();
                    }

                    if(version_compare(Mage::getVersion(), '1.8', '>='))
                    {
                        //We remove weee discount from discount if weee is not taxed
                        if($isWeeeEnabled)
                        {
                            $discount     = $discount - $item->getWeeeDiscount();
                            $baseDiscount = $baseDiscount - $item->getBaseWeeeDiscount();
                        }
                    }

                    $taxSubtotal     = max($subtotal - $discount, 0);
                    $baseTaxSubtotal = max($baseSubtotal - $baseDiscount, 0);

                    $rowTax     = $this->_calculator->calcTaxAmount($taxSubtotal - $amountReedemItem, $rate, $inclTax, false);
                    $baseRowTax = $this->_calculator->calcTaxAmount($baseTaxSubtotal - $amountReedemItem, $rate, $inclTax, false);
                    if(version_compare(Mage::getVersion(), '1.8', '>='))
                    {
                        if($isWeeeEnabled && $this->_weeeHelper->isTaxable())
                        {
                            $weeeTax = $this->_calculateRowWeeeTax($item->getWeeeDiscount(), $item, $rate, false);
                            $rowTax += $weeeTax;
                            $baseWeeeTax = $this->_calculateRowWeeeTax($item->getBaseWeeeDiscount(), $item, $rate);
                            $baseRowTax += $baseWeeeTax;
                            $taxGroups[$rateKey]['weee_tax'][]      = $weeeTax;
                            $taxGroups[$rateKey]['base_weee_tax'][] = $baseWeeeTax;
                        }
                    }

                    $rowTax     = $this->_deltaRound($rowTax, $rateKey, $inclTax);
                    $baseRowTax = $this->_deltaRound($baseRowTax, $rateKey, $inclTax, 'base');

                    $item->setTaxAmount($item->getTaxAmount() + max(0, $rowTax));
                    $item->setBaseTaxAmount($item->getBaseTaxAmount() + max(0, $baseRowTax));

                    //Calculate the Row taxes before discount
                    $rowTaxBeforeDiscount     = $this->_calculator->calcTaxAmount(
                        $subtotal,
                        $rate,
                        $inclTax,
                        false
                    );
                    $baseRowTaxBeforeDiscount = $this->_calculator->calcTaxAmount(
                        $baseSubtotal,
                        $rate,
                        $inclTax,
                        false
                    );

                    if(version_compare(Mage::getVersion(), '1.8', '>='))
                    {
                        if($isWeeeTaxable)
                        {
                            $weeeRowTaxBeforeDiscount = $this->_calculateRowWeeeTax(0, $item, $rate, false);
                            $rowTaxBeforeDiscount += $weeeRowTaxBeforeDiscount;
                            $baseWeeeRowTaxBeforeDiscount = $this->_calculateRowWeeeTax(0, $item, $rate);
                            $baseRowTaxBeforeDiscount += $baseWeeeRowTaxBeforeDiscount;
                        }
                    }

                    $taxBeforeDiscountRounded     = max(
                        0,
                        $this->_deltaRound($rowTaxBeforeDiscount, $rateKey, $inclTax, 'tax_before_discount')
                    );
                    $baseTaxBeforeDiscountRounded = max(
                        0,
                        $this->_deltaRound($baseRowTaxBeforeDiscount, $rateKey, $inclTax, 'tax_before_discount_base')
                    );

                    if(!$item->getNoDiscount())
                    {
                        if($item->getWeeeTaxApplied())
                        {
                            $item->setDiscountTaxCompensation($item->getDiscountTaxCompensation() +
                                $taxBeforeDiscountRounded - max(0, $rowTax));
                        }
                    }

                    if($inclTax && $discount > 0)
                    {
                        $roundedHiddenTax     = $taxBeforeDiscountRounded - max(0, $rowTax);
                        $baseRoundedHiddenTax = $baseTaxBeforeDiscountRounded - max(0, $baseRowTax);
                        $this->_hiddenTaxes[] = array(
                            'rate_key'   => $rateKey,
                            'qty'        => 1,
                            'item'       => $item,
                            'value'      => $roundedHiddenTax,
                            'base_value' => $baseRoundedHiddenTax,
                            'incl_tax'   => $inclTax,
                        );
                    }
                    break;
            }

            $rowTotalInclTax = $item->getRowTotalInclTax();
            if(!isset($rowTotalInclTax) || $recalculateRowTotalInclTax)
            {
                if($this->_config->priceIncludesTax($this->_store))
                {
                    $item->setRowTotalInclTax($subtotal);
                    $item->setBaseRowTotalInclTax($baseSubtotal);
                }
                else
                {
                    $item->setRowTotalInclTax(
                        $item->getRowTotalInclTax() + $taxBeforeDiscountRounded - $weeeRowTaxBeforeDiscount);
                    $item->setBaseRowTotalInclTax(
                        $item->getBaseRowTotalInclTax()
                        + $baseTaxBeforeDiscountRounded
                        - $baseWeeeRowTaxBeforeDiscount);
                }
            }
            $taxGroups[$rateKey]['totals'][]      = max(0, $taxSubtotal);
            $taxGroups[$rateKey]['base_totals'][] = max(0, $baseTaxSubtotal);
            $taxGroups[$rateKey]['tax'][]         = max(0, $rowTax);
            $taxGroups[$rateKey]['base_tax'][]    = max(0, $baseRowTax);

            return $this;
        }

        /**
         * Add tax totals information to address object
         *
         * @param   Mage_Sales_Model_Quote_Address $address
         * @return  Mage_Tax_Model_Sales_Total_Quote
         */
        public function fetch(Mage_Sales_Model_Quote_Address $address)
        {
            $applied = $address->getAppliedTaxes();
            $store   = $address->getQuote()->getStore();
            $amount  = $address->getTaxAmount();

            $items                   = $this->_getAddressItems($address);
            $discountTaxCompensation = 0;
            foreach ($items as $item)
            {
                $discountTaxCompensation += $item->getDiscountTaxCompensation();
            }
            $taxAmount = $amount + $discountTaxCompensation;

            $area = null;
            if($this->_config->displayCartTaxWithGrandTotal($store) && $address->getGrandTotal())
            {
                $area = 'taxes';
            }

            if(($amount != 0) || ($this->_config->displayCartZeroTax($store)))
            {
                $address->addTotal(array(
                    'code'      => $this->getCode(),
                    'title'     => Mage::helper('tax')->__('Tax'),
                    'full_info' => $applied ? $applied : array(),
                    'value'     => $amount,
                    'area'      => $area
                ));
            }

            $store = $address->getQuote()->getStore();
            /**
             * Modify subtotal
             */
            if($this->_config->displayCartSubtotalBoth($store) || $this->_config->displayCartSubtotalInclTax($store))
            {
                if($address->getSubtotalInclTax() > 0)
                {
                    $subtotalInclTax = $address->getSubtotalInclTax();
                }
                else
                {
                    $subtotalInclTax = $address->getSubtotal() + $taxAmount - $address->getShippingTaxAmount();
                }
                $address->addTotal(array(
                    'code'           => 'subtotal',
                    'title'          => Mage::helper('sales')->__('Subtotal'),
                    'value'          => $subtotalInclTax,
                    'value_incl_tax' => $subtotalInclTax,
                    'value_excl_tax' => $address->getSubtotal(),
                ));
            }

            return $this;
        }
    }