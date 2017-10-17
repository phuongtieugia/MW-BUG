<?php
class MW_FreeGift_Model_Quote_Address_Total_Freegift extends Mage_Sales_Model_Quote_Address_Total_Abstract
{
	protected function _getMaxFreeItem()
	{        
		return sizeof(Mage::getModel('freegift/salesrule')->getCollection());
	}
	protected function _initProduct($productId)
	{
		if ($productId) {
			$product = Mage::getModel('catalog/product')
				->setStoreId(Mage::app()->getStore()->getId())
				->load($productId);
			if ($product->getId()) {
				return $product;
			}
		}
		return false;
	}
	
	protected function _getFreeGiftItemByGiftKey($key, $quote)
	{
		foreach($quote->getAllItems() as $item)
		{
			$params = unserialize($item->getOptionByCode('info_buyRequest')->getValue());
			if(isset($params['freegift_key']) && ($params['freegift_key'] == $key)){
                return $item;
            }
		}
		return false;
	}
	protected function _canProcessRule($rule, $address)
	{
		if(!$rule->getData('is_active')){
			return false;
		}
		if($rule->getData('discount_qty') && ($rule->getData('discount_qty') <= $rule->getData('times_used'))){
			return false;
		}
		if (!$rule->hasIsValid()) {
			$rule->afterLoad();
			if (!$rule->validate($address)) {
				$rule->setIsValid(false);
				return false;
			}
			$rule->setIsValid(true);
		}
		return $rule->getIsValid();
		
	}
	public function collect(Mage_Sales_Model_Quote_Address $address)
	{
		
		if(Mage::app()->getFrontController()->getRequest()->getControllerName() == 'onepage'){
			return $this;
		}
		parent::collect($address);
		/*Mage::getModel('sales/quote_address_total_tax')->collect($address);*/
		if(!Mage::getStoreConfig('freegift/config/enabled')) return false;
		$quote = $address->getQuote();
		$eventArgs = array(
			'website_id'=>Mage::app()->getStore($quote->getStoreId())->getWebsiteId(),
			'customer_group_id'=>$quote->getCustomerGroupId(),
			'freegift_coupon_code'=>$quote->getFreegiftCouponCode(),
		);
		$items = $address->getAllVisibleItems();
		if (!count($items)) {
			return $this;
		}
		$quote->setFreegiftAppliedRuleIds('');
		$quote->setFreegiftIds('');
		$totalQty = $address->getData('total_qty');

		foreach($items as $item){
			if((isset($params['freegift']) && $params['freegift']) ||(isset($params['free_catalog_gift']) && $params['free_catalog_gift']) || (isset($params['freegift_with_code']) && $params['freegift_with_code'])) {
				$totalQty --;
			}
		}

		$address->setData('total_qty',$totalQty)->save();
		foreach($items as $item){
			$params = unserialize($item->getOptionByCode('info_buyRequest')->getValue());
			//mage::log($params);
			if ($item->getHasChildren() && $item->isChildrenCalculated() && !isset($params['bundle_option'])) {
				foreach ($item->getChildren() as $child) {
					$eventArgs['item'] = $child;
					Mage::dispatchEvent('freegift_quote_address_freegift_item', $eventArgs);
				}
			}else {
                $_product = $item->getProduct();
                $_resource = $_product->getResource();
                $attrs = $_product->getAttributes();
                $__product        = Mage::getModel('catalog/product')->load($_product->getId());
                foreach($attrs as $attr){
                    try{
                        //$_product->setData($attr->getAttributeCode(), $_product->getData($attr->getAttributeCode()));
                        //$_product->setData($attr->getAttributeCode(), Mage::getResourceModel('catalog/product')->getAttributeRawValue($_product->getId(), $attr->getAttributeCode(), Mage::app()->getStore()));
                    }catch (Exception $e){
                        $e->getMessage();
                    }
                }

				$eventArgs['item'] = $item;
				Mage::dispatchEvent('freegift_quote_address_freegift_item', $eventArgs);
			}
		}

		$countFreeItem = 0;
		$messages = '';
		$freeProductIds = explode(",",$quote->getFreegiftIds());
		/*Reset free gift coupon code */
		$quote->setFreegiftCouponCode('');
		$appliedCoupon = array();
		$productDublicate = array();

        foreach($items as $item){
            $params = unserialize($item->getOptionByCode('info_buyRequest')->getValue());
            if(isset($params['apllied_rule']) && $params['apllied_rule']){
                if($productDublicate != null && in_array($item->getProductId(),$productDublicate[$params['apllied_rule']])){
                    $quote->removeItem($item->getId());
                }
                else{
                    $productDublicate[$params['apllied_rule']][] = $item->getProductId();

                }
            }
            if(isset($params['rule_id']) && $params['rule_id']) {
                if($productDublicate != null && in_array($item->getProductId(),$productDublicate[$params['rule_id']])){
                    $quote->removeItem($item->getId());
                }
                else{
                    $productDublicate[$params['rule_id']][] = $item->getProductId();

                }
            }
            if(isset($params['freegift']) && $params['freegift']) {
                //continue;
                //return $this;
                if(!$item->getParentItem()) $countFreeItem ++;
                if($freeProductIds[0] == "") $quote->removeItem($item->getId());

                if(in_array($item->getProductId(),$freeProductIds)){
                    if($item->getQty() > 1){
                        $item->setQty(1);
                    }
                    $product_id = $item->getProductId();
                    Mage::getModel('freegift/observer')->setDefaultQuoteItem($item);
                }else{
                    if($parentItem = $item->getParentItem()){
                    }else{
                        $quote->removeItem($item->getId());
                    }
                }
            }

            if(isset($params['free_catalog_gift']) && $params['free_catalog_gift']){
                continue;
                $quoteItem = $this->_getFreeGiftItemByGiftKey($params['freegift_parent_key'], $quote);
                if($quoteItem){
                    $product_id = $item->getProductId();
                    /* Added by ANH TO */
                    $_product        = Mage::getModel('catalog/product')->load($product_id);
                    $_quote = Mage::getModel('sales/quote')->load($quoteItem->getQuoteId());

                    $stock_qty = (int)Mage::getModel('cataloginventory/stock_item')->loadByProduct($_product)->getQty();
                    if($quoteItem->getQty() > $stock_qty){
                        $qty_4gift = $stock_qty;
                    }else{
                        $qty_4gift = $quoteItem->getQty();
                    }
                    if($qty_4gift > 0){
                        Mage::getModel('freegift/observer')->setDefaultQuoteItem($item, $qty_4gift);
                    }

                }else{
                    $quote->removeItem($item->getId());
                }
            }

            if(isset($params['freegift_with_code']) && $params['freegift_with_code']){
                if(!in_array($params['freegift_coupon_code'], $appliedCoupon)){
                    $appliedCoupon[] = $params['freegift_coupon_code'];
                }

                $rule = Mage::getModel('freegift/salesrule')->load((isset($params['rule_id']) ? $params['rule_id'] : $params['apllied_rule']));
                if(!$this->_canProcessRule($rule, $address)){
                    $quote->removeItem($item->getId());
                    continue;
                }
                if(!in_array($item->getProductId(),$freeProductIds)) $quote->removeItem($item->getId());
                if($freeProductIds[0] == "") $quote->removeItem($item->getId());
                $product_id = $item->getProductId();
                Mage::getModel('freegift/observer')->setDefaultQuoteItem($item, 1);
            }
        }

		if(sizeof($appliedCoupon)) $quote->setFreegiftCouponCode(serialize($appliedCoupon));
		$ruleIds = $this->_getRuleApplieQuote($quote);
		$numberFreeItems = array();
		$cntItemFreeGift = array();
		if(is_array($ruleIds)){
	        foreach ($ruleIds as $id) {
				$dem = 0;
				$rule = Mage::getModel('freegift/salesrule')->load($id);
				$ruleId = $rule['rule_id'];
				$numberFreeItems[$ruleId] = $rule['number_of_free_gift'];
				$giftProductIds = $this->_getProductIdByRuleFree($ruleId,$quote);
				foreach($items as $item){
					$itemName = $item->getName();
					$params = unserialize($item->getOptionByCode('info_buyRequest')->getValue());
					$rule_id = "";
					$apllied_rule = "";

					if(isset($params['apllied_rule'])){
						$apllied_rule = $params['apllied_rule'];
						if (in_array($item->getProductId(), $giftProductIds) && !isset($params['free_catalog_gift']) && $apllied_rule == $ruleId) {
			            	$dem++;
				        }
						if (in_array($item->getProductId(), $giftProductIds) && !isset($params['free_catalog_gift']) && !in_array($apllied_rule,$ruleIds)) {
				            $quote->removeItem($item->getId());
				        }
					}
			        if(isset($params['rule_id'])){
						$rule_id = $params['rule_id'];
						if (in_array($item->getProductId(), $giftProductIds) && !isset($params['free_catalog_gift']) && $rule_id == $ruleId)
						{
			            	$dem++;
				        }
						if (in_array($item->getProductId(), $giftProductIds) && !isset($params['free_catalog_gift']) && !in_array($rule_id,$ruleIds))
						{
				            $quote->removeItem($item->getId());
				        }
					}
				}
				if($dem > $numberFreeItems[$ruleId]){
					$quote->removeItem($item->getId());
				}
			}
		}

		return $this;
	}
	
	protected function _getProductIdByRuleFree($ruleId,$quote)
    {
        $rules = Mage::getModel('freegift/salesrule')->load($ruleId);        
		$giftProductIds = explode(',', $rules->getGiftProductIds());
		$listProductFreeGift = array();
		foreach($giftProductIds as $productId){
			$dis = $this->_displayFreeGift($productId,$quote);
			if($dis) $listProductFreeGift[] = $productId;			
		}
        return $listProductFreeGift;
    }
	
	protected function _displayFreeGift($productId,$quote)
	{
		$freegift         = Mage::getModel('catalog/product')->load($productId);
		$productType	= $freegift->getTypeID();	
		$visibility 	  = $freegift->getVisibility();
		if($visibility == MW_FreeGift_Model_Visibility::NOT_VISIBLE_INDIVIDUALLY) return false;		
		$product_qty      = (int) Mage::getModel('cataloginventory/stock_item')->loadByProduct($freegift)->getQty();
		$productIsInStock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($freegift)->getIsInStock();		
		$qtyNow           = $this->_checkQtyProduct($freegift->getId(), $product_qty,$quote);
		if($productType == "configurable"){
			if ($productIsInStock == 1 && $freegift->getStatus() == 1) {
				return true;
			}
		}
		else{
			if ($qtyNow > 0 && $productIsInStock == 1 && $freegift->getStatus() == 1) {
				return true;
			}	
		}		
		return false;
	}
	
	protected function _checkQtyProduct($productId, $qty,$quote)
	{		
		$items      = $quote->getAllVisibleItems();
		foreach ($items as $item) {
			$prId  = $item->getProductId();
			$prQty = $item->getQty();
			if ($productId == $prId) {
				return $qty - $prQty;
			}
		}
		return $qty;
	}
	
	protected function _getRuleApplieQuote($quote)
    {
        $items = $quote->getAllVisibleItems();
        if (count($items) < 1) {        		
            $quote->setFreegiftAppliedRuleIds('');
            $quote->setFreegiftIds('');
        }
        if ($ruleids = $quote->getFreegiftAppliedRuleIds()) {			
            return explode(",", $ruleids);
        }		
        return false;
    }
	
	
}
