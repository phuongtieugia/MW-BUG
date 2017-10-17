<?php
class MW_FreeGift_Helper_Data extends Mage_Core_Helper_Abstract
{
    public $_NAMEITEM = "";	
	const MYCONFIG = "freegift/config/enabled";

    // Define layout file name
    const LAYOUT_FILE = 'mw_freegift.xml';
    // Define template folder name
    const TEMPLATE_FOLDER = 'mw_freegift';

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
    
    public function getFreeProducts()
    {
        if ($freeids = Mage::getSingleton('checkout/session')->getQuote()->getFreegiftIds())
            return explode(",", $freeids);
        return false;
    }
    public function _canProcessRule($rule, $address)
    {
        //multi coupon
        if(!$rule->getData('is_active')){
            return false;
        }
        if($rule->getData('discount_qty') && ($rule->getData('discount_qty') <= $rule->getData('times_used'))){
            return false;
        }
        //if (!$rule->hasIsValid()) {
            $rule->afterLoad();
            if (!$rule->validate($address)) {
                $rule->setIsValid(false);
                return false;
            }
            $rule->setIsValid(true);
        //}
        return $rule->getIsValid();

    }
    public function renderOptions()
    {           
        $product = Mage::registry('current_product');      
        
        /* If product type is not simple, configurable or downloadable
         * return false (will move to product page) */
        if (!$product->isConfigurable() && $product->getTypeId() != 'bundle' && $product->getTypeId() != 'simple' && $product->getTypeId() != 'downloadable' && $product->getTypeId() != 'virtual' && $product->getTypeId() != 'grouped') {
            echo 'false';
            die();
        }
        /* If product have custom option of file type 
         * return false (will move to product page) */
        if (Mage::helper('freegift')->hasFileOption()) {
            echo 'false';
            die();
        }
        if ($product->getTypeId() == 'grouped') { 
        	//render group            
            $g = Mage::getSingleton('core/layout');            
            $product_type_data_extra = $g->createBlock('core/text_list', 'product_type_data_extra');
            if (version_compare(Mage::getVersion(), '1.4.0.1', '>')) {
                $reference_product_type_data_extra = $g->createBlock('cataloginventory/stockqty_type_grouped', 'reference_product_type_data_extra')->setTemplate('cataloginventory/stockqty/composite.phtml');
                $product_type_data_extra->append($reference_product_type_data_extra);
            }
            $addtocart = $g->createBlock('catalog/product_view', 'addtocart')->setTemplate('catalog/product/view/addtocart.phtml');
            $grouped   = $g->createBlock('catalog/product_view_type_grouped', 'product_type_data')->setTemplate('mw_freegift/grouped.phtml')->append($product_type_data_extra)->append($addtocart);
            return $grouped->renderView(); 	
        } else { 
        	//render configurable
            $block = Mage::getSingleton('core/layout');
            
            $options = $block->createBlock('catalog/product_view_options', 'product_options')->setTemplate('catalog/product/view/options.phtml')->addOptionRenderer('text', 'catalog/product_view_options_type_text', 'catalog/product/view/options/type/text.phtml')->addOptionRenderer('file', 'catalog/product_view_options_type_file', 'catalog/product/view/options/type/file.phtml')->addOptionRenderer('select', 'catalog/product_view_options_type_select', 'catalog/product/view/options/type/select.phtml')->addOptionRenderer('date', 'catalog/product_view_options_type_date', 'catalog/product/view/options/type/date.phtml');
            $price   = $block->createBlock('catalog/product_view', 'product_price')->setTemplate('catalog/product/view/price_clone.phtml');
            $js      = $block->createBlock('core/template', 'product_js')->setTemplate('catalog/product/view/options/js.phtml');
            if ($product->getTypeId() == 'bundle') { 
                $price->addPriceBlockType('bundle', 'bundle/catalog_product_price', 'mw_freegift/bundle/catalog/product/view/price.phtml');
                $tierprices = $block->createBlock('bundle/catalog_product_view', 'tierprices')->setTemplate('bundle/catalog/product/view/tierprices.phtml');
                $extrahind  = $block->createBlock('cataloginventory/qtyincrements', 'extrahint')->setTemplate('cataloginventory/qtyincrements.phtml');
                $bundle     = $block->createBlock('bundle/catalog_product_view_type_bundle', 'type_bundle_options')->setTemplate('bundle/catalog/product/view/type/bundle/options.phtml');
                $bundle->addRenderer('select', 'bundle/catalog_product_view_type_bundle_option_select');
                $bundle->addRenderer('multi', 'bundle/catalog_product_view_type_bundle_option_multi');
                $bundle->addRenderer('radio', 'bundle/catalog_product_view_type_bundle_option_radio');
                $bundle->addRenderer('checkbox', 'bundle/catalog_product_view_type_bundle_option_checkbox');
                
                $bundlejs = $block->createBlock('bundle/catalog_product_view_type_bundle', 'jsforbundle')->setTemplate('mw_freegift/bundle.phtml');
              
            }
            if ($product->isConfigurable()) {
                $configurable     = $block->createBlock('catalog/product_view_type_configurable', 'product_configurable_options')->setTemplate('catalog/product/view/type/options/configurable.phtml');
                $configurableData = $block->createBlock('catalog/product_view_type_configurable', 'product_type_data')->setTemplate('catalog/product/view/type/configurable.phtml');
            }
            if ($product->getTypeId() == 'downloadable') {
                $downloadable     = $block->createBlock('downloadable/catalog_product_links', 'product_downloadable_options')->setTemplate('mw_freegift/downloadable/catalog/product/links.phtml');
                $downloadableData = $block->createBlock('downloadable/catalog_product_view_type', 'product_type_data')->setTemplate('downloadable/catalog/product/type.phtml');
            }
            $addtocart = $block->createBlock('catalog/product_view', 'addtocart')->setTemplate('catalog/product/view/addtocart.phtml');
            
            
            $main = $block->createBlock('catalog/product_view')->setTemplate('mw_freegift/wrapper.phtml')->append($js)->append($options);
            if (version_compare(Mage::getVersion(), '1.4.0.1', '>')) {
                $calendar = $block->createBlock('core/html_calendar', 'html_calendar')->setTemplate('page/js/calendar.phtml');
                $main->append($calendar);
            }
            if ($product->isConfigurable()) {
                $main->append($configurableData);
                $main->append($configurable);
            }
            if ($product->getTypeId() == 'downloadable') {
                $main->append($downloadableData);
                $main->append($downloadable);
                $main->insert($downloadable);
            }
            if ($product->getTypeId() == 'bundle') {
                $main->append($bundle);
                $main->insert($bundle);
                
                $main->append($tierprices);
                $main->append($extrahind);
                $main->append($bundlejs);
            }
            $main->append($price)->append($addtocart);
            
            return $main->renderView();
        }
    }
    public function hasFileOption()
    {
        $product = Mage::registry('current_product');
        if ($product) {
            foreach ($product->getOptions() as $option) {
                if ($option->getType() == Mage_Catalog_Model_Product_Option::OPTION_TYPE_FILE)
                    return true;
            }
        }
        return false;
    }
    public function sendResponse($cart, $carttitle, $urlCheckout, $freegiftbox)
    {
        $options   = "0";
        $wishlist  = "";
        $wishtitle = "";
        $addwhat   = "0";
        if ($product = Mage::registry('current_product')) {
            $options = $product->getHasOptions();
            if ($product->getTypeId() == 'grouped') {
                $options = "1";
            }
        }
        Mage::getSingleton('checkout/session')->setIsajax("0");
        $iswishlist = Mage::getSingleton('checkout/session')->getIswishlist();
        
        $nameitem        = ($this->_NAMEITEM) ? $this->_NAMEITEM : "";
        $this->_NAMEITEM = '';
        $isfirst         = Mage::getSingleton('checkout/session')->getIsfirst(); 
        if ($iswishlist == 1 AND $isfirst == 1) {
            if ($nameitem == '') {
                $nameitem = (Mage::getSingleton('checkout/session')->getNameitem()) ? Mage::getSingleton('checkout/session')->getNameitem() : "";
            }
            Mage::getSingleton('checkout/session')->setIsfirst('0'); //reset var session			
            Mage::getSingleton('checkout/session')->setNameitem(''); //reset var session
            Mage::getSingleton('checkout/session')->setIswishlist('0'); //reset var session
            $wishlist  = $this->renderWishlist();
            $wishtitle = $this->renderWishlistTitle();
            header('content-type: text/javascript');
            echo '{"r":"' . $addwhat . '", "wishlinks":"' . $wishtitle . '", "wishlist":' . json_encode($wishlist) . ', "cart":' . json_encode($cart) . ', "links":"' . $carttitle . '","urlCheckout":"' . $urlCheckout . '","freegiftbox":' . json_encode($freegiftbox) . ',"options":' . $options . ', "nameitem":' . json_encode($nameitem) . '}';
            die();
        } elseif ($iswishlist == 2 AND $isfirst == 1) {
            if ($nameitem == '') {
                $nameitem = (Mage::getSingleton('checkout/session')->getNameitem()) ? Mage::getSingleton('checkout/session')->getNameitem() : "";
            }
            Mage::getSingleton('checkout/session')->setIsfirst('0'); //reset var session			
            Mage::getSingleton('checkout/session')->setNameitem(''); //reset var session
            Mage::getSingleton('checkout/session')->setIswishlist('0'); //reset var session
            $miniwish  = $this->renderMiniWish();
            $wishtitle = $this->renderWishlistTitle();
            header('content-type: text/javascript');
            echo '{"r":"' . $addwhat . '", "wishlinks":"' . $wishtitle . '", "wishlist":' . json_encode($miniwish) . ', "cart":' . json_encode($cart) . ', "links":"' . $carttitle . '","urlCheckout":"' . $urlCheckout . '","freegiftbox":' . json_encode($freegiftbox) . ',"options":' . $options . ', "nameitem":' . json_encode($nameitem) . '}';
            die();
        } else {
            header('content-type: text/javascript');
            echo '{"r":"' . $addwhat . '","freegiftbox":' . json_encode($freegiftbox) . ',"urlCheckout":"' . $urlCheckout . '", "cart":' . json_encode($cart) . ', "links":"' . $carttitle . '","options":' . $options . ', "nameitem":' . json_encode($nameitem) . '}';
            die();
        }
    }
    public function renderBigCart()
    {
        $bc = Mage::getSingleton('core/layout');
        
        $totals        = $bc->createBlock('checkout/cart_totals')->setTemplate('checkout/cart/totals.phtml');
        $shipping      = $bc->createBlock('checkout/cart_shipping')->setTemplate('checkout/cart/shipping.phtml');
        $coupon        = $bc->createBlock('checkout/cart_coupon')->setTemplate('checkout/cart/coupon.phtml');
        // top methods
        $t_onepage     = $bc->createBlock('checkout/onepage_link')->setTemplate('checkout/onepage/link.phtml');
        $t_methods     = $bc->createBlock('core/text_list')->append($t_onepage, 'top_methods');
        //methods
        $onepage       = $bc->createBlock('checkout/onepage_link')->setTemplate('checkout/onepage/link.phtml');
        $multishipping = $bc->createBlock('checkout/multishipping_link')->setTemplate('checkout/multishipping/link.phtml');
        $methods       = $bc->createBlock('core/text_list')->append($onepage, "onepage")->append($multishipping, "multishipping");
        // Cross-sales etc
        $crossel       = $bc->createBlock('checkout/cart_crosssell')->setTemplate('checkout/cart/crosssell.phtml');
        
        $freeGift = $bc->createBlock('freegift/product')->setTemplate('mw_freegift/freegift.phtml');
        $dungdk   = $bc->createBlock('page/html_wrapper')->append($freeGift, 'freegiftbox');
        
        Mage::getSingleton('checkout/session')->setCartWasUpdated(true);
        $main = $bc->createBlock('checkout/cart')->setEmptyTemplate('checkout/cart/noItems.phtml')->setCartTemplate('checkout/cart.phtml')->setTemplate('checkout/cart.phtml')->setChild('form_before', $dungdk)->setChild('top_methods', $t_methods)->setChild('totals', $totals)->setChild('shipping', $shipping)->setChild('coupon', $coupon)->setChild('methods', $methods)->setChild('crosssell', $crossel);
        $main->chooseTemplate();
        
        return $main->renderView();
    }
    
    public function renderCartTitle()
    {
        $count = Mage::helper('checkout/cart')->getSummaryCount();
        if ($count == 1) {
            $text = Mage::helper('freegift')->__('My Cart (%s item)', $count);
        } elseif ($count > 0) {
            $text = Mage::helper('freegift')->__('My Cart (%s items)', $count);
        } else {
            $text = Mage::helper('freegift')->__('My Cart');
        }
        return $text;
    }
    public function renderMiniCart()
    {
        $b = Mage::getSingleton('core/layout');        
        $minicart = $b->createBlock('checkout/cart_sidebar')->addItemRender('simple', 'checkout/cart_item_renderer', 'checkout/cart/sidebar/default.phtml')->addItemRender('configurable', 'checkout/cart_item_renderer_configurable', 'checkout/cart/sidebar/default.phtml')->addItemRender('grouped', 'checkout/cart_item_renderer_grouped', 'checkout/cart/sidebar/default.phtml')->setTemplate('checkout/cart/sidebar.phtml');
        if (version_compare(Mage::getVersion(), '1.4.0.1', '>=')) {
            $paypal        = $b->createBlock('paypal/express_shortcut', 'paypal_cart_sidebar.shortcut')->setTemplate('paypal/express/shortcut.phtml');
            $extra_actions = $b->createBlock('core/text_list', 'extra_actions')->append($paypal);
            $minicart->append($extra_actions);
        }
        return $minicart->renderView();
    }
    public function renderFreeGiftBox()
    {
        $block       = Mage::getSingleton('core/layout');
        $freegiftbox = $block->createBlock('freegift/product')->setTemplate('mw_freegift/freegift.phtml');
        return $freegiftbox->renderView();
    }
    
    public function renderFreeGiftCatalogList($product)
    {
		if(Mage::getStoreConfig('freegift/config/enabled'))
		{
			$block        = Mage::getSingleton('core/layout');
			$freegiftlist = $block->createBlock('freegift/product')->assign('product', $product)->setTemplate('mw_freegift/freegift_catalog_list.phtml');
			return $freegiftlist->renderView();
		}
    }
    
    public function renderFreeGiftLabel($product)
    {
		if(Mage::getStoreConfig('freegift/config/enabled'))
		{
			$block        = Mage::getSingleton('core/layout');
			$freegiftlist = $block->createBlock('freegift/product')->assign('product', $product)->setTemplate('mw_freegift/freegift_label.phtml');
			return $freegiftlist->renderView();
		}
    }
    
    public function checkVersion($str)
    {
        $a       = explode('.', $str);
        $modules = array_keys((array) Mage::getConfig()->getNode('modules')->children());
        if (in_array('Enterprise_Banner', $modules)) {
            if ($a[1] >= '12') {
                return "enterprise12";
            }
        } elseif (in_array('Enterprise_Enterprise', $modules)) {
            if ($a[1] <= '10') {
                return "enterprise10";
            }
        } else {
            if ($a[1] == '7' || $a[1] == '8') {
                return "mg1.7";
            }
            if ($a[1] == '6') {
                return "mg1.6";
            }
            if ($a[1] == '5') {
                return "mg1.5";
            }
			if ($a[1] == '4') {
                return "mg1.4";
            }

	        return "mg{$a[0]}.{$a[1]}";
        }
    }
	
	 public function myConfig()
    {
    	return self::MYCONFIG;
    }
	
	function disableConfig()
	{
		Mage::getSingleton('core/config')->saveConfig(Mage::helper('freegift')->myConfig(),0);
		$websites  = Mage::getModel('core/website')->getCollection()->getData();
    	foreach($websites as $row)
    	{
    		if($row['code']!="admin")
    		Mage::getSingleton('core/config')->saveConfig(Mage::helper('freegift')->myConfig(),0,'websites',$row['website_id']);
    	}   	  
    	
       $stores  = Mage::getModel('core/store')->getCollection()->getData();
    	foreach($stores as $row)
    	{
    		if($row['code']!="admin")
    		Mage::getSingleton('core/config')->saveConfig(Mage::helper('freegift')->myConfig(),0,'stores',$row['store_id']);
    	}
    	Mage::getSingleton('core/config')->saveConfig(Mage::helper('freegift')->myConfig(),0);
	}
	
	function enableConfig()
	{
		Mage::getSingleton('core/config')->saveConfig(Mage::helper('freegift')->myConfig(),1);
		$websites  = Mage::getModel('core/website')->getCollection()->getData();
    	foreach($websites as $row)
    	{
    		if($row['code']!="admin")
    		Mage::getSingleton('core/config')->saveConfig(Mage::helper('freegift')->myConfig(),1,'websites',$row['website_id']);
    	}   	  
    	
       $stores  = Mage::getModel('core/store')->getCollection()->getData();
    	foreach($stores as $row)
    	{
    		if($row['code']!="admin")
    		Mage::getSingleton('core/config')->saveConfig(Mage::helper('freegift')->myConfig(),1,'stores',$row['store_id']);
    	}
    	Mage::getSingleton('core/config')->saveConfig(Mage::helper('freegift')->myConfig(),1);
	}

    public function dataInCart(){
        $block = Mage::getSingleton('core/layout');

        $cart            = Mage::getSingleton('checkout/cart');
        $block_cart = Mage::app()->getLayout()->createBlock('checkout/cart');
        $block_totals = Mage::app()->getLayout()->createBlock('checkout/cart_totals');
        $block_freegift = Mage::app()->getLayout()->createBlock('freegift/product');
        $block_freegift_quote = Mage::app()->getLayout()->createBlock('freegift/quote')->assign('ajax', 1);
        $block_freegift_banner = Mage::app()->getLayout()->createBlock('freegift/promotionbanner')->assign('ajax', 1);

        $html = "";
        $template_render = "mw_freegift/checkout/cart/item/default.phtml";
        foreach($cart->getQuote()->getAllVisibleItems() as $item){
            switch($item->getProduct()->getTypeId()){
                case 'simple':
                case 'virtual':
                    $block_render = 'checkout/cart_item_renderer';
                    break;
                case 'grouped':
                    $block_render = 'checkout/cart_item_renderer_grouped';
                    break;
                case 'configurable':
                    $block_render = 'checkout/cart_item_renderer_configurable';
                    break;
                case 'downloadable':
                    $block_render = 'downloadable/checkout_cart_item_renderer';
                    $template_render = "mw_freegift/checkout/cart/item/default.phtml";
                    break;
                case 'bundle':
                    $block_render = 'bundle/checkout_cart_item_renderer';
                    break;
            }
            $html .= $block_cart->addItemRender($item->getProduct()->getTypeId(), $block_render, $template_render)->getItemHtml($item);
        }
        $cart->getQuote()->collectTotals();
        $data['html_items']         = $html;
        $data['html_gift']          = $block_freegift->toHtml();
        $data['html_gift_quote']    = $block_freegift_quote->_toHtml();
        $data['html_gift_banner']   = $block_freegift_banner->_toHtml();
        $data['html_total']         = $block_totals->renderTotals();
        $data['html_grand_total']   = $block_totals->renderTotals('footer');
        $data['count']              = Mage::helper('checkout/cart')->getSummaryCount();
        return $data;
    }
    public function configjs(){
        $path_js = "mw_freegift/lib/config-".Mage::app()->getStore()->getCode().".js";
        return $path_js;
    }
}