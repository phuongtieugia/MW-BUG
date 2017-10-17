<?php
/**
 * User: Anh TO
 * Date: 3/24/14
 * Time: 3:07 PM
 */

class MW_FreeGift_Model_Observer_Layout extends Mage_Core_Model_Abstract{
    protected $_multi_path_js;
    protected $baseDir;
    public function controllerLayoutBefore(Varien_Event_Observer  $observer){
        $action = $observer->getEvent()->getAction();
        $layout = $observer->getEvent()->getLayout();
        if(Mage::getDesign()->getArea() == 'frontend'){
            $store_view = Mage::app()->getStore()->getCode();
            $paramHttps = (Mage::app()->getStore()->isCurrentlySecure()) ?  array('_forced_secure'=>true) : array();

            $this->baseDir = Mage::getBaseDir();
            foreach (Mage::app()->getWebsites() as $website) {
                foreach ($website->getGroups() as $group) {
                    $stores = $group->getStores();
                    foreach ($stores as $store) {
                        $this->_multi_path_js[$store->getCode()] = $this->baseDir."/js/mw_freegift/lib/config-".$store->getCode().".js";
                    }
                }
            }

            /** Create file config for javascript */
            $base_url = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
            $versioninfo = Mage::getVersionInfo();
            $js = "var mw_baseUrl = '';\n";

            $js = str_replace("{BASE_URL}", $base_url, $js);
            $js .= "var mw_js_baseUrl = '';\n";
            $js .= "var mw_post_baseUrl = '';\n";
            $js .= "var mw_ctrl = '".Mage::app()->getRequest()->getControllerName()."';\n";
            $js .= "var mw_mdn = '".Mage::app()->getRequest()->getModuleName()."';\n";
            $js .= "var mw_act = '".Mage::app()->getRequest()->getActionName()."';\n";
            $js .= "var version_num = '".$versioninfo['major'].".".$versioninfo['minor']."';\n";
            $js .= "var package_name = '".Mage::getSingleton('core/design_package')->getPackageName()."';\n";
            $js .= "var theme_name   = '".Mage::getSingleton('core/design_package')->getTheme('frontend')."';\n";

            $js .= 'var mw_session_id = "'.Mage::getSingleton("core/session")->getEncryptedSessionId().'";'."\n";

            $js .= "var isLogged = '".Mage::getSingleton('customer/session')->isLoggedIn()."';\n";
            if(Mage::helper('freegift/version')->isMageCommunity()){
                $version = 'mc';
            }else if(Mage::helper('freegift/version')->isMageEnterprise()){
                $version = 'me';
            }else if(Mage::helper('freegift/version')->isMageProfessional()){
                $version = 'mp';
            }

            $js .= "var version = '$version';\n";
            $js .= "window.hasGiftProduct = false;\n";
            $js .= "window.hasPromotionMessage = false;\n";
            $js .= "window.hasPromotionBanner = false;\n";
            $js .= "
window.freegiftConfig = {
    url: {
        add                                 : '',
        configure                           : '',
        getproduct                          : '',
        updatePost                          : '',
        delete                              : '',
        cart                                : ''
    },
};\n";
            $file = new Varien_Io_File();
            foreach($this->_multi_path_js as $code => $path){
                if(Mage::app()->getStore()->getCode() == $code){
                    $file->write($path, $js);
                    $file->close();
                }
            }
        }
    }
}