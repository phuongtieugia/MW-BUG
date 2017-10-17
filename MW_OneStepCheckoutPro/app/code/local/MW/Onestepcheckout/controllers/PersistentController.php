<?php
require_once 'Mage/Persistent/controllers/IndexController.php';
class MW_Onestepcheckout_PersistentController extends Mage_Persistent_IndexController
{

 public function saveMethodAction()
    {    	
        if ($this->_getHelper()->isPersistent()) {
            $this->_getHelper()->getSession()->removePersistentCookie();
            /** @var $customerSession Mage_Customer_Model_Session */
            $customerSession = Mage::getSingleton('customer/session');
            if (!$customerSession->isLoggedIn()) {
                $customerSession->setCustomerId(null)
                    ->setCustomerGroupId(null);
            }

            Mage::getSingleton('persistent/observer')->setQuoteGuest();
        }

       // $checkoutUrl = $this->_getRefererUrl();
        // $this->_redirectUrl($checkoutUrl . (strpos($checkoutUrl, '?') ? '&' : '?') . 'register');
        $this->_redirectUrl(Mage::helper('checkout/url')->getCheckoutUrl()."?register"); 
		return;
    }

}