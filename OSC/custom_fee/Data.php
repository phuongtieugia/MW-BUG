<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Checkout
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Checkout default helper
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */

class MW_Onestepcheckout_Helper_Data extends Mage_Core_Helper_Abstract
{
  const EE = 3;
  const PE = 2;
  const CE = 1;
  const ENTERPRISE_COMPANY = 'Enterprise';
  const PROFESSIONAL_DESIGN = "pro";
  const MYCONFIG = "onestepcheckout/config/enabled";
  const DELIVERY_ENABLE = 1;


  protected static $_platform = 0;
  public function checkSession(){
      $iswrap = Mage::getSingleton('core/session')->getIsWrap();
      if($iswrap){
          return 1;
      }else{
          return 0;
      }
  }
  public function checkSessionMwfee(){
      $ismwfee = Mage::getSingleton('core/session')->getIsMwfee();
      if($ismwfee){
          return 1;
      }else{
          return 0;
      }
  }
  public function getPlatform()
    {
            if (self::$_platform == 0) {
            $pathToClaim = BP . DS . "app" . DS . "etc" . DS . "modules" . DS . self::ENTERPRISE_COMPANY . "_" . self::ENTERPRISE_COMPANY .  ".xml";
            $pathToEEConfig = BP . DS . "app" . DS . "code" . DS . "core" . DS . self::ENTERPRISE_COMPANY . DS . self::ENTERPRISE_COMPANY . DS . "etc" . DS . "config.xml";
            $isCommunity = !file_exists($pathToClaim) || !file_exists($pathToEEConfig);
            if ($isCommunity) {
                 self::$_platform = self::CE;
            } else {
                $_xml = @simplexml_load_file($pathToEEConfig,'SimpleXMLElement', LIBXML_NOCDATA);
                if(!$_xml===FALSE) {
                    $package = (string)$_xml->default->design->package->name;
                    $theme = (string)$_xml->install->design->theme->default;
                    $skin = (string)$_xml->stores->admin->design->theme->skin;
                    $isProffessional = ($package == self::PROFESSIONAL_DESIGN) && ($theme == self::PROFESSIONAL_DESIGN) && ($skin == self::PROFESSIONAL_DESIGN);
                    if ($isProffessional) {
                        self::$_platform = self::PE;
                        return self::$_platform;
                    }
                }
                self::$_platform = self::EE;
            }
        }
        return self::$_platform;
    }

    const XML_PATH_GUEST_CHECKOUT           = 'checkout/options/guest_checkout';

    protected $_agreements = null;

    /**
     * Retrieve checkout session model
     *
     * @return Mage_Checkout_Model_Session
     */
    public function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Retrieve checkout quote model object
     *
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        return $this->getCheckout()->getQuote();
    }

    public function formatPrice($price)
    {
        return $this->getQuote()->getStore()->formatPrice($price);
    }

    public function convertPrice($price, $format=true)
    {
        return $this->getQuote()->getStore()->convertPrice($price, $format);
    }

    public function getRequiredAgreementIds()
    {
        if (is_null($this->_agreements)) {
            if (!Mage::getStoreConfigFlag('checkout/options/enable_agreements')) {
                $this->_agreements = array();
            } else {
                $this->_agreements = Mage::getModel('checkout/agreement')->getCollection()
                    ->addStoreFilter(Mage::app()->getStore()->getId())
                    ->addFieldToFilter('is_active', 1)
                    ->getAllIds();
            }
        }
        return $this->_agreements;
    }

    /**
     * Get onepage checkout availability
     *
     * @return bool
     */
    public function canOnepageCheckout()
    {
        return (bool)Mage::getStoreConfig('checkout/options/onepage_checkout_enabled');
    }

    /**
     * Get sales item (quote item, order item etc) price including tax based on row total and tax amount
     *
     * @param   Varien_Object $item
     * @return  float
     */
    public function getPriceInclTax($item)
    {
        if ($item->getPriceInclTax()) {
            return $item->getPriceInclTax();
        }
        $qty = ($item->getQty() ? $item->getQty() : ($item->getQtyOrdered() ? $item->getQtyOrdered() : 1));
        $price = (floatval($qty)) ? ($item->getRowTotal() + $item->getTaxAmount())/$qty : 0;
        return Mage::app()->getStore()->roundPrice($price);
    }

    /**
     * Get sales item (quote item, order item etc) row total price including tax
     *
     * @param   Varien_Object $item
     * @return  float
     */
    public function getSubtotalInclTax($item)
    {
        if ($item->getRowTotalInclTax()) {
            return $item->getRowTotalInclTax();
        }
        $tax = $item->getTaxAmount();
        return $item->getRowTotal() + $tax;
    }

    public function getBasePriceInclTax($item)
    {
        $qty = ($item->getQty() ? $item->getQty() : ($item->getQtyOrdered() ? $item->getQtyOrdered() : 1));
        $price = (floatval($qty)) ? ($item->getBaseRowTotal() + $item->getBaseTaxAmount())/$qty : 0;
        return Mage::app()->getStore()->roundPrice($price);
    }

    public function getBaseSubtotalInclTax($item)
    {
        $tax = ($item->getBaseTaxBeforeDiscount() ? $item->getBaseTaxBeforeDiscount() : $item->getBaseTaxAmount());
        return $item->getBaseRowTotal()+$tax;
    }

    /**
     * Send email id payment was failed
     *
     * @param Mage_Sales_Model_Quote $checkout
     * @param string $message
     * @param string $checkoutType
     * @return Mage_Checkout_Helper_Data
     */
    public function sendPaymentFailedEmail($checkout, $message, $checkoutType = 'onepage')
    {
        $translate = Mage::getSingleton('core/translate');
        /* @var $translate Mage_Core_Model_Translate */
        $translate->setTranslateInline(false);

        $mailTemplate = Mage::getModel('core/email_template');
        /* @var $mailTemplate Mage_Core_Model_Email_Template */

        $template = Mage::getStoreConfig('checkout/payment_failed/template', $checkout->getStoreId());

        $copyTo = $this->_getEmails('checkout/payment_failed/copy_to', $checkout->getStoreId());
        $copyMethod = Mage::getStoreConfig('checkout/payment_failed/copy_method', $checkout->getStoreId());
        if ($copyTo && $copyMethod == 'bcc') {
            $mailTemplate->addBcc($copyTo);
        }

        $_reciever = Mage::getStoreConfig('checkout/payment_failed/reciever', $checkout->getStoreId());
        $sendTo = array(
            array(
                'email' => Mage::getStoreConfig('trans_email/ident_'.$_reciever.'/email', $checkout->getStoreId()),
                'name'  => Mage::getStoreConfig('trans_email/ident_'.$_reciever.'/name', $checkout->getStoreId())
            )
        );

        if ($copyTo && $copyMethod == 'copy') {
            foreach ($copyTo as $email) {
                $sendTo[] = array(
                    'email' => $email,
                    'name'  => null
                );
            }
        }
        $shippingMethod = '';
        if ($shippingInfo = $checkout->getShippingAddress()->getShippingMethod()) {
            $data = explode('_', $shippingInfo);
            $shippingMethod = $data[0];
        }

        $paymentMethod = '';
        if ($paymentInfo = $checkout->getPayment()) {
            $paymentMethod = $paymentInfo->getMethod();
        }

        $items = '';
        foreach ($checkout->getItemsCollection() as $_item) {
            /* @var $_item Mage_Sales_Model_Quote_Item */
            $items .= $_item->getProduct()->getName() . '  x '. $_item->getQty() . '  '
                    . $checkout->getStoreCurrencyCode() . ' ' . $_item->getProduct()->getFinalPrice($_item->getQty()) . "\n";
        }
        $total = $checkout->getStoreCurrencyCode() . ' ' . $checkout->getGrandTotal();

        foreach ($sendTo as $recipient) {
            $mailTemplate->setDesignConfig(array('area'=>'frontend', 'store'=>$checkout->getStoreId()))
                ->sendTransactional(
                    $template,
                    Mage::getStoreConfig('checkout/payment_failed/identity', $checkout->getStoreId()),
                    $recipient['email'],
                    $recipient['name'],
                    array(
                        'reason' => $message,
                        'checkoutType' => $checkoutType,
                        'dateAndTime' => Mage::app()->getLocale()->date(),
                        'customer' => $checkout->getCustomerFirstname() . ' ' . $checkout->getCustomerLastname(),
                        'customerEmail' => $checkout->getCustomerEmail(),
                        'billingAddress' => $checkout->getBillingAddress(),
                        'shippingAddress' => $checkout->getShippingAddress(),
                        'shippingMethod' => Mage::getStoreConfig('carriers/'.$shippingMethod.'/title'),
                        'paymentMethod' => Mage::getStoreConfig('payment/'.$paymentMethod.'/title'),
                        'items' => nl2br($items),
                        'total' => $total
                    )
                );
        }

        $translate->setTranslateInline(true);

        return $this;
    }

    protected function _getEmails($configPath, $storeId)
    {
        $data = Mage::getStoreConfig($configPath, $storeId);
        if (!empty($data)) {
            return explode(',', $data);
        }
        return false;
    }

    /**
     * Check if multishipping checkout is available.
     * There should be a valid quote in checkout session. If not, only the config value will be returned.
     *
     * @return bool
     */
    public function isMultishippingCheckoutAvailable()
    {
        $quote = $this->getQuote();
        $isMultiShipping = (bool)(int)Mage::getStoreConfig('shipping/option/checkout_multiple');
        if ((!$quote) || !$quote->hasItems()) {
            return $isMultiShipping;
        }
        $maximunQty = (int)Mage::getStoreConfig('shipping/option/checkout_multiple_maximum_qty');
        return $isMultiShipping
            && !$quote->hasItemsWithDecimalQty()
            && $quote->validateMinimumAmount(true)
            && (($quote->getItemsSummaryQty() - $quote->getItemVirtualQty()) > 0)
            && ($quote->getItemsSummaryQty() <= $maximunQty)
            && !$quote->hasNominalItems()
        ;
    }

    /**
     * Check is allowed Guest Checkout
     * Use config settings and observer
     *
     * @param Mage_Sales_Model_Quote $quote
     * @param int|Mage_Core_Model_Store $store
     * @return bool
     */
    public function isAllowedGuestCheckout(Mage_Sales_Model_Quote $quote, $store = null)
    {
        if ($store === null) {
            $store = $quote->getStoreId();
        }
        $guestCheckout = Mage::getStoreConfigFlag(self::XML_PATH_GUEST_CHECKOUT, $store);

        if ($guestCheckout == true) {
            $result = new Varien_Object();
            $result->setIsAllowed($guestCheckout);
            Mage::dispatchEvent('checkout_allow_guest', array(
                'quote'  => $quote,
                'store'  => $store,
                'result' => $result
            ));

            $guestCheckout = $result->getIsAllowed();
        }

        return $guestCheckout;
    }
	public function onlyProductDownloadable(){
		$itemProduct=$this->getQuote()->getAllVisibleItems();
		foreach($itemProduct as $item){
				if($item->getProduct()->getTypeId()!='downloadable' AND $item->getProduct()->getTypeId()!='virtual' AND $item->getProduct()->getTypeId()!='giftcard')
					return false;
		}
		return true;
	}
	public function haveProductDownloadable(){
		$itemProduct=$this->getQuote()->getAllVisibleItems();
		foreach($itemProduct as $item){
				if($item->getProduct()->getTypeId()=='downloadable')
					return true;
		}
		return false;
	}
	public function issubscribed(){
		$issubscribe= Mage::getModel('newsletter/subscriber')->loadByCustomer(Mage::getSingleton('customer/session')->getCustomer())->isSubscribed();
		if(!Mage::getSingleton('customer/session')->isLoggedIn() or !$issubscribe){
			return true;
		}
		else{
			return false;
		}
	}

	// check show address book
	public function showAddressBook()
	{
		if (Mage::getStoreConfig('onestepcheckout/addfield/addressbook'))
			return true;
		return false;
	}

	public function showEditCartLink()
	{
		if(Mage::getStoreConfig('onestepcheckout/addfield/editcartlink') )
			return true;
		return false;
	}

	public function showComment()
	{
		if(Mage::getStoreConfig('onestepcheckout/addfield/enable_messagetosystem'))
			return true;
		return false;
	}

	// default cupon is disable
	public function showCouponCode()
	{
		if(Mage::getStoreConfig('onestepcheckout/addfield/allowcoupon') )
			return true;
		return false;
	}

	//default showimageproduct is disable
	public function showImageProduct()
	{
		if(Mage::getStoreConfig('onestepcheckout/addfield/showimageproduct') )
			return true;
		return false;
	}


   //default enable_giftmessage is disable
	public function enableGiftMessage()
	{
		if(Mage::getStoreConfig('onestepcheckout/addfield/enable_giftmessage'))
			return true;
		return false;
	}

	public function switchTemplateIf()
	{
				$changestyle = 0;
				$disable_os = Mage::getStoreConfig('onestepcheckout/config/disable_os');
	    		$arrdisable_os = explode(',', $disable_os);
		    	$user_agent=$_SERVER['HTTP_USER_AGENT'];
		    	$redirect_os = false;
		    	if(count($arrdisable_os)>0)
		    	{
			    	foreach ($arrdisable_os as $regex) //	foreach ($os_array as $regex => $value)
			    		{
			    			if(strlen(trim($regex))>3)
			    			{
				    			$regex_prg = '/'.trim($regex).'/i';

						        if (preg_match($regex_prg, $user_agent))
						        {
						            $redirect_os = true;
						        }
			    			}
			    		}

			    	if($redirect_os)
			    	{
			    		$changestyle = 1;
			    	}
			    	else
			    	{
			    		if(Mage::getStoreConfig('onestepcheckout/config/enabled')){
							$changestyle = 0;
						}
						else
						{
							$changestyle = 1;
						}
			    	}
		    	}
			if($changestyle)
			{
				return "checkout/onepage.phtml";
			}
			else
			{
					return "mw_onestepcheckout/onepage.phtml";
			}
	}

	public function myConfig(){
    	return self::MYCONFIG;
    }

	const MYNAME = "MW_Onestepcheckout";

	function disableConfig(){
			Mage::getSingleton('core/config')->saveConfig(Mage::helper('onestepcheckout')->myConfig(),0);
			$websites  = Mage::getModel('core/website')->getCollection()->getData();
    		foreach($websites as $row)
    		{
    			if($row['code']!="admin")
    			Mage::getSingleton('core/config')->deleteConfig(Mage::helper('onestepcheckout')->myConfig(),'websites',$row['website_id']);
    		}

    	   $stores  = Mage::getModel('core/store')->getCollection()->getData();
    		foreach($stores as $row)
    		{
    			if($row['code']!="admin")
    			Mage::getSingleton('core/config')->deleteConfig(Mage::helper('onestepcheckout')->myConfig(),'stores',$row['store_id']);
    		}

			Mage::getConfig()->reinit();
	}

	function issubcribleemail($email){
		$issubscribe= Mage::getModel('newsletter/subscriber')->loadByEmail($email)->isSubscribed();
		if(!$issubscribe){
			return true;
		}
		else{
			return false;
		}
	}

    public function configjs(){
        $path_js = "mw_onestepcheckout/js/".$_SERVER['SERVER_NAME']."-onestep-config-".Mage::app()->getStore()->getCode().".js";
        return (Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA).$path_js);
    }
    public function configcss(){
        $path_css = "mw_onestepcheckout/css/".$_SERVER['SERVER_NAME']."-customcss.new-".Mage::app()->getStore()->getCode().".css";

        return (Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA).$path_css);
    }

    function colourBrightness($hex, $percent) {
        $hash = '';
        if (stristr($hex,'#')) {
            $hex = str_replace('#','',$hex);
            $hash = '#';
        }
        /// HEX TO RGB
        $rgb = array(hexdec(substr($hex,0,2)), hexdec(substr($hex,2,2)), hexdec(substr($hex,4,2)));
        //// CALCULATE
        for ($i=0; $i<3; $i++) {
            // See if brighter or darker
            if ($percent > 0) {
                // Lighter
                $rgb[$i] = round($rgb[$i] * $percent) + round(255 * (1-$percent));
            } else {
                // Darker
                $positivePercent = $percent - ($percent*2);
                $rgb[$i] = round($rgb[$i] * $positivePercent) + round(0 * (1-$positivePercent));
            }
            // In case rounding up causes us to go to 256
            if ($rgb[$i] > 255) {
                $rgb[$i] = 255;
            }
        }
        //// RBG to Hex
        $hex = '';
        for($i=0; $i < 3; $i++) {
            // Convert the decimal digit to hex
            $hexDigit = dechex($rgb[$i]);
            // Add a leading zero if necessary
            if(strlen($hexDigit) == 1) {
                $hexDigit = "0" . $hexDigit;
            }
            // Append to the hex string
            $hex .= $hexDigit;
        }
        return $hash.$hex;
    }
    public function getAdditionaldays()
    {
        $array = array();
        $week = Mage::getStoreConfig('onestepcheckout/deliverydate/weekend');
        $listDay = explode(",", Mage::getStoreConfig("onestepcheckout/deliverydate/enableday"));
        if (!$listDay[0])
            return '';
        foreach ($listDay as $item) {
            $t = explode("/", $item);
            $numday = date("w", mktime(0, 0, 0, $t[0], $t[1], $t[2]));
            if (strstr($week, $numday)) {
                $array[] = $item;
            }
        }
        return implode(",", $array);
    }

    public function getNationaldays()
    {
        $array = array();
        $week = Mage::getStoreConfig('onestepcheckout/deliverydate/weekend');
        $listDay = explode(",", Mage::getStoreConfig("onestepcheckout/deliverydate/disableday"));
        if (!$listDay[0])
            return '';
        foreach ($listDay as $item) {
            $t = explode("/", $item);
            $numday = date("w", mktime(0, 0, 0, $t[0], $t[1], $t[2]));
            if (!strstr($week, $numday)) {
                $array[] = $item;
            }
        }
        return implode(",", $array);
    }
    public function renderOnepageItemAfter($name, $template){
        $layout = Mage::getModel('core/layout');
        $layout->setArea(Mage::app()->getLayout()->getArea());
        $update = $layout->getUpdate();
        $update->load('checkout_onepage_review');
        $layout->generateXml();
        $layout->generateBlocks();
        return $layout->getBlock($name)->setTemplate($template)->toHtml();
    }
    /**
     *
     * Check DDate
     * DDate have not "Enable config" Field
     */
    public function isDDateRunning()
    {
        if(Mage::helper('core')->isModuleEnabled('MW_Ddate') &&
            Mage::helper('core')->isModuleOutputEnabled('MW_Ddate'))
        {
            if(Mage::getStoreConfig('onestepcheckout/deliverydate/allow_options')!= MW_Onestepcheckout_Helper_Data::DELIVERY_ENABLE)
                return true;
        }

        return false;
    }
    public function isFacebook(){
      if(Mage::getStoreConfig('onestepcheckout/addfield/app_fb_id') != ""){
        return true;
      }
      return false;
    }
}
