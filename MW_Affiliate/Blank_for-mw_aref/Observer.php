<?php
class MW_Affiliate_Model_Observer
{
	public function dispathClickLink()
	{   
		$invite = Mage::app()->getRequest()->getParam('mw_aref');
		if($invite)
		{
			$referral_to = explode("?mw_aref", Mage::helper('core/url')->getCurrentUrl());
			Mage::dispatchEvent('affiliate_referral_link_click',array('invite'=>$invite,'request'=>Mage::app()->getRequest(),'referral_to'=>$referral_to[0]));
			Mage::getSingleton('core/session', array("name"=>"frontend"))->addSuccess(Mage::helper('affiliate')->__('Thank you for visiting our site'));
		}
		
		/* Check whether the url come from affiliate-site or not */
		if(Mage::getModel('affiliate/affiliatewebsitemember')) {
			$referredFrom = Mage::app()->getRequest()->getServer('HTTP_REFERER');
			$url = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
			
	    	if($referredFrom !='' && substr_count($referredFrom,$url)== 0 ){
				if($emailInvitation = Mage::helper('affiliate')->isDirectReferral($referredFrom))
				{
					Mage::dispatchEvent('affiliate_referral_link_click',
										array(
											'invite'  		=> $emailInvitation,
											'request' 		=> Mage::app()->getRequest(),
											'referral_to'	=> Mage::helper('core/url')->getCurrentUrl()
										));
					Mage::getSingleton('core/session', array("name"=>"frontend"))->addSuccess(Mage::helper('affiliate')->__('Thank you for visiting our site'));
				}
	    	}
		}
	}
	
	public function deleteCustomer($argv)
	{
		Mage::helper('affiliate/data')->getDeleteCustomer();
	}
	
	public function clickReferralLink($argv)
    {   
   		$store_id = Mage::app()->getStore()->getId();
   		$invite = $argv->getInvite();
    	if(Mage::helper('affiliate/data')->getEnabledStore($store_id)) 
    	{
    		$query = "SELECT `e`.* FROM `customer_entity` AS `e` WHERE (`e`.`entity_type_id` = '1') AND (md5(email)='".$invite."')";
        	$resource = Mage::getSingleton('core/resource');
        	$readConnection = $resource->getConnection('core_read');
        	$results = $readConnection->fetchAll($query);
        	$customer_id = $results[0]['entity_id'];
        	$timeCokie = Mage::helper('affiliate/data')->getTimeCookieStore($store_id);

        	if($customer_id != "")
       		{
       		 	if(Mage::helper('affiliate')->getActiveAffiliate($customer_id) == 1 && Mage::helper('affiliate')->getLockAffiliate($customer_id) == 0)
     		 	{
           	    	$clientIP = Mage::app()->getRequest()->getServer('REMOTE_ADDR');
					$cookie = (int)Mage::getModel('core/cookie')->get('customer');
                	if(!$cookie)
               		{
               			$referral_from = '';
			    		if(isset($_SERVER['HTTP_REFERER'])) {
			    			$referral_from = trim($_SERVER['HTTP_REFERER']);
			    		}
			    	
			    		if($referral_from !=''){
			    			$referral_from_domains = explode("://", $referral_from);
			    			$referral_from_domain = explode("/", $referral_from_domains[1]);
			    		}else{
			    			$referral_from_domain[0] = '';
			    		}

				    	Mage::getModel('core/cookie')->set('customer',$customer_id,60*60*24*$timeCokie);
                   		Mage::getModel('core/cookie')->set('mw_referral_from',$referral_from,60*60*24*$timeCokie);
                   		Mage::getModel('core/cookie')->set('mw_referral_from_domain',$referral_from_domain[0],60*60*24*$timeCokie);
                   		Mage::getModel('core/cookie')->set('mw_referral_to',$referral_to,60*60*24*$timeCokie);
                   		$invitation_type = MW_Affiliate_Model_Typeinvitation::REFERRAL_LINK;
               		 	
                   		// Calculate the commission from referral visitor 
                   		$affiliateCustomer = Mage::getModel('affiliate/affiliatecustomers')->load($customer_id);
                   		$referralVisitorCommission = Mage::helper('affiliate')->calculateReferralVisitorCommission($customer_id, $affiliateCustomer->getLinkClickIdPivot());
                   		
                   		// Save new invitation to db 
                   		$invitationModel = Mage::getModel('affiliate/affiliateinvitation'); 
                        $historyData = array(
                        			'customer_id'			=> $customer_id,
			                       	'email'					=> '', 
				                    'status'				=> MW_Affiliate_Model_Statusinvitation::CLICKLINK, 
				                    'ip'					=> $clientIP,
                        			'count_click_link'		=> 1,
                        			'count_register'		=> 0, 
                        			'count_purchase'		=> 0,
                        			'count_subscribe'		=> 0,
                        	        'referral_from'			=> $referral_from, 
                        			'referral_from_domain'	=> $referral_from_domain[0],
                        			'referral_to'			=> $referral_to,
                        			'order_id'				=> '',
                        			'invitation_type'		=> $invitation_type,
				                    'invitation_time'		=> now(),
                        			'commission'			=> $referralVisitorCommission
                       	);
                        $invitationModel->setData($historyData)->save();
                        
						Mage::helper('affiliate')->saveAffiliateTransactionReferral($customer_id,$referralVisitorCommission,$customer_id,$invitation_type,MW_Credit_Model_Transactiontype::REFERRAL_VISITOR);

						// If there is enough visitors to get commission then update customer overall commission 
                        if($referralVisitorCommission > 0) 
                        {
                        	// Update link_click_id_pivot
                        	$affiliateCustomer->setLinkClickIdPivot($invitationModel->getId());
                        	
                        	// Update total commission in affiliate_customers
                        	$currentCommission = $affiliateCustomer->getTotalCommission();
                        	$affiliateCustomer->setTotalCommission($currentCommission + $referralVisitorCommission);
                        	
                        	// Save affiliate_customers
                        	$affiliateCustomer->save(); 
                        	
	                        // Update customer credit
	                        Mage::helper('affiliate')->insertCustomerCredit($customer_id);
	                        $customerCredit = Mage::getModel('credit/creditcustomer')->load($customer_id);
	                        $currentCredit = $customerCredit->getCredit();
	                        $newCredit = $currentCredit + $referralVisitorCommission;
	                        $customerCredit->setCredit($newCredit)->save();
	                        
	                        // Update credit history table
	                        $creditHistoryData = array(
                    			'customer_id'			=> $customer_id,
                    			'type_transaction'		=> MW_Credit_Model_Transactiontype::REFERRAL_VISITOR,
                    			'status'				=> MW_Credit_Model_Orderstatus::COMPLETE,
                    			'transaction_detail'	=> $invitationModel->getId(),
                    			'amount'				=> $referralVisitorCommission,
                    			'beginning_transaction'	=> $currentCredit,
                    			'end_transaction'		=> $newCredit,
                    			'created_time'			=> now()
	                       	);
	                        Mage::getModel("credit/credithistory")->setData($creditHistoryData)->save();
	         			}
               		}
	     		}
       		}
    	}
    }
    
    // kiem tra xem co can overwrite form register ko?
    public function overwriteFormRegister($argv)
    {
    	 $store_id = Mage::app()->getStore()->getId();
    	 if(Mage::helper('affiliate/data')->getEnabledStore($store_id))  
    	 { 
    	 	$overwrite_form = (int)Mage::helper('affiliate/data')->getOverWriteRegisterStore($store_id);
    	 	$auto_signup_affiliate = (int)Mage::helper('affiliate/data')->getAutoSignUpAffiliateStore($store_id);
    	 	if(!$auto_signup_affiliate && $overwrite_form) {
    	 		Mage::app()->getLayout()->getBlock('customer_form_register')->setTemplate('mw_affiliate/customer/form/register.phtml');
    	 	}
    	 } 
    }
    
    // kiem tra customer truoc khi luu
    public function beforeSaveCustomer($argv)
    {
    	 $store_id = Mage::app()->getStore()->getId();
    	 if(Mage::helper('affiliate/data')->getEnabledStore($store_id))  
    	 {  
    	 	$show_signup_affiliate = (int)Mage::helper('affiliate/data')->getShowSignUpFormAffiliateRegisterStore($store_id);
      	 	$session = Mage::getSingleton('customer/session');
    	 	$session->unsetData('check_affiliate');
            $session->unsetData('payment_gateway');
            $session->unsetData('payment_email');
            $session->unsetData('auto_withdrawn');
            $session->unsetData('withdrawn_level');
            $session->unsetData('reserve_level');
            $session->unsetData('bank_name');
            $session->unsetData('name_account');
            $session->unsetData('bank_country');
            $session->unsetData('swift_bic');
            $session->unsetData('referral_site');
           
    	 	$data = $argv->getData('controller_action')->getRequest()->getPost();
    	 	$referral_code = '';
    	 	if(isset($data['referral_code'])) {
    	 		$referral_code = $data['referral_code'];
    	 	}
    	 	
            $max = (double)Mage::helper('affiliate/data')->getWithdrawMaxStore($store_id);
  			$min = (double)Mage::helper('affiliate/data')->getWithdrawMinStore($store_id);
  			if(isset($data['check_affiliate']) && $show_signup_affiliate == 1)
  			{
  				$session->setCheckAffiliate($data['check_affiliate']);
  			}
    	 	if(isset($data['check_affiliate']) && $show_signup_affiliate == 2)
			{   
				// set session
				$getway_withdrawn = $data['getway_withdrawn'];
	    	 	$paypalemail = $data['paypal_email'];
	            $auto_withdrawn = (int)$data['auto_withdrawn'];
	            $payment_release_level = (double)$data['payment_release_level'];
	            $reserve_level = $data['reserve_level'];
	            $bank_name = $data['bank_name'];
	            $name_account = $data['name_account'];
	            $bank_country = $data['bank_country'];
	            $swift_bic = $data['swift_bic'];
	            $referral_site = $data['referral_site'];
				$session->setCheckAffiliate($data['check_affiliate']);
				$session->setPaymentGateway($getway_withdrawn);
				$session->setPaymentEmail($paypalemail);
				$session->setAutoWithdrawn($auto_withdrawn);
				$session->setBankName($bank_name);
				$session->setNameAccount($name_account);
				$session->setBankCountry($bank_country);
				$session->setSwiftBic($swift_bic);
				if($referral_site) {
					$session->setReferralSite($referral_site);
				}
				if($payment_release_level) {
					$session->setWithdrawnLevel($payment_release_level);
				}
				if($reserve_level) {
					$session->setReserveLevel($reserve_level);
				}
				if($getway_withdrawn != 'banktransfer' && $getway_withdrawn != 'check')
				{
	        		$collectionFilter = Mage::getModel('affiliate/affiliatecustomers')->getCollection()
		                    			->addFieldToFilter('payment_email',$paypalemail);
		            if(sizeof($collectionFilter) > 0 )
		            {
		            	Mage::getSingleton('customer/session')->addError(Mage::helper('affiliate')->__('There is already an account with this emails paypal'));
		            	Mage::app()->getResponse()->setRedirect(Mage::getUrl('customer/account/create'));
		            	Mage::app()->getResponse()->sendHeaders();
		            	exit;
		            }
				}
	            if($auto_withdrawn == MW_Affiliate_Model_Autowithdrawn::AUTO)
	            {
	            	if($payment_release_level < $min  || $payment_release_level > $max)
	            	{
			            Mage::getSingleton('customer/session')->addError(Mage::helper('affiliate')->__('Please insert a value of Auto payment when account balance reaches that is in range of [%s, %s]',$min,$max));
		            	Mage::app()->getResponse()->setRedirect(Mage::getUrl('customer/account/create'));
		            	Mage::app()->getResponse()->sendHeaders();
		            	exit; 
	            	}
	            }
			}
    	 	// check referral code 
    	 	if($referral_code != '') {
    	 		$check = Mage::helper('affiliate') ->checkReferralCode($referral_code);
    	 		if($check == 0) {
    	 			Mage::getSingleton('customer/session')->addError(Mage::helper('affiliate')->__('The referral code is invalid.'));
	            	Mage::app()->getResponse()->setRedirect(Mage::getUrl('customer/account/create'));
	            	Mage::app()->getResponse()->sendHeaders();
	            	exit;
    	 		}
    	 	}
    	}
    }
    
    // luu customer bang su kien cua magento
    public function setCustomerAccount($argv)
    {   
    	 $store_id = Mage::app()->getStore()->getId();
    	 if(Mage::helper('affiliate/data')->getEnabledStore($store_id))  
    	 {	    
    	 		$auto_signup_affiliate = (int)Mage::helper('affiliate/data')->getAutoSignUpAffiliateStore($store_id);
    	 		$show_signup_affiliate = (int)Mage::helper('affiliate/data')->getShowSignUpFormAffiliateRegisterStore($store_id);
    	 		$data = $argv->getData('controller_action')->getRequest()->getPost();
    	 		$customers = Mage::getModel('customer/customer')->setWebsiteId(Mage::app()->getStore()->getWebsiteId())->getCollection();
        		$customers->getSelect()->where("email='".$data['email']."'");
        		$customer_id = 0;
        		if(sizeof($customers) > 0)
	       		 {
	            	foreach ($customers as $customer)
	           		 {
	           		 	$customer_id = $customer->getId();
	           		 	break;
	           		 }
	       		 }
	       		$collectionFilter = Mage::getModel('affiliate/affiliatecustomers')
	       							->getCollection()
	                    			->addFieldToFilter('customer_id', $customer_id);
    	 		if($customer_id && sizeof($collectionFilter) == 0)
    	 		{   
		    		$session = Mage::getSingleton('customer/session');
		    	 	$session->unsetData('check_affiliate');
		            $session->unsetData('payment_gateway');
		            $session->unsetData('payment_email');
		            $session->unsetData('auto_withdrawn');
		            $session->unsetData('withdrawn_level');
		            $session->unsetData('reserve_level');
		            $session->unsetData('bank_name');
		            $session->unsetData('name_account');
		            $session->unsetData('bank_country');
		            $session->unsetData('swift_bic');
		            $session->unsetData('referral_site');
		            
		    		$clientIP = Mage::app()->getRequest()->getServer('REMOTE_ADDR');

		    		$referral_code = '';
    	 			if(isset($data['referral_code'])) {
    	 				$referral_code = $data['referral_code'];
    	 			}
		    		
		    		// neu khong ton tai cokie cua thanh vien gioi thieu . gan cookie = 0
    	 			$cookie = (int)Mage::getModel('core/cookie')->get('customer');
		    		if($cookie) {
		    			if(Mage::helper('affiliate')->getLockAffiliate($cookie) > 0) {
		    				$cookie = 0;
		    			}
		    		} 
		    		else {
		    			$cookie = 0;
		    		};
		    		
		    		$cookie_old = $cookie;
		    		// set lai gia tri cho customer invited
		    		if($referral_code != '') {
		    			$cookie = Mage::helper('affiliate')->getCustomerIdByReferralCode($referral_code, $cookie);
		    		}
		    		$invitation_type = MW_Affiliate_Model_Typeinvitation::NON_REFERRAL;
		    		if($cookie != 0) {
		    			$invitation_type = MW_Affiliate_Model_Typeinvitation::REFERRAL_LINK;
		    		}
		    		if($cookie_old != $cookie && $cookie != 0) {
		    			$invitation_type = MW_Affiliate_Model_Typeinvitation::REFERRAL_CODE;
		    		}
		    		
		    		$active = MW_Affiliate_Model_Statusactive::INACTIVE;
	    			$payment_gateway = ''; 
	    			$paypal_email = '';
	    			$auto_withdrawn = 0;
	    			$payment_release_level = 0;
	    			$reserve_level = 0;
	    			$bank_name = '';
	    			$name_account = '';
	    			$bank_country = '';
	    			$swift_bic = '';
	    			$account_number = '';
	    			$re_account_number = '';
	    			$referral_site = '';

	    			// neu khach hang dang ky la thanh vien affiliate
		    		if($auto_signup_affiliate){
		    			$active = MW_Affiliate_Model_Statusactive::PENDING;
		    			$auto_approved = Mage::helper('affiliate/data')->getAutoApproveRegisterStore($store_id);
		    			if($auto_approved) $active = MW_Affiliate_Model_Statusactive::ACTIVE;
		    		}
		    		else if(isset($data['check_affiliate']) && $show_signup_affiliate == 2)
		    		{   
		    			$active = MW_Affiliate_Model_Statusactive::PENDING;
		    			$auto_approved = Mage::helper('affiliate/data')->getAutoApproveRegisterStore($store_id);
		    			
		    			if($auto_approved) {
		    				$active = MW_Affiliate_Model_Statusactive::ACTIVE;
		    			}
		    			
			    		$payment_gateway = $data['getway_withdrawn'];
			    		$paypal_email = $data['paypal_email'];
			    		$auto_withdrawn = $data['auto_withdrawn'];
			    		$payment_release_level = $data['payment_release_level'];
			    		$reserve_level = $data['reserve_level'];
			    		$referral_site = $data['referral_site'];
			    		
			    		if($payment_gateway == 'check') {
			    			$paypal_email = '';
			    		}
			    		
			    		if($payment_gateway == 'banktransfer') {
			    			$paypal_email = '';
			    			$bank_name = $data['bank_name'];
				            $name_account = $data['name_account'];
				            $bank_country = $data['bank_country'];
				            $swift_bic = $data['swift_bic'];
				            $account_number = $data['account_number'];
				            $re_account_number = $data['re_account_number'];
			    		}
			    		
		    		}
		    		else if(isset($data['check_affiliate']) && $show_signup_affiliate == 1)
		    		{
		    			$active = MW_Affiliate_Model_Statusactive::PENDING;
		    			$auto_approved = Mage::helper('affiliate/data')->getAutoApproveRegisterStore($store_id);
		    			if($auto_approved) $active = MW_Affiliate_Model_Statusactive::ACTIVE;
		    		};
		    		
		    		// trong truong hop rut tien bang tay
		    		if($auto_withdrawn == MW_Affiliate_Model_Autowithdrawn::MANUAL) {
		    			$payment_release_level = 0;
		    		}
		    		if(!$reserve_level) {
		    			$reserve_level = 0;
		    		}
		    		if(!$referral_site) {
		    			$referral_site = '';
		    		}
		    		
		    		// luu khach hang vao csdl 
		    		$customerData = array(
		    							  'customer_id'			=> $customer_id,
			              				  'active'				=> $active,
			              				  'payment_gateway'		=> $payment_gateway,
			              				  'payment_email'		=> $paypal_email,
			              				  'auto_withdrawn'		=> $auto_withdrawn,
			              				  'withdrawn_level'		=> $payment_release_level,
		    							  'reserve_level'		=> $reserve_level,
		    							  'bank_name'			=> $bank_name,
								    	  'name_account'		=> $name_account,
								    	  'bank_country'		=> $bank_country,
								    	  'swift_bic'			=> $swift_bic,
								    	  'account_number'		=> $account_number,
								    	  're_account_number'	=> $re_account_number,
								    	  'referral_site'		=> $referral_site,
		    							  'total_commission'	=> 0,
		    							  'total_paid'			=> 0,
		    							  'referral_code' 		=> '',
		    							  'status'				=> 1,
		    							  'invitation_type'		=> $invitation_type,
		    							  'customer_time' 		=> now(),
			              				  'customer_invited'	=> $cookie
		    						);

		    		if(!($active == MW_Affiliate_Model_Statusactive::INACTIVE && $cookie == 0)) {
		    			Mage::getModel('affiliate/affiliatecustomers')->saveCustomerAccount($customerData);
		    		}
		    		
		    		// truong hop co customer invited
		    		$referral_from = Mage::getModel('core/cookie')->get('mw_referral_from');
		    		$referral_to = Mage::getModel('core/cookie')->get('mw_referral_to');
		    		$referral_from_domain = Mage::getModel('core/cookie')->get('mw_referral_from_domain');
		    		if(!$referral_from) {
		    			$referral_from = '';
		    		}
		    		if(!$referral_to) {
		    			$referral_to = '';
		    		}
		    		if(!$referral_from_domain) {
		    			$referral_from_domain = '';
		    		}
		    		
		    		if($cookie != 0) { 
						// Update for affiliate pro v4.0 (Subscribe)
						$isSubscribed = $data['is_subscribed'];		    			
		    			Mage::helper('affiliate')->updateAffiliateInvitionNew($customer_id, $cookie, $clientIP,$referral_from,$referral_from_domain,$referral_to,$invitation_type,$isSubscribed);
		    		}
		    		
		    		// gui mail cho khach hang khi dang ky lam thanh vien affiliate
		    		if($active == MW_Affiliate_Model_Statusactive::PENDING)
		    		{   
		    			Mage::helper('affiliate')->sendEmailCustomerPending($customer_id);
		    			// gui mail cho admin chiu trach nhiem active customer affiliate
			    		Mage::helper('affiliate')->sendEmailAdminActiveAffiliate($customer_id);
			    		
		    		}
		    		else if($active == MW_Affiliate_Model_Statusactive::ACTIVE)
		    		{
		    			// set lai referral code cho cac customer affiliate
                     	Mage::helper('affiliate')->setReferralCode($customer_id);
                     	
		    			$store_id = Mage::getModel('customer/customer')->load($customer_id)->getStoreId();
	        			Mage::helper('affiliate')->setMemberDefaultGroupAffiliate($customer_id,$store_id);
		    			
		    			//set total member customer program 
						Mage::helper('affiliate')->setTotalMemberProgram();
                     	//gui mail khi admin dong y cho gia nhap vao affiliate
                     	Mage::helper('affiliate')->sendMailCustomerActiveAffiliate($customer_id);
		    		};
		        	      	
    	      }	
    	  }
    }
    
	public function saveCustomerAffiliate($argv)
    {   
    	$store_id = Mage::app()->getStore()->getId();
    	if(Mage::helper('affiliate/data')->getEnabledStore($store_id))  
    	{	
    		$data = $argv->getAffiliateAccount();
    		$clientIP = Mage::app()->getRequest()->getServer('REMOTE_ADDR');
    		$referral_code = $data['referral_code'];
    		$cookie = (int)Mage::getModel('core/cookie')->get('customer');
    		
    		// neu khong ton tai cokie cua thanh vien gioi thieu . gan cookie = 0
    		if($cookie) {
    			if (Mage::helper('affiliate')->getLockAffiliate($cookie)== 1) {
    				$cookie = 0;
    			}
	    	} 
	    	else {
    			$cookie = 0;
    		};
    		$cookie_old = $cookie;
    		
    		// set lai gia tri cho customer invited
	    	if($referral_code != '') {
	    		$cookie = Mage::helper('affiliate')->getCustomerIdByReferralCode($referral_code, $cookie);
	    	}
		    	
	    	$invitation_type = MW_Affiliate_Model_Typeinvitation::NON_REFERRAL;
    		if($cookie != 0) {
    			$invitation_type = MW_Affiliate_Model_Typeinvitation::REFERRAL_LINK;
    		}
    		if($cookie_old != $cookie && $cookie != 0 ) {
    			$invitation_type = MW_Affiliate_Model_Typeinvitation::REFERRAL_CODE;
    		}
		    	
    		$customer_id = (int)$argv->getCustomerAffiliate()->getId();
	    	$check_affiliate = $argv->getCheckAffiliate();
	    	$payment_gateway = $data['getway_withdrawn'];
	    	$paypal_email = $data['paypal_email'];
	    	$auto_withdrawn = $data['auto_withdrawn'];
	    	$payment_release_level = $data['payment_release_level'];
	    	$reserve_level = $data['reserve_level'];
	    	$bank_name = '';
    		$name_account = '';
    		$bank_country = '';
    		$swift_bic = '';
    		$account_number = '';
    		$re_account_number = '';
    		$referral_site = '';
	    	$referral_site = $data['referral_site'];
	    	
	    	if($payment_gateway == 'check') {
	    		$paypal_email = '';
	    	}
		    if($payment_gateway == 'banktransfer') {
	    		$paypal_email = '';
	    		$bank_name = $data['bank_name'];
		        $name_account = $data['name_account'];
		        $bank_country = $data['bank_country'];
	            $swift_bic = $data['swift_bic'];
	            $account_number = $data['account_number'];
	            $re_account_number = $data['re_account_number'];
    		}

    		$active = MW_Affiliate_Model_Statusactive::PENDING;
    		// neu co tu dong approved affiliate
    		$auto_approved = Mage::helper('affiliate/data')->getAutoApproveRegisterStore($store_id);

    		if($auto_approved) {
    			$active = MW_Affiliate_Model_Statusactive::ACTIVE;
    		}
    		// neu khach hang khong dang ky la thanh vien affiliate
    		if(!$check_affiliate)
    		{   
    			$active = MW_Affiliate_Model_Statusactive::INACTIVE;
    			$payment_gateway = ''; 
    			$paypal_email = '';
    			$auto_withdrawn = 0;
    			$payment_release_level = 0;
    			$reserve_level = 0;
    			$bank_name = '';
    			$name_account = '';
    			$bank_country = '';
    			$swift_bic = '';
    			$account_number = '';
    			$re_account_number = '';
    			$referral_site = '';
    		}
    		// trong truong hop rut tien bang tay
    		if($auto_withdrawn == MW_Affiliate_Model_Autowithdrawn::MANUAL) {
    			$payment_release_level = 0;
    		}
    		if(!$reserve_level) {
    			$reserve_level = 0;
    		}
    		if(!$referral_site) {
    			$referral_site = '';
    		}
    		// luu khach hang vao csdl 
    		$customerData = array('customer_id'			=> $customer_id,
	              				  'active'				=> $active,
	              				  'payment_gateway'		=> $payment_gateway,
	              				  'payment_email'		=> $paypal_email,
	              				  'auto_withdrawn'		=> $auto_withdrawn,
	              				  'withdrawn_level'		=> $payment_release_level,
    							  'reserve_level'		=> $reserve_level,
    							  'bank_name'			=> $bank_name,
						    	  'name_account'		=> $name_account,
						    	  'bank_country'		=> $bank_country,
						    	  'swift_bic'			=> $swift_bic,
						    	  'account_number'		=> $account_number,
						    	  're_account_number'	=> $re_account_number,
						    	  'referral_site'		=> $referral_site,
    							  'total_commission'	=> 0,
    							  'total_paid'			=> 0,
    							  'referral_code' 		=> '',
    							  'status'				=> 1,
    		                      'invitation_type'		=> $invitation_type,
    							  'customer_time' 		=> now(),
	              				  'customer_invited'	=> $cookie
    						);
    		Mage::getModel('affiliate/affiliatecustomers')->saveCustomerAccount($customerData);
    		
    		// truong hop co customer invited
    		$referral_from = Mage::getModel('core/cookie')->get('mw_referral_from');
    		$referral_to = Mage::getModel('core/cookie')->get('mw_referral_to');
    		$referral_from_domain = Mage::getModel('core/cookie')->get('mw_referral_from_domain');
    		if(!$referral_from) {
    			$referral_from = '';
    		}
    		if(!$referral_to) {
    			$referral_to = '';
    		}
    		if(!$referral_from_domain) {
    			$referral_from_domain = '';
    		}
    		if($cookie != 0) {
    			Mage::helper('affiliate')->updateAffiliateInvitionNew($customer_id, $cookie, $clientIP,$referral_from,$referral_from_domain,$referral_to,$invitation_type);
    		}
    			
    		// gui mail cho khach hang khi dang ky lam thanh vien affiliate
    		if($active == MW_Affiliate_Model_Statusactive::PENDING)
    		{   
    			Mage::helper('affiliate')->sendEmailCustomerPending($customer_id);
    			// gui mail cho admin chiu trach nhiem active customer affiliate
	    		Mage::helper('affiliate')->sendEmailAdminActiveAffiliate($customer_id);
    		}
    	 	else if($active == MW_Affiliate_Model_Statusactive::ACTIVE)
	    	{
    			// set lai referral code cho cac customer affiliate
                Mage::helper('affiliate')->setReferralCode($customer_id);
    			$store_id = Mage::getModel('customer/customer')->load($customer_id)->getStoreId();
        		Mage::helper('affiliate')->setMemberDefaultGroupAffiliate($customer_id,$store_id);
    			//set total member customer program 
				Mage::helper('affiliate')->setTotalMemberProgram();
                //gui mail khi admin dong y cho gia nhap vao affiliate
                Mage::helper('affiliate')->sendMailCustomerActiveAffiliate($customer_id);
	    	};	
	    	
        }
    }
    
    // update status order
   	public function updateStatusOrder($arvgs)
    {
    	$order = $arvgs->getOrder();
    	$store_id = $order->getStoreId();
    	if(Mage::helper('affiliate/data')->getEnabledStore($store_id))  
	    {   
	    	$status_add_commsion = Mage::helper('affiliate/data')->getStatusAddCommissionStore($store_id);
	    	$status_subtract_commsion = Mage::helper('affiliate/data')->getStatusSubtractCommissionStore($store_id);	 

	    	$order_id = $order->getIncrementId();
	    	
	    	if($order->getStatus() == $status_add_commsion) {
	    		$this->saveOrderComplete($order_id);
	    	}
	    	
	    	if($order->getStatus() == $status_subtract_commsion) {
	    		$this->saveOrderClosed($order_id,$store_id);
	    	}
	    	
	    	if($order->getStatus() == 'canceled') {
	    		$this->saveOrderCanceled($order_id);	 	 
	    	}
	    }  	
    }
    
    // update status canceled for trasaction affiliate
    public function saveOrderCanceled($order_id)
    {
    	$collections = Mage::getModel('affiliate/affiliatehistory')->getCollection()
	                    		->addFieldToFilter('order_id', $order_id)
	                    		->addFieldToFilter('status',MW_Affiliate_Model_Status::PENDING);
	                    		
        foreach ($collections as $collection) {
            $collection->setStatus(MW_Affiliate_Model_Status::CANCELED);
            $collection->setTransactionTime(now());
    		$collection->save();
        }
     	$transaction_collections = Mage::getModel('affiliate/affiliatetransaction')->getCollection()
                    				->addFieldToFilter('order_id',$order_id)
                    				->addFieldToFilter('status',MW_Affiliate_Model_Status::PENDING);
        foreach ($transaction_collections as $transaction_collection) 
        {
         	$transaction_collection->setStatus(MW_Affiliate_Model_Status::CANCELED);
    		$transaction_collection->setTransactionTime(now());
    		$transaction_collection->save();
        }
    }
    
	// cap nhat lai trang thai complete cua order
	// update status complete when add commission
	public function saveOrderComplete($order_id)
    {     
     	$order_ids = array();
    	$order_ids[] = $order_id;
    	$check_holding = Mage::helper('affiliate')->holdingTimeConfig();
    	$collections = Mage::getModel('affiliate/affiliatehistory')
    				   ->getCollection()
                       ->addFieldToFilter('order_id',$order_id)
                       ->addFieldToFilter('status',MW_Affiliate_Model_Status::PENDING);
        foreach($collections as $collection)
    	{    
    		 $program_id = $collection->getProgramId();
    		 $history_commission = $collection->getHistoryCommission();
    		 $affiliate_programs = Mage::getModel('affiliate/affiliateprogram')
    		 					   ->getCollection()
                    			   ->addFieldToFilter('program_id', $program_id);
             foreach($affiliate_programs as $affiliate_program) 
             {
    			 $total_commission_old = $affiliate_program->getTotalCommission();
    			 $total_commission_new = $total_commission_old + $history_commission;
    			 $total_commission_new = round($total_commission_new,2);
    			 Mage::getModel('affiliate/affiliateprogram')->load($program_id)->setTotalCommission($total_commission_new)->save();
             }
    		 
             /* Set status to holding when order complete => commission-holding-function */ 
    		 //$collection->setStatus(MW_Affiliate_Model_Status::COMPLETE);
             
	 		 if($check_holding > 0){
	 			$collection->setStatus(MW_Affiliate_Model_Status::HOLDING);
	 		 }else{
	 		 	$collection->setStatus(MW_Affiliate_Model_Status::COMPLETE);
	 		 }
    		 
        	 
    		 $collection->setTransactionTime(now());
    		 $collection->save();	
    	}
       	$transaction_collections = Mage::getModel('affiliate/affiliatetransaction')->getCollection()
		                    		->addFieldToFilter('order_id',$order_id)
		                    		->addFieldToFilter('status',MW_Affiliate_Model_Status::PENDING);
        foreach ($transaction_collections as $transaction_collection) 
        { 
        	if($check_holding > 0){
	 			$transaction_collection->setStatus(MW_Affiliate_Model_Status::HOLDING);
	 		 }else{
	 		 	$transaction_collection->setStatus(MW_Affiliate_Model_Status::COMPLETE);
	 		 }
	 		 
    		$transaction_collection->setTransactionTime(now());
    		$transaction_collection->save();
        }
              
        if(sizeof($collections) > 0) {
        	Mage::dispatchEvent('mw_affiliate_save_credit_history', array('order_ids'=>$order_ids)); 
        }
    }
    
    // update status closed (refund product) to order
 	public function saveOrderClosed($order_id,$store_id)
    {     
     	$enableDiscount = Mage::helper('affiliate/data')->getDicountWhenRefundProductStore($store_id);
  
    	$order_ids = array();
    	$order_ids[] = $order_id;
     	$collections = Mage::getModel('affiliate/affiliatehistory')
     				   ->getCollection()
                       ->addFieldToFilter('order_id',$order_id)
                       ->addFieldToFilter('status',MW_Affiliate_Model_Status::COMPLETE);
        foreach ($collections as $collection) 
        {
        	if($enableDiscount == 1)
          	{
          		$program_id = $collection ->getProgramId();
    			$history_commission = $collection ->getHistoryCommission();
    			$affiliate_programs = Mage::getModel('affiliate/affiliateprogram')->getCollection()
                    								  ->addFieldToFilter('program_id',$program_id);
                foreach ($affiliate_programs as $affiliate_program) 
                {
	    			$total_commission_old = $affiliate_program ->getTotalCommission();
	    			$total_commission_new = $total_commission_old - $history_commission;
	    			$total_commission_new = round($total_commission_new,2);
	    			//$affiliate_program ->setTotalCommission($total_commission_new)->save();
	    			Mage::getModel('affiliate/affiliateprogram')->load($program_id)->setTotalCommission($total_commission_new)->save();
                }
          	}
           	$collection->setStatus(MW_Affiliate_Model_Status::CLOSED);
           	$collection->setTransactionTime(now());
    		$collection->save();
        }
     	$transaction_collections = Mage::getModel('affiliate/affiliatetransaction')->getCollection()
                    				->addFieldToFilter('order_id',$order_id)
                    				->addFieldToFilter('status',MW_Affiliate_Model_Status::COMPLETE);
        foreach ($transaction_collections as $transaction_collection) 
        {
            $transaction_collection->setStatus(MW_Affiliate_Model_Status::CLOSED);
    		$transaction_collection->setTransactionTime(now());
    		$transaction_collection->save();
        }
        
        if($enableDiscount == 1) {
        	Mage::dispatchEvent('mw_affiliate_refund_order',array('order_ids'=>$order_ids));
        }
    }
      
    public function runCronMemberProgram()
    {
		Mage::helper('affiliate')->setTotalMemberProgram();
    }
      	
	public function runCron()
    {   
      	$store_id = Mage::app()->getStore()->getId();
    	if (Mage::helper('affiliate')->getEnabled()) 
    	{  	 
    	 	// gio he thong
    	 	$day_week = (int)date('w', Mage::getModel('core/date')->timestamp(time()));
    	 	$day_month = (int)date('j', Mage::getModel('core/date')->timestamp(time()));
    	 	$withdrawn_period = (int)Mage::helper('affiliate/data')->getWithdrawnPeriodStore($store_id);
    	 	$withdrawn_days = (int)Mage::helper('affiliate/data')->getWithdrawnDayStore($store_id);
    	 	$withdrawn_month = (int)Mage::helper('affiliate/data')->getWithdrawnMonthStore($store_id);
    	 	if(is_null($withdrawn_days)) {
    	 		$withdrawn_days = 50;
    	 	}
    	 	if(is_null($withdrawn_month)) {
    	 		$withdrawn_month = 50;
    	 	}
    	 	$fee = (int)Mage::helper('affiliate/data')->getFeeStore($store_id);
    	 	if(($withdrawn_period == 1 && $day_week == $withdrawn_days) || ($withdrawn_period == 2 && $day_month == $withdrawn_month) )
    	 	{
    	 		 $collections = Mage::getModel('affiliate/affiliatecustomers')
    	 		 				->getCollection()
    	 		 				->addFieldToFilter('active',MW_Affiliate_Model_Statusactive::ACTIVE)
    	 		 				->addFieldToFilter('auto_withdrawn',MW_Affiliate_Model_Autowithdrawn::AUTO)
                    			->addFieldToFilter('status',MW_Affiliate_Model_Statusreferral::ENABLED);
                 foreach ($collections as $collection) 
                 {
                 	$withdrawn_level = $collection ->getWithdrawnLevel();
                 	$customer_id = $collection ->getCustomerId();
                 	$reserve_level = $collection ->getReserveLevel();
                 	$withdrawn = $withdrawn_level + $reserve_level;
                 	
                 	$credit = Mage::getModel('credit/creditcustomer')->load($customer_id)->getCredit();
                 	if($credit >= $withdrawn)
                 	{   
                 		// luu vao bang withdrawn
                 		$withdraw_receive = $credit - $fee;
                 		$affiliate_customer = Mage::getModel('affiliate/affiliatecustomers')->load($customer_id);
						$payment_gateway = $affiliate_customer->getPaymentGateway();
						$payment_email = $affiliate_customer->getPaymentEmail();
						if($payment_gateway == 'banktransfer') {
							$payment_email = '';
						}
					    	
					  	$bank_name = $affiliate_customer->getBankName();
						$name_account = $affiliate_customer->getNameAccount();
						$bank_country = $affiliate_customer->getBankCountry();
						$swift_bic = $affiliate_customer->getSwiftBic();
						$account_number= $affiliate_customer->getAccountNumber();
						$re_account_number = $affiliate_customer->getReAccountNumber();
		
                 		$withdrawnData =  array(
                 								'customer_id'		=> $customer_id,
                 								'payment_gateway'	=> $payment_gateway,
					              				'payment_email'		=> $payment_email,
					              				'bank_name'			=> $bank_name,
									    	    'name_account'		=> $name_account,
									    	    'bank_country'		=> $bank_country,
									    	    'swift_bic'			=> $swift_bic,
									    	    'account_number'	=> $account_number,
									    	    're_account_number'	=> $re_account_number,
				              					'withdrawn_amount'	=> $credit,
				              					'fee'				=> $fee,
												'amount_receive'	=> $withdraw_receive,
				          						'status'			=> MW_Affiliate_Model_Status::PENDING,
				                        		'withdrawn_time'	=> now()
                 							);
						Mage::getModel('affiliate/affiliatewithdrawn')->setData($withdrawnData)->save();
						 
						// update lai credit
						$creditcustomer = Mage::getModel('credit/creditcustomer')->load($customer_id);
			    		$oldCredit = $creditcustomer->getCredit();
			   			$amount = - $credit;
			  			$newCredit = $oldCredit + $amount;
			  			$newCredit = round($newCredit,2);
			   			$creditcustomer->setCredit($newCredit)->save();
			   			
			   			$collectionWithdrawns = Mage::getModel('affiliate/affiliatewithdrawn')
			   									->getCollection()
			                    				->addFieldToFilter('customer_id',$customer_id)
		                    					->setOrder('withdrawn_id','DESC');
				        foreach($collectionWithdrawns as $collectionWithdrawn)
				        {
				        	$withdrawn_id = $collectionWithdrawn->getWithdrawnId();
				        	break;
				        }
			   			// luu vao bang credit history
			       		$historyData = array(
			       							 'customer_id'			=> $customer_id,
						 					 'type_transaction'		=> MW_Credit_Model_Transactiontype::WITHDRAWN, 
						 					 'status'				=> MW_Credit_Model_Orderstatus::PENDING,
							     		     'transaction_detail'	=> $withdrawn_id, 
							           		 'amount'				=> $amount, 
							         		 'beginning_transaction'=> $oldCredit,
							        		 'end_transaction'		=> $newCredit,
							           	     'created_time'			=> now()
			       						);
			   			Mage::getModel("credit/credithistory")->setData($historyData)->save();
			   			
			   			// gui mail cho khach hang khi rut tien tu dong
			   			Mage::helper('affiliate')->sendMailCustomerRequestWithdrawn($customer_id, $credit);
                 	}
           	    }
    		}
   		}
	}
	
	public function runCronHoldingCommission() 
	{
		$collections = Mage::getModel('affiliate/affiliatehistory')
					   ->getCollection()
					   ->addFieldToFilter('status', array('eq' => MW_Affiliate_Model_Status::HOLDING));
		 
		$currentTime = Mage::getModel('core/date')->timestamp(time());
		$affiliateCustomerModel = Mage::getModel('affiliate/affiliatecustomers');
		$creditHistoryModel = Mage::getModel('credit/credithistory');
		$creditCustomerModel = Mage::getModel('credit/creditcustomer');
		 
		foreach($collections as $item)
		{
			/*$order_id = $item->getOrderId();
			$o = Mage::getModel('sales/order')->loadByIncrementId($order_id); 
			if(Mage::getStoreConfig('affiliate/general/status_subtract_commission') == $o->getStatus()){

				$item->setTransactionTime(now());
				$item->setStatus(MW_Affiliate_Model_Status::CLOSED);
				$item->save();
				$transaction_collections = Mage::getModel('affiliate/affiliatetransaction')->getCollection()
                    									 ->addFieldToFilter('order_id',$item->getOrderId())
                    									 ->addFieldToFilter('status', array('eq' => MW_Affiliate_Model_Status::HOLDING));
                foreach ($transaction_collections as $transaction_collection) {
                	$transaction_collection->setStatus(MW_Affiliate_Model_Status::CLOSED)->save();
                }
			}
			else{}*/
			$beginningTime = strtotime($item->getTransactionTime());
			$holdingTime = intval(Mage::getStoreConfig('affiliate/general/commission_holding_period')) * 86400;
		
			if($beginningTime + $holdingTime < $currentTime) {
				/* Update affiliate customer table */
				$affiliateCustomer = $affiliateCustomerModel->load($item->getCustomerInvited());
				$currentCommission = $affiliateCustomer->getTotalCommission();
				$affiliateCustomer->setTotalCommission($currentCommission + $item->getHistoryCommission());
				$affiliateCustomer->save();
				 
				/* Update credit history table */
				$creditHistory = $creditHistoryModel
				->getCollection()
				->addFieldToFilter('transaction_detail', array('eq' => $item->getOrderId()))
				->getFirstItem();
				$creditHistoryModel->load($creditHistory->getCreditHistoryId())->setStatus(MW_Credit_Model_Orderstatus::COMPLETE);
				$creditHistoryModel->save();
		
				/* Update credit customer table */
				$creditCustomer = $creditCustomerModel->load($item->getCustomerInvited());
				$currentCredit = $creditCustomer->getCredit();
				$creditCustomer->setCredit($currentCredit + $item->getHistoryCommission());
				$creditCustomer->save();
				 
				/* Update affiliate history table */
				$item->setTransactionTime(now());
				$item->setStatus(MW_Affiliate_Model_Status::COMPLETE);
				$item->save();
				$transaction_collections = Mage::getModel('affiliate/affiliatetransaction')->getCollection()
                    									 ->addFieldToFilter('order_id',$item->getOrderId())
                    									 ->addFieldToFilter('status', array('eq' => MW_Affiliate_Model_Status::HOLDING));
                foreach ($transaction_collections as $transaction_collection) {
                	$transaction_collection->setStatus(MW_Affiliate_Model_Status::COMPLETE)->save();
                }
                
			}
		}
	}
		
}