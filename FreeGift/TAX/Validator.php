<?php
class MW_FreeGift_Model_Validator extends Mage_Core_Model_Abstract
{
    protected $_rules;
    protected $_allRules;
    public function init($websiteId, $customerGroupId, $freegiftCouponCode)
    {
        $this->setWebsiteId($websiteId)->setCustomerGroupId($customerGroupId);
        $key = 'freegift_' . $websiteId . '_' . $customerGroupId;
        if (!isset($this->_rules[$key])) {
            $flagRule = Mage::getSingleton('core/session')->getFlagRule();

            if ($flagRule == "") {
                $collection = Mage::getResourceModel('freegift/salesrule_collection')->addOrder('sort_order', 'DESC')->setValidationFilter($websiteId, $customerGroupId)
                    ->addFieldToFilter('coupon_type', 1);
            } else {
                $arrRule = explode(",",$flagRule);
                $allowRule = $arrRule;
                $collection = Mage::getResourceModel('freegift/salesrule_collection')->addOrder('sort_order', 'DESC')->setValidationFilter($websiteId, $customerGroupId)->addFieldToFilter('rule_id', array('in'=>$allowRule));
                $collection->getSelect()->orWhere('coupon_type = 1');
            }
            $collection->getSelect()->where('((discount_qty > times_used) or (discount_qty = 0))');
            $collection->addFieldToFilter('is_active', 1);
            $collection->load();
            /*getData($collection);*/
            $this->_rules[$key] = $collection;
        }

        $this->_freegift_ids = array();
        return $this;
    }

    // Get all rule active
    public function getAllRuleActive($websiteId,$customerGroupId)
    {
        $key = 'freegift_' . $websiteId . '_' . $customerGroupId;
        if (!isset($this->_allRules[$key])) {
            $collection = Mage::getResourceModel('freegift/salesrule_collection')->addOrder('sort_order', 'DESC')->setValidationFilter($websiteId, $customerGroupId);
            $collection->addFieldToFilter('coupon_type', 2);
            $collection->getSelect()->where('((discount_qty > times_used) or (discount_qty = 0))');
            $collection->addFieldToFilter('is_active', 1);
            $collection->load();
            $this->_allRules[$key] = $collection;
        }
        return $this;
    }

    public function _getAllRules($websiteId,$customerGroupId){
        $key = 'freegift_' . $websiteId . '_' . $customerGroupId;
        return $this->_allRules[$key];
    }

    public function processCoupon($items)
    {
        $data = array();
        foreach($items as $item) {
            $quote   = $item->getQuote();
            $address = $this->_getAddress($item);

            $itemPrice     = $this->_getItemPrice($item);
            $baseItemPrice = $this->_getItemBasePrice($item);

            if ($itemPrice <= 0) {
                return $this;
            }

            $freegiftIds    = array();
            $appliedRuleIds = array();

            //fix item freegift weight to 0
            $weight = 0;
            $qty = 0;
            $items = $address->getAllVisibleItems();
            foreach($items as $it){
                $params = unserialize($it->getOptionByCode('info_buyRequest')->getValue());
                if(!isset($params['freegift']) && !isset($params['free_catalog_gift'])) {
                    $weight = $weight + ($it->getWeight()*$it->getQty());
                    $qty = $qty + $it->getQty();
                }
            }
            foreach ($this->_getAllRules(Mage::app()->getStore()->getWebsiteId(),Mage::getSingleton('customer/session')->getCustomerGroupId()) as $rule) {
                /* @var $rule Mage_SalesRule_Model_Rule */
                $address->setWeight($weight);
                $address->setTotalQty($qty);
                if (!$this->_canProcessRule($rule, $address)) {
                    continue;
                }
                $data[] = $rule->getRuleId();
            }
        }

        return $data;
    }
    protected function _getRules()
    {
        $key = 'freegift_' . $this->getWebsiteId() . '_' . $this->getCustomerGroupId();

        return $this->_rules[$key];
    }
    protected function _getAddress(Mage_Sales_Model_Quote_Item_Abstract $item)
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
    protected function _canProcessRule($rule, $address)
    {
        $temp_rule = Mage::getSingleton('checkout/session')->getRulegifts();

        /**
         * passed all validations, remember to be valid
         */
        $rule->setIsValid(true);

        if($rule->getEnableSocial()== 1 && $rule->getIsActive() ==1){
            array_push($temp_rule,$rule->getData());
            Mage::getSingleton('checkout/session')->setRulegifts($temp_rule);

            $google_plus = Mage::getSingleton('checkout/session')->getGooglePlus();
            $like_fb = Mage::getSingleton('checkout/session')->getLikeFb();
            $share_fb = Mage::getSingleton('checkout/session')->getShareFb();
            $twitter = Mage::getSingleton('checkout/session')->getTwitter();

            if($google_plus == 'true' || $like_fb == 'true' || $share_fb == 'true' || $twitter=='true'){
                $rule->setIsValid(true);
            }else{
                $rule->setIsValid(false);
            }
        }

        //if (!$rule->hasIsValid()) {
        $rule->afterLoad();
        /**
         * quote does not meet rule's conditions
         */

        if (!$rule->validate($address)){
            $rule->setIsValid(false);
            return false;
        }




        return $rule->getIsValid();
    }

    public function process(Mage_Sales_Model_Quote_Item_Abstract $item)
    {
        $quote   = $item->getQuote();
        $address = $this->_getAddress($item);

        $itemPrice     = $this->_getItemPrice($item);
        $baseItemPrice = $this->_getItemBasePrice($item);

        if ($itemPrice <= 0) {
            return $this;
        }
        $freegiftIds    = array();
        $appliedRuleIds = array();
        //fix item freegift weight to 0
        $weight = 0;
        $qty = 0;
        $items = $address->getAllVisibleItems();

        foreach($items as $it){
            $params = unserialize($it->getOptionByCode('info_buyRequest')->getValue());
            if(!isset($params['freegift']) && !isset($params['free_catalog_gift'])) {
                $weight = $weight + ($it->getWeight()*$it->getQty());
                $qty = $qty + $it->getQty();
            }
        }
        $temp_rule = array();
        Mage::getSingleton('checkout/session')->setRulegifts($temp_rule);
        foreach ($this->_getRules() as $rule) {
            /* @var $rule Mage_SalesRule_Model_Rule */
            $address->setWeight($weight);
            $address->setTotalQty($qty);

            $checkTax = (int) Mage::getStoreConfig('tax/cart_display/subtotal');
            if($checkTax != 1) {
                $address->setSubtotal($address->getSubtotalInclTax());
                $address->setBaseSubtotal($address->getBaseSubtotalInclTax());
            }

            if (!$this->_canProcessRule($rule, $address)) {
                continue;
            }

            $appliedRuleIds[$rule->getRuleId()] = $rule->getRuleId();
            $freegiftIds                        = $this->mergeIds($freegiftIds, $rule->getData('gift_product_ids'));

            if ($rule->getStopRulesProcessing()) {
                break;
            }
        }

        $quote->setFreegiftAppliedRuleIds($this->mergeIds($quote->getFreegiftAppliedRuleIds(), $appliedRuleIds));

       $myId = array();
       $quoteid = Mage::getSingleton('checkout/session')->getQuote();
       $cartItems = $quoteid->getAllVisibleItems();
       foreach ($cartItems as $item)
       {
           $productId = $item->getProductId();
           array_push($myId,$productId);
       }

       Mage::getSingleton('checkout/session')->setProductgiftid($myId);

        $quote->setFreegiftIds($this->mergeIds($quote->getFreegiftIds(), $freegiftIds));

        $this->_max_free_item = 1;
        return $this;
    }
    public function mergeIds($a1, $a2, $asString = true)
    {
        if (!is_array($a1)) {
            $a1 = empty($a1) ? array() : explode(',', $a1);
        }
        if (!is_array($a2)) {
            $a2 = empty($a2) ? array() : explode(',', $a2);
        }
        $a = array_unique(array_merge($a1, $a2));
        if ($asString) {
            $a = implode(',', $a);
        }
        return $a;
    }
    protected function _getItemPrice($item)
    {
        $price = $item->getDiscountCalculationPrice();
        return ($price !== null) ? $price : $item->getCalculationPrice();
    }
    protected function _getItemBasePrice($item)
    {
        $price = $item->getDiscountCalculationPrice();
        return ($price !== null) ? $item->getBaseDiscountCalculationPrice() : $item->getBaseCalculationPrice();
    }
    protected function _getItemQty($item, $rule)
    {
        $qty = $item->getTotalQty();
        return $rule->getDiscountQty() ? min($qty, $rule->getDiscountQty()) : $qty;
    }
}
