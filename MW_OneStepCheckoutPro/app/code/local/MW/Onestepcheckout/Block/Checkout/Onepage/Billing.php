<?php
class MW_Onestepcheckout_Block_Checkout_Onepage_Billing extends Mage_Checkout_Block_Onepage_Billing
{
    public function getCountryHtmlSelect($type)
    {
    	if(Mage::getSingleton('core/session')->getCountryId())
		      $countryId = Mage::getSingleton('core/session')->getCountryId();
		else 
              $countryId = $this->getAddress()->getCountryId();
        
        if (is_null($countryId)){
			if(Mage::getStoreConfig('onestepcheckout/config/enable_geoip')){
				$countryId=Mage::registry('Countrycode');
			}
			elseif (Mage::getStoreConfig('onestepcheckout/config/default_country')) {
           		 $countryId = Mage::getStoreConfig('onestepcheckout/config/default_country');	           		 	
			}
			else {
				$countryId = Mage::getStoreConfig('general/country/default');	
			}
        }
          
        $select = $this->getLayout()->createBlock('core/html_select')
            ->setName($type.'[country_id]')
            ->setId($type.':country_id')
            ->setTitle(Mage::helper('checkout')->__('Country'))
            ->setClass('validate-select billing_country')
            ->setValue($countryId)
            ->setOptions($this->getCountryOptions());    
        if ($type === 'shipping') {
            $select->setExtraParams('onchange="shipping.setSameAsBilling(false);"');
        }      
        return $select->getHtml();
    }
  
    
   
}