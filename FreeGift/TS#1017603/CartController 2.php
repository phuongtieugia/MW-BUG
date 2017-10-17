<?php
require_once 'Mage/Checkout/controllers/CartController.php';
class MW_FreeGift_CartController extends Mage_Checkout_CartController
{
	/**
	* Action list where need check enabled cookie
	*
	* @var array
	*/
	protected $_cookieCheckActions = array('add');
	
	/**
	* Retrieve shopping cart model object
	*
	* @return Mage_Checkout_Model_Cart
	*/
	protected function _getCart()
	{
		return Mage::getSingleton('checkout/cart');
	}
	
	/**
	* Get checkout session model instance
	*
	* @return Mage_Checkout_Model_Session
	*/
	protected function _getSession()
	{
		return Mage::getSingleton('checkout/session');
	}
	
	/**
	* Get current active quote instance
	*
	* @return Mage_Sales_Model_Quote
	*/
	protected function _getQuote()
	{
		return $this->_getCart()->getQuote();
	}
	
	/**
	* Set back redirect url to response
	*
	* @return Mage_Checkout_CartController
	*/
	protected function _goBack()
	{
		$returnUrl = $this->getRequest()->getParam('return_url');
		if ($returnUrl) {
			
			if (!$this->_isUrlInternal($returnUrl)) {
				throw new Mage_Exception('External urls redirect to "' . $returnUrl . '" denied!');
			}
			
			$this->_getSession()->getMessages(true);
			$this->getResponse()->setRedirect($returnUrl);
			} elseif (!Mage::getStoreConfig('checkout/cart/redirect_to_cart')
			&& !$this->getRequest()->getParam('in_cart')
			&& $backUrl = $this->_getRefererUrl()
		) {
			$this->getResponse()->setRedirect($backUrl);
			} else {
			if (($this->getRequest()->getActionName() == 'add') && !$this->getRequest()->getParam('in_cart')) {
				$this->_getSession()->setContinueShoppingUrl($this->_getRefererUrl());
			}
			$this->_redirect('checkout/cart');
		}
		return $this;
	}
	
	/**
	* Initialize product instance from request data
	*
	* @return Mage_Catalog_Model_Product || false
	*/
	protected function _initProduct($productId = null)
	{
        if($productId == null){
            $productId = (int) $this->getRequest()->getParam('product');
        }
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
    public function gpromotionAction(){
        if($this->getRequest()->getPost()){
            if($this->getRequest()->getPost('type') == 'message'){
                $block_freegift_quote = Mage::app()->getLayout()->createBlock('freegift/quote')->assign('ajax', 1);
                echo $block_freegift_quote->_toHtml();
                exit;
            }
            if($this->getRequest()->getPost('type') == 'banner'){
                $block_freegift_banner = Mage::app()->getLayout()->createBlock('freegift/promotionbanner')->assign('ajax', 1);
                echo $block_freegift_banner->_toHtml();
                exit;
            }
        }
    }
    public function gitemsAction(){
        if($this->getRequest()->getPost('ajax')){
            if($this->getRequest()->getPost('act') == 'items'){
                $cart            = Mage::getSingleton('checkout/cart');
                $block_cart = Mage::app()->getLayout()->createBlock('checkout/cart');
                $block_totals = Mage::app()->getLayout()->createBlock('checkout/cart_totals');

                $block_freegift_quote = Mage::app()->getLayout()->createBlock('freegift/quote')->assign('ajax', 1);
                $block_freegift_quote->assign('ajax', 1);
                $html_promotion_quote =  $block_freegift_quote->_toHtml();
                $block_freegift_banner = Mage::app()->getLayout()->createBlock('freegift/promotionbanner');
                $block_freegift_banner->assign('ajax', 1);
                $html_promotion_banner =  $block_freegift_banner->_toHtml();

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
                            $template_render = "mw_freegift/downloadable/checkout/cart/item/default.phtml";
                            break;
                        case 'bundle':
                            $block_render = 'bundle/checkout_cart_item_renderer';
                            break;
                    }
                    $html .= $block_cart->addItemRender($item->getProduct()->getTypeId(), $block_render, $template_render)->getItemHtml($item);
                }
                $cart->getQuote()->collectTotals();
                echo json_encode(array(
                    'html' => $html,
                    'total' => $block_totals->renderTotals(),
                    'grand_total' => $block_totals->renderTotals('footer'),
                    'promotion_quote' => $html_promotion_quote,
                    'promotion_banner' => $html_promotion_banner,
                ));
                exit;
            }else if($this->getRequest()->getPost('act') == 'totals'){
                $block_totals = Mage::app()->getLayout()->createBlock('checkout/cart_totals');
                $html  = $block_totals->renderTotals();
                $html .= $block_totals->renderTotals('footer');
                echo json_encode(array('total' => $block_totals->renderTotals(), 'grand_total' => $block_totals->renderTotals('footer')));
                exit;
            }
        }
    }
	public function goptionAction(){
        if($this->getRequest()->getPost()){
            if(is_numeric($this->getRequest()->getPost('pid'))){
                $productId = $this->getRequest()->getPost('pid');
                $product         = $this->__initProduct($productId);
                $product->setThumbInOption((string)Mage::helper('catalog/image')->init($product, 'small_image')->resize(35));

                $displayProduct =  array(
                    'name' => $product->getName(),
                    'image' => $product->getThumbInOption()
                );
                $options = array();
                if($product->getTypeId() == "configurable"){
                    $options = $this->getOptionsByProduct($product);
                }
                $custom_options = $this->getCustomOption($product);
                echo json_encode(array('data' => $options[0], 'product_type_id' => $product->getTypeId(), 'spConfig' => $options[1], 'product' => $displayProduct, 'custom_options' => (count($custom_options) ? $custom_options : '')));
                exit;
                return;
            }

        }
    }
    public function addgAction(){
        $cart = $this->_getCart();
        $product = $this->_initProduct();
        $params = $this->getRequest()->getParams();
        Mage::getModel('freegift/observer')->checkout_cart_add_product($params, $product, $cart);
    }
    public function configureAction(){
        $cart   = $this->_getCart();
        $id = (int) $this->getRequest()->getParam('item_id');
        $params = $this->getRequest()->getParams();

        if (!isset($params['options'])) {
            $params['options'] = array();
        }

        if(isset($params['options']) && count($params['options']) > 0){
            $options = array();
            foreach($params['options'] as $opt_id => $val){
                if(is_array($val)){
                    foreach($val as $k => $v){
                        if(!in_array($v, $options[$opt_id])){
                            $options[$opt_id][$k]  = $v;
                        }
                    }
                }else{
                    $options[$opt_id]  = $val;
                }
            }
        }
        $params['options']  = $options;
        try {
            if (isset($params['qty'])) {
                $filter = new Zend_Filter_LocalizedToNormalized(
                    array('locale' => Mage::app()->getLocale()->getLocaleCode())
                );
                $params['qty'] = $filter->filter($params['qty']);
            }

            $quoteItem = $cart->getQuote()->getItemById($id);
            if (!$quoteItem) {
                Mage::throwException($this->__('Quote item is not found.'));
            }

            $params['id'] = $params['item_id'];

            $item = $cart->updateItem($id, new Varien_Object($params));

            if (is_string($item)) {
                Mage::throwException($item);
            }
            if ($item->getHasError()) {
                Mage::throwException($item->getMessage());
            }

            $related = $this->getRequest()->getParam('related_product');
            if (!empty($related)) {
                $cart->addProductsByIds(explode(',', $related));
            }

            $cart->save();

            $this->_getSession()->setCartWasUpdated(true);

            Mage::dispatchEvent('checkout_cart_update_item_complete',
                array('item' => $item, 'request' => $this->getRequest(), 'response' => $this->getResponse())
            );
            $error = 1;
            $product = $this->_initProduct($item->getProduct()->getId());
            //$message = $this->__('<b><a href="'.$product->getProductUrl().'">%s</a></b> was updated in your shopping cart.', Mage::helper('core')->escapeHtml($item->getProduct()->getName()));
            $error = 0;
            $block_cart = Mage::app()->getLayout()->createBlock('checkout/cart');

            $template_render = "mw_freegift/checkout/cart/item/default.phtml";
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
                    $template_render = "downloadable/checkout/cart/item/default.phtml";
                    break;
                case 'bundle':
                    $block_render = 'bundle/checkout_cart_item_renderer';
                    break;
            }

            echo json_encode(array(
                'error'     => 0,
                'item_id'   => $id,
                'item_html' => $block_cart->addItemRender($item->getProduct()->getTypeId(), $block_render, $template_render)->getItemHtml($item),
            ));
        } catch (Mage_Core_Exception $e) {
            if ($this->_getSession()->getUseNotice(true)) {
                echo json_encode(array(
                    'error' => 1,
                    'msg'   => $e->getMessage()
                ));
            } else {
                $messages = array_unique(explode("\n", $e->getMessage()));
                $msg = "";
                foreach ($messages as $message) {
                    $msg .= $messages ."\n<br>";
                }
                echo json_encode(array(
                    'error' => 1,
                    'msg'   => implode("-", $messages)
                ));
            }

        } catch (Exception $e) {
            echo json_encode(array(
                'error' => 1,
                'msg'   => $this->__('Cannot update the item.')
            ));
        }
    }
    public function grabDataCartAction(){
        $params = $this->getRequest()->getPost();
        switch($params['type']){
            case 'checkout_cart':
                echo json_encode(Mage::helper('freegift')->dataInCart());
                break;
        }
    }
    public function gurlgiftAction(){
        $params = unserialize(base64_decode($this->getRequest()->getPost('data')));
        $item_id = (int)base64_decode($this->getRequest()->getPost('item_id'));
        $params['item_id'] = $item_id;

        if(isset($params['free_catalog_gift']) && $params['free_catalog_gift']){
            if(!empty($params['freegift_parent_key'])){
                $item = Mage::getModel('freegift/observer')->getQuoteItemByGiftItemId($item_id);

                $productId = $item->getProductId();
                $product         = $this->__initProduct($productId);
                $product->setThumbInOption((string)Mage::helper('catalog/image')->init($product, 'small_image')->resize(35));
                $displayProduct =  array(
                    'name' => $product->getName(),
                    'image' => $product->getThumbInOption()
                );
                if($product->getTypeId() == "configurable"){
                    $options = $this->getOptionsByProduct($product);
                    $custom_options = $this->getCustomOption($product);
                    $data_attr = array(
                        'pid'                   => $productId,
                        'data_ffg_type'         => 'catalog',
                        'data_catalog_gift'     => $item->getItemId(),
                        'load_in_page'          => $this->getRequest()->getPost('load_in_page'),
                    );
                    echo json_encode(array('item_id' => $item_id, 'data' => $options[0], 'product_type_id' => $product->getTypeId(), 'spConfig' => $options[1], 'product' => $displayProduct, 'data_attr' => $data_attr, 'custom_options' => (count($custom_options) ? $custom_options : '')));
                    exit;
                }
            }
        }

        if(isset($params['freegift']) && $params['freegift']){
            $item = Mage::getModel('freegift/observer')->getQuoteItemByGiftItemId($item_id);

            $productId = $item->getProductId();
            $product         = $this->__initProduct($productId);
            $product->setThumbInOption((string)Mage::helper('catalog/image')->init($product, 'small_image')->resize(35));
            $displayProduct =  array(
                'name' => $product->getName(),
                'image' => $product->getThumbInOption()
            );
            if($product->getTypeId() == "configurable"){
                $options = $this->getOptionsByProduct($product);
                $data_attr = array(
                    'pid'               => $productId,
                    'data_ffg_type'     => 'rule',
                    'data_applied_rule'     => $params['apllied_rule'],
                    'data_catalog_gift'     => $item->getItemId(),
                    'load_in_page'          => $this->getRequest()->getPost('load_in_page'),
                );
                echo json_encode(array('data' => $options[0], 'product_type_id' => $product->getTypeId(), 'product' => $displayProduct, 'spConfig' => $options[1], 'data_attr' => $data_attr));
                exit;
            }
        }
    }
    protected function getCustomOption($product){
        $custom_options = array();
        if($product->getOptions()){
            $custom_options = array();
            foreach ($product->getOptions() as $key => $o) {
                $optionType = $o->getType();
                if ($optionType == 'drop_down') {
                    /* Support only drop down now */
                    $custom_options[$o->getOptionId()]['label'] =  $o->getTitle();
                    $custom_options[$o->getOptionId()]['option_id'] =  $o->getOptionId();
                    foreach ($o->getValues() as $k => $v) {
                        $custom_options[$o->getOptionId()]['options'][$v->getOptionTypeId()] = array(
                            'label' => $v->getTitle(),
                            'value' => $v->getOptionTypeId(),
                        );
                    }
                }
            }
        }
        return $custom_options;
    }
    protected function getOptionsByProduct($product){
        $spConfig = Mage::helper('freegift/configurable')->getJsonConfig($product);

        $attrs  = $product->getTypeInstance()->getConfigurableAttributesAsArray();

        $super_attributes = array();
        foreach($attrs as $kp => $attr){
            foreach($attr['values'] as $kc => $spa){
                /** Loop attribute and get first option */
                $childProduct = Mage::getModel('catalog/product_type_configurable')->getProductByAttributes(array($attr['attribute_id'] => $spa['value_index']), $product);
                if(!$childProduct->isSalable()){continue;}
                else{
                    $super_attributes[$attr['attribute_id']] = $attr;
                }
            }
        }
        return array($super_attributes, $spConfig);
    }
    protected function __initProduct($productId)
    {
        if ($productId) {
            $product = Mage::getModel('catalog/product')->setStoreId(Mage::app()->getStore()->getId())->load($productId);
            if ($product->getId()) {
                return $product;
            }
        }
        return false;
    }
	/**
	* Shopping cart display action
	*/
	public function indexAction()
	{
		$cart = $this->_getCart();
		if ($cart->getQuote()->getItemsCount()) {
			$cart->init();
			$cart->save();
			
			if (!$this->_getQuote()->validateMinimumAmount()) {
				$minimumAmount = Mage::app()->getLocale()->currency(Mage::app()->getStore()->getCurrentCurrencyCode())
					->toCurrency(Mage::getStoreConfig('sales/minimum_order/amount'));
				
				$warning = Mage::getStoreConfig('sales/minimum_order/description')
					? Mage::getStoreConfig('sales/minimum_order/description')
					: Mage::helper('checkout')->__('Minimum order amount is %s', $minimumAmount);
				
				$cart->getCheckoutSession()->addNotice($warning);
			}
		}
		
		// Compose array of messages to add
		$messages = array();
		foreach ($cart->getQuote()->getMessages() as $message) {
			if ($message) {
				// Escape HTML entities in quote message to prevent XSS
				$message->setCode(Mage::helper('core')->escapeHtml($message->getCode()));
				$messages[] = $message;
			}
		}
		$cart->getCheckoutSession()->addUniqueMessages($messages);
		
		/**
		* if customer enteres shopping cart we should mark quote
		* as modified bc he can has checkout page in another window.
		*/
		$this->_getSession()->setCartWasUpdated(true);
		
		Varien_Profiler::start(__METHOD__ . 'cart_display');
		$this
		->loadLayout()
			->_initLayoutMessages('checkout/session')
			->_initLayoutMessages('catalog/session')
			->getLayout()->getBlock('head')->setTitle($this->__('Shopping Cart'));
		$this->renderLayout();
		Varien_Profiler::stop(__METHOD__ . 'cart_display');
	}
	public function ggiftAction(){
        $params = $this->getRequest()->getParams();
        if($params['ajax'] == 'true'){
            $block_cart = Mage::app()->getLayout()->createBlock('checkout/cart');
            $block_totals = Mage::app()->getLayout()->createBlock('checkout/cart_totals');
            $block_fg_product = Mage::app()->getLayout()->createBlock('freegift/product');
            $cart            = Mage::getSingleton('checkout/cart');
            print_r($block_fg_product->toHtml());
            exit;
        }
    }
	/**
	* Add product to shopping cart action
	*/
	public function addAction(){
        $cart   = $this->_getCart();
        $params = $this->getRequest()->getParams();

		try {
			if (isset($params['qty'])) {
				$filter = new Zend_Filter_LocalizedToNormalized(
				array('locale' => Mage::app()->getLocale()->getLocaleCode())
					);
				$params['qty'] = $filter->filter($params['qty']);
			}
			
			$product = $this->_initProduct();
			$related = $this->getRequest()->getParam('related_product');

			/**
			* Check product availability
			*/
			if (!$product) {
				$this->_goBack();
				return;
				}

			//Add custom Option for product if it's free gift item
			if(isset($params['freegift'])){							
				$product->addCustomOption('freegift',1);
			}

            $continue  = true;

			if ($product->getTypeId() == 'grouped' && isset($params['freegift'])){
				$associatedProducts = $product->getTypeInstance(true)->getAssociatedProducts($product);

				foreach($associatedProducts as $associatedProduct){										
					foreach($params['super_group'] as $k=>$v){				
						if($v>0){								
							if($associatedProduct->getEntityId() == $k){
								$params1 = array(
									'uenc' => $params['uenc'],
									'product' => $k, // This would be $product->getId()
									'qty' => $v,
									'freegift' => 1,
									'apllied_rule' => $params['apllied_rule'],
									'in_cart' => 1,
								
								);
								$product = Mage::getModel('catalog/product')->load($k);									
								$product->addCustomOption('freegift', 1);
								$cart->addProduct($product, $params1);	                                
							}								
						}
					}						
				}
			}else if(isset($params['free_catalog_gift']) || isset($params['freegift'])){
                $continue = false;

			}else if($product->getTypeId() == 'grouped'){
                $associatedProducts = $product->getTypeInstance(true)->getAssociatedProducts($product);
                foreach($associatedProducts as $associatedProduct){
                    if(isset($params['super_group']) && count($params['super_group']) > 0){
                        foreach($params['super_group'] as $k => $v){
                            if($v>0){
                                if($associatedProduct->getEntityId() == $k){

                                    $params1 = array(
                                        'uenc' => $params['uenc'],
                                        'product' => $k, // This would be $product->getId()
                                        'qty' => $v,
                                        'in_cart' => 1,

                                    );
                                    $product = Mage::getModel('catalog/product')->load($k);
                                    $cart->addProduct($product, $params1);
                                }
                            }
                        }
                    }else{
                        $params1 = array(
                            'uenc' => $params['uenc'],
                            'product' => $associatedProduct->getEntityId(), // This would be $product->getId()
                            'qty' => 1,
                            'in_cart' => 1,

                        );
                        $product = Mage::getModel('catalog/product')->load($associatedProduct->getEntityId());
                        $cart->addProduct($product, $params1);
                    }

                }
                $cart->save();
                $continue = false;
            }
            if($product->getTypeId() == 'configurable' && isset($params['free_catalog_gift'])){
                $continue = false;
            }


            if($continue){
                if (!empty($related)) {
                    $cart->addProductsByIds(explode(',', $related));
                }
                $cart->addProduct($product, $params);
                $cart->save();
                $this->_getSession()->setCartWasUpdated(true);
            }

			
			/**
			* @todo remove wishlist observer processAddToCart
			*/

			Mage::dispatchEvent('checkout_cart_add_product_complete',
				array('product' => $product, 'request' => $this->getRequest(), 'response' => $this->getResponse(), 'params' => $params, 'cart' => $cart)
			);
			if (!$this->_getSession()->getNoCartRedirect(true)) {
				if (!$cart->getQuote()->getHasError()){
					$message = $this->__('%s was added to your shopping cart.', Mage::helper('core')->escapeHtml($product->getName()));
					$this->_getSession()->addSuccess($message);
				}
				$this->_goBack();
			}
			} catch (Mage_Core_Exception $e) {
				if ($this->_getSession()->getUseNotice(true)) {
						$this->_getSession()->addNotice(Mage::helper('core')->escapeHtml($e->getMessage()));
					} else {
						$messages = array_unique(explode("\n", $e->getMessage()));
					foreach ($messages as $message) {
						$this->_getSession()->addError(Mage::helper('core')->escapeHtml($message));
					}
				}
			
				$url = $this->_getSession()->getRedirectUrl(true);
				if($url) {
					$this->getResponse()->setRedirect($url);
				}else {
					$this->_redirectReferer(Mage::helper('checkout/cart')->getCartUrl());
				}
			} catch (Exception $e) {
				$this->_getSession()->addException($e, $this->__('Cannot add the item to shopping cart'));
				Mage::logException($e);
				$this->_goBack();
		}
	}
    protected  function addAjaxAction(){
        $cart   = $this->_getCart();
        $params = $this->getRequest()->getPost();
        if(isset($params['options']) && count($params['options']) > 0){
            $options = array();
            foreach($params['options'] as $opt_id => $val){
                if(is_array($val)){
                    foreach($val as $k => $v){
                        if(!in_array($v, $options[$opt_id])){
                            $options[$opt_id][$k]  = $v;
                        }
                    }
                }else{
                    $options[$opt_id]  = $val;
                }
            }
        }
        $params['options']  = $options;
        try{
            $product = $this->_initProduct($params['product']);
            if (!$product) {
                echo json_encode(array(
                    'error' => 1,
                    'msg'   => "No product"
                ));
                return;
            }
            $params['cusotm_unec'] = 'By Ajax Cart - Mage-World.COM';

            $cart->addProduct($product, $params);
            $cart->save();
            $this->_getSession()->setCartWasUpdated(true);

            Mage::dispatchEvent('checkout_cart_add_product_complete',
                array('product' => $product, 'request' => $this->getRequest(), 'response' => $this->getResponse(), 'params' => $params, 'cart' => $cart)
            );

            echo json_encode(array(
                'error' => 0,
            ));
        }catch (Mage_Core_Exception $e){
            echo json_encode(array(
                'error' => 1,
                'msg'   => $e->getMessage()
            ));
        }catch(Exception $e){
            echo json_encode(array(
                'error' => 1,
                'msg'   => $e->getMessage()
            ));
        }
        exit;
    }
	public function addgroupAction()
	{
		$orderItemIds = $this->getRequest()->getParam('order_items', array());
		if (is_array($orderItemIds)) {
			$itemsCollection = Mage::getModel('sales/order_item')
				->getCollection()
				->addIdFilter($orderItemIds)
				->load();
			/* @var $itemsCollection Mage_Sales_Model_Mysql4_Order_Item_Collection */
			$cart = $this->_getCart();
			foreach ($itemsCollection as $item) {
				try {
						$cart->addOrderItem($item, 1);
					} catch (Mage_Core_Exception $e) {
						if ($this->_getSession()->getUseNotice(true)) {
								$this->_getSession()->addNotice($e->getMessage());
							} else {
								$this->_getSession()->addError($e->getMessage());
						}
					} catch (Exception $e) {
						$this->_getSession()->addException($e, $this->__('Cannot add the item to shopping cart'));
						Mage::logException($e);
						$this->_goBack();
				}
			}
			$cart->save();
			$this->_getSession()->setCartWasUpdated(true);
		}
		$this->_goBack();
	}

	/**
	* Update product configuration for a cart item
	*/
	public function updateItemOptionsAction()
	{
		$cart   = $this->_getCart();
		$id = (int) $this->getRequest()->getParam('id');
		$params = $this->getRequest()->getParams();
		
		if (!isset($params['options'])) {
			$params['options'] = array();
		}
		try {
			if (isset($params['qty'])) {
				$filter = new Zend_Filter_LocalizedToNormalized(
				array('locale' => Mage::app()->getLocale()->getLocaleCode())
					);
				$params['qty'] = $filter->filter($params['qty']);
			}
			
			$quoteItem = $cart->getQuote()->getItemById($id);
			if (!$quoteItem) {
				Mage::throwException($this->__('Quote item is not found'));
			}
			
			$item = $cart->updateItem($id, new Varien_Object($params));
			if (is_string($item)) {
				Mage::throwException($item);
			}
			if ($item->getHasError()) {
				Mage::throwException($item->getMessage());
			}
			
			$related = $this->getRequest()->getParam('related_product');
			if (!empty($related)) {
				$cart->addProductsByIds(explode(',', $related));
			}
			
			$cart->save();
			
			$this->_getSession()->setCartWasUpdated(true);
			
			Mage::dispatchEvent('checkout_cart_update_item_complete',
			array('item' => $item, 'request' => $this->getRequest(), 'response' => $this->getResponse())
				);
			if (!$this->_getSession()->getNoCartRedirect(true)) {
				if (!$cart->getQuote()->getHasError()){
					$message = $this->__('%s was updated in your shopping cart.', Mage::helper('core')->htmlEscape($item->getProduct()->getName()));
					$this->_getSession()->addSuccess($message);
				}
				$this->_goBack();
			}
			} catch (Mage_Core_Exception $e) {
				if ($this->_getSession()->getUseNotice(true)) {
					$this->_getSession()->addNotice($e->getMessage());
					} else {
					$messages = array_unique(explode("\n", $e->getMessage()));
					foreach ($messages as $message) {
						$this->_getSession()->addError($message);
					}
				}
				
				$url = $this->_getSession()->getRedirectUrl(true);
				if ($url) {
					$this->getResponse()->setRedirect($url);
					} else {
					$this->_redirectReferer(Mage::helper('checkout/cart')->getCartUrl());
				}
			} catch (Exception $e) {
			$this->_getSession()->addException($e, $this->__('Cannot update the item'));
			Mage::logException($e);
			$this->_goBack();
		}
		$this->_redirect('*/*');
	}
	
	/**
	* Update shopping cart data action
	*/
	public function updatePostAction()
	{
        $params = $this->getRequest()->getPost();

        if(isset($params['ajax_add'])){
            $this->addAjaxAction();
            exit;
        }

		$updateAction = (string)$this->getRequest()->getParam('update_cart_action');

		switch ($updateAction) {
			case 'empty_cart':
				$this->_emptyShoppingCart();
				break;
			case 'update_qty':
				$this->_updateShoppingCart();
				break;
            default:
                $this->_updateShoppingCart();
                break;
		}
        switch($params['type']){
            case 'checkout_cart':
                $data['cart'] = json_encode(Mage::helper('freegift')->dataInCart());
                break;
        }
        if(isset($params['ajax_gift']) && $params['ajax_gift'] == "true"){
            echo $data['cart'];
            exit;
        }
		$this->_goBack();
	}
	
	/**
	* Update customer's shopping cart
	*/

    protected function _updateShoppingCart()
    {
        $params = $this->getRequest()->getParams();
        try {
            $cartData = $this->getRequest()->getParam('cart');

            if (is_array($cartData)) {
                $filter = new Zend_Filter_LocalizedToNormalized(
                    array('locale' => Mage::app()->getLocale()->getLocaleCode())
                );
                foreach ($cartData as $index => $data) {
                    if (isset($data['qty'])) {
                        $cartData[$index]['qty'] = $filter->filter(trim($data['qty']));
                    }
                }
                $cart = $this->_getCart();
                if (! $cart->getCustomerSession()->getCustomer()->getId() && $cart->getQuote()->getCustomerId()) {
                    $cart->getQuote()->setCustomerId(null);
                }

                $cartData = $cart->suggestItemsQty($cartData);
                $cart->updateItems($cartData)
                    ->save();
            }
            $this->_getSession()->setCartWasUpdated(true);
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError(Mage::helper('core')->escapeHtml($e->getMessage()));
        } catch (Exception $e) {
            $this->_getSession()->addException($e, $this->__('Cannot update shopping cart.'));
            Mage::logException($e);
        }
        if(isset($params['ajax_gift']) && $params['ajax_gift']){
            return true;
        }
    }
	/**
	* Empty customer's shopping cart
	*/
	protected function _emptyShoppingCart()
	{
		try {
				$this->_getCart()->truncate()->save();
				$this->_getSession()->setCartWasUpdated(true);
			} catch (Mage_Core_Exception $exception) {
				$this->_getSession()->addError($exception->getMessage());
			} catch (Exception $exception) {
				$this->_getSession()->addException($exception, $this->__('Cannot update shopping cart.'));
		}
        $params = $this->getRequest()->getPost();
        Mage::dispatchEvent('checkout_cart_empty_after', array());
        if(isset($params['ajax_gift']) && $params['ajax_gift']){
            return true;
        }
	}
	
	/**
	* Delete shoping cart item action
	*/
    public function deleteAction()
    {
        if (!Mage::getStoreConfig('freegift/config/enabled'))
        {
            parent::deleteAction();
        }
        $params = $this->getRequest()->getParams();
        $id = (int) $this->getRequest()->getParam('id');

        if ($id) {
            try {
                $this->_getCart()->removeItem($id)
                    ->save();
            } catch (Exception $e) {
                $this->_getSession()->addError($this->__('Cannot remove the item.'));
                if(isset($params['ajax'])){
                    echo json_encode(array('error' => 1, 'msg' => $e->getMessage()));
                    exit;
                }
                Mage::logException($e);
            }
        }

        if(isset($params['ajax'])){
            echo json_encode(array('error' => 0));
            exit;
        }
    }
	
	/**
	* Initialize shipping information
	*/
	public function estimatePostAction()
	{
		$country    = (string) $this->getRequest()->getParam('country_id');
		$postcode   = (string) $this->getRequest()->getParam('estimate_postcode');
		$city       = (string) $this->getRequest()->getParam('estimate_city');
		$regionId   = (string) $this->getRequest()->getParam('region_id');
		$region     = (string) $this->getRequest()->getParam('region');
		
		$this->_getQuote()->getShippingAddress()
			->setCountryId($country)
			->setCity($city)
			->setPostcode($postcode)
			->setRegionId($regionId)
			->setRegion($region)
			->setCollectShippingRates(true);
		$this->_getQuote()->save();
		$this->_goBack();
	}
	
	public function estimateUpdatePostAction()
	{
		$code = (string) $this->getRequest()->getParam('estimate_method');
		if (!empty($code)) {
			$this->_getQuote()->getShippingAddress()->setShippingMethod($code)/*->collectTotals()*/->save();
		}
		$this->_goBack();
	}
	
	/**
	* Initialize coupon
	*/
	public function couponPostAction()
	{
		/**
		* No reason continue with empty shopping cart
		*/
		if (!$this->_getCart()->getQuote()->getItemsCount()) {
			$this->_goBack();
			return;
		}
		
		$couponCode = (string) $this->getRequest()->getParam('coupon_code');
		if ($this->getRequest()->getParam('remove') == 1) {
			$couponCode = '';
		}
		$oldCouponCode = $this->_getQuote()->getCouponCode();
		
		if (!strlen($couponCode) && !strlen($oldCouponCode)) {
			$this->_goBack();
			return;
		}
		
		try {
			$this->_getQuote()->getShippingAddress()->setCollectShippingRates(true);
			$this->_getQuote()->setCouponCode(strlen($couponCode) ? $couponCode : '')
				->collectTotals()
				->save();
			
			if (strlen($couponCode)) {
				if ($couponCode == $this->_getQuote()->getCouponCode()) {
					$this->_getSession()->addSuccess(
					$this->__('Coupon code "%s" was applied.', Mage::helper('core')->htmlEscape($couponCode))
						);
				}
				else {
					$this->_getSession()->addError(
					$this->__('Coupon code "%s" is not valid.', Mage::helper('core')->htmlEscape($couponCode))
						);
				}
				} else {
				$this->_getSession()->addSuccess($this->__('Coupon code was canceled.'));
			}
			
			} catch (Mage_Core_Exception $e) {
				$this->_getSession()->addError($e->getMessage());
			} catch (Exception $e) {
				$this->_getSession()->addError($this->__('Cannot apply the coupon code.'));
				Mage::logException($e);
		}
		
		$this->_goBack();
	}

    public function getProductAction(){
        $block = Mage::getSingleton('core/layout');
        $params = $this->getRequest()->getParams();

        if(!isset($params['action'])) $params['action'] = 'view';

        switch($params['action']){
            case 'view':
                $product = $this->_initProduct($params['product']);

                if (!$product) {
                    return;
                }
                $textBtn = Mage::helper('core')->__("Add to Cart");
                $session_id = false;
                $qty = 1;
                break;
            case 'configure':
                /* Edit product in cart based id of item */
                $id = (int) $params['item_id'];
                $quoteItem = null;
                $cart = $this->_getCart();

                if ($id) {
                    $quoteItem = $cart->getQuote()->getItemById($id);
                }

                if (!$quoteItem) {
                    return;
                }

                $qty = $quoteItem->getQty();
                $product = $this->_initProduct($quoteItem->getProduct()->getId());

                if (!$product) {
                    return;
                }

                try {
                    $_params = new Varien_Object();
                    $_params->setCategoryId(false);
                    $_params->setConfigureMode(true);
                    $_params->setBuyRequest($quoteItem->getBuyRequest());

                    $product = Mage::helper('freegift/catalog_product_view')->prepareAndRender($quoteItem->getProduct()->getId(), $_params);
                } catch (Exception $e) {
                    $this->_getSession()->addError($this->__('Cannot configure product.'));
                    Mage::logException($e);
                    return;
                }
                $textBtn = Mage::helper('core')->__("Update Cart");
                $session_id = true;
                break;
        }

        $options_js = $block->createBlock('catalog/product_view')->setData('area','frontend')->setProductId($product->getId())->getJsonConfig();

        $options        = $block->createBlock('catalog/product_view_options')->setData('area','frontend')->setProduct($product)->setTemplate('catalog/product/view/options.phtml')->addOptionRenderer('text', 'catalog/product_view_options_type_text', 'catalog/product/view/options/type/text.phtml')->addOptionRenderer('file', 'catalog/product_view_options_type_file', 'catalog/product/view/options/type/file.phtml')->addOptionRenderer('select', 'catalog/product_view_options_type_select', 'catalog/product/view/options/type/select.phtml')->addOptionRenderer('date', 'catalog/product_view_options_type_date', 'catalog/product/view/options/type/date.phtml');
        $js             = $block->createBlock('core/template', 'product_js')->setData('area','frontend')->setTemplate('catalog/product/view/options/js.phtml');
        $product_price  = $block->createBlock('catalog/product_price')->setData('area','frontend')->setProduct($product)->setTemplate('catalog/product/view/price_clone.phtml');
        $html  = "";
        $html .= '
            <script type="text/javascript">
                var optionsPrice = new Product.OptionsPrice('.$options_js.');
            </script>';

        $html .= "<div class='product-options-top'>";
        $html .= $js;
        $html .= $options->renderView();

        if ($product->getTypeId() == 'configurable'){
            try{
                if(version_compare(Mage::getVersion(), '1.9', '>='))
                {
                    $attr_renderers = $block->createBlock('core/text_list', 'product.info.options.configurable.renderers');
                    $configurable     = $block->createBlock('catalog/product_view_type_configurable')->setData('area','frontend')
                                              ->setProduct($product)
                                              ->setChild('attr_renderers', $attr_renderers)
                                              ->setTemplate('catalog/product/view/type/options/configurable.phtml');
                }
                else
                {
                    $configurable     = $block->createBlock('catalog/product_view_type_configurable')->setData('area','frontend')->setProduct($product)->setTemplate('catalog/product/view/type/options/configurable.phtml');
                }


                $html .= $configurable->renderView();
                $html .= "</div>"; /* End from div product-options-top */

                if($params['is_gift'] == "true" || $params['action'] == 'configure'){
                    $html .= "<div class='product-options-bottom'>".
                        "<div class=\"add-to-cart\">
                        <button type=\"button\" title=\"".$textBtn."\" class=\"button btn-cart\"><span><span>".$textBtn."</span></span></button>
                 </div>".
                        $block->createBlock('catalog/product_view')->getPriceHtml($product).
                        "</div>";
                }else{
                    $html .= "<div class='product-options-bottom'>".
                        "<div class=\"add-to-cart\">
                        <label for=\"qty\">Qty:</label>
                        <input type=\"text\" name=\"qty\" id=\"product_qty\" maxlength=\"12\" value=\"$qty\" title=\"Qty\" class=\"input-text qty\">
                    <button type=\"button\" title=\"".$textBtn."\" class=\"button btn-cart\"><span><span>".$textBtn."</span></span></button>
                 </div>".
                        $block->createBlock('catalog/product_view')->getPriceHtml($product).
                        "</div>";
                }
            }catch(Exception $e){

            }

        }
        else if($product->getTypeId() == 'simple'){
            if($params['is_gift'] == "false" || $params['action'] == 'configure'){
                $html .= "<div class='product-options-bottom'>".
                    "<div class=\"add-to-cart\">
                    <button type=\"button\" title=\"".$textBtn."\" class=\"button btn-cart\"><span><span>".$textBtn."</span></span></button>
                 </div>".
                    $block->createBlock('catalog/product_view')->getPriceHtml($product).
                    "</div>";
            }else{
                $html .= "<div class='product-options-bottom'>".
                    "<div class=\"add-to-cart\">
                        <label for=\"qty\">Qty:</label>
                        <input type=\"text\" name=\"qty\" id=\"product_qty\" maxlength=\"12\" value=\"$qty\" title=\"Qty\" class=\"input-text qty\">
                    <button type=\"button\" title=\"".$textBtn."\" class=\"button btn-cart\"><span><span>".$textBtn."</span></span></button>
                 </div>".
                    $block->createBlock('catalog/product_view')->getPriceHtml($product).
                    "</div>";
            }
        }
        else if($product->getTypeId() == 'bundle'){
            Mage::register('current_product', $product);
            $product_price = $block->createBlock('bundle/catalog_product_price')->setProduct($product)->setTemplate('bundle/catalog/product/view/price.phtml');
            $tierprices = $block->createBlock('bundle/catalog_product_view')->setProduct($product)->setTemplate('bundle/catalog/product/view/tierprices.phtml');
            $extrahind  = $block->createBlock('cataloginventory/qtyincrements')->setTemplate('cataloginventory/qtyincrements.phtml');

            $bundle     = $block->createBlock('bundle/catalog_product_view_type_bundle')->setProduct($product)->setTemplate('bundle/catalog/product/view/type/bundle/options.phtml');
            $bundle->addRenderer('select', 'bundle/catalog_product_view_type_bundle_option_select');
            $bundle->addRenderer('multi', 'bundle/catalog_product_view_type_bundle_option_multi');
            $bundle->addRenderer('radio', 'bundle/catalog_product_view_type_bundle_option_radio');
            $bundle->addRenderer('checkbox', 'bundle/catalog_product_view_type_bundle_option_checkbox');
            if(Mage::helper('freegift/version')->isMageEnterprise()){
                $bundle_type_template = 'mw_freegift/bundle/catalog/product/view/type/bundle.phtml';
            }else{
                $bundle_type_template = 'bundle/catalog/product/view/type/bundle.phtml';
            }
            $bundlejs_custom = $block->createBlock('bundle/catalog_product_view_type_bundle')->setProduct($product)->setTemplate($bundle_type_template);

            $html .= $bundle->renderView();
            $html .= "</div>"; /* End from div product-options-top */

            if($params['is_gift'] == "false" || $params['action'] == 'configure'){
                $html .= "<div class='product-options-bottom'>".
                    "<div class=\"add-to-cart\">
                        <button type=\"button\" title=\"".$textBtn."\" class=\"button btn-cart\"><span><span>".$textBtn."</span></span></button>
                     </div>".
                    $bundlejs_custom->renderView().
                    "</div>";
            }else{
                $html .= "<div class='product-options-bottom'>".
                    "<div class=\"add-to-cart\">
                        <label for=\"qty\">Qty:</label>
                        <input type=\"text\" name=\"qty\" id=\"product_qty\" maxlength=\"12\" value=\"$qty\" title=\"Qty\" class=\"input-text qty\">
                        <button type=\"button\" title=\"".$textBtn."\" class=\"button btn-cart\"><span><span>".$textBtn."</span></span></button>
                     </div>".
                    $bundlejs_custom->renderView().
                    "</div>";
            }

        }
        else if($product->getTypeId() == 'downloadable'){
            $downloadable     = $block->createBlock('downloadable/catalog_product_links')->setProduct($product)->setTemplate('downloadable/catalog/product/links.phtml');
            $downloadableData = $block->createBlock('downloadable/catalog_product_view_type')->setProduct($product)->setTemplate('downloadable/catalog/product/type.phtml');
            $html .= $downloadable->renderView();
            $html .= $downloadableData->renderView();
            $html .= "</div>"; /* End from div product-options-top */

            if($params['is_gift'] == "false" || $params['action'] == 'configure'){
                $html .= "<div class='product-options-bottom'>".
                    "<div class=\"add-to-cart\">
                    <button type=\"button\" title=\"".$textBtn."\" class=\"button btn-cart\"><span><span>".$textBtn."</span></span></button>
                 </div>".
                    $block->createBlock('catalog/product_view')->getPriceHtml($product).
                    "</div>";
            }else{
                $html .= "<div class='product-options-bottom'>".
                    "<div class=\"add-to-cart\">
                    <label for=\"qty\">Qty:</label>
                    <input type=\"text\" name=\"qty\" id=\"product_qty\" maxlength=\"12\" value=\"$qty\" title=\"Qty\" class=\"input-text qty\">
                    <button type=\"button\" title=\"".$textBtn."\" class=\"button btn-cart\"><span><span>".$textBtn."</span></span></button>
                 </div>".
                    $block->createBlock('catalog/product_view')->getPriceHtml($product).
                    "</div>";
            }
        }
        else if($product->getTypeId() == 'grouped'){
            Mage::register('current_product', $product);
            $product_type_data_extra = $block->createBlock('core/text_list');
            if (version_compare(Mage::getVersion(), '1.4.0.1', '>')) {
                $reference_product_type_data_extra = $block->createBlock('cataloginventory/stockqty_type_grouped')->setTemplate('cataloginventory/stockqty/composite.phtml');
                $product_type_data_extra->append($reference_product_type_data_extra);
            }

            $grouped   = $block->createBlock('catalog/product_view_type_grouped')->setProduct($product)->setTemplate('catalog/product/view/type/grouped.phtml')->append($product_type_data_extra);

            $html .= $grouped->renderView();
            $html .= "</div>"; /* End from div product-options-top */
            if($params['is_gift'] == "false" || $params['action'] == 'configure'){
                $html .= "<div class='product-options-bottom'>".
                    "<div class=\"add-to-cart\">
                    <button type=\"button\" title=\"".$textBtn."\" class=\"button btn-cart\"><span><span>".$textBtn."</span></span></button>
                 </div>".
                    "</div>";
            }else{
                $html .= "<div class='product-options-bottom'>".
                    "<div class=\"add-to-cart\">
                    <label for=\"qty\">Qty:</label>
                    <input type=\"text\" name=\"qty\" id=\"product_qty\" maxlength=\"12\" value=\"$qty\" title=\"Qty\" class=\"input-text qty\">
                    <button type=\"button\" title=\"".$textBtn."\" class=\"button btn-cart\"><span><span>".$textBtn."</span></span></button>
                 </div>".
                    "</div>";
            }
        }
        else{
            if($params['is_gift'] == "false" || $params['action'] == 'configure'){
                $html .= "<div class='product-options-bottom'>".
                    "<div class=\"add-to-cart\">
                    <button type=\"button\" title=\"".$textBtn."\" class=\"button btn-cart\"><span><span>".$textBtn."</span></span></button>
                 </div>".
                    $block->createBlock('catalog/product_view')->getPriceHtml($product).
                    "</div>";
            }else{
                $html .= "<div class='product-options-bottom'>".
                    "<div class=\"add-to-cart\">
                    <label for=\"qty\">Qty:</label>
                    <input type=\"text\" name=\"qty\" id=\"product_qty\" maxlength=\"12\" value=\"$qty\" title=\"Qty\" class=\"input-text qty\">
                    <button type=\"button\" title=\"".$textBtn."\" class=\"button btn-cart\"><span><span>".$textBtn."</span></span></button>
                 </div>".
                    $block->createBlock('catalog/product_view')->getPriceHtml($product).
                    "</div>";
            }
        }
        echo $html;
    }
	public function loadtotalAction(){
		$_hcart = Mage::helper('checkout/cart');
		$_cartQty = $_hcart->getSummaryCount();
		$str = "";
		$sttop = "";
		if ($_cartQty>0){
			if ($_cartQty==1){
				$str.='<p class="amount">'.$_hcart->__('There is <a href="%s">1 item</a> in your cart.', Mage::getUrl('checkout/cart'));
				
			}
			else{
				$str.='<p class="amount">'.$_hcart->__('There are <a href="%s">%s items</a> in your cart.', Mage::getUrl('checkout/cart'), $_cartQty);
			}
			$str.='<p class="subtotal">';
			if (Mage::helper('catalog')->canApplyMsrp()){
				$str.='<span class="map-cart-sidebar-total">'.$_hcart->__('ORDER TOTAL WILL BE DISPLAYED BEFORE YOU SUBMIT THE ORDER').'</span>';
			}
			else{
				$str.='<span class="label">'.$_hcart->__('Cart Subtotal: ').'</span>'.Mage::helper('checkout')->formatPrice(Mage::getSingleton('checkout/cart')->getQuote()->getSubtotal());
				if ($_subtotalInclTax = Mage::getSingleton('checkout/session')->getQuote()->getSubtotalInclTax()){
					$str.='<br />('.Mage::helper('checkout')->formatPrice($_subtotalInclTax). Mage::helper('tax')->getIncExcText(true).')';
				}
			}
			$sttop.='<p class="amount"><a class="title" href="'.Mage::getUrl('checkout/cart').'">'.$_hcart->__('Корзина').'</a>';
			$sttop.='<span class="qty"> '.$_cartQty.'</span>'.$_hcart->__(' товара(ов): ');
			$sttop.='<span class="price">'.Mage::helper('checkout')->formatPrice(Mage::getSingleton('checkout/cart')->getQuote()->getSubtotal()).'</span>';
			$sttop.='<p class="cart-link"><a href="'.Mage::getUrl('checkout/cart').'">'.$_hcart->__('Корзина').'</a></p>';
			$str.='</p>';
			
				
		}
		$html = array(
			'sidebar' => $str,
			'top'=>$sttop
		);
		echo json_encode($html);
	}
}

























