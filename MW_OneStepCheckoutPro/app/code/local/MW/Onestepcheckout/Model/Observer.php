<?php
class MW_Onestepcheckout_Model_Observer
{
	public function model_config_data_save_before($ovserver)
	{
	$config_onecheckout = $_POST;
	if(isset($config_onecheckout['config_state']) && isset($config_onecheckout['config_state']['onestepcheckout_config']) && $config_onecheckout['config_state']['onestepcheckout_config']!="")
	{
	if(isset($config_onecheckout['groups']['addfield']['fields']) && isset($config_onecheckout['groups']['config']['fields']) )
	 {		
		
	 	$addfield = $config_onecheckout['groups']['addfield']['fields'];
		$config = $config_onecheckout['groups']['config']['fields'];	
		$termcondition = $config_onecheckout['groups']['termcondition']['fields'];		
		
		if(isset($config['enabled']['value']) && intval($config['enabled']['value']==1))
		{
			// set default country
			if(isset($config['default_country']['value']))
			{	
				Mage::getModel('core/config')->saveConfig('general/country/default', $config['default_country']['value'] );
			}
			
			if (isset($addfield['street_lines']['value']) && intval($addfield['street_lines']['value'])>=1 && intval($addfield['street_lines']['value'])<=4)
			{
				Mage::getModel('core/config')->saveConfig('customer/address/street_lines',$addfield['street_lines']['value']);
			}
			else 
			{
				Mage::getModel('core/config')->saveConfig('customer/address/street_lines',2);
			}
			
			if(isset($config['allowguestcheckout']['value']))
			{
				Mage::getModel('core/config')->saveConfig('checkout/options/guest_checkout', 1);
				Mage::getModel('core/config')->saveConfig('catalog/downloadable/disable_guest_checkout', 0);
			}
			
			if(isset($addfield['prefix_show']['value']))
			Mage::getModel('core/config')->saveConfig('customer/address/prefix_show',$addfield['prefix_show']['value']);	

			if(isset($addfield['middlename_show']['value']))
			Mage::getModel('core/config')->saveConfig('customer/address/middlename_show',$addfield['middlename_show']['value']);
			
			if(isset($addfield['suffix_show']['value']))
			Mage::getModel('core/config')->saveConfig('customer/address/suffix_show',$addfield['suffix_show']['value']);
			
			if(isset($addfield['dob_show']['value']))
			Mage::getModel('core/config')->saveConfig('customer/address/dob_show',$addfield['dob_show']['value']);
			
			if(isset($addfield['taxvat_show']['value']))
			Mage::getModel('core/config')->saveConfig('customer/address/taxvat_show',$addfield['taxvat_show']['value']);
			
			if(isset($termcondition['allow_options']['value']) && intval($termcondition['allow_options']['value'])==1)
			{
				Mage::getModel('core/config')->saveConfig('checkout/options/enable_agreements',0);
			}
			
			//fix magento ent
			if( isset($addfield['taxvat_show']['value']) && $addfield['taxvat_show']['value']!=0)
			{
				Mage::getModel('core/config')->saveConfig('customer/create_account/vat_frontend_visibility',1);
			}
			else 
			{
				Mage::getModel('core/config')->saveConfig('customer/create_account/vat_frontend_visibility',0);
			}
			
			if(isset($addfield['gender_show']['value']))
			Mage::getModel('core/config')->saveConfig('customer/address/gender_show',$addfield['gender_show']['value']);
									
			// set option or required for zip post code, state provice.				
			//config to zip postal code
			if(isset($addfield['zip']['value']) && (intval($addfield['zip']['value']) == 1 || intval($addfield['zip']['value']) == 0))
			{	
				$country_allow = Mage::getStoreConfig('general/country/allow');
				
				Mage::getModel('core/config')->saveConfig('general/country/optional_zip_countries',$country_allow);
			}
			
			if(isset($addfield['zip']['value']) &&  intval($addfield['zip']['value']) == 2)
			{		
				Mage::getModel('core/config')->saveConfig('general/country/optional_zip_countries','');					
			}
			
			// config state is option
			if(version_compare(Mage::getVersion(),'1.7.0.0','>=') && (isset($addfield['state']['value']) && (intval($addfield['state']['value'])==1 || intval($addfield['state']['value'])==0)))
			{
				//$inchooSwitch = new Mage_Core_Model_Config();
				Mage::getModel('core/config')->saveConfig('general/region/state_required','' );
				Mage::getModel('core/config')->saveConfig('general/region/display_all',1);
			}	
			
			// config state is required		
			if(version_compare(Mage::getVersion(),'1.7.0.0','>=') && isset($addfield['state']['value']) && intval($addfield['state']['value'])==2)
			{
				$country_allow = Mage::getStoreConfig('general/country/allow');
				Mage::getModel('core/config')->saveConfig('general/region/state_required',$country_allow );
				Mage::getModel('core/config')->saveConfig('general/region/display_all',1);
			}	
		}
		else 
		{
			// required zip post code with every countries
			Mage::getModel('core/config')->saveConfig('general/country/optional_zip_countries','');
			if(version_compare(Mage::getVersion(),'1.7.0.0','>='))
				{
					Mage::getModel('core/config')->saveConfig('general/region/state_required','');
					Mage::getModel('core/config')->saveConfig('general/region/display_all',1);
				}
		}
		}
	}
	}
	
	public function checkout_cart_add_product_complete($ovserver)
	{
		if(Mage::getStoreConfig('onestepcheckout/config/disable_shop_cart'))
		{				
			Mage::app()->getResponse()->setRedirect(Mage::getUrl('checkout/onepage'));			
			Mage::app()->getResponse()->sendResponse(); 
			exit;
		}
	}
	
	public function saveAfter($o)
	{	
				
		$order = $o->getEvent()->getOrder();
		
		try {	
			//remove postcode with value = '.'
			$billingmodel = Mage::getModel('sales/order_address');
			$billing = $order->getBillingAddress()->getData();
			if(!Mage::helper('onestepcheckout')->onlyProductDownloadable())
			{
		  		$shipping = $order->getShippingAddress()->getData();
				$billingmodel->load($shipping['entity_id']);
		  		if($billingmodel->getPostcode() == ".") 
		  			{
		  				$billingmodel->setPostcode('')->setId($shipping['entity_id']);
		  				$billingmodel->save();
		  			}
			}	
	  			
			
	  		$billingmodel->load($billing['entity_id']);
	  		if($billingmodel->getPostcode() == ".") 
	  			{
	  				$billingmodel->setPostcode('')->setId($billing['entity_id']);
	  				$billingmodel->save();
	  			}
	  				  		
				if(Mage::getSingleton('core/session')->getDeliveryInforOrder())
				{
						$deliveryinfor = Mage::getSingleton('core/session')->getDeliveryInforOrder();
						//Mage::log(Zend_Debug::dump($deliveryinfor));			
						$customercomment =  $deliveryinfor[0]; // comment
						$deliverystatus =  $deliveryinfor[1]; // deliverydate
						$deliverydate =   $deliveryinfor[2]; // checkoutdate
						$deliverytime =  $deliveryinfor[3]; //checkouttime
						
						$saleorderid = $order->getId();
						$row_saleorderid = Mage::getModel('onestepcheckout/onestepcheckout')->getCollection()
											->addFieldToFilter('sales_order_id',$saleorderid);
						
						if(!$row_saleorderid->getSize())
						{
							$orderonestep=Mage::getModel('onestepcheckout/onestepcheckout');
							$orderonestep->setSalesOrderId($order->getId());
							$orderonestep->setMwCustomercommentInfo($customercomment);
							$order->setMwCustomercommentInfo($customercomment);
							/* if($deliverystatus=="late"){				
								$orderonestep->setMwDeliverydateDate($deliverydate);
								$orderonestep->setMwDeliverydateTime($deliverytime);
							} */
							if($deliverystatus=="late"){	
								$dates = explode("/",$deliverydate);
								$newdate = $dates[2]."-".$dates[1]."-".$dates[0];
								if($dates[1] > 12){
									$newdate = $dates[2]."-".$dates[0]."-".$dates[1];
								}
								$orderonestep->setMwDeliverydateDate($newdate);
								$orderonestep->setMwDeliverydateTime($deliverytime);
								$order->setMwDeliverydate($newdate);
								$order->setMwDeliverytime($deliverytime);
							}
							$orderonestep->save();	
							$order->save();
						}						
						Mage::getSingleton('core/session')->unsDeliveryInforOrder();
				}
		
	  			$islogin = Mage::getSingleton('customer/session')->isLoggedIn();
	  			if($islogin && Mage::getSingleton('core/session')->getAccountInfor())
	  			{            	
	  				$accountinformation  = Mage::getSingleton('core/session')->getAccountInfor();	  				
	  				// save account information 	  				 
	  				 $customerId =   Mage::getSingleton('customer/session')->getCustomerId();
	  				 $customer = Mage::getSingleton('customer/customer')->load($customerId);  	
	  				        		         	
					 if($accountinformation[0] != "") // dob
					 {
						$dateofbirth = date("Y-m-d H:m:i",strtotime($accountinformation[0]));
						$customer->setDob($dateofbirth);
					 }
					 if($accountinformation[1] != "") // gender
						$customer->setGender($accountinformation[1]);
					 if($accountinformation[2] != "") // taxvat
						$customer->setTaxvat($accountinformation[2]);
					 if($accountinformation[3] != "") // suffix
						$customer->setSuffix($accountinformation[3]);
					 if($accountinformation[4] != "") // prefix
						$customer->setPrefix($accountinformation[4]);
					 if($accountinformation[5] != "") // middlename
						$customer->setMiddlename($accountinformation[5]);	
					if($accountinformation[6] != "") // middlename
						$customer->setFirstname($accountinformation[6]);	
					if($accountinformation[7] != "") // middlename
						$customer->setLastname($accountinformation[7]);				
			      	 $customer->setEntityId($customerId);
			         $customer->save();
			         Mage::getSingleton('customer/session')->setCustomer($customer);
	  				//unset sessiong account
	  				Mage::getSingleton('customer/session')->unsAccountInfor();
	  			} else {
					Mage::getSingleton('checkout/session')->setQuoteId($order->getQuoteId());
				}
		}
  		catch(Exception $e)
  		{
  			Mage::log('save account infomation: '.$e);
  		}
  			
		if($order->getPayment()->getMethod()=="sagepayform" || $order->getPayment()->getMethod()=="sagepaydirectpro")
		{		
				$isSage = Mage::helper('sagepaysuite')->isSagePayMethod($order->getPayment()->getMethod());
		
				if($isSage === false){
					return $o;
				}
				
			    $transation = Mage::getModel('sagepaysuite2/sagepaysuite_transaction');
				if($transation->loadByParent($order->getId())->getId()){
					return $o;
				}
		
				if((int)Mage::getStoreConfig('payment/sagepaysuite/order_error_save', Mage::app()->getStore()->getId()) === 1){
					Mage::throwException(Mage::getStoreConfig('payment/sagepaysuite/order_error_save_message', Mage::app()->getStore()->getId()));
				}
				
				$session = Mage::getSingleton('sagepaysuite/session');
				$rqVendorTxCode = Mage::app()->getRequest()->getParam('vtxc');
		        $sessionVendor = ($rqVendorTxCode) ? $rqVendorTxCode : $session->getLastVendorTxCode();
				
				/**
				 * Multishipping vendors
				 */
				$multiShippingTxCodes = Mage::registry('sagepaysuite_ms_txcodes');
				if($multiShippingTxCodes){
		
					Mage::unregister('sagepaysuite_ms_txcodes');
		
					$sessionVendor = current($multiShippingTxCodes);
		
					array_shift($multiShippingTxCodes);
					reset($multiShippingTxCodes);
		
					Mage::register('sagepaysuite_ms_txcodes', $multiShippingTxCodes);
		
				}
				/**
				 * Multishipping vendors
				 */
		
		        $reg = Mage::registry('Ebizmarts_SagePaySuite_Model_Api_Payment::recoverTransaction');
		        if(!is_null($reg)){
		        	$sessionVendor = $reg;
		        }
		
		        if(is_null($sessionVendor)){
		
					$dbtrn = $transation->loadByParent($order->getId());
		        	if(!$dbtrn->getId()){
		
						#For empty payments or old orders (standalone payment methods).
						if( (Mage::app()->getRequest()->getControllerModule() == 'Mage_Api') || Mage::registry('current_shipment') || Mage::registry('sales_order') || Mage::registry('current_creditmemo') || Mage::registry('current_invoice')){
							return $o;
						}
		
		        		$logfileName = $order->getIncrementId() . '-' . time() . '_Payment_Failed.log';
		
						$request_data = $_REQUEST;
						if( isset($request_data['payment']) ){
							$request_data['payment']['cc_number'] = 'XXXXXXXXXXXXX';
							$request_data['payment']['cc_cid'] = 'XXX';
						}
		
						Sage_Log::log($order->getIncrementId(), null, $logfileName);
						Sage_Log::log(Mage::helper('core/http')->getHttpUserAgent(false), null, $logfileName);
						Sage_Log::log(print_r($request_data, true), null, $logfileName);
						Sage_Log::log('--------------------', null, $logfileName);
		
						Mage::throwException('Payment has failed, please reload checkout page and try again. Your card has not been charged. 2');
		        	}
		
		            return $o;
		        }
				$tran = $transation
		            ->loadByVendorTxCode($sessionVendor)
		            ->setOrderId($order->getId());
				if($tran->getId()){
		
					if($tran->getToken()){
						$token = Mage::getModel('sagepaysuite2/sagepaysuite_tokencard')->loadByToken($tran->getToken());
						if($token->getId()){
							$tran->setCardType($token->getCardType())
								->setLastFourDigits($token->getLastFour());
						}
					}		
					$tran->save();		
				}
		
				// Ip address for SERVER method
				if( $session->getRemoteAddr() ){
		        	$order->setRemoteIp($this->getSession()->getRemoteAddr());
				}
						
		        # Invoice automatically PAYMENT transactions
		        if($session->getInvoicePayment() || (!is_null($reg) && $tran->getTxType() == 'PAYMENT')){
		            $session->unsetData('invoice_payment');
		            Mage::getModel('sagepaysuite/api_payment')->invoiceOrder($order);
		        }
		}
	}
	
	public function checkLicense($o)
	{
		$modules = Mage::getConfig()->getNode('modules')->children();
		$modulesArray = (array)$modules; 
		$modules2 = array_keys((array)Mage::getConfig()->getNode('modules')->children()); 
		if(!in_array('MW_Mcore', $modules2) || !$modulesArray['MW_Mcore']->is('active') || Mage::getStoreConfig('mcore/config/enabled')!=1)
		{
			Mage::helper('onestepcheckout')->disableConfig();
		}
		
	}
    
    /**
     * 
     * Delete all files in this folder
     * 
     */
    
    public function cleanCacheMedia()
    {
           $dirjs = Mage::getBaseDir().'\\media\\mw_onestepcheckout\\js\\';
           $dircss = Mage::getBaseDir().'\\media\\mw_onestepcheckout\\css\\';
           
           //Delete all
           $this->unlinkRecursive($dirjs,true);
           $this->unlinkRecursive($dircss,true);
           $this->unlinkRecursive( Mage::getBaseDir().'\\media\\mw_onestepcheckout\\',true);
    }
    
    /**
    * Recursively delete a directory
    *
    * @param string $dir Directory name
    * @param boolean $deleteParent Delete 
    */
    public function unlinkRecursive($dir, $deleteParent)
    {
        if(!$child = @opendir($dir))
        {
            return;
        }
        while (false !== ($obj = readdir($child)))
        {
            if($obj == '.' || $obj == '..')
            {
                continue;
            }
    
            if (!@unlink($dir . '/' . $obj))
            {
                $this->unlinkRecursive($dir.'/'.$obj, true);
            }
        }
    
        closedir($child);
       
        if ($deleteParent)
        {
            @rmdir($dir);
        }
       
        return;
    } 
}
