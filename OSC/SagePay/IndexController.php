<?php
require_once 'Mage/Checkout/controllers/OnepageController.php';
/*require_once 'Mobile_Detect.php';*/
if (!class_exists('Mobile_Detect')) {
        require_once 'Mobile_Detect.php';
    }
class MW_Onestepcheckout_IndexController extends Mage_Checkout_OnepageController
{
    protected $notshiptype = 0;
    protected $_colspan = 5;
    protected $_html_updatecart = "";

    public function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    public function getQuote()
    {
        return Mage::getSingleton('checkout/session')->getQuote();
    }

    public function getOnepage()
    {
        return Mage::getSingleton('checkout/type_onepage');
    }

    protected function _getQuote()
    {
        return Mage::getSingleton('checkout/cart')->getQuote();
    }

    public function indexAction()
    {

		//var_dump(Mage::helper('onestepcheckout')->configjs()); exit;
        //fix for persistent
        $registerParam = $this->getRequest()->getParam('register');
        if($registerParam || $registerParam === '') {
            $this->getCheckout()->setPersistentRegister('register');
        } else if($this->getCheckout()->getPersistentRegister()) {
            $this->getCheckout()->unsPersistentRegister();
        }
        //end fix for persistent

        if(!$this->checkBeforeCheckout()) {
            $this->_redirect('checkout/cart');
        }

        $changestyle = $this->checkChangeStyle(); // change style = 1, use default checkout
        if($changestyle == 1) {

            Mage::getSingleton('checkout/session')->setCartWasUpdated(false);
            Mage::getSingleton('customer/session')->setBeforeAuthUrl(Mage::getUrl('*/*/*', array('_secure' => true)));
            $this->getOnepage()->initCheckout();

            $this->loadLayout();

            $blocks = $this->getLayout()->getBlock('content')->unsetChild('onestepcheckout.daskboard');
            $skin   = $this->getLayout()->getBlock('head')->unsetChild('onestepcheckout.head');
            if(Mage::helper('onestepcheckout/data')->getPlatform() == 3)
                $template = $this->getLayout()->getBlock('root')->setTemplate('page/1column.phtml');
            else
                $template = $this->getLayout()->getBlock('root')->setTemplate('page/2columns-right.phtml');

            $this->renderLayout();
        } else {
            $this->getGeoip();
            if($this->_initAction()) {
                //fx for persistent
                if(!$this->isCustomerLoggedIn() && version_compare(Mage::getVersion(), '1.6.0.0', '>=')) {

                    if(Mage::helper('persistent/session')->isPersistent()) {
                        $this->_redirect('persistent/index/saveMethod');
                    }
                }
                //end fix for persistent

                if($blocks = $this->getLayout()->getBlock('checkout.onepage')) { //if exists block checkout.onepage
                    $blocks = $this->getLayout()->getBlock('checkout.onepage')->unsetChildren(); //remove block checkout.onepage default magento
                }
                $this->renderLayout();
            } else
                $this->_redirect('checkout/cart');
        }
    }

    public function saveAction()
    {
        $post   = $this->getRequest()->getPost();
        $html   = array();
        $layout = $this->getLayout();

        if($post) {
            $updates = json_decode($post['updates']);
            // gift wrap
            if(isset($updates->updategiftwrap)){
                $iswrap   = $updates->updategiftwrap;

                if($iswrap){
                    Mage::getSingleton('core/session')->setIsWrap(true);
                }else{
                    Mage::getSingleton('core/session')->setIsWrap(false);
                }
                $this->getOnepage()->getQuote()->collectTotals()->save();
            }

            if(isset($updates->updaterewardpoints)){
                $store_id = Mage::app()->getStore()->getId();
                $step = Mage::helper('rewardpoints/data')->getPointStepConfig($store_id);

                if(!(Mage::helper('rewardpoints')->moduleEnabled()))
                {
                    $html['rewardpoints'] = '';
                }else{
                    $rewardpoints = $this->getRequest()->getParam('rewardpoints');
                    if($rewardpoints < 0) $rewardpoints = - $rewardpoints;
                    $rewardpoints = round(($rewardpoints/$step),0) * $step;
                    if($rewardpoints >= 0)
                    {
                        Mage::helper('rewardpoints')->setPointToCheckOut($rewardpoints);
                        Mage::getSingleton('checkout/session')->getQuote()->collectTotals()->save();
                    }
                }
            }
            if(isset($updates->removeproduct)) {
                $html['removed'] = $this->_removeproduct();
                /* $html['removed']['html_giftbox'], $html['removed']['html_link']  */
            }

            if(isset($updates->updatecart)) {
                $html['cart'] = $this->_updateqty();
                /** get message of cart */
                /* $html['cart']['msg'], $html['cart']['error']  */
            }

            if(isset($updates->updatecouponcode)) {
                $html['coupon'] = $this->_updatecoupon();
                /** get html message of coupon */
                /* $html['coupon']['html'] */
            }

            if(isset($updates->updatebillingaddress)) {
                $html['billing'] = $this->_updatebillingform(isset($updates->changebycountry) ? false : true);
                /** get html billing address */
            }
            if(isset($updates->updateshippingaddress)) {
                $html['shipping'] = $this->_updateshippingform();
                /** get html shipping address */
            }
            if(isset($updates->updateshippingmethod)) {
                $data   = $post['shipping_method'];
                $result = $this->getOnepage()->saveShippingMethod($data);
                if(!$result) {
                    Mage::dispatchEvent('checkout_controller_onepage_save_shipping_method',
                        array('request' => $this->getRequest(),
                              'quote'   => $this->getOnepage()->getQuote()));
                    $this->getOnepage()->getQuote()->collectTotals();
                }
                $this->getOnepage()->getQuote()->collectTotals()->save();
            }

            if(isset($updates->updateshippingtype)) {
                $this->_updateshippingtype();
                /** get html shipping medthod */
                $flag_html_shipping      = true;
                $block_shipping_method   = $layout->createBlock('checkout/onepage_shipping_method_available')->setTemplate('mw_onestepcheckout/daskboard/onepage/shipping_method/available.phtml');
                $html['shipping_method'] = $block_shipping_method->toHtml();
            }

            if(isset($updates->updatepaymentmethod)) {
                $data = $post['payment'];
                try {
                    $this->getOnepage()->savePayment($data);
                } catch (Exception $e) {
                    //$this->_getQuote()->save();
                }
            }
            if(isset($updates->updatepaymenttype)) {
                /** get html all payment type */
                $flag_html_payment      = true;
                $block_payment_method   = $layout->createBlock('checkout/onepage_payment_methods')->setAjax(1)->setTemplate('mw_onestepcheckout/daskboard/onepage/payment/methods.phtml');
                $html['payment_method'] = $block_payment_method->toHtml();
            }
            if(isset($updates->checkvat)) {
                $html['VAT'] = $this->getvatAction();
            }
			/* ------------------- */
			if($post['ship_to_same_address'] == '1') {
				$post['shipping'] = $post['billing'];
			}
			$_SESSION['billing_shipping_sagepay'] = $post;

            $block_totals = $layout->createBlock('checkout/cart_totals');
            $block_totals->setTemplate("mw_onestepcheckout/daskboard/onepage/review/totals.phtml");
            $this->getColspanTotal();
            $block_onepage = $layout->createBlock('checkout/cart');
            //$block_onepage->setTemplate("mw_onestepcheckout/daskboard/onepage/review/info.phtml");
            $template_render = "mw_onestepcheckout/daskboard/onepage/review/item.phtml";
            $html_item       = "";
            foreach ($block_onepage->getItems() as $item) {
                switch ($item->getProduct()->getTypeId()) {
                    case 'simple':
                        $template_render = "mw_onestepcheckout/daskboard/onepage/review/item.phtml";
                    case 'virtual':
                        $block_render = 'checkout/cart_item_renderer';
                        $template_render = "mw_onestepcheckout/daskboard/onepage/review/item.phtml";
                        break;
                    case 'grouped':
                        $block_render = 'checkout/cart_item_renderer_grouped';
                        $template_render = "mw_onestepcheckout/daskboard/onepage/review/item.phtml";
                        break;
                    case 'configurable':
                        $block_render = 'checkout/cart_item_renderer_configurable';
                        $template_render = "mw_onestepcheckout/daskboard/onepage/review/item.phtml";
                        break;
                    case 'downloadable':
                        $block_render = 'downloadable/checkout_cart_item_renderer';
                        $template_render = "mw_onestepcheckout/daskboard/onepage/review/downloadable/item.phtml";
                        break;
                    case 'bundle':
                        $block_render = 'bundle/checkout_cart_item_renderer';
                        $template_render = "mw_onestepcheckout/daskboard/onepage/review/item.phtml";
                        break;
                }

                $html_item .= $block_onepage->addItemRender($item->getProduct()->getTypeId(), $block_render, $template_render)->getItemHtml($item);
            }
            $block_onepage_p = $layout->createBlock('checkout/cart');
            $block_onepage_p->setTemplate("mw_onestepcheckout/daskboard/onepage/review/info.phtml");
            $block_item_before = $layout->createBlock('core/text_list', 'checkout.onepage.review.info.items.before');
            $html              = array(
                'vat'                 => (isset($html['VAT'])) ? $html['VAT'] : "",
                'item_before'         => $block_item_before->toHtml(),
                'items'               => $html_item,
                'review_info'         => $block_onepage_p->renderView(),
                'totals'              => $this->_html_updatecart . $block_totals->renderTotals(null, $this->_colspan),
                'totals_footer'       => $block_totals->renderTotals('footer', $this->_colspan),
                'earn_points'         => ($this->isRWPRunning()) ? Mage::helper('core/mwrewardpoints')->earnPointsOnepageReviewRewardPoints() : '',
                'billing'             => (isset($html['billing'])) ? $html['billing'] : "",
                'shipping'            => (isset($html['shipping'])) ? $html['shipping'] : "",
                'coupon'              => (isset($html['coupon'])) ? $html['coupon'] : "",
                'shipping_method'     => (isset($html['shipping_method'])) ? $html['shipping_method'] : "",
                'payment_method'      => (isset($html['payment_method'])) ? $html['payment_method'] : "",
                'item_payment_method' => (isset($html['item_payment_method'])) ? $html['item_payment_method'] : "",
            );
            echo json_encode($html);
            exit;
        }
    }

    public function getColspanTotal()
    {
        $numcol = 3;
        if(Mage::helper('onestepcheckout')->showImageProduct())
            $numcol = $numcol + 1;
        if(Mage::getStoreConfig("onestepcheckout/config/allowremoveproduct"))
            $numcol = $numcol + 1;
        if(Mage::getStoreConfig("onestepcheckout/config/updateqtyproduct")) {
            $this->_html_updatecart = "
                                    <tr class='first'>
                                        <td class=\"a-right\" colspan=\"" . ($numcol + 1) . "\">
                                        <button class=\"button btn-update-cart\" title=\"" . $this->__('Update Shopping Cart') . "\" type=\"button\"><span><span>" . $this->__('Update Cart') . "</span></span></button>
                                        </td>
                                    </tr>";
        }

        $this->_colspan = Mage::helper('tax')->displayCartBothPrices() ? 5 : $numcol;
    }

    public function _initAction()
    {

        Mage::getSingleton('checkout/session')->setCartWasUpdated(false);
        Mage::getSingleton('customer/session')->setBeforeAuthUrl(Mage::getUrl('*/*/*', array('_secure' => true)));
        $this->getOnepage()->initCheckout();

        //init address (if not init address, it can't load ajax)
        $this->initInfoaddress();
        $defaultpaymentmethod = $this->initpaymentmethod(); // ex: $defaultpaymentmethod='checkmo';
        if($defaultpaymentmethod) {
            try {
                Mage::getSingleton('checkout/session')->getQuote()->getShippingAddress()->setPaymentMethod($defaultpaymentmethod);
                $payment = Mage::getSingleton('checkout/session')->getQuote()->getPayment();
                $payment->importData(Array('method' => $defaultpaymentmethod));
            } catch (Exception $e) {
            }
        }
        $defaultshippingmethod = $this->initshippingmethod(); //ex: $defaultshippingmethod='flatrate_flatrate';
        if($defaultshippingmethod) {
            Mage::getSingleton('checkout/session')->getQuote()->getShippingAddress()->setShippingMethod($defaultshippingmethod);
        }

        // if Promotions ->Shopping Cart Price Rule has "apply action" = cart_fixed
        // recalculate discount
        $applyrule   = $this->getQuote()->getAppliedRuleIds(); //
        $applyaction = Mage::getModel('salesrule/rule')->load($applyrule)->getSimpleAction();
        if($applyaction != 'cart_fixed') {
            Mage::getSingleton('checkout/session')->getQuote()->setTotalsCollectedFlag(false);
        }
        ///end init address

        $this->loadLayout();
        $this->_initLayoutMessages('customer/session');
        $this->_initLayoutMessages('checkout/session');
        $this->_initLayoutMessages('catalog/session');
        Mage::getSingleton('catalog/session')->getData('messages')->clear();
        Mage::getSingleton('checkout/session')->getData('messages')->clear(); // clear all message
        $this->getLayout()->getBlock('head')->setTitle($this->__('Checkout'));

        return true;
    }

    public function initshippingmethod()
    {
        $listmethod      = '';
        $guessCustomer   = Mage::getSingleton('checkout/session')->getQuote();
        $addresses       = $guessCustomer->getShippingAddress();
        $list_shipmethod = $addresses->getGroupedAllShippingRates();
        foreach ($list_shipmethod as $code => $_rates) {
            $listmethod[] = $code;
        }
        if(!$guessCustomer->isVirtual()) {
            if($listmethod == null) {
                return;
            }
            if(sizeof($listmethod) == 1) {
                return $listmethod[0] . '_' . $listmethod[0];
            } else {
                foreach ($listmethod as $methodname) {
                    if(Mage::getStoreConfig("onestepcheckout/config/default_shippingmethod") == $methodname . '_' . $methodname) {
                        return $methodname . '_' . $methodname;
                    }
                }
            }
        }

        return;
    }

    //check payment method for satisfied
    public function _canUseMethod($method)
    {
        if(!$method->canUseForCountry($this->getQuote()->getBillingAddress()->getCountry())) {
            return false;
        }
        if(!$method->canUseForCurrency(Mage::app()->getStore()->getBaseCurrencyCode())) {
            return false;
        }
        /**
         * Checking for min/max order total for assigned payment method
         */
        $total    = Mage::getSingleton('checkout/session')->getQuote()->getBaseGrandTotal();
        $minTotal = $method->getConfigData('min_order_total');
        $maxTotal = $method->getConfigData('max_order_total');
        if((!empty($minTotal) && ($total < $minTotal)) || (!empty($maxTotal) && ($total > $maxTotal))) {
            return false;
        }

        return true;
    }

    public function initpaymentmethod()
    {
        $listmethod     = '';
        $guessCustomer  = Mage::getSingleton('checkout/session')->getQuote();
        $store          = $guessCustomer ? $guessCustomer->getStoreId() : null;
        $methods        = Mage::helper('payment')->getStoreMethods($store, $guessCustomer);
        $billingCountry = Mage::getSingleton('checkout/session')->getQuote()->getBillingAddress()->getCountryId();
        foreach ($methods as $key => $method) {
            if($this->_canUseMethod($method)) {
                $listmethod[] = $method->getCode();
            }
        }
        try {
            if($listmethod == null or $listmethod == '') {
                return;
            }
            if(sizeof($listmethod) == 1) {
                return $listmethod[0];
            } else {
                foreach ($listmethod as $methodname) {
                    if(Mage::getStoreConfig("onestepcheckout/config/default_paymentmethod") == $methodname) { //echo $methodname.'=='.Mage::getStoreConfig("onestepcheckout/config/default_paymentmethod")."<br>";
                        return $methodname;
                    }
                }
            }

            return;
        } catch (Exception $e) {
            echo $e->getMessage();
            die;
        }
    }

    public function initInfoaddress()
    {
        $quoteAddress = Mage::getSingleton('checkout/session')->getQuote()->getBillingAddress();
        $countryid    = $quoteAddress->getCountryId();
        $postcode     = $quoteAddress->getPostcode();
        $region       = $quoteAddress->getRegion();
        $regionid     = $quoteAddress->getRegionId();
        $city         = $quoteAddress->getCity();

        //var_dump($countryid,$postcode,$region,$regionid,$city); die();
        //$temp = Mage::getSingleton('checkout/session')->getQuote()->collectTotals();
        //var_dump($temp->getData()); die();

        if(!empty($countryid) && strpos('n/a', $countryid) === false)
            Mage::getSingleton('core/session')->setCountryId($countryid);
        else
            Mage::getSingleton('core/session')->unsCountryId();

        if(!empty($postcode) && strpos('n/a', $postcode) === false)
            Mage::getSingleton('core/session')->setPostcode($postcode);
        else
            Mage::getSingleton('core/session')->unsPostcode();

        if(!empty($region) && strpos('n/a', $region) === false)
            Mage::getSingleton('core/session')->setRegion($region);
        else
            Mage::getSingleton('core/session')->unsRegion();

        if(!empty($regionid) && strpos('n/a', $regionid) === false)
            Mage::getSingleton('core/session')->setRegionId($regionid);
        else
            Mage::getSingleton('core/session')->unsRegionId();

        if(!empty($city) && strpos('n/a', $city) === false)
            Mage::getSingleton('core/session')->setCity($city);
        else
            Mage::getSingleton('core/session')->unsCity();

        $customerAddressId = '';

        $countrygeo = (string)Mage::registry('Countrycode');
        if(Mage::getStoreConfig('onestepcheckout/config/enable_geoip') && $countrygeo != null) {

            if(!(Mage::getSingleton('core/session')->getCountryId())) {
                $countryid = (string)Mage::registry('Countrycode');
                if($countryid != "")
                    Mage::getSingleton('core/session')->setCountryId($countryid);
            }

            if(!(Mage::getSingleton('core/session')->getPostcode())) {
                $postcode = (string)Mage::registry('Zipcode');
                if($postcode != "")
                    Mage::getSingleton('core/session')->setPostcode($postcode);

            }

            if(!(Mage::getSingleton('core/session')->getRegion())) {
                $region = (string)Mage::registry('Regionname');
                if($region != "")
                    Mage::getSingleton('core/session')->setRegion($region);
            }

            if(Mage::getModel('customer/address_abstract')->getRegionModel((string)Mage::registry('Regionid')))
                if(!(Mage::getSingleton('core/session')->getRegionId())) {
                    $regionid = (string)Mage::registry('Regionid');
                    if($regionid != "")
                        Mage::getSingleton('core/session')->setRegionId($regionid);
                }

            if(!(Mage::getSingleton('core/session')->getCity())) {
                $city = (string)Mage::registry('City');
                if($city != "")
                    Mage::getSingleton('core/session')->setCity($city);
            }

        } elseif(Mage::getStoreConfig('onestepcheckout/config/default_country')) {
            if(empty($countryid))
                $countryid = Mage::getStoreConfig('onestepcheckout/config/default_country');
        } else {
            if(empty($countryid))
                $countryid = Mage::getStoreConfig('general/country/default');
        }
        $postData = array(
            'address_id'           => '',
            'firstname'            => '',
            'lastname'             => '',
            'company'              => '',
            'email'                => $quoteAddress->getEmail(),
            'street'               => array('', ''),
            'city'                 => $city,
            'region_id'            => $regionid,
            'region'               => $region,
            'postcode'             => $postcode,
            'country_id'           => $countryid,
            'telephone'            => '',
            'fax'                  => '',
            'save_in_address_book' => '0'
        );

        if(Mage::getSingleton('customer/session')->isLoggedIn()) {
            $customerAddressId = Mage::getSingleton('customer/session')->getCustomer()->getDefaultBilling();
            //var_dump($customerAddressId);die();
        }

        if(($postData['country_id'] != '')  OR $customerAddressId) {
            $postData = $this->filterdata($postData, "init");
            //var_dump($postData);die();
            //echo"<pre>";var_dump($postData);die();
            if(version_compare(Mage::getVersion(), '1.4.0.1', '>='))
                {

                    $data = $this->_filterPostData($postData);
                }
            else
                $data = $postData;
            if(isset($data['email'])) {
                $data['email'] = trim($data['email']);
            }

            $this->saveBilling($data, $customerAddressId);
            $this->saveShipping($data, $customerAddressId);
        } else {
            $this->_getQuote()->getShippingAddress()
                ->setCountryId('')
                ->setPostcode('')
                ->setCollectShippingRates(true);
            $this->_getQuote()->save();

            // $this->loadLayout()->renderLayout();
            return;
        }
    }

    public function saveShipping($data, $customerAddressId)
    {
        if(empty($data)) {
            return array('error' => -1, 'message' => $this->_helper->__('Invalid data.'));
        }
        $address = $this->getQuote()->getShippingAddress();

        if(!empty($customerAddressId)) {
            $customerAddress = Mage::getModel('customer/address')->load($customerAddressId);
            if($customerAddress->getId()) {
                if($customerAddress->getCustomerId() != $this->getQuote()->getCustomerId()) {
                    return array('error'   => 1,
                                 'message' => $this->_helper->__('Customer Address is not valid.')
                    );
                }
                $address->importCustomerAddress($customerAddress);
            }
        } else {
            unset($data['address_id']);
            $address->addData($data);
        }
        $address->implodeStreetAddress();
        $address->setCollectShippingRates(true);
    }

    public function saveBilling($data, $customerAddressId)
    {
        if(empty($data)) {
            return array('error' => -1, 'message' => $this->_helper->__('Invalid data.'));
        }
        $address = $this->getQuote()->getBillingAddress();
        if(!empty($customerAddressId)) {
            $customerAddress = Mage::getModel('customer/address')->load($customerAddressId);
            if($customerAddress->getId()) {
                if($customerAddress->getCustomerId() != $this->getQuote()->getCustomerId()) {
                    return array('error'   => 1,
                                 'message' => $this->_helper->__('Customer Address is not valid.')
                    );
                }
                $address->importCustomerAddress($customerAddress);
            }
        } else {
            unset($data['address_id']);
            $address->addData($data);
        }

        $address->implodeStreetAddress();
        if(!$this->getQuote()->isVirtual()) {
            $shipping = $this->getQuote()->getShippingAddress();
            $shipping->setSameAsBilling(0);
        }
    }

    protected function _processValidateCustomer(Mage_Sales_Model_Quote_Address $address)
    {
        $dob = '';
        if($address->getDob()) {
            $dob = Mage::app()->getLocale()->date($address->getDob(), null, null, false)->toString('yyyy-MM-dd');
            $this->getQuote()->setCustomerDob($dob);
        }
        if($address->getTaxvat()) {
            $this->getQuote()->setCustomerTaxvat($address->getTaxvat());
        }
        if($address->getGender()) {
            $this->getQuote()->setCustomerGender($address->getGender());
        }
        if($this->getQuote()->getCheckoutMethod() == 'register') {
            $customer = Mage::getModel('customer/customer');
            $this->getQuote()->setPasswordHash($customer->encryptPassword($address->getCustomerPassword()));
            foreach (array(
                         'firstname'    => 'firstname',
                         'lastname'     => 'lastname',
                         'email'        => 'email',
                         'password'     => 'customer_password',
                         'confirmation' => 'confirm_password',
                         'taxvat'       => 'taxvat',
                         'gender'       => 'gender',
                     ) as $key => $dataKey) {
                $customer->setData($key, $address->getData($dataKey));
            }
            if($dob) {
                $customer->setDob($dob);
            }
            $validationResult = $customer->validate();
            if(true !== $validationResult && is_array($validationResult)) {
                return array(
                    'error'   => -1,
                    'message' => implode(', ', $validationResult)
                );
            }
        } elseif(self::METHOD_GUEST == $this->getQuote()->getCheckoutMethod()) {
            $email = $address->getData('email');
            if(!Zend_Validate::is($email, 'EmailAddress')) {
                return array(
                    'error'   => -1,
                    'message' => $this->_helper->__('Invalid email address "%s"', $email)
                );
            }
        }

        return true;
    }

    public function updateshippingmethodAction()
    {
        $data   = $this->getRequest()->getPost('shipping_method', '');
        $result = $this->getOnepage()->saveShippingMethod($data);
        if(!$result) {
            Mage::dispatchEvent('checkout_controller_onepage_save_shipping_method',
                array('request' => $this->getRequest(),
                      'quote'   => $this->getOnepage()->getQuote()));
            $this->getOnepage()->getQuote()->collectTotals();
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        }
        $this->getOnepage()->getQuote()->collectTotals()->save();
        $this->loadLayout()->renderLayout();
    }

    public function updatepaymentmethodAction()
    {
        $data = $this->getRequest()->getPost('payment');

        try {
            $this->getOnepage()->savePayment($data);
        } catch (Exception $e) {
            $this->_getQuote()->save();
        }
        if($data['ajax'] == 'true') {
            exit;
        }
        $this->loadLayout();
        $this->renderLayout();
    }

    public function updateemailmsgAction()
    {
        if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
            $email                = (string)$this->getRequest()->getParam('email');
            $websiteid            = Mage::app()->getWebsite()->getId();
            $store                = Mage::app()->getStore();
            $customer             = Mage::getSingleton('customer/customer');
            $customer->website_id = $websiteid;
            $customer->setStore($store);
            $customer->loadByEmail($email);
            if($customer->getId()) {
                echo "0";
            } else {
                echo "1";
            }
        }else{
            echo "1";
        }

        return;
    }

    protected function _removeproduct()
    {
        $id         = (int)$this->getRequest()->getParam('id');
        $hasgiftbox = $this->getRequest()->getParam('hasgiftbox');
        if($id) {
            try {
                Mage::getSingleton('checkout/cart')->removeItem($id)
                    ->save();
                $qty  = Mage::getSingleton('checkout/cart')->getItemsQty();
                $link = $this->getQtyAfterMyCart($qty);

            } catch (Exception $e) {
                return array(
                    'error' => 1,
                    'msg'   => Mage::helper('onestepcheckout')->__('Cannot remove the item.'),
                );
            }
        }
        if(!$this->_getQuote()->getItemsCount()) {
            return array(
                'error' => 1,
                'msg'   => 'EMPTY',
            );
        } else {
            if($hasgiftbox) {
                return array(
                    'error'        => 1,
                    'msg'          => '',
                    'html_giftbox' => json_encode($this->renderGiftbox()),
                    'html_link'    => $link,
                );
            } else {
                return array(
                    'error'        => 1,
                    'msg'          => '',
                    'html_giftbox' => '',
                    'html_link'    => $link,
                );
            }
        }
    }

    public function removeproductAction()
    {
        $id         = (int)$this->getRequest()->getParam('id');
        $hasgiftbox = $this->getRequest()->getParam('hasgiftbox');
        if($id) {
            try {
                Mage::getSingleton('checkout/cart')->removeItem($id)
                    ->save();
                $qty  = Mage::getSingleton('checkout/cart')->getItemsQty();
                $link = $this->getQtyAfterMyCart($qty);

            } catch (Exception $e) {
                $success = 0;
                echo '{"r":"' . $success . '","error":"' . Mage::helper('onestepcheckout')->__('Cannot remove the item.') . '","view":' . json_encode($this->renderReview()) . ',"link":"' . $link . '"}';
                die;

                return;
            }
        }
        $success = 1;
        if(!$this->_getQuote()->getItemsCount()) {
            echo '{"r":"' . $success . '","view":"<script type=\"text/javascript\">window.location=\"' . Mage::getUrl('checkout/onepage') . '\"</script>","link":"' . $link . '"}';
            die;

            return;
        } else {
            if($hasgiftbox) {
                echo '{"r":"' . $success . '","view":' . json_encode($this->renderReview()) . ',"giftbox":' . json_encode($this->renderGiftbox()) . ',"link":"' . $link . '"}';
                die;

                return;
            } else {
                echo '{"r":"' . $success . '","view":' . json_encode($this->renderReview()) . ',"link":"' . $link . '"}';
                die;

                return;
            }
        }
    }

    protected function _updateqty()
    {
        $qty             = 0;
        $error_updateqty = "";
        try {
            $cartData = $this->getRequest()->getParam('cart');
            if(is_array($cartData)) {
                $filter = new Zend_Filter_LocalizedToNormalized(
                    array('locale' => Mage::app()->getLocale()->getLocaleCode())
                );

                foreach ($cartData as $index => $data) {
                    if(isset($data['qty'])) {
                        $cartData[$index]['qty'] = $filter->filter(trim($data['qty']));
                        $qty += $cartData[$index]['qty'];
                    }
                }
                $link = $this->getQtyAfterMyCart($qty);
                $cart = Mage::getSingleton('checkout/cart');
                if(!$cart->getCustomerSession()->getCustomer()->getId() && $cart->getQuote()->getCustomerId()) {
                    $cart->getQuote()->setCustomerId(null);
                }
                $cartData = $cart->suggestItemsQty($cartData);
                $cart->updateItems($cartData)
                    ->save();


                foreach ($this->getQuote()->getAllItems() as $item) {
                    if($item->getMessage() && trim($item->getMessage())) {
                        $error_updateqty = $this->__('There are some errors when update quantity product.');
                        break;
                    }
                }
            }
            Mage::getSingleton('checkout/session')->setCartWasUpdated(true);
        } catch (Mage_Core_Exception $e) {
            return array(
                'error' => 0,
                'msg'   => $e->getMessage()
            );
            exit;
        } catch (Exception $e) {
            return array(
                'error' => 0,
                'msg'   => Mage::helper('onestepcheckout')->__('Cannot update shopping cart.')
            );
            exit;
        }

        return array(
            'error' => 1,
            'msg'   => ''
        );
        exit;
    }

    public function updateqtyAction()
    {
        $qty             = 0;
        $error_updateqty = "";
        try {
            $cartData = $this->getRequest()->getParam('cart');
            if(is_array($cartData)) {
                $filter = new Zend_Filter_LocalizedToNormalized(
                    array('locale' => Mage::app()->getLocale()->getLocaleCode())
                );

                foreach ($cartData as $index => $data) {
                    if(isset($data['qty'])) {
                        $cartData[$index]['qty'] = $filter->filter(trim($data['qty']));
                        $qty += $cartData[$index]['qty'];
                    }
                }
                $link = $this->getQtyAfterMyCart($qty);
                $cart = Mage::getSingleton('checkout/cart');
                if(!$cart->getCustomerSession()->getCustomer()->getId() && $cart->getQuote()->getCustomerId()) {
                    $cart->getQuote()->setCustomerId(null);
                }
                $cartData = $cart->suggestItemsQty($cartData);
                $cart->updateItems($cartData)
                    ->save();


                foreach ($this->getQuote()->getAllItems() as $item) {
                    if($item->getMessage() && trim($item->getMessage())) {
                        $error_updateqty = $this->__('There are some errors when update quantity product.');
                        break;
                    }
                }
            }
            Mage::getSingleton('checkout/session')->setCartWasUpdated(true);
        } catch (Mage_Core_Exception $e) {
            $success = 0;
            echo '{"r":"' . $success . '","error":"' . $e->getMessage() . '","view":' . json_encode($this->renderReview()) . ',"link":"' . $link . '","error_updateqty":"' . $error_updateqty . '"}';
            die;

            return;
        } catch (Exception $e) {
            $success = 0;
            echo '{"r":"' . $success . '","error":"' . Mage::helper('onestepcheckout')->__('Cannot update shopping cart.') . '","view":' . json_encode($this->renderReview()) . ',"link":"' . $link . '","error_updateqty":"' . $error_updateqty . '"}';
            die;

            return;
        }
        $success = 1;
        if(!$this->_getQuote()->getItemsCount()) {
            echo '{"r":"' . $success . '","view":"<script type=\"text/javascript\">window.location=\"' . Mage::getUrl('checkout/onepage') . '\"</script>","link":"' . $link . '","error_updateqty":"' . $error_updateqty . '"}';
            die;

            return;
        } else {
            echo '{"r":"' . $success . '","view":' . json_encode($this->renderReview()) . ',"link":"' . $link . '","error_updateqty":"' . $error_updateqty . '"}';
            die;

            return;
        }
    }

    public function updatecouponAction()
    {
        if(!$this->_getQuote()->getItemsCount()) {
            echo '{"r":"0","coupon":' . json_encode($this->renderCoupon()) . ',"view":' . json_encode($this->renderReview()) . '}';
            exit;
        }
        $couponCode = (string)$this->getRequest()->getParam('coupon_code');
        if($this->getRequest()->getParam('remove') == 1) {
            $couponCode = '';
        }
        $oldCouponCode = $this->_getQuote()->getCouponCode();
        if(!strlen($couponCode) && !strlen($oldCouponCode)) {
            echo '{"r":"0","coupon":' . json_encode($this->renderCoupon()) . ',"view":' . json_encode($this->renderReview()) . '}';
            die;

            return;
        }
        try {
            $this->_getQuote()->getShippingAddress()->setCollectShippingRates(true);
            $this->_getQuote()->setCouponCode(strlen($couponCode) ? $couponCode : '')
                ->collectTotals()
                ->save();
            if($couponCode) {
                if($couponCode == $this->_getQuote()->getCouponCode()) {
                    Mage::getSingleton('checkout/session')->addSuccess(
                        $this->__('Coupon code "%s" was applied.', Mage::helper('core')->htmlEscape($couponCode))
                    );
                } else {
                    Mage::getSingleton('checkout/session')->addError(
                        $this->__('Coupon code "%s" is not valid.', Mage::helper('core')->htmlEscape($couponCode))
                    );
                }
            } else {
                Mage::getSingleton('checkout/session')->addSuccess($this->__('Coupon code was canceled.'));
            }
        } catch (Mage_Core_Exception $e) {
            Mage::getSingleton('checkout/session')->addError($e->getMessage());
        } catch (Exception $e) {
            Mage::getSingleton('checkout/session')->addError($this->__('Cannot apply the coupon code.'));
        }
        $this->_initLayoutMessages('checkout/session');
        $success = 1;
        echo '{"r":"' . $success . '","coupon":' . json_encode($this->renderCoupon()) . ',"view":' . json_encode($this->renderReview()) . '}';
        die;

        return;
    }

    protected function _updatecoupon()
    {
        $this->_initLayoutMessages('checkout/session');
        if(!$this->_getQuote()->getItemsCount()) {
            return array(
                'html' => $this->renderCoupon(),
            );
            exit;
        }
        $couponCode = (string)$this->getRequest()->getParam('coupon_code');
        if($this->getRequest()->getParam('remove') == 1) {
            $couponCode = '';
        }
        $oldCouponCode = $this->_getQuote()->getCouponCode();
        if(!strlen($couponCode) && !strlen($oldCouponCode)) {
            return array(
                'html' => $this->renderCoupon(),
            );
            exit;
        }
        try {
            $this->_getQuote()->getShippingAddress()->setCollectShippingRates(true);
            $this->_getQuote()->setCouponCode(strlen($couponCode) ? $couponCode : '')
                ->collectTotals()
                ->save();
            if($couponCode) {
                if($couponCode == $this->_getQuote()->getCouponCode()) {
                    Mage::getSingleton('checkout/session')->addSuccess(
                        $this->__('Coupon code "%s" was applied.', Mage::helper('core')->htmlEscape($couponCode))
                    );
                } else {
                    Mage::getSingleton('checkout/session')->addError(
                        $this->__('Coupon code "%s" is not valid.', Mage::helper('core')->htmlEscape($couponCode))
                    );
                }
            } else {
                Mage::getSingleton('checkout/session')->addSuccess($this->__('Coupon code was canceled.'));
            }
        } catch (Mage_Core_Exception $e) {
            Mage::getSingleton('checkout/session')->addError($e->getMessage());
        } catch (Exception $e) {
            Mage::getSingleton('checkout/session')->addError($this->__('Cannot apply the coupon code.'));
        }
        $this->_initLayoutMessages('checkout/session');
        $success = 1;

        return array(
            'html' => $this->renderCoupon(),
        );
        exit;
    }

    public function updateRefferalAction()
    {
        Mage::getSingleton('checkout/session')->unsReferralError();
        $referral_code = $this->getRequest()->getParam('referral_code');
        if($this->getRequest()->getParam('remove') == 1) {
            Mage::getSingleton('checkout/session')->unsetData('referral_code');
            $referral_code = '';
        }
        if($referral_code != '') {
            $check = Mage::helper('affiliate')->checkReferralCodeCart($referral_code);
            if($check == 0) {
                Mage::getSingleton('checkout/session')->setReferralError($this->__('The referral code is invalid.'));
                echo '{"r":"0","coupon":' . json_encode($this->renderReferral()) . ',"view":' . json_encode($this->renderReview()) . '}';
                die;

                return;
            }
        }
        try {
            if($referral_code != '') {
                Mage::getSingleton('checkout/session')->setReferralCode($referral_code); // set session
                Mage::getSingleton('checkout/session')->setReferralSuccess($this->__('The referral code was applied successfully.'));
            } else {
                Mage::getSingleton('checkout/session')->setReferralSuccess($this->__('The referral code has been cancelled successfully.'));
            }
        } catch (Mage_Core_Exception $e) {
            Mage::getSingleton('checkout/session')->setReferralError($e->getMessage());
        } catch (Exception $e) {
            Mage::getSingleton('checkout/session')->setReferralError($this->__('Can not apply the referral code.'));
        }
        $this->_initLayoutMessages('checkout/session');
        $success = 1;
        echo '{"r":"' . $success . '","coupon":' . json_encode($this->renderReferral()) . ',"view":' . json_encode($this->renderReview()) . '}';
        die;

        return;
    }

    public function renderReferral()
    {
        $layout = $this->getLayout();
        $update = $layout->getUpdate();
        $update->load('checkout_onepage_index');
        $layout->generateXml();
        $layout->generateBlocks();
        $output = $layout->getBlock('credit.checkout.cart.referral.code.osc')->toHtml();

        return $output;
    }

    public function renderReview()
    {
        $layout = $this->getLayout();
        $update = $layout->getUpdate();
        $update->load('checkout_onepage_index');
        $layout->generateXml();
        $layout->generateBlocks();
        $output = $layout->getBlock('info')->toHtml();

        return $output;
    }

    public function renderCoupon()
    {
        $layout = $this->getLayout();
        $update = $layout->getUpdate();
        $update->load('checkout_onepage_index');
        $layout->generateXml();
        $layout->generateBlocks();
        $output = $layout->getBlock('checkout.onepage.coupon')->toHtml();

        return $output;
    }

    public function renderBillingForm()
    {
        $layout = $this->getLayout();
        $output = $layout->createBlock('onestepcheckout/checkout_onepage_billing_sortbilling')->setTemplate("mw_onestepcheckout/daskboard/onepage/billing/sortbilling.phtml")->toHtml();

        return $output;
    }

    public function renderShippingForm()
    {
        $layout = $this->getLayout();
        $output = $layout->createBlock('onestepcheckout/checkout_onepage_shipping_sortshipping')->setTemplate("mw_onestepcheckout/daskboard/onepage/shipping/sortshipping.phtml")->toHtml();

        return $output;
    }

    public function renderGiftbox()
    {
        $layout = $this->getLayout();
        $update = $layout->getUpdate();
        $update->load('checkout_onepage_index');
        $layout->generateXml();
        $layout->generateBlocks();
        $output = $layout->getBlock('onestepcheckout.onepage.shipping_method.additional')->toHtml();

        return $output;
    }
    public function renderReviewInfo(){
        $layout = $this->getLayout();
        $update = $layout->getUpdate();
        $update->load('checkout_onepage_index');
        $layout->generateXml();
        $layout->generateBlocks();
        $output = $layout->getBlock('info')->toHtml();

        return $output;
    }
    public function updatepaymenttypeAction()
    {
        if($this->notshiptype == 1) {
            $this->loadLayout()->renderLayout();
        } else {
            $this->updateshippingtypeAction();
        }
    }

    public function getvatAction()
    {
        $countrycode = $this->getRequest()->getParam('countrycode');
        $vat         = $this->getRequest()->getParam('vatnumber');
        if(empty($vat)) {
            return '';
        }
        $result = @file_get_contents('http://vatid.eu/check/' . $countrycode . '/' . $vat);
        if($result) {
            return $result;
        } else {
            $urlcheckvat = 'http://isvat.appspot.com/' . $countrycode . '/' . $vat;
            $result      = @file_get_contents($urlcheckvat);
            if((string)$result == "true" || (string)$result == "false") {
                return '<?xml version="1.0" encoding="UTF-8"?>
			<response>
			  <country-code>' . $countrycode . '</country-code>
			  <vat-number>' . $vat . '</vat-number>
			  <valid>' . $result . '</valid>
			  <name>--ds-</name>
			  <address>---</address>
			</response>';

            } else {
                return '<?xml version="1.0" encoding="UTF-8"?>
			<response>
			  <country-code>' . $countrycode . '</country-code>
			  <vat-number>' . $vat . '</vat-number>
			  <valid>false</valid>
			  <name>---</name>
			  <address>---</address>
			</response>';
            }
        }
    }

    protected function _updateshippingtype()
    {
        $this->clearBillingSession();
        $this->notshiptype = 1;
        $isbilling         = "billing";
        if($this->getRequest()->getPost('ship_to_same_address') == "1") {
            $isbilling = "billing";
        } else {
            $isbilling = "shipping";
        }
        $postData          = $this->getRequest()->getPost($isbilling, array());
        $customerAddressId = $this->getRequest()->getPost($isbilling . '_address_id', false);

        if($this->getRequest()->getPost('withoutaddressselect') == "true") {
            if($this->getRequest()->getPost($isbilling . '_address_id') != "") {
                $customerAddressId = "";
            }
        }


        if(($postData['country_id'] != '')  OR $customerAddressId) {
            $postData                     = $this->filterdata($postData);
            $postData['use_for_shipping'] = '1';
            if(version_compare(Mage::getVersion(), '1.4.0.1', '>='))
                $data = $this->_filterPostData($postData);
            if(isset($data['email'])) {
                $data['email'] = trim($data['email']);
            }
            if($isbilling == "billing") {
                $this->getOnepage()->saveBilling($data, $customerAddressId);
            } else {
                $this->getOnepage()->saveShipping($data, $customerAddressId);
            }
        } else {
            $this->_getQuote()->getShippingAddress()
                ->setCountryId('')
                ->setPostcode('')
                ->setCollectShippingRates(true);
            $this->_getQuote()->save();
        }
    }

    public function updateshippingtypeAction()
    {
        $this->clearBillingSession();
        $this->notshiptype = 1;
        if($this->getRequest()->isPost()) {
            $isbilling = "billing";
            if($this->getRequest()->getPost('ship_to_same_address') == "1") {
                $isbilling = "billing";
            } else {
                $isbilling = "shipping";
            }
            $postData          = $this->getRequest()->getPost($isbilling, array());
            $customerAddressId = $this->getRequest()->getPost($isbilling . '_address_id', false);
            if($this->getRequest()->getPost($isbilling . '_address_id') != "") {
                $customerAddressId = "";
            }
            if(($postData['country_id'] != '')  OR $customerAddressId) {
                $postData                     = $this->filterdata($postData);
                $postData['use_for_shipping'] = '1';
                if(version_compare(Mage::getVersion(), '1.4.0.1', '>='))
                    $data = $this->_filterPostData($postData);
                if(isset($data['email'])) {
                    $data['email'] = trim($data['email']);
                }
                if($isbilling == "billing") {
                    $result = $this->getOnepage()->saveBilling($data, $customerAddressId);
                } else
                    $result = $this->getOnepage()->saveShipping($data, $customerAddressId);
            } else {
                $this->_getQuote()->getShippingAddress()
                    ->setCountryId('')
                    ->setPostcode('')
                    ->setCollectShippingRates(true);
                $this->_getQuote()->save();
                $this->loadLayout()->renderLayout();

                return;
            }
        }
        $this->loadLayout()->renderLayout();
    }

    public function updatetimepickerAction()
    {
        $dateisnow            = $this->getRequest()->getPost('now');
        $starttime            = $this->getRequest()->getPost('stime');
        $starray              = explode(":", $starttime);
        $count_stimetominutes = $starray[0] * 60 + $starray[1];
        $endtime              = $this->getRequest()->getPost('etime');
        $etarray              = explode(":", $endtime);
        $count_etimetominutes = $etarray[0] * 60 + $etarray[1];
        $count_timenow        = date("G", Mage::getModel("core/date")->timestamp(time())) * 60 + date("i", Mage::getModel("core/date")->timestamp(time()));
        if($dateisnow) {
            if($count_timenow >= $count_stimetominutes) {
                if($count_timenow < $count_etimetominutes) {
                    echo date("G", Mage::getModel("core/date")->timestamp(time())) . ":" . date("i", Mage::getModel("core/date")->timestamp(time())); //cho phep in tren timepicker time hien tai
                } else {
                    echo "";
                }
            } else {
                echo $starttime;
            }
        } else
            echo $starttime;
    }

    public function updateloginAction()
    {
        $email    = $this->getRequest()->getPost('email');
        $password = $this->getRequest()->getPost('password');
        if($this->isCustomerLoggedIn()) {
            $this->_redirect('*/*/');

            return;
        }
        if($this->getRequest()->isPost()) {
            if(!empty($email) && !empty($password)) {
                try {
                    Mage::getSingleton('customer/session')->login($email, $password);
                } catch (Mage_Core_Exception $e) {
                } catch (Exception $e) {
                }
            }
        }
        if(Mage::getSingleton('customer/session')->isLoggedIn()) {
            echo "1";
        } else {
            echo "0";
        }
    }

    public function forgotpassAction()
    {
        $email      = $this->getRequest()->getPost('email');
        $emailerror = "0";
        if($email) {
            if(!Zend_Validate::is($email, 'EmailAddress')) {
                $this->_getSession()->setForgottenEmail($email);
                $emailerror = "0";
                echo $emailerror;

                return $emailerror;
            }
            $customer = Mage::getModel('customer/customer')
                ->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
                ->loadByEmail($email);
            if($customer->getId()) {
                try {
                    $newPassword = $customer->generatePassword();
                    $customer->changePassword($newPassword, false);
                    $customer->sendPasswordReminderEmail();
                    $emailerror = "1";
                    echo $emailerror;

                    return $emailerror;
                } catch (Exception $e) {
                }
            } else {
                $emailerror = "2";
                Mage::getSingleton('customer/session')->setForgottenEmail($email);
                echo $emailerror;

                return;
            }
        } else {
            $emailerror = "0";
            echo $emailerror;

            return $emailerror;
        }
    }

    public function updateordermethodAction()
    {
        //Zend_debug::dump($this->getRequest()->getPost());
		//Mage::log($this->getRequest()->getPost()); return;
		if(Mage::helper('onestepcheckout')->isDDateRunning())
        {
            $ddatecheck = $this->getRequest()->getpost('ddate');
            if($ddatecheck['date'] == '')
            {
                echo json_encode(array(
                        'error' => 1,
                        'msg'   => Mage::helper('onestepcheckout')->__('Please select Delivery Time!')
                ));exit;
            }
        }
        $vote = $this->getRequest()->getPost('vote');
        if($vote) {
            $this->voteAdd();
        }
        if(!$this->isCustomerLoggedIn()) {
            if(isset($_POST['register_new_account'])) {
                $isguest = $this->getRequest()->getPost('register_new_account');
                if($isguest == '1' or Mage::helper('onestepcheckout')->haveProductDownloadable()) { //if checkbox register_new_accoutn checked or exist downloadable product, create new acc
                    $result_save_method = $this->getOnepage()->saveCheckoutMethod('register');


                } else {
                    $result_save_method = $this->getOnepage()->saveCheckoutMethod('guest');
                }
            } else {
                //fix for persistent
                if($this->getCheckout()->getPersistentRegister() && $this->getCheckout()->getPersistentRegister() == "register") {
                    $result_save_method = $this->getOnepage()->saveCheckoutMethod('register');
                } //end fix for persistent
                else {
                    if((!Mage::getStoreConfig('onestepcheckout/config/allowguestcheckout') || !Mage::getStoreConfig('checkout/options/guest_checkout') || Mage::helper('onestepcheckout')->haveProductDownloadable())) {
                        $result_save_method = $this->getOnepage()->saveCheckoutMethod('register');
                    } else {
                        $result_save_method = $this->getOnepage()->saveCheckoutMethod('guest');
                    }
                }
            }
        }
        if($this->getRequest()->isPost()) {
            $data_save_billing = $this->filterdata($this->getRequest()->getPost('billing', array()), false);
            if($this->isCustomerLoggedIn()) {
                $this->saveAddress('billing', $data_save_billing);
            }
            $customerAddressId = $this->getRequest()->getPost('billing_address_id', false);
            if($this->getRequest()->getPost('billing_address_id') != "" && (!isset($data_save_billing['save_in_address_book']) || (isset($data_save_billing['save_in_address_book']) && $data_save_billing['save_in_address_book']) == 0)) {
                $customerAddressId = "";
            }
            if($this->isCustomerLoggedIn() && (isset($data_save_billing['save_in_address_book']) && $data_save_billing['save_in_address_book'] == 1) && !Mage::getStoreConfig('onestepcheckout/addfield/addressbook')) {
                $customerAddressId = $this->getDefaultAddress('billing');
            }
            if(isset($data_save_billing['email'])) {
                $data_save_billing['email'] = trim($data_save_billing['email']);
                if(Mage::helper('onestepcheckout')->issubcribleemail($data_save_billing['email'])) {
                    if($this->getRequest()->getPost('subscribe_newsletter') == '1') {
                        if($this->isCustomerLoggedIn()) {
                            $customer = Mage::getSingleton('customer/session')->getCustomer();
                            $customer->setIsSubscribed(1);
                        } else {
                            $this->savesubscibe($data_save_billing['email']);
                        }
                    }
                }
            }
            //$data_save_billing['use_for_shipping'] = '1';
            $result_save_billing  = $this->getOnepage()->saveBilling($data_save_billing, $customerAddressId);
            $data_customercomment = nl2br($this->getrequest()->getpost('onestepcheckout_comments'));
            $Deliverystatus       = $this->getrequest()->getpost('deliverydate');
            $Deliverydate = $this->getrequest()->getpost('onestepcheckout_date');
            $Deliverytime= $this->getrequest()->getpost('onestepcheckout_time');
            //Get Delivery of DDate
            $scheduled = $this->getrequest()->getpost('ddate');

            if ($scheduled || $Deliverydate )
            {
                if(Mage::helper('onestepcheckout')->isDDateRunning())
                {
                    if($scheduled && !$Deliverydate){
                        $Deliverydate_ddate = $scheduled['date'];
                        $Deliverytime_ddate = $scheduled['dtime'];
                        $Deliverytime = $scheduled['osc_dtime'];
                        $Deliverystatus = $scheduled['osc_deliverydate'];
                    }else{
                        $date_array = explode('/',$Deliverydate);
                        $Deliverydate_ddate = $date_array[2].'-'.$date_array[1].'-'.$date_array[0];
                        $Deliverytime_ddate = MW_Ddate_Model_Dtime::getDtimeIdFromTime($Deliverytime);
                    }

                    //Save in Ddate Database
                    $ddate = array();
                    $ddate['date'] = $Deliverydate_ddate;
                    $ddate['dtime'] = $Deliverytime_ddate;
                    $ddate['ddate_comment'] = $data_customercomment;
                    $resultddate = Mage::getModel('ddate/type_onepage')->saveDdate($ddate);

                }else{
                    $Deliverydate_ddate = $Deliverydate;
                }

                $delivery_infor = array($data_customercomment, $Deliverystatus, $Deliverydate_ddate, $Deliverytime);
                Mage::getSingleton('core/session')->setDeliveryInforOrder($delivery_infor);
                Mage::getSingleton('core/session')->setDeliveryInforEmail($delivery_infor);
            }
            else {
                /*fixcode: not ddate not save comment*/
                $delivery_infor = array($data_customercomment, "", "", "");
                Mage::getSingleton('core/session')->setDeliveryInforOrder($delivery_infor);
                Mage::getSingleton('core/session')->setDeliveryInforEmail($delivery_infor);
            }

            if(isset($data_save_billing['save_into_account']) && intval($data_save_billing['save_into_account']) == 1 && $this->isCustomerLoggedIn()) {
                $this->setAccountInfoSession($data_save_billing);
            }
        }
        // Shipping
        $isclick = $this->getRequest()->getPost('ship_to_same_address');
        $ship    = "billing";
        if($isclick != '1') {
            $ship = "shipping";
        }
        if($this->getrequest()->ispost()) {
            $data_save_shipping = $this->filterdata($this->getrequest()->getpost($ship, array()), false);
            if($this->isCustomerLoggedIn() && !$isclick) {
                $this->saveAddress('shipping', $data_save_shipping);
            }
            if($isclick == '1') {
                $data_save_shipping['same_as_billing'] = 1;
            }
            // change address if user change infomation
            // reassign customeraddressid and save to shipping
            $customeraddressid = $this->getrequest()->getpost($ship . '_address_id', false);

            // if user chage shipping, billing infomation but not save to database
            if($isclick || ($this->getRequest()->getPost('shipping_address_id') != "" && (!isset($data_save_shipping['save_in_address_book']) || (isset($data_save_shipping['save_in_address_book']) && $data_save_shipping['save_in_address_book'] == 0)))) {
                $customeraddressid = "";
            }
            if(!$isclick && $this->isCustomerLoggedIn() && (isset($data_save_shipping['save_in_address_book']) && $data_save_shipping['save_in_address_book'] == 1) && !Mage::getStoreConfig('onestepcheckout/addfield/addressbook')) {
                $customeraddressid = $this->getDefaultAddress('shipping');
            }
            //	$data_save_shipping['use_for_shipping'] = '1';
            $result_save_shipping = $this->getOnepage()->saveShipping($data_save_shipping, $customeraddressid);
            //save shipping
        }
        // Shipping method
        if($this->getRequest()->isPost()) {
            $data_save_shipping_method   = $this->getRequest()->getPost('shipping_method', '');
            $result_save_shipping_method = $this->getOnepage()->saveShippingMethod($data_save_shipping_method);
            if(!$result_save_shipping_method) {
                Mage::dispatchEvent('checkout_controller_onepage_save_shipping_method', array('request' => $this->getRequest(), 'quote' => $this->getOnepage()->getQuote()));
                $this->getOnepage()->getQuote()->collectTotals();
            }
            $this->getOnepage()->getQuote()->collectTotals();
        }

        // Payment method
        $result_savepayment = array();
        $this->getOnepage()->getQuote()->getPayment()->setMethodInstance(null);
        $data_savepayment = $this->getRequest()->getPost('payment', array());

        try {
            $result_savepayment = $this->getOnepage()->savePayment($data_savepayment);
        } catch (Exception $e) {
            $message = $e->getMessage();
            echo json_encode(array(
                'error' => 1,
                'msg'   => $message
            ));
            exit;
        }

        $redirectUrl = $this->getOnepage()->getQuote()->getPayment()->getCheckoutRedirectUrl();

        if(!empty($redirectUrl))
        {
            echo json_encode(array(
                'error' => 0,
                'msg'   => '',
                'redirect'   => $redirectUrl
            ));
            exit;
        }

        $result_order = array();
        if($data_order = $this->getRequest()->getPost('payment', false)) {
            $this->getOnepage()->getQuote()->getPayment()->importData($data_order);
        }

        //Fix for Sagepay
        $paymentMethod = $this->getOnepage()->getQuote()->getPayment()->getMethod();
        Mage::getSingleton('core/session')->unsErrorpayment();

        if($paymentMethod == 'sagepayserver') {
            $resultData = array();
            try {
                Mage::helper('sagepaysuite')->validateQuote();
                $result     = Mage :: getModel('sagepaysuite/sagePayServer')->registerTransaction($this->getRequest()->getPost());
                $resultData = $result->getData();
                if($result->getResponseStatus() == Ebizmarts_SagePaySuite_Model_Api_Payment :: RESPONSE_CODE_APPROVED) {
                    $redirectUrl = $result->getNextUrl();
                } else {
                    Mage::getSingleton('core/session')->setErrorpayment($resultData['response_status_detail']);
                    echo json_encode(array(
                        'error' => 0,
                        'msg'   => $resultData['response_status_detail']
                    ));
                    exit;
                }
            } catch (Exception $e) {
                $resultData['response_status']        = 'ERROR';
                $resultData['response_status_detail'] = $e->getMessage();
                Mage::getSingleton('core/session')->setErrorpayment($resultData['response_status_detail']);
                echo json_encode(array(
                    'error' => 1,
                    'msg'   => $e->getMessage()
                ));
                exit;
            }
            if(isset($redirectUrl)) {
                echo json_encode(array(
                    'error'    => 0,
                    'msg'      => '',
                    'redirect' => $redirectUrl
                ));
                exit;
            }
        } else if($paymentMethod == 'sagepaydirectpro') {
            $resultData = array();
            try {
                Mage::helper('sagepaysuite')->validateQuote();
                $directModel = Mage :: getModel('sagepaysuite/sagePayDirectPro');

                $result = $directModel->registerTransaction($this->getRequest()->getPost());

                $resultData = $result->getData();

                $response_status = $result->getResponseStatus();

                if($response_status == Ebizmarts_SagePaySuite_Model_Api_Payment :: RESPONSE_CODE_3DAUTH) {
                    /*echo 111;
                    $this->_forward('_expireAjax', 'directPayment', 'sgps', $this->getRequest()->getParams());
                    $this->_forward('threedPost', 'directPayment', 'sgps', $this->getRequest()->getParams());
                    return;*/
                } else {
                    try {
                        $this->getOnepage()->saveOrder();
                    } catch (Exception $e) {
                        Mage::getSingleton('core/session')->setErrorpayment($e->getMessage());
                        echo json_encode(array(
                            'error' => 1,
                            'msg'   => $e->getMessage(),
                        ));
                        exit;
                    }
                    $redirectUrl             = $this->getOnepage()->getCheckout()->getRedirectUrl();
                    $result_order['success'] = true;
                    $result_order['error']   = false;

                    $this->getOnepage()->getQuote()->save();
                    if(isset($redirectUrl)) {
                        echo json_encode(array(
                            'error'    => 0,
                            'msg'      => '',
                            'redirect' => $redirectUrl
                        ));
                        exit;
                    }
                    echo json_encode(array(
                        'error'    => 0,
                        'msg'      => '',
                        'redirect' => Mage::getUrl('checkout/onepage/success')
                    ));
                    exit;
                }
            } catch (Exception $e) {
                echo json_encode(array(
                    'error' => 1,
                    'msg'   => $e->getMessage(),
                ));
                exit;
                Sage_Log :: logException($e);
                $result_order['response_status']        = 'ERROR';
                $result_order['response_status_detail'] = $e->getMessage();
                Mage::getSingleton('core/session')->setErrorpayment($result_order['response_status_detail']);

                return;
            }
        } elseif($paymentMethod == 'sagepayform') {
            Mage::helper('sagepaysuite')->validateQuote();
            $this->_forward('_initCheckout', 'formPayment', 'sgps', $this->getRequest()->getPost());
            $this->_forward('go', 'formPayment', 'sgps', $this->getRequest()->getPost());

            return;
        } else {
            if($paymentMethod == "hosted_pro" || $paymentMethod == "payflow_link" || $paymentMethod == "payflow_advanced") {
                echo json_encode(array(
                    'error' => 0,
                    'msg'   => "hosted_pro",
                ));
                exit;
            } else {
                try {
                    $this->getOnepage()->saveOrder();
                } catch (Exception $e) {
                    echo json_encode(array(
                        'error' => 1,
                        'msg'   => $e->getMessage(),
                    ));
                    exit;
                }
                $redirectUrl = $this->getOnepage()->getCheckout()->getRedirectUrl();

                if(isset($redirectUrl)) {
                    echo json_encode(array(
                        'error'    => 0,
                        'msg'      => '',
                        'redirect' => $redirectUrl
                    ));
                    exit;
                }
                $this->getOnepage()->getQuote()->save();
                $result_order['success'] = true;
                $result_order['error']   = false;
                echo json_encode(array(
                    'error'    => 0,
                    'msg'      => '',
                    'redirect' => Mage::getUrl('checkout/onepage/success')
                ));
                exit;

                return;
            }
        }
    }

    protected function filterdata($data, $filter = "true")
    {
        $arrayname  = array('address_id', 'firstname', 'lastname', 'company', 'email', 'city', 'region_id', 'region', 'postcode', 'country_id', 'telephone', 'fax', 'save_in_address_book');
        $filterdata = array();
        //if filter = true, assign n/a to save data when load ajax
        //if filter = false, assign null to data to load ajax when place order
        $navalue = "n/a";
        if($filter == "init") {
            $filterdata = array(
                'prefix'               => Mage::getStoreConfig('customer/address/prefix_show') == 'req' ? 'n/a' : '',
                'address_id'           => '',
                'firstname'            => '',
                'lastname'             => '',
                'company'              => '',
                'email'                => null,
                'street'               => array('', '', '', ''),
                'city'                 => '',
                'region_id'            => '',
                'region'               => '',
                'postcode'             => '',
                'country_id'           => '',
                'telephone'            => '',
                'fax'                  => '',
                'month'                => null,
                'day'                  => null,
                'year'                 => null,
                'dob'                  => Mage::getStoreConfig('customer/address/dob_show') == 'req' ? '01/01/1900' : '',
                'gender'               => Mage::getStoreConfig('customer/address/gender_show') == 'req' ? 'n/a' : '',
                'taxvat'               => Mage::getStoreConfig('customer/address/taxvat_show') == 'req' ? 'n/a' : '',
                'suffix'               => Mage::getStoreConfig('customer/address/suffix_show') == 'req' ? 'n/a' : '',
                'save_in_address_book' => '0' //,
                //'use_for_shipping' =>'1'
            );

        } else {
            if($filter == 'true') {
                $filterdata = array(
                    'prefix'               => Mage::getStoreConfig('customer/address/prefix_show') == 'req' ? 'n/a' : '',
                    'address_id'           => 'n/a',
                    'firstname'            => 'n/a',
                    'lastname'             => 'n/a',
                    'company'              => 'n/a',
                    'email'                => 'n/a@na.na',
                    'street'               => array('n/a', 'n/a', 'n/a', 'n/a'),
                    'city'                 => 'n/a',
                    'region_id'            => 'n/a',
                    'region'               => 'n/a',
                    'postcode'             => '.',
                    'country_id'           => 'n/a',
                    'telephone'            => 'n/a',
                    'fax'                  => 'n/a',
                    'month'                => null,
                    'day'                  => null,
                    'year'                 => null,
                    'dob'                  => Mage::getStoreConfig('customer/address/dob_show') == 'req' ? '01/01/1900' : '',
                    'gender'               => Mage::getStoreConfig('customer/address/gender_show') == 'req' ? 'n/a' : '',
                    'taxvat'               => Mage::getStoreConfig('customer/address/taxvat_show') == 'req' ? 'n/a' : '',
                    'suffix'               => Mage::getStoreConfig('customer/address/suffix_show') == 'req' ? 'n/a' : '',
                    'save_in_address_book' => '0' //,
                    //'use_for_shipping' =>'1'
                );
            } else {
                $filterdata = array(
                    'prefix'               => '',
                    'address_id'           => '',
                    'firstname'            => '',
                    'lastname'             => '',
                    'company'              => '',
                    'email'                => '',
                    'street'               => array('', '', '', ''),
                    'city'                 => '',
                    'region_id'            => '',
                    'region'               => '',
                    'postcode'             => '.',
                    'country_id'           => '',
                    'telephone'            => '',
                    'fax'                  => '',
                    'month'                => null,
                    'day'                  => null,
                    'year'                 => null,
                    'dob'                  => null,
                    'gender'               => '',
                    'taxvat'               => '',
                    'suffix'               => '',
                    'save_in_address_book' => '0' //,
                    //'use_for_shipping' =>'1'
                );
            }
        }

        foreach ($data as $item => $value) {
            if(!is_array($value)) {
                if($value != '') // fix error save address book when saveaddressbook.value =0
                    $filterdata[$item] = $value;
            } else {
                $street       = $value;
                $street_lines = Mage::getStoreConfig('onestepcheckout/addfield/street_lines');

                if(isset($street[0])) {
                    if(($filter == "true" || $filter == "init") && empty($street[0])) {
                        $street[0] = "n/a";
                    }
                    switch (intval($street_lines)) {
                        case 2:
                            $filterdata[$item] = array($street[0], $street[1]);
                            break;
                        case 3:
                            $filterdata[$item] = array($street[0], $street[1], $street[2]);
                            break;
                        case 4:
                            $filterdata[$item] = array($street[0], $street[1], $street[2], $street[3]);
                            break;
                        default:
                            $filterdata[$item] = array($street[0]);
                            break;
                    }
                }
            }
        }

        return $filterdata;
    }

    public function isCustomerLoggedIn()
    {
        return Mage::getSingleton('customer/session')->isLoggedIn();
    }

    public function getGeoip()
    {
        if(Mage::getStoreConfig('onestepcheckout/config/enable_geoip')) {
            try {
                $key             = "718b4c6e9d4b09b15cbb35e846457723cd0fe7d842401c7203069b7252f7d289";
                $date            = Mage::app()->getLocale()->date();
                $timezone_server = $date->get(Zend_Date::TIMEZONE_SECS) / 3600;
                $timezone_client = $timezone_server;
                $xml             = simplexml_load_file('http://api.ipinfodb.com/v3/ip-city/?key=' . $key . '&ip=' . $_SERVER['REMOTE_ADDR'] . '&format=xml');

                if(!$xml)
                    return false;

                $info_ip_address = $xml->children();

                if((string)$info_ip_address[0] != 'OK' || strpos((string)$info_ip_address[3], "-") !== false)
                    return false;

                if(!empty($info_ip_address[3])) {
                    $longitude = $info_ip_address[9];
                    $hourpart  = (float)$longitude / 15;
                    $off       = $hourpart - (int)$hourpart;

                    $timezone_client = (int)$hourpart;

                    if(abs($off) > 0.5) {
                        $timezone_client = ((int)$hourpart > 0) ? (int)$hourpart + 1 : (int)$hourpart - 1;
                    }

                    Mage::register('Countrycode', (string)$info_ip_address[3]);
                    Mage::register('Countryname', (string)$info_ip_address[4]);
                    $Regioncode = "";
                    $Regionid   = "";

                    if((string)$info_ip_address[5] != "-" && (string)$info_ip_address[5] != "")
                        Mage::register('Regionname', ucfirst(strtolower((string)$info_ip_address[5])));
                    else
                        Mage::register('Regionname', "");

                    if((string)$info_ip_address[6] != "-" && (string)$info_ip_address[6] != "")
                        Mage::register('City', ucfirst(strtolower((string)$info_ip_address[6])));
                    else
                        Mage::register('City', "");

                    if((string)$info_ip_address[7] != "-" && (string)$info_ip_address[7] != "")
                        Mage::register('Zipcode', (string)$info_ip_address[7]);
                    else
                        Mage::register('Zipcode', "");

                    Mage::register('Latitude', (string)$info_ip_address[8]);
                    Mage::register('Timezoneclient', $timezone_client);
                    Mage::register('Timezoneserver', $timezone_server);

                    $statesCollection = Mage::getModel('directory/region')->getResourceCollection()
                        ->addCountryFilter((string)$info_ip_address[3])
                        ->load();
                    if(count($statesCollection->getData()) == 0) {
                        $statesCollection = Mage::getModel('directory/region')->getResourceCollection()
                            ->addCountryFilter((string)$info_ip_address[4])
                            ->load();
                    }
                    foreach ($statesCollection as $state) {
                        if(!$state->getRegionId()) {
                            continue;
                        }
                        if((string)$info_ip_address[5] != "-" && (string)$info_ip_address[5] != "") {
                            if(trim(strtolower($state->getName())) == trim(strtolower((string)$info_ip_address[5]))) {
                                $Regioncode = $state->getCode();
                                $Regionid   = $state->getRegionId();
                                break;
                            }
                        }
                    }
                    Mage::register('Regionid', $Regionid);
                    Mage::register('Regioncode', $Regioncode);
                } else {
                    Mage::register('Countrycode', '');
                }

            } catch (Exception $e) {
            }
        }

    }

    public function savesubscibe($mail)
    {
        if($mail) {
            if(version_compare(Mage::getVersion(), '1.3.2.4', '>')) {
                $session         = Mage::getSingleton('checkout/session');
                $customerSession = Mage::getSingleton('customer/session');
                $email           = (string)$mail;

                try {
                    if(!Zend_Validate::is($email, 'EmailAddress')) {
                        Mage::throwException($this->__('Please enter a valid email address.'));
                    }

                    if(Mage::getStoreConfig(Mage_Newsletter_Model_Subscriber::XML_PATH_ALLOW_GUEST_SUBSCRIBE_FLAG) != 1 &&
                        !$customerSession->isLoggedIn()
                    ) {
                        Mage::throwException($this->__('Sorry, but administrator denied subscription for guests. Please <a href="%s">register</a>.', Mage::getUrl('customer/account/create/')));
                    }

                    $ownerId = Mage::getModel('customer/customer')
                        ->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
                        ->loadByEmail($email)
                        ->getId();
                    if($ownerId !== null && $ownerId != $customerSession->getId()) {
                        Mage::throwException($this->__('Sorry, but your can not subscribe email adress assigned to another user.'));
                    }

                    $status = Mage::getModel('newsletter/subscriber')->subscribe($email);
                    if($status == Mage_Newsletter_Model_Subscriber::STATUS_NOT_ACTIVE) {
                        $session->addSuccess($this->__('Confirmation request has been sent.'));
                    } else {
                        $session->addSuccess($this->__('Thank you for your subscription.'));
                    }
                } catch (Mage_Core_Exception $e) {
                    $session->addException($e, $this->__('There was a problem with the subscription: %s', $e->getMessage()));
                } catch (Exception $e) {
                    $session->addException($e, $this->__('There was a problem with the subscription.'));
                }
            } else {
                if($this->getRequest()->isPost() && $this->getRequest()->getPost('email')) {
                    $session = Mage::getSingleton('core/session');
                    $email   = (string)$this->getRequest()->getPost('email');

                    try {
                        if(!Zend_Validate::is($email, 'EmailAddress')) {
                            Mage::throwException($this->__('Please enter a valid email address'));
                        }

                        $status = Mage::getModel('newsletter/subscriber')->subscribe($email);
                        if($status == Mage_Newsletter_Model_Subscriber::STATUS_NOT_ACTIVE) {
                            $session->addSuccess($this->__('Confirmation request has been sent'));
                        } else {
                            $session->addSuccess($this->__('Thank you for your subscription'));
                        }
                    } catch (Mage_Core_Exception $e) {
                        $session->addException($e, $this->__('There was a problem with the subscription: %s', $e->getMessage()));
                    } catch (Exception $e) {
                        $session->addException($e, $this->__('There was a problem with the subscription'));
                    }
                }
            }
        }

    }

    public function updatebillingformAction()
    {
        $this->updatebillingform();
    }

    public function updatesortbillingformAction()
    {
        $this->updatebillingform();
    }

    // shipping change address
    public function updateshippingformAction()
    {
        $this->updateshippingform();
    }

    public function updatesortshippingformAction()
    {
        $this->updateshippingform();
    }

    protected function _updateshippingform()
    {
        $this->clearBillingSession();
        if($this->getRequest()->isPost()) {
            $postData          = $this->getRequest()->getPost();
            $customerAddressId = $postData['shipping_address_id'];

            $postData          = $this->filterdata($postData);
            if(version_compare(Mage::getVersion(), '1.4.0.1', '>='))
                $data = $this->_filterPostData($postData);
            else
                $data = $postData;
            $this->getOnepage()->saveShipping($data, $customerAddressId);
            if(intval($customerAddressId) != 0) {
                return $this->renderShippingForm();
            }
        }

    }

    public function updateshippingform()
    {
        $this->clearBillingSession();
        if($this->getRequest()->isPost()) {
            $postData          = $this->getRequest()->getPost();
            $customerAddressId = $postData['shipping_address_id'];
            if(intval($customerAddressId) != 0) {
                $postData = $this->filterdata($postData);
                if(version_compare(Mage::getVersion(), '1.4.0.1', '>='))
                    $data = $this->_filterPostData($postData);
                else
                    $data = $postData;
                $result = $this->getOnepage()->saveShipping($data, $customerAddressId);
            }
        }
        $this->loadLayout()->renderLayout();

    }

    protected function _updatebillingform($render = true)
    {
        $this->clearBillingSession();
        if($this->getRequest()->isPost()) {
            $postData          = $this->getRequest()->getPost();
            $customerAddressId = $postData['billing_address_id'];
            if(intval($customerAddressId) != 0) {
                $postData = $this->filterdata($postData);
                if(version_compare(Mage::getVersion(), '1.4.0.1', '>='))
                    $data = $this->_filterPostData($postData);
                else
                    $data = $postData;

                if(isset($data['email'])) {
                    $data['email'] = trim($data['email']);
                }
                $this->getOnepage()->saveBilling($data, $customerAddressId);
                if($render) return $this->renderBillingForm();
                return '';
            }
        }
    }

    public function updatebillingform()
    {
        $this->clearBillingSession();
        if($this->getRequest()->isPost()) {
            $postData          = $this->getRequest()->getPost();
            $customerAddressId = $postData['billing_address_id'];
            if(intval($customerAddressId) != 0) {
                $postData = $this->filterdata($postData);
                if(version_compare(Mage::getVersion(), '1.4.0.1', '>='))
                    $data = $this->_filterPostData($postData);
                else
                    $data = $postData;

                if(isset($data['email'])) {
                    $data['email'] = trim($data['email']);
                }
                $result = $this->getOnepage()->saveBilling($data, $customerAddressId);
            } else {
            }
        }
        $this->loadLayout()->renderLayout();
    }

    public function saveAddress($type, $data)
    {

        $addressId = $this->getRequest()->getPost($type . '_address_id');

        if(isset($data['save_in_address_book']) && $data['save_in_address_book'] == 1) {
            if($addressId == "" && !Mage::getStoreConfig('onestepcheckout/addfield/addressbook')) {
                $addressId = $this->getDefaultAddress($type);
            }
            if($addressId != "") {
                // Save data
                $customer = Mage::getSingleton('customer/session')->getCustomer();
                $address  = Mage::getModel('customer/address');

                $existsAddress = $customer->getAddressById($addressId);
                if($existsAddress->getId() && $existsAddress->getCustomerId() == $customer->getId()) {
                    $address->setId($existsAddress->getId());
                }

                $errors = array();
                /* @var $addressForm Mage_Customer_Model_Form */
                $addressForm = Mage::getModel('customer/form');
                $addressForm->setFormCode('customer_address_edit')->setEntity($address);
                $addressData   = $this->getRequest()->getPost($type, array()); //$addressForm->extractData($this->getRequest());
                $addressErrors = $addressForm->validateData($addressData);

                if($addressErrors !== true) {
                    $errors = $addressErrors;
                }

                try {
                    $addressForm->compactData($addressData);
                    $address->setCustomerId($customer->getId())
                        ->setIsDefaultBilling($this->getRequest()->getParam('default_billing', false))
                        ->setIsDefaultShipping($this->getRequest()->getParam('default_shipping', false));
                    $address->save();
                } catch (Mage_Core_Exception $e) {
                    Mage::logException($e);
                }
            }
        }
    }

    protected function _expireAjax()
    {
        if(!Mage :: getSingleton('checkout/session')->getQuote()->hasItems()) {
            $this->getResponse()->setHeader('HTTP/1.1', '403 Session Expired')->setHeader('Login-Required', 'true')->sendResponse();
            die();
        }
    }

    public function voteAdd()
    {
        $pollId   = intval($this->getRequest()->getParam('poll_id'));
        $answerId = intval($this->getRequest()->getParam('vote'));

        /** @var $poll Mage_Poll_Model_Poll */
        $poll = Mage::getModel('poll/poll')->load($pollId);

        /**
         * Check poll data
         */
        if($poll->getId() && !$poll->getClosed() && !$poll->isVoted()) {
            $vote = Mage::getModel('poll/poll_vote')
                ->setPollAnswerId($answerId)
                ->setIpAddress(Mage::helper('core/http')->getRemoteAddr(true))
                ->setCustomerId(Mage::getSingleton('customer/session')->getCustomerId());

            $poll->addVote($vote);
            Mage::getSingleton('core/session')->setJustVotedPoll($pollId);
            Mage::dispatchEvent(
                'poll_vote_add',
                array(
                    'poll' => $poll,
                    'vote' => $vote
                )
            );
        }
    }

    public function updateAction()
    {
        $sku           = $this->getRequest()->getParam('sku');
        $coupon        = $this->getRequest()->getParam('coupon');
        $comment       = $this->getRequest()->getParam('comment');
        $option_in_url = $this->getRequest()->getParam('options');
        try {
            if($sku) {
                $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);
                if($product) {
                    $product        = $product->load($product->getEntityId());
                    $param          = array();
                    $param['qty']   = 1;
                    $optionselected = array();
                    if($comment) {
                        Mage::getSingleton('checkout/session')->setLicenseComment($comment);
                    } else {
                        if(Mage::getSingleton('checkout/session')->getLicenseComment())
                            Mage::getSingleton('checkout/session')->unsLicenseComment();
                    }
                    if($coupon) {
                        // update coupon code
                        $this->applycoupon($coupon);
                    }
                    if($option_in_url != "") {
                        $arroption_in_url = explode(',', $option_in_url);
                        if($arroption_in_url) {
                            $option = $product->getProductOptionsCollection();
                            foreach ($option as $o) {
                                if($o) {
                                    $values = $o->getValues();
                                    foreach ($values as $v) {
                                        $each_option = $v->getData();
                                        $pos         = array_search($each_option['default_title'], $arroption_in_url);
                                        if(false !== $pos) {
                                            $optionselected[$each_option['option_id']] = $each_option['option_type_id'];
                                            unset($arroption_in_url[$pos]);
                                        }
                                    }
                                }
                            }
                            if($optionselected) {
                                $param['options'] = $optionselected;
                            }
                        }
                    }
                    $cart = Mage::getSingleton('checkout/cart');
                    $cart->init();
                    $cart->addProduct($product, $param);
                    $cart->save();
                    $this->_redirectUrl(Mage::getUrl('checkout/onepage/'));
                } else {
                    $this->_redirectUrl(Mage::getBaseUrl());
                }
            } else {
                $this->_redirectUrl(Mage::getBaseUrl());
            }

        } catch (Exception $e) {
            $this->_redirectUrl(Mage::getBaseUrl());
        }
    }

    public function applycoupon($coupon)
    {
        $coupon_code = $coupon;

        if($coupon_code != '') {
            Mage::getSingleton("checkout/session")->setData("coupon_code", $coupon_code);
            Mage::getSingleton('checkout/cart')->getQuote()->setCouponCode($coupon_code)->save();
        }
    }


    public function clearBillingSession()
    {
        if(Mage::getSingleton('core/session')->getCountryId())
            Mage::getSingleton('core/session')->unsCountryId();

        if(Mage::getSingleton('core/session')->getPostcode())
            Mage::getSingleton('core/session')->unsPostcode();

        if(Mage::getSingleton('core/session')->getRegion())
            Mage::getSingleton('core/session')->unsRegion();

        if(Mage::getSingleton('core/session')->getRegionId())
            Mage::getSingleton('core/session')->unsRegionId();

        if(Mage::getSingleton('core/session')->getCity())
            Mage::getSingleton('core/session')->unsCity();
    }

    public function getQtyAfterMyCart($qty)
    {
        if($qty == 0)
            return $this->__("My Cart");
        else if($qty == 1) {
            return $this->__("My Cart (") . $this->__($qty . " item)");
        } else if($qty) {
            return $this->__("My Cart (") . $this->__($qty . " items)");
        }
    }

    public function getDefaultAddress($type)
    {
        $addressId = "";
        $customer  = Mage::getSingleton('customer/session')->getCustomer();
        if($type == "billing") {
            $address   = $customer->getDefaultBillingAddress();
            $addressId = $address->getEntityId();
        } else {
            $address   = $customer->getDefaultShippingAddress();
            $addressId = $address->getEntityId();
        }

        return $addressId;
    }

    public function setAccountInfoSession($data_save_billing)
    {

        $dob = "";
        if(isset($data_save_billing['dob']))
            $dob = $data_save_billing['dob'];

        $gender = "";
        if(isset($data_save_billing['gender']))
            $gender = $data_save_billing['gender'];

        $taxvat = "";
        if(isset($data_save_billing['taxvat']))
            $taxvat = $data_save_billing['taxvat'];

        $suffix = "";
        if(isset($data_save_billing['suffix']))
            $suffix = $data_save_billing['suffix'];

        $prefix = "";
        if(isset($data_save_billing['prefix']))
            $prefix = $data_save_billing['prefix'];

        $middlename = "";
        if(isset($data_save_billing['middlename']))
            $middlename = $data_save_billing['middlename'];

        $middlename = "";
        if(isset($data_save_billing['middlename']))
            $middlename = $data_save_billing['middlename'];

        $firstname = "";
        if(isset($data_save_billing['firstname']))
            $firstname = $data_save_billing['firstname'];

        $lastname = "";
        if(isset($data_save_billing['lastname']))
            $lastname = $data_save_billing['lastname'];

        $account_infor = array($dob, $gender, $taxvat, $suffix, $prefix, $middlename, $firstname, $lastname);
        Mage::getSingleton('core/session')->setAccountInfor($account_infor);
    }

    public function checkChangeStyle()
    {
        $changestyle = 0;
        Mage::getSingleton('core/session')->unsOs();
        if(!Mage::getSingleton('core/session')->getOs()) {
            Mage::getSingleton('core/session')->setOs('notchange');
            $disable_os  = Mage::getStoreConfig('onestepcheckout/config/disable_os');
            $redirect_os = false;
            if($disable_os) {
                $detect     = new Mobile_Detect;
                $deviceType = ($detect->isMobile() ? ($detect->isTablet() ? 'tablet' : 'phone') : 'computer');
                if($deviceType == "phone") {
                    Mage::getSingleton('core/session')->setOs('change');
                    $redirect_os = true;
                    $changestyle = 1;
                } else {
                    if(Mage::getStoreConfig('onestepcheckout/config/enabled')) {
                        Mage::getSingleton('core/session')->setOs('notchange');
                        $changestyle = 0;
                    } else {
                        Mage::getSingleton('core/session')->setOs('change');
                        $changestyle = 1;
                    }
                }
            } else {
                if(Mage::getStoreConfig('onestepcheckout/config/enabled')) {
                    Mage::getSingleton('core/session')->setOs('notchange');
                    $changestyle = 0;
                } else {
                    Mage::getSingleton('core/session')->setOs('change');
                    $changestyle = 1;
                }
            }
        }

        return $changestyle;
    }

    public function checkBeforeCheckout()
    {
        if(!Mage::helper('checkout')->canOnepageCheckout()) {
            Mage::getSingleton('checkout/session')->addError($this->__('The onepage checkout is disabled.'));

            return false;
        }

        $quote = $this->getOnepage()->getQuote();
        if(!$quote->hasItems() || $quote->getHasError()) {
            return false;
        }

        if(!$quote->validateMinimumAmount()) {
            $error = Mage::getStoreConfig('sales/minimum_order/error_message');
            Mage::getSingleton('checkout/session')->addError($error);

            return false;
        }

        return true;
    }

    public function synctimeAction()
    {
        exit;
    }

    protected function _getSession()
    {
        return Mage::getSingleton('checkout/session');
    }

    protected function isRWPRunning(){
        if(Mage::helper('core')->isModuleEnabled('MW_RewardPoints') && Mage::helper('core')->isModuleOutputEnabled('MW_RewardPoints')){
            if(Mage::getStoreConfig('rewardpoints/config/enabled')){
                return true;
            }
        }
        return false;
    }

    public function testAction(){
        $temp = MW_Onestepcheckout_Model_Observer_Layout::createCustomCss();
        var_dump($temp);
    }

    public function partnerAction(){
        $this->loadLayout();
        $this->renderLayout();
    }
}