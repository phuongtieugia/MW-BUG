<?php
class MW_FreeGift_Block_Product extends Mage_Core_Block_Template
{
    protected $_priceBlock = array();

    protected $_block = 'catalog/product_price';

    protected $_priceBlockDefaultTemplate = 'catalog/product/price.phtml';

    protected $_tierPriceDefaultTemplate = 'catalog/product/view/tierprices.phtml';

    protected $_priceBlockTypes = array();
    protected $_ruleArr = array();
    protected $_numberFreeAllow = 0;
    protected $_free_product = array();
    public function _prepareLayout()
    {
        $this->addProductsByRuleCart();
        return parent::_prepareLayout();
    }
    public function _beforeToHtml()
    {
        if ($this->getVertical())
            $this->setTemplate('mw_freegift/freegift_vertical.phtml');
        else
            $this->setTemplate('mw_freegift/freegift.phtml');
    }

    public function getPriceBlockTemplate()
    {
        return $this->_getData('freegift_price_block_template');
    }
    protected function _getPriceBlock($productTypeId)
    {
        if (!isset($this->_priceBlock[$productTypeId])) {
            $block = $this->_block;
            if (isset($this->_priceBlockTypes[$productTypeId])) {
                if ($this->_priceBlockTypes[$productTypeId]['block'] != '') {
                    $block = $this->_priceBlockTypes[$productTypeId]['block'];
                }
            }
            $this->_priceBlock[$productTypeId] = $this->getLayout()->createBlock($block);
        }
        return $this->_priceBlock[$productTypeId];
    }

    protected function _getPriceBlockTemplate($productTypeId)
    {
        if (isset($this->_priceBlockTypes[$productTypeId])) {
            if ($this->_priceBlockTypes[$productTypeId]['template'] != '') {
                return $this->_priceBlockTypes[$productTypeId]['template'];
            }
        }
        return $this->_priceBlockDefaultTemplate;
    }


    /**
     * Prepares and returns block to render some product type
     *
     * @param string $productType
     * @return Mage_Core_Block_Template
     */
    public function _preparePriceRenderer($productType)
    {
        return $this->_getPriceBlock($productType)->setTemplate($this->_getPriceBlockTemplate($productType))->setUseLinkForAsLowAs($this->_useLinkForAsLowAs);
    }

    /**
     * Returns product price block html
     *
     * @param Mage_Catalog_Model_Product $product
     * @param boolean $displayMinimalPrice
     * @param string $idSuffix
     * @return string
     */
    public function getPriceHtml($product, $displayMinimalPrice = false, $idSuffix = '')
    {
        return $this->_preparePriceRenderer($product->getTypeId())->setProduct($product)->setDisplayMinimalPrice($displayMinimalPrice)->setIdSuffix($idSuffix)->toHtml();
    }

    public function getRuleByFreeProductId($productId)
    {
        $quote        = Mage::getSingleton('checkout/session')->getQuote();
        $aplliedRules = $quote->getFreegiftAppliedRuleIds();
        $aplliedRules = explode(',', $aplliedRules);
        foreach ($aplliedRules as $rule_id) {
            $rule       = Mage::getModel('freegift/salesrule')->load($rule_id);
            $productIds = explode(',', $rule->getData('gift_product_ids'));
            if (in_array($productId, $productIds)) {
                return $rule;
            }
        }
        return false;
    }

    public function getRulesByProductId($productId)
    {
        $quote        = Mage::getSingleton('checkout/session')->getQuote();
        $aplliedRules = $quote->getFreegiftAppliedRuleIds();
        $aplliedRules = explode(',', $aplliedRules);
        $rules = array();
        foreach ($aplliedRules as $rule_id) {
            $rule       = Mage::getModel('freegift/salesrule')->load($rule_id);
            $productIds = explode(',', $rule->getData('gift_product_ids'));
            if (in_array($productId, $productIds)) {
                $rules[] = $rule;
            }
        }
        return $rules;
    }

    public function getFreeProducts()
    {
        $items = Mage::getSingleton('checkout/session')->getQuote()->getAllVisibleItems();
        if (count($items) < 1) {
            $quote = Mage::getSingleton('checkout/session')->getQuote();
            $quote->setFreegiftAppliedRuleIds('');
            $quote->setFreegiftIds('');
        }
        $listProduct = array();

        if ($freeids = Mage::getSingleton('checkout/session')->getQuote()->getFreegiftIds()) {
            $this->_free_product = explode(",", $freeids);
        }

        return $this->_free_product;
    }

    public function getFreeGiftCatalogProduct(){
        $items = Mage::getSingleton('checkout/session')->getQuote()->getAllVisibleItems();
        $missingGiftProducts = array();
        foreach($items as $item){
            $params = unserialize($item->getOptionByCode('info_buyRequest')->getValue());
            if(!isset($params['freegift_key'])){
                if($item->getProduct()->getTypeId() == 'bundle' || $item->getProduct()->getTypeId() == 'configurable'){
                    /** This loop is for product type: bundle */
                    foreach($item->getChildren() as $_item){
                        $_params = unserialize($_item->getOptionByCode('info_buyRequest')->getValue());
                        if(isset($_params['freegift_key'])){
                            $_quote = Mage::getModel('sales/quote')->load($_item->getQuoteId());
                            $_quotes_session  = Mage::getModel('sales/quote_item')->getCollection()
                                ->setQuote($_quote)
                                ->addFieldToFilter('product_id', $_params['product'])
                                ->getFirstItem();

                            /* Re-save infoBuy_request */
                            $collection  = Mage::getModel('sales/quote_item_option')->getCollection();
                            $con         = Mage::getModel('core/resource')->getConnection('core_write');
                            $sql = "UPDATE {$collection->getTable('sales/quote_item_option')} SET value= '".serialize($_params)."' WHERE product_id={$_params['product']} AND code = 'info_buyRequest'";
                            $params = $_params;
                            $con->query($sql);
                            break;
                        }
                    }
                }
            }
            /* If item is gift product */
            if ((isset($params['free_catalog_gift']) && $params['free_catalog_gift']) || (isset($params['freegift']) && $params['freegift']) || (isset($params['freegift_with_code']) && $params['freegift_with_code']))
                continue;

            if(isset($params['apllied_catalog_rules']) && $params['apllied_catalog_rules']){
                $now = Mage::getModel('core/date')->date('Y-m-d');
                $_infoRequest   = unserialize($params['apllied_catalog_rules']);
                foreach($_infoRequest as $ruleId){
                    $rule = Mage::getModel('freegift/rule')->load($ruleId);
                    if(!$rule->getIsActive()){
                        continue;
                    }
                    if ($rule->getData('discount_qty') && ($rule->getData('discount_qty') < $rule->getData('times_used'))) {
                        continue;
                    }
                    /** get condition buy X get Y */
                    $custom_cdn = unserialize($rule->getconditionCustomized());
                    if ((!$rule->getFromDate() || $now >= $rule->getFromDate()) && (!$rule->getToDate() || $now <= $rule->getToDate())) {
                        $_product        = Mage::getModel('catalog/product')->load($item->getProductId());
                        $giftProductIds = explode(',', $rule->getGiftProductIds());
                        $chiildGiftIds = Mage::getModel('freegift/observer')->getChildrenGiftProducts($params['freegift_key']);

                        foreach($giftProductIds as $key => $productId){
                            if(!in_array($productId, $chiildGiftIds)){
                                if(isset($custom_cdn['buy_x_get_y'])){
                                    if($item->getQty() < $custom_cdn['buy_x_get_y']['bx']){
                                        continue;
                                    }
                                }
                                $missingGiftProducts[$item->getId()][$rule->getRuleId()][] = $productId;
                            }
                        }
                    }else{
                        continue;
                    }
                }
            }
        }
        return $missingGiftProducts;
    }

    public function getNumberOfAddedFreeItems(){
        $items         = Mage::getSingleton('checkout/session')->getQuote()->getAllVisibleItems();
        $countFreeItem = 0;
        foreach ($items as $item) {
            $params = unserialize($item->getOptionByCode('info_buyRequest')->getValue());
            if (isset($params['freegift']) && $params['freegift']) {
                $countFreeItem++;
            }
        }
        return $countFreeItem;
    }



    public function getMaxFreeItem()
    {
        $kbc = $this->_free_product;

        $arr = array();
        if ($kbc != null) {
            foreach ($kbc as $value) {
                $rules = $this->getRulesByProductId($value);
                if ($rules){
                    foreach($rules as $rule){
                        $arr[$rule->getId()] = $rule->getNumberOfFreeGift();
                    }
                }
            }
        }
        return $arr;
    }

    public function getNumberOfFreeGift()
    {
        $kbc = $this->getFreeProducts();
        $dem = 0;
        if ($kbc != null) {
            foreach ($kbc as $value) {
                $abc = $this->getRuleByFreeProductId($value);
                if ($abc)
                    $arr[] = $abc->getId();
            }
            ksort($arr);
            for ($i = 0; $i < count((array_unique($arr))); $i++) {
                $rule = Mage::getModel('freegift/salesrule')->load($arr[$i]);
                $dem += $rule->getNumberOfFreeGift();
            }
        }
        return $dem;
    }

    public function _toHtml()
    {
        if (!Mage::getStoreConfig('freegift/config/enabled'))
            return '';

        $html = $this->renderView();
        return $html;
    }

    /**
     * Retrieve url for add product to cart
     * Will return product view page URL if product has required options
     *
     * @param Mage_Catalog_Model_Product $product
     * @param array $additional
     * @return string
     */
    public function getAddToCartUrl($product, $additional = array())
    {
        $isRequire = false;
        foreach ($product->getOptions() as $o) {
            if($o->getIsRequire()) $isRequire = true;
        }
        if ($product->getTypeInstance(true)->hasRequiredOptions($product) || $product->isConfigurable() || $isRequire) {
            if (!isset($additional['_escape'])) {
                $additional['_escape'] = true;
            }
            if (!isset($additional['_query'])) {
                $additional['_query'] = array();
            }
            $additional['_query']['options']  = 'cart';
            $additional['_query']['freegift'] = $additional['freegift'];
            if(isset($additional['rule_id']))
                $additional['_query']['apllied_rule']  = $additional['rule_id'];
            if(isset($additional['free_catalog_gift']))
                $additional['_query']['free_catalog_gift'] = $additional['free_catalog_gift'];
            if(isset($additional['freegift_with_code']))
                $additional['_query']['freegift_with_code'] = $additional['freegift_with_code'];
            if(isset($additional['freegift_coupon_code']))
                $additional['_query']['freegift_coupon_code'] = $additional['freegift_coupon_code'];
            if (isset($additional['apllied_rule'])) {

                $additional['_query']['apllied_rule'] = $additional['apllied_rule'];
            }
            return $this->getProductUrl($product, $additional);
        }
        if($product->isGrouped()){
            $additional['_query']['freegift'] = $additional['freegift'];
            if(isset($additional['rule_id']))
                $additional['_query']['apllied_rule']  = $additional['rule_id'];
            if(isset($additional['freegift_with_code']))
                $additional['_query']['freegift_with_code'] = $additional['freegift_with_code'];
            if(isset($additional['freegift_coupon_code']))
                $additional['_query']['freegift_coupon_code'] = $additional['freegift_coupon_code'];
            if (isset($additional['apllied_rule'])) {

                $additional['_query']['apllied_rule'] = $additional['apllied_rule'];
            }
            return $this->getProductUrl($product, $additional);
        }
        return $this->helper('checkout/cart')->getAddUrl($product, $additional);
    }
    /**
     * Retrieve Product URL using UrlDataObject
     *
     * @param Mage_Catalog_Model_Product $product
     * @param array $additional the route params
     * @return string
     */
    public function getProductUrl($product, $additional = array())
    {
        if ($this->hasProductUrl($product)) {
            if (!isset($additional['_escape'])) {
                $additional['_escape'] = true;
            }
            return $product->getUrlModel()->getUrl($product, $additional);
        }

        return '#';
    }

    /**
     * Check Product has URL
     *
     * @param Mage_Catalog_Model_Product $product
     * @return bool
     */
    public function hasProductUrl($product)
    {
        if ($product->getVisibleInSiteVisibilities()) {
            return true;
        }
        if ($product->hasUrlDataObject()) {
            if (in_array($product->hasUrlDataObject()->getVisibility(), $product->getVisibleInSiteVisibilities())) {
                return true;
            }
        }

        return false;
    }

    public function getRuleFreeProductIds($productId)
    {
        $rules = Mage::getModel('freegift/salesrule')->getCollection();
        foreach ($rules as $rule) {
            $productIds = explode(',', $rule->getData('gift_product_ids'));
            if (in_array($productId, $productIds)) {
                return $rule;
            }
        }
        return false;
    }

    public function getProductIdByRuleFree($ruleId)
    {
        $rules = Mage::getModel('freegift/salesrule')->load($ruleId);
        $giftProductIds = explode(',', $rules->getGiftProductIds());
        return $giftProductIds;
    }

    public function getRuleApplieQuote()
    {
        $items = Mage::getSingleton('checkout/session')->getQuote()->getAllVisibleItems();

        if (count($items) < 1) {
            $quote = Mage::getSingleton('checkout/session')->getQuote();
            $quote->setFreegiftAppliedRuleIds('');
            $quote->setFreegiftIds('');
        }
        if ($ruleids = Mage::getSingleton('checkout/session')->getQuote()->getFreegiftAppliedRuleIds()) {
            return explode(",", $ruleids);
        }
        return false;
    }

    public function checkProductInQuote($ruleId, $productId)
    {
        $items          = Mage::getSingleton('checkout/session')->getQuote()->getAllVisibleItems();
        $dem            = 0;
        $rules          = Mage::getModel('freegift/salesrule')->load($ruleId);
        $giftProductIds = explode(',', $rules->getGiftProductIds());
        $max            = $rules->getNumberOfFreeGift();
        foreach ($items as $it) {
            $productQuote = $it->getProductId();
            $params1      = unserialize($it->getOptionByCode('info_buyRequest')->getValue());
            if (isset($params1['apllied_rule'])) {
                if (in_array($productQuote, $giftProductIds) && !isset($params1['free_catalog_gift']) && $params1['apllied_rule'] == $ruleId) {
                    $dem++;
                }
                if ($productQuote == $productId && $params1['apllied_rule'] == $ruleId) {
                    return false;
                }
            }
            if (isset($params1['apllied_rules'])) {
                $ruleAppli = unserialize($params1['apllied_rules']);
                if ($productQuote == $productId && $ruleAppli[0] == $ruleId) {
                    return false;
                }
            }
            if (isset($params1['rule_id'])) {
                $rule_id = $params1['rule_id'];
                if (in_array($productQuote, $giftProductIds) && !isset($params1['free_catalog_gift']) && $rule_id == $ruleId) {
                    $dem++;
                }
                if ($productQuote == $productId && $rule_id == $ruleId) {
                    return false;
                }
            }
        }
        if ($dem >= $max) {
            return false;
        }
        return true;
    }

    public function _canAddFreeGift($ruleId,$productId){
        $canAdd = true;
        $items  = Mage::getSingleton('checkout/session')->getQuote()->getAllVisibleItems();
        foreach ($items as $it) {
            $params = unserialize($it->getOptionByCode('info_buyRequest')->getValue());
            if(isset($params['apllied_rule'])){
                if($params['apllied_rule'] == $ruleId && $params['product'] == $productId) $canAdd = false;
            }
            if(isset($params['rule_id'])){
                if($params['rule_id'] == $ruleId && $params['product'] == $productId) $canAdd = false;
            }
        }
        return $canAdd;
    }
    public function addProductsByRuleCart(){
        $cart    = Mage::getSingleton('checkout/cart');
        $ruleApplieFree  = $this->getRuleApplieQuote();
        $productIds      = $this->getFreeProducts();
        $maxFreeItems    = $this->getMaxFreeItem();

        foreach ($maxFreeItems as $key => $value) {
            $ruleId        = $key;
            $this->_ruleArr[] = $ruleId;
            $productByRule = $this->getProductIdByRuleFree($ruleId);
            if ($ruleId == $key) {
                $maxFree = $value;

                // Auto add product to cart if numberoffreegift equal freegift item.
                if (count($productByRule) <= $maxFree) {
                    foreach ($productByRule as $proId) {
                        $canAdd = $this->_canAddFreeGift($key,$proId);

                        if($canAdd){
                            $product = Mage::getModel('catalog/product')->load($proId);
                            $inStock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product)->getIsInStock();

                            if ($inStock) {
                                $isRequire = false;
                                foreach ($product->getOptions() as $o) {
                                    if($o->getIsRequire()) $isRequire = true;
                                }
                                if(($product->getTypeId()=='simple') && !$product->getTypeInstance(true)->hasRequiredOptions($product) && !$isRequire){
                                    $rule = Mage::getModel('freegift/salesrule')->load($ruleId);
                                    $params1 = array(
                                        'product' => $proId, // This would be $product->getId()
                                        'qty' => 1,
                                        'freegift' => 1,
                                        'apllied_rule' => $ruleId,
                                        'in_cart' => 1,
                                        'text_gift' => array(
                                            'label' => 'Free Gift',
                                            'value' => $rule->getName()
                                        )
                                    );
                                    $product->addCustomOption('freegift', 1);
                                    $cart->addProduct($product, $params1);
                                    $cart->save();
                                    Mage::helper('checkout/cart')->getCart()->setCartWasUpdated(false);
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    public function _displayFreeGift()
    {
        $ruleApplieFree  = $this->getRuleApplieQuote();
        $productIds      = $this->getFreeProducts();
        $maxFreeItems    = $this->getMaxFreeItem();
        $itemsFirst      = Mage::getSingleton('checkout/session')->getQuote()->getAllVisibleItems();
        $strFirst        = "";
        $display 		 = true;
        $skip = false;
        if ($productIds) {
            /* start check for rules */
            $items         = Mage::getSingleton('checkout/session')->getQuote()->getAllVisibleItems();
            $countFreeGift = 0;
            foreach ($items as $item) {
                $params1 = unserialize($item->getOptionByCode('info_buyRequest')->getValue());
                $ruleApply = "";
                if(isset($params1['rule_id'])) $ruleApply = $params1['rule_id'];
                else if(isset($params1['apllied_rule'])){
                    /*$countFreeGift++;*/
                    $ruleApply = $params1['apllied_rule'];
                }
                else $ruleApply = "";
                if ((isset($params1['freegift']) || isset($params1['freegift_coupon_code'])) && in_array($ruleApply, $this->_ruleArr)) {
                    $countFreeGift++;
                }
            }

            if ($countFreeGift >= $this->_numberFreeAllow) {
                $display = false;
            }
        }else{
            $display = false;
        }

        return $display;
    }

    public function _getFreeGiftItems($ruleApplieFree = false)
    {
        $ruleApplieFree  = (!$ruleApplieFree) ? $this->getRuleApplieQuote() : $ruleApplieFree;
        $productIds      = $this->getFreeProducts();
        $maxFreeItems    = $this->getMaxFreeItem();
        $cartFirst       = array();
        $numberFreeAllow = 0;
        $itemsFirst      = Mage::getSingleton('checkout/session')->getQuote()->getAllVisibleItems();
        $proIdLast       = array();
        $strFirst        = "";
        $countFreeGift   = 0;
        $productIdInQuote = array();
        if ($productIds) {
            foreach ($itemsFirst as $it) {
                $params = unserialize($it->getOptionByCode('info_buyRequest')->getValue());
                if (!in_array($it->getProductId(), $productIds) || isset($params['free_catalog_gift'])) {
                    $cartFirst[] = $it->getProductId();
                } else if (!isset($params['freegift'])) {
                } else {
                    $proIdLast[] = $it->getProductId();
                }
                if (isset($params['freegift_coupon_code'])) {
                    $proIdLast[] = $it->getProductId();
                }
                if (isset($params1['freegift']) || isset($params1['freegift_coupon_code'])) {
                    $countFreeGift++;
                }
                $apllied_rule = "";
                $rule_id = "";
                if(isset($params['apllied_rule'])) $apllied_rule = $params['apllied_rule'];
                if(isset($params['rule_id'])) $rule_id      = $params['rule_id'];
                foreach ($ruleApplieFree as $ruleId) {
                    if (($ruleId != $apllied_rule) || ($ruleId != $rule_id)) {
                        $productIdInQuote[$it->getProductId()] = $ruleId;
                    }
                }
            }
            $arr = array();
            foreach (array_unique($productIds) as $productId) {
                $ruleByProduct = $this->getRuleByFreeProductId($productId);
                if ($ruleByProduct)
                    $arr[] = $ruleByProduct->getId();
            }
            $str_pro = '';
            if ($arr != null) {
                foreach (array_unique($arr) as $rule1) {
                    $dem            = 0;
                    $rules          = Mage::getModel('freegift/salesrule')->load($rule1);
                    $giftProductIds = $this->getProductIdByRuleFree($rule1);
                    foreach ($itemsFirst as $iLast) {
                        $params1 = unserialize($iLast->getOptionByCode('info_buyRequest')->getValue());
                        $rule_id = "";
                        $apllied_rule = "";
                        if(isset($params1['apllied_rule'])) $apllied_rule = $params1['apllied_rule'];
                        if(isset($params1['rule_id'])) $rule_id = $params1['rule_id'];
                        if (in_array($iLast->getProductId(), $giftProductIds) && !isset($params1['free_catalog_gift']) && (isset($params1['apllied_rule'])) && $apllied_rule == $rule1) {
                            $dem++;
                        }
                        if (in_array($iLast->getProductId(), $giftProductIds) && !isset($params1['free_catalog_gift']) && (isset($params1['apllied_rule'])) && $rule_id == $rule1) {
                            $dem++;
                        }
                    }
                    foreach ($giftProductIds as $giftProductId) {
                        if (!in_array($giftProductId, $proIdLast)) {
                            $pos = substr_count($str_pro, $giftProductId.",");
                            $max = $rules->getNumberOfFreeGift();
                            if ($pos == 0 && $dem < $max) {
                                $str_pro .= $giftProductId . ',';
                            }
                        }
                    }
                }
            }
            $str_pro = substr($str_pro, 0, strlen($str_pro) - 1);
            if ($str_pro) $productIds = explode(",", $str_pro);
            foreach ($ruleApplieFree as $ruleId){
                foreach ($productIdInQuote as $key => $value) {
                    $productFreeGift = $this->getProductIdByRuleFree($ruleId);
                    if (($ruleId == $value) || ($ruleId == $value)) {
                        foreach ($productFreeGift as $prFreeGift) {
                            if ($prFreeGift != $key && !in_array($key, $productIds)) {
                                $productIds[] = $key;
                            }
                        }
                    }
                }
            }
        }

        return $productIds;
    }

    public function getItemProductHtml($data){
        $block       = Mage::getSingleton('core/layout');
        $freegiftbox = $block->createBlock('freegift/product_item')->setTemplate('mw_freegift/freegift_catalog.phtml')->setData($data);
        return $freegiftbox->renderView();
    }
}
