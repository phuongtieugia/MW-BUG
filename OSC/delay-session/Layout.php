<?php
class MW_Onestepcheckout_Model_Observer_Layout extends Mage_Core_Model_Abstract
{
    protected $_base_url;
    protected $_path_js;
    protected $_multi_path_js;
    protected $_multi_path_before_css;
    protected $_multi_path_after_css;
    protected $_path_before_css;
    protected $baseDir;

    public function controllerLayoutBefore(Varien_Event_Observer $observer)
    {
        $action        = $observer->getEvent()->getAction();
        $layout        = $observer->getEvent()->getLayout();
        $this->baseDir = Mage::getBaseDir();

        $path_to_skin = Mage::getSingleton('core/design_package')->getPackageName() . "/" . Mage::getSingleton('core/design_package')->getTheme('frontend');
        foreach (Mage::app()->getWebsites() as $website)
        {
            foreach ($website->getGroups() as $group)
            {
                $stores = $group->getStores();
                foreach ($stores as $store)
                {
                    $this->_multi_path_js[$store->getCode()]        = $this->baseDir . "/media/mw_onestepcheckout/js/" . $_SERVER['SERVER_NAME'] . "-onestep-config-" . $store->getCode() . ".js";
                    $this->_multi_path_after_css[$store->getCode()] = $this->baseDir . "/media/mw_onestepcheckout/css/" . $_SERVER['SERVER_NAME'] . "-customcss.new-" . $store->getCode() . ".css";
                }
            }
        }


        $this->_path_js         = $this->baseDir . "/js/mw_onestepcheckout/onestep-config.js";
        $this->_path_before_css = $this->baseDir . "/skin/frontend/" . $path_to_skin . "/mw_onestepcheckout/css/customcss.css";
        $this->_path_after_css  = $this->baseDir . "/skin/frontend/" . $path_to_skin . "/mw_onestepcheckout/css/customcss.new.css";

        //var_dump($path_to_skin,$this->_path_before_css); die();

        if(Mage::getDesign()->getArea() == 'frontend')
        {
            $this->createConfigFile();
            $this->createCustomCss();
        }
    }

    //protected function createCustomCss()
    public function createCustomCss()
    {
        $store_id = Mage::app()->getStore()->getStoreId();
        $color    = (Mage::getStoreConfig('onestepcheckout/display_setting/style_color', $store_id) == "") ? "#337BAA" : "#" . Mage::getStoreConfig('onestepcheckout/display_setting/style_color', $store_id);


        $color = $this->hex2rgb($color);
        $data_css = file_get_contents($this->_path_before_css);


        preg_match_all("/\{COLOR?(.*?)\}/", $data_css, $cssColor);
        $cssColor = array_unique($cssColor[0]);


        $arrConvertColor = array();
        $color    = (Mage::getStoreConfig('onestepcheckout/display_setting/style_color', $store_id) == "") ? "#337BAA" : "#" . Mage::getStoreConfig('onestepcheckout/display_setting/style_color', $store_id);
        foreach ($cssColor as $c)
        {
            if(strpos($c, "_") > -1)
            {
                preg_match("/\{COLOR_([0-9])_([0-9])\}/", $c, $num);
                $r1 = isset($num[1]) ? $num[1] : '';
                $r2 = isset($num[2]) ? $num[2] : '';
                $arrConvertColor[$c] = Mage::helper('onestepcheckout')->colourBrightness($color, $r1 . "." . $r2);
            }
            else
            {
                $arrConvertColor[$c] = $color;
            }
        }


        $checkout_color = Mage::getStoreConfig('onestepcheckout/display_setting/checkout_button_color', $store_id);
        if($checkout_color =='') $checkout_color = 'ea7608';
        if(Mage::getStoreConfig('onestepcheckout/display_setting/style', $store_id) == 3){
            $checkout_color = '#'.$checkout_color;
            preg_match_all("/\{COLORCHECKOUT?(.*?)\}/", $data_css, $cssColorCheckout);
            $cssColorCheckout = array_unique($cssColorCheckout[0]);
            $arrConvertColorCheckout = array();
            foreach ($cssColorCheckout as $c)
            {
                if(strpos($c, "_") > -1)
                {
                    preg_match("/\{COLORCHECKOUT_([0-9])_([0-9])\}/", $c, $num);
                    $arrConvertColorCheckout[$c] = Mage::helper('onestepcheckout')->colourBrightness($checkout_color, $num[1] . "." . $num[2]);
                }
                else
                {
                    $arrConvertColorCheckout[$c] = $checkout_color;
                }
            }
            $data_css = strtr($data_css, $arrConvertColorCheckout);
        }
// style for gift box
//            $data_css = file_get_contents($this->_path_before_css);
        if(Mage::getStoreConfig('onestepcheckout/display_setting/style', $store_id) == 3){
            preg_match_all("/\{COLORGIFT_?(.*?)\}/", $data_css, $cssColorGift);
            $cssColorGift = array_unique($cssColorGift[0]);
            $arrConvertColorGift = array();
//                var_dump($cssColorGift); die();
            foreach ($cssColorGift as $c)
            {
                if(strpos($c, "_") > -1)
                {
                    preg_match("/\{COLORGIFT_([0-9])_([0-9])\}/", $c, $num);
                    $arrConvertColorGift[$c] = Mage::helper('onestepcheckout')->colourBrightness($color, $num[1] . "." . $num[2]);
                }
                else
                {
                    $arrConvertColorGift[$c] = $color;
                }
            }
            $data_css = strtr($data_css, $arrConvertColorGift);
        }else{
            preg_match_all("/\{COLORGIFT_?(.*?)\}/", $data_css, $cssColorGift);
            $cssColorGift = array_unique($cssColorGift[0]);
            $arrConvertColorGift = array();
//                var_dump($cssColorGift); die();
            foreach ($cssColorGift as $c)
            {
                   $arrConvertColorGift[$c] = $color;
            }
            $data_css = strtr($data_css, $arrConvertColorGift);
        }

        $data_css = strtr($data_css, $arrConvertColor);

        $data_css = strtr($data_css, array(
            '{BOX_WIDTH}' => Mage::getStoreConfig('onestepcheckout/termcondition/boxwidth', $store_id)
        ));

        if(Mage::getStoreConfig('onestepcheckout/display_setting/page_layout', $store_id) == 2)
        {
            $data_css = strtr($data_css, array(
                '{COL_MIDDLE_PAGE_LAYOUT}' => 'width: 100% !important;',
                '{COL_RIGHT_PAGE_LAYOUT}'  => 'float: none !important; clear: both !important;'
            ));
        }

        if(Mage::getStoreConfig('onestepcheckout/display_setting/style', $store_id) == 2){
            $data_css = strtr($data_css, array(
                '{TEMP_BACKGROUND}' => 'none !important'
            ));
        }

        $height_bar = Mage::getStoreConfig('onestepcheckout/display_setting/height_bar', $store_id);
        switch($height_bar){
            case 1:
                $height_bar_value = '30px';
                $background_pos_value = '10px 5px';
                $font_site_value = '14px';
                $checkout_height_value = '40px !important';
                $checkout_lineheight_value = '40px !important';
                $checkout_font_value = '24px !important';
                break;
            case 2:
                $height_bar_value = '40px';
                $background_pos_value = '10px 8px';
                $font_site_value = '16px';
                $checkout_height_value = '47px !important';
                $checkout_lineheight_value = '47px !important';
                $checkout_font_value = '26px !important';
                break;
            case 3:
                $height_bar_value = '43px';
                $background_pos_value = '10px 11px';
                $font_site_value = '17px';
                $checkout_height_value = '50px !important';
                $checkout_lineheight_value = '50px !important';
                $checkout_font_value = '27px !important';
                break;
            case 4:
                $height_bar_value = '46px';
                $background_pos_value = '10px 13px';
                $font_site_value = '18px';
                $checkout_height_value = '53px !important';
                $checkout_lineheight_value = '53px !important';
                $checkout_font_value = '28px !important';
                break;
        }
        //$height_bar_value = (($height_bar+2)*10).'px';
        //$background_pos_value = '10px '.($height_bar*5).'px';

        $data_css = strtr($data_css, array(
            '{HEIGHT_BAR}' => $height_bar_value,
            '{BACKGROUND_POS}' => $background_pos_value,
            '{FONT_SITE}' => $font_site_value,

            '{CHECKOUT_HEIGHT}' => $checkout_height_value,
            '{CHECKOUT_LINEHEIGHT}' => $checkout_lineheight_value,
            '{CHECKCOUT_FONT}' => $checkout_font_value
        ));



        $checkout_color = Mage::getStoreConfig('onestepcheckout/display_setting/checkout_button_color', $store_id);
        if($checkout_color =='') $checkout_color = 'ea7608';
        $data_css = strtr($data_css, array(
            '{CHECKOUT_BTN_COLOR}' => 'background : #'.$checkout_color
        ));



        $file = new Varien_Io_File();

        //if(Mage::app()->getRequest()->getModuleName() == 'onestepcheckout')
        //{
            foreach ($this->_multi_path_after_css as $code => $path)
            {
                if(!file_exists($path) && Mage::app()->getStore()->getCode() == $code)
                {
                    $file->write($path, $data_css);
                    $file->close();
                }
            }
        //}
    }
    function hex2rgb($hex) {
       $hex = str_replace("#", "", $hex);

       if(strlen($hex) == 3) {
          $r = hexdec(substr($hex,0,1).substr($hex,0,1));
          $g = hexdec(substr($hex,1,1).substr($hex,1,1));
          $b = hexdec(substr($hex,2,1).substr($hex,2,1));
       } else {
          $r = hexdec(substr($hex,0,2));
          $g = hexdec(substr($hex,2,2));
          $b = hexdec(substr($hex,4,2));
       }
       //$rgb = array($r, $g, $b);
       $rgb = 'rgba('.$r.','.$g.','.$b.',1)';
       return $rgb;
       //return implode(",", $rgb); // returns the rgb values separated by commas
       // return $rgb; // returns an array with the rgb values
    }

    protected function createConfigFile()
    {
        $store_id        = Mage::app()->getStore()->getStoreId();
        $base_url        = explode("/", Mage::getBaseUrl('js'));
        $base_url        = explode($base_url[count($base_url) - 2], Mage::getBaseUrl('js'));
        $this->_base_url = $base_url[0];

        $versioninfo = Mage::getVersionInfo();
        $paramHttps  = (Mage::app()->getStore()->isCurrentlySecure()) ? array('_forced_secure' => true) : array();
        $base_URLs   = preg_replace('/http:\/\//is', 'https://', $base_url[0]);
        /** Create file config for javascript */
        $js = "var mw_baseUrl = '{BASE_URL}';\n";

        $js = str_replace("{BASE_URL}", $base_url[0], $js);
        $js .= "var mw_baseUrls = '$base_URLs';\n";
        $js .= "var use_ssl = " . (Mage::app()->getStore()->isCurrentlySecure() ? 1 : 0) . ";\n";

        $js .= "var version_num = '" . $versioninfo['major'] . "." . $versioninfo['minor'] . "';\n";
        $js .= "var package_name = '" . Mage::getSingleton('core/design_package')->getPackageName() . "';\n";
        $js .= "var theme_name   = '" . Mage::getSingleton('core/design_package')->getTheme('frontend') . "';\n";

        $js .= "var isLogged = 0;\n";

        $temp_layout = Mage::getStoreConfig('onestepcheckout/display_setting/style', $store_id);
        if(!$temp_layout){
            $temp_layout =1;
        }
        $temp_fb = Mage::getStoreConfig('onestepcheckout/addfield/app_fb_id');
        if(!$temp_fb) {
            $temp_fb = 1;
        }
		$round_corner = Mage::getStoreConfig('onestepcheckout/display_setting/round_corner');
		if(!$round_corner) {
			$round_corner = 1;
		}
        $js.= "
            var Mwosctranslator = new Translate([]);
            Mwosctranslator.add('Close', '".Mage::helper('onestepcheckout')->__('Close')."');
            Mwosctranslator.add('Please select a payment method for your order!', '".Mage::helper('onestepcheckout')->__('Please select a payment method for your order!')."');
            Mwosctranslator.add('Please select a shipping method for your order!', '".Mage::helper('onestepcheckout')->__('Please select a shipping method for your order!')."');
            Mwosctranslator.add('Please select delivery time!', '".Mage::helper('onestepcheckout')->__('Please select delivery time!')."');
            Mwosctranslator.add('Your order cannot be completed at this time as there is no shipping methods available for it. Please make necessary changes in your shipping address.', '".Mage::helper('onestepcheckout')->__('Your order cannot be completed at this time as there is no shipping methods available for it. Please make necessary changes in your shipping address.')."');
            Mwosctranslator.add('TaxVat Number is not verified', '".Mage::helper('onestepcheckout')->__('TaxVat Number is not verified')."');
            Mwosctranslator.add('Please enter referral code.', '".Mage::helper('onestepcheckout')->__('Please enter referral code.')."');
            Mwosctranslator.add('Please wait..', '".Mage::helper('onestepcheckout')->__('Please wait..')."');
            Mwosctranslator.add('Not Verified', '".Mage::helper('onestepcheckout')->__('Not Verified')."');
            Mwosctranslator.add('Time:', '".Mage::helper('onestepcheckout')->__('Time:')."');
            Mwosctranslator.add('time off this day', '".Mage::helper('onestepcheckout')->__('time off this day')."');
            Mwosctranslator.add('Back to Login', '".Mage::helper('onestepcheckout')->__('Back to Login')."');
            Mwosctranslator.add('Password is sent successfully!', '".Mage::helper('onestepcheckout')->__('Password is sent successfully!')."');
            Mwosctranslator.add('We have now sent you a new password to your email address. Click the link below to return to login.', '".Mage::helper('onestepcheckout')->__('We have now sent you a new password to your email address. Click the link below to return to login.')."');
            Mwosctranslator.add('There is already a customer registered using this email address. Please login using this email address or enter a different email address.', '".Mage::helper('onestepcheckout')->__('There is already a customer registered using this email address. Please login using this email address or enter a different email address.')."');
        ";
        $js .= "
            window.onestepConfig = {
                url: {
                    save                        : '" . Mage::getUrl('onestepcheckout/index/save_' . md5(time()), $paramHttps) . "',
                    preparesave                 : '" . Mage::getUrl('onestepcheckout/index/preparesave', $paramHttps) . "',
                    syncTime                    : '" . Mage::getUrl('onestepcheckout/index/synctime', $paramHttps) . "',
                    updShippingMethod           : '" . Mage::getUrl('onestepcheckout/index/updateshippingmethod', $paramHttps) . "',
                    updPaymentMethod            : '" . Mage::getUrl('onestepcheckout/index/updatepaymentmethod', $paramHttps) . "',
                    updCoupon                   : '" . Mage::getUrl('onestepcheckout/index/updatecoupon', $paramHttps) . "',
                    updRefferal                 : '" . Mage::getUrl('onestepcheckout/index/updateRefferal', $paramHttps) . "',
                    updQty                      : '" . Mage::getUrl('onestepcheckout/index/updateqty', $paramHttps) . "',
                    removeProduct               : '" . Mage::getUrl('onestepcheckout/index/removeproduct', $paramHttps) . "',
                    updOrderMethod              : '" . Mage::getUrl('onestepcheckout/index/updateordermethod', $paramHttps) . "',
                    remove_saveOrder            : '" . Mage::getUrl('checkout/onepage/saveOrder/form_key/' . Mage::getSingleton('core/session')->getFormKey(), $paramHttps) . "',
                    saveOrder                   : '" . Mage::getUrl('checkout/onepage/saveOrder', $paramHttps) . "',
                    updateEmailMsg              : '" . Mage::getUrl('onestepcheckout/index/updateemailmsg', $paramHttps) . "',
                    updateShippingType          : '" . Mage::getUrl('onestepcheckout/index/updateshippingtype', $paramHttps) . "',
                    updatePaymentType           : '" . Mage::getUrl('onestepcheckout/index/updatepaymenttype', $paramHttps) . "',
                    updateSortBillingForm       : '" . Mage::getUrl('onestepcheckout/index/updatesortbillingform', $paramHttps) . "',
                    updateSortShippingForm      : '" . Mage::getUrl('onestepcheckout/index/updatesortshippingform', $paramHttps) . "',
                    udpateTimepicker            : '" . Mage::getUrl('onestepcheckout/index/updatetimepicker', $paramHttps) . "',
                    updateLogin                 : '" . Mage::getUrl('onestepcheckout/index/updatelogin', $paramHttps) . "',
                    forgotPass                  : '" . Mage::getUrl('onestepcheckout/index/forgotpass', $paramHttps) . "'
                },
                delivery: {
                    rangeDay        :   '+" . (Mage::getStoreConfig("onestepcheckout/deliverydate/rangeday", $store_id) ? Mage::getStoreConfig("onestepcheckout/deliverydate/rangeday") : 0) . "w',
                    weekendDays     :   '" . Mage::getStoreConfig("onestepcheckout/deliverydate/weekend", $store_id) . "',
                    asaOption       :   " . (Mage::getStoreConfig('onestepcheckout/deliverydate/asa_option', $store_id) ? 1 : 0) . ",
                    disabledDays    :   '" . Mage::helper("onestepcheckout")->getNationaldays() . "',
                    enableDays      :   '" . Mage::helper("onestepcheckout")->getAdditionaldays() . "',
                    formatDate      :   '" . Mage::getStoreConfig("onestepcheckout/deliverydate/formatdate", $store_id) . "',
                    isNowDay        :   '" . date(Mage::getStoreConfig("onestepcheckout/deliverydate/formatdate", $store_id), Mage::getModel('core/date')->timestamp(time())) . "',
                    isNowTime       :   '" . date("G:i", Mage::getModel('core/date')->timestamp(time())) . "',
                    isNow           :   '" . date('m/d/Y', Mage::getModel('core/date')->timestamp(time())) . "',
                    buttonImage     :   '" . Mage::getDesign()->getSkinUrl('mw_onestepcheckout/images/grid-cal.gif') . "',
                    clockImagePNG   :   '" . Mage::getDesign()->getSkinUrl('mw_onestepcheckout/images/clock.png') . "',
                    clockImageGIF   :   '" . Mage::getDesign()->getSkinUrl('mw_onestepcheckout/images/clock.gif') . "'
                },

                ajaxPaymentOnShipping   : " . (Mage::getStoreConfig('onestepcheckout/allow_ajax/ajax_shipping_payment', $store_id) ? 1 : 0) . ",
                ajaxShippingOnQty       : " . (Mage::getStoreConfig('onestepcheckout/allow_ajax/ajax_updatepro_shipping', $store_id) ? 1 : 0) . ",
                ajaxShipping            : " . (Mage::getStoreConfig('onestepcheckout/allow_ajax/ajax_shipping', $store_id) ? 1 : 0) . ",
                ajaxPayment             : " . (Mage::getStoreConfig('onestepcheckout/allow_ajax/ajax_payment', $store_id) ? 1 : 0) . ",
                ajaxCountry             : " . (Mage::getStoreConfig('onestepcheckout/allow_ajax/ajax_country', $store_id) ? 1 : 0) . ",
                ajaxShippingOnAddresss  : " . (Mage::getStoreConfig('onestepcheckout/allow_ajax/ajax_shippingmethod', $store_id) ? 1 : 0) . ",
                ajaxPaymentOnAddresss   : " . (Mage::getStoreConfig('onestepcheckout/allow_ajax/ajax_paymentmethod', $store_id) ? 1 : 0) . ",
                ajaxRegion              : " . (Mage::getStoreConfig('onestepcheckout/allow_ajax/ajax_region', $store_id) ? 1 : 0) . ",
                ajaxZipcode             : " . (Mage::getStoreConfig('onestepcheckout/allow_ajax/ajax_zipcode', $store_id) ? 1 : 0) . ",
                ajaxCity                : " . (Mage::getStoreConfig('onestepcheckout/allow_ajax/ajax_city', $store_id) ? 1 : 0) . ",
                ajaxEmail               : " . (Mage::getStoreConfig('onestepcheckout/allow_ajax/ajax_email', $store_id) ? 1 : 0) . ",

                styleColor              : '" . Mage::getStoreConfig('onestepcheckout/display_setting/style_color', $store_id) . "',
            	round_corner		  : " . $round_corner . ",
                checkOutColor           : '" . ( Mage::getStoreConfig('onestepcheckout/display_setting/checkout_button_color', $store_id) ?  Mage::getStoreConfig('onestepcheckout/display_setting/checkout_button_color', $store_id) : 'ea7608'). "',

                pageLayout              : " . (int)Mage::getStoreConfig('onestepcheckout/display_setting/page_layout', $store_id) . ",
                styleLayout             : " . $temp_layout . ",
                defaultShippingmethod   : '" . Mage::getStoreConfig('onestepcheckout/config/default_shippingmethod', $store_id) . "',

                addfieldZip             : " . Mage::getStoreConfig('onestepcheckout/addfield/zip', $store_id) . ",
                addfieldState           : " . Mage::getStoreConfig('onestepcheckout/addfield/state', $store_id) . ",
                addfieldCountry         : " . (Mage::getStoreConfig('onestepcheckout/addfield/country', $store_id) ? 1 : 0) . ",

                hasDefaultBilling       : " . (Mage::getSingleton('customer/session')->getCustomer()->getDefaultBilling() ? 1 : 0) . ",
                hasAddress              : " . (count(Mage::getSingleton('customer/session')->getCustomer()->getAddresses()) ? 1 : 0) . ",

                validVAT                : " . (Mage::getStoreConfig('onestepcheckout/config/valid_vat', $store_id) && Mage::getStoreConfig('onestepcheckout/addfield/taxvat_show', $store_id) != "" ? 1 : 0) . ",

                onlyProductDownloadable : " . (Mage::helper('onestepcheckout')->onlyProductDownloadable() ? 1 : 0) . ",
                isDeliveryDate          : " . ((Mage::getStoreConfig('onestepcheckout/deliverydate/allow_options', $store_id) ? 1 : 0)) . ",
                isGeoIp                 : " . (Mage::getStoreConfig('onestepcheckout/config/enable_geoip', $store_id) ? 1 : 0) . ",
                devMode                 : " . (Mage::getStoreConfig('onestepcheckout/config/devmode', $store_id) ? 1 : 0) . ",
                FbAppId                 : '" .  $temp_fb . "',
                defaultCountry          : '" . (Mage::getStoreConfig('onestepcheckout/config/default_country', $store_id)) . "',
                createAccount           : " . (Mage::getStoreConfig('onestepcheckout/config/create_account', $store_id) ? 1 : 0) . "
            };\n";
        $file = new Varien_Io_File();
        $file->checkAndCreateFolder($this->baseDir . "/media/mw_onestepcheckout/js/");
        $file->checkAndCreateFolder($this->baseDir . "/media/mw_onestepcheckout/css/");

        //if(Mage::app()->getRequest()->getModuleName() == 'onestepcheckout')
        //{
            foreach ($this->_multi_path_js as $code => $path)
            {
                if(!file_exists($path) && Mage::app()->getStore()->getCode() == $code)
                {
                    $file->write($path, $js);
                    $file->close();
                }
            }
        //}

        return $js;
    }
}