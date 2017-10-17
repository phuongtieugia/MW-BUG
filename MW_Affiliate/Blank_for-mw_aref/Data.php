<?php
class MW_Affiliate_Helper_Data extends Mage_Core_Helper_Abstract
{  
	const MYCONFIG = "affiliate/config/enabled";
	
	public function getEnterprisePro()
	{
		$modules = array_keys((array)Mage::getConfig()->getNode('modules')->children());
		if(in_array('Enterprise_Enterprise',$modules)) 
		{
				return true;
		}
		return false;
	}
	public function insertCustomerCredit($customer_id)
	{
		$customerCredit = Mage::getModel('credit/creditcustomer')->load($customer_id);
		if(!($customerCredit->getId())) 
     	{
			//Add credit to new customer
           	$customerData = array(
           						  'customer_id'=>$customer_id,
            	   		       	  'credit'=>0
           					);
          	Mage::getModel("credit/creditcustomer")->saveCreditCustomer($customerData); 
		}
	}
	
	public function holdingTimeConfig()
	{
		$store_id = Mage::app()->getStore()->getId();
		$holding_time = (int)Mage::getStoreConfig('affiliate/general/commission_holding_period',$store_id);
		return $holding_time;
	}
	
	public function getDeleteCustomer()
	{
		$customer_ids = Mage::getModel('customer/customer')->getCollection()->getAllIds();
    	$customer_affiliate_ids = Mage::getModel('affiliate/affiliatecustomers')->getCollection()->getAllIds();
    	$array_customer_deletes = array_diff($customer_affiliate_ids, $customer_ids);
    	if(sizeof($array_customer_deletes)>0){
    		foreach ($array_customer_deletes as $array_customer_delete) {
    			Mage::getModel('affiliate/affiliatecustomers')->load($array_customer_delete)->delete();
    		}
    	}
	}
	
	public function getAffiliateHistory()
	{
		$customer_id = (int)Mage::getSingleton("customer/session")->getCustomer()->getId();
		$collectionFilter = Mage::getModel('affiliate/affiliatehistory')->getCollection()
                    	->addFieldToFilter('customer_id',$customer_id);
		return sizeof($collectionFilter);
	}
	
	public function getReferralCodeByCheckout()
    {
    	return Mage::getSingleton('checkout/session')->getReferralCode();
    }
    
    public function setReferralCode($customer_id)
    {   
    	$affiliate_customers = Mage::getModel('affiliate/affiliatecustomers')->load($customer_id);
    	$store_id = $affiliate_customers->getStoreId();
    	$length = (int)Mage::helper('affiliate/data')->getLengthReferralCodeStore($store_id);
    	$i = 0;
		$referral_code = $this->rand_str($length);
		while ($i == 0) {
		       $affiliate_customers_filter = Mage::getModel('affiliate/affiliatecustomers')->getCollection()
								                        ->addFieldToFilter('referral_code', $referral_code);
		       if(sizeof($affiliate_customers_filter) > 0) {
			       $i = 0;
			       $referral_code = $this->rand_str($length);
		       } else {
		       	   $i = 1;
		       }  				        
		}
		$referral_code_new = $referral_code;
		$affiliate_customers->setReferralCode($referral_code_new)->save();
    }
    
	public function rand_str($length = 32, $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890')
	{
	    // Length of character list
	    $chars_length = (strlen($chars) - 1);
	
	    // Start our string
	    $string = $chars{rand(0, $chars_length)};
	   
	    // Generate random string
	    for ($i = 1; $i < $length; $i = strlen($string))
	    {
	        // Grab a random character from our list
	        $r = $chars{rand(0, $chars_length)};
	       
	        // Make sure the same two characters don't appear next to each other
	        if ($r != $string{$i - 1}) $string .=  $r;
	    }
	   
	    // Return the string
	    return $string;
	}
	
    public function getCustomerIdByReferralCode($referral_code, $cokie)
    {   
    	$result = $cokie;
    	if(isset($referral_code) && $referral_code != ''){
	    	$collectionCustomers = Mage::getModel('affiliate/affiliatecustomers')->getCollection()
		                    					->addFieldToFilter('referral_code',$referral_code);
		    if(sizeof($collectionCustomers) >0){
		    	foreach ($collectionCustomers as $collectionCustomer) {
		    		$customer_id = $collectionCustomer ->getCustomerId();
		    		$active = $collectionCustomer ->getActive();
	    			$status = $collectionCustomer ->getStatus();
	    			break;
		    	}
		    	if($active == MW_Affiliate_Model_Statusactive::ACTIVE && $status == MW_Affiliate_Model_Statusreferral::ENABLED){
		            $result = $customer_id;		
		        }
		    }
    	}
    	return $result;
    }
    
    public function checkReferralCode($referral_code)
    {   
    	$result = 0;
    	$collectionCustomers = Mage::getModel('affiliate/affiliatecustomers')->getCollection()
	                    					->addFieldToFilter('referral_code',$referral_code);
	    if(sizeof($collectionCustomers) > 0){
	    	foreach ($collectionCustomers as $collectionCustomer) {
	    		$active = $collectionCustomer->getActive();
    			$status = $collectionCustomer->getStatus();
    			break;
	    	}
	    	if($active == MW_Affiliate_Model_Statusactive::ACTIVE && $status == MW_Affiliate_Model_Statusreferral::ENABLED){
	            $result = 1;		
	        }
	    }
    	return $result;
    }
    
	public function checkReferralCodeCart($referral_code)
    {   
    	$result = 0;
    	$customer_id = (int)Mage::getSingleton("customer/session")->getCustomer()->getId();
    	$collectionCustomers = Mage::getModel('affiliate/affiliatecustomers')->getCollection()
	                    					->addFieldToFilter('referral_code',$referral_code);
	    if(sizeof($collectionCustomers) >0){
	    	foreach ($collectionCustomers as $collectionCustomer) {
	    		$customer_id_referral_code = $collectionCustomer ->getCustomerId();
	    		$active = $collectionCustomer ->getActive();
    			$status = $collectionCustomer ->getStatus();
    			break;
	    	}
	    	if($active == MW_Affiliate_Model_Statusactive::ACTIVE && $status == MW_Affiliate_Model_Statusreferral::ENABLED){
	            $result = 1;
	            if(isset($customer_id) && $customer_id == $customer_id_referral_code)	$result = 0;	
	        }
	    }
    	return $result;
    }
    
    public function setMemberGroupAffiliate($group_id, $customer_id)
    {
    	$data = array('customer_id'=>$customer_id,
                      'group_id'=>$group_id);
    	
    	Mage::getModel('affiliate/affiliategroupmember')->setData($data)->save();
    }
    
	public function setTotalMemberProgram()
	{
		$collectionPrograms = Mage::getModel('affiliate/affiliateprogram')->getCollection();
		if(sizeof($collectionPrograms) >0){
			foreach ($collectionPrograms as $collectionProgram) {
				$total_member = 0;
				$program_id = $collectionProgram ->getProgramId();
				$groupPrograms = Mage::getModel('affiliate/affiliategroupprogram')->getCollection()
										->addFieldToFilter('program_id',$program_id);
				if(sizeof($groupPrograms) >0){
					foreach ($groupPrograms as $groupProgram) {
						$group_id = $groupProgram ->getGroupId();
						$customerPrograms = Mage::getModel('affiliate/affiliategroupmember')->getCollection()
											->addFieldToFilter('group_id',$group_id);
						$total_member = $total_member + sizeof($customerPrograms);
					}						
					
				}
				Mage::getModel('affiliate/affiliateprogram')->load($program_id)->setTotalMembers($total_member)->save();
			}
		}
	}
	
	public function getAffiliateActive()
	{
		$customer_id = (int)Mage::getSingleton("customer/session")->getCustomer()->getId();
		$collectionFilter = Mage::getModel('affiliate/affiliatecustomers')->getCollection()
                    	->addFieldToFilter('customer_id',$customer_id)
                    	->addFieldToFilter('active',MW_Affiliate_Model_Statusactive::ACTIVE);
		return sizeof($collectionFilter);
	}
	
	public function formatMoney($money)
	{
		return Mage::helper('core')->currency($money);
	}
	
	public function getGroupNameByCustomer($customer_id)
	{
		  $customerGroups = Mage::getModel('affiliate/affiliategroupmember')->getCollection()
									->addFieldToFilter('customer_id',$customer_id);
		  $group_name  = '';
		  if(sizeof($customerGroups) >0 ){
		  	 foreach ($customerGroups as $customerGroup) {
			  	  $group_id = $customerGroup ->getGroupId();
			  	  break;
			  }
			  $affiliate_group = Mage::getModel('affiliate/affiliategroup')->load($group_id);
		      $group_name = $affiliate_group ->getGroupName();
		  }						
		  
	      return $group_name;
	}
	
	public function getActiveAndEnableAffiliate($customer_id)
	{
		$customer_id = (int)$customer_id;
		$collectionFilter = Mage::getModel('affiliate/affiliatecustomers')->getCollection()
		                    	->addFieldToFilter('customer_id',$customer_id)
		                    	->addFieldToFilter('active',MW_Affiliate_Model_Statusactive::ACTIVE)
		                    	->addFieldToFilter('status',MW_Affiliate_Model_Statusreferral::ENABLED);
		return sizeof($collectionFilter);
	}
	
	/*public function getLockAffiliate($customer_id)
	{   
		$customer_id = (int)$customer_id;
		//echo $customer_id;
		$collectionFilter = Mage::getModel('affiliate/affiliatecustomers')->getCollection()
		                    	->addFieldToFilter('customer_id',$customer_id)
		                    	->addFieldToFilter('status',MW_Affiliate_Model_Statusreferral::LOCKED);
		                    	//echo $collectionFilter->getSelect(); exit;
		return sizeof($collectionFilter);
	}*/

	public function getLockAffiliate($customer_id)
	{   
		$customer_id = (int)$customer_id;
		$query = "SELECT COUNT(`customer_id`) as 'count' FROM `mw_affiliate_customers` WHERE `customer_id` = '".$customer_id."' AND `status` = '2'";
    	$resource = Mage::getSingleton('core/resource');
    	$readConnection = $resource->getConnection('core_read');
    	$results = $readConnection->fetchAll($query);
    	return $results[0]['count'];
	}
	
	public function getAffiliateLock()
	{   
		$customer_id = (int)Mage::getSingleton("customer/session")->getCustomer()->getId();
		$collectionFilter = Mage::getModel('affiliate/affiliatecustomers')->getCollection()
		                    	->addFieldToFilter('customer_id',$customer_id)
		                    	->addFieldToFilter('status',MW_Affiliate_Model_Statusreferral::LOCKED);
		return sizeof($collectionFilter);
	}
	
	/*public function getActiveAffiliate($customer_id)
	{   
		$customer_id = (int)$customer_id;
		$collectionFilter = Mage::getModel('affiliate/affiliatecustomers')->getCollection()
		                    	->addFieldToFilter('customer_id',$customer_id)
		                    	->addFieldToFilter('active',MW_Affiliate_Model_Statusactive::ACTIVE);
		return sizeof($collectionFilter);
	}*/
	public function getActiveAffiliate($customer_id)
	{   
		$customer_id = (int)$customer_id;
		$query = "SELECT COUNT(`customer_id`) as 'count' FROM `mw_affiliate_customers` WHERE `customer_id` = '".$customer_id."' AND `active` = '2'";
    	$resource = Mage::getSingleton('core/resource');
    	$readConnection = $resource->getConnection('core_read');
    	$results = $readConnection->fetchAll($query);
    	return $results[0]['count'];
	}
	
	// customer khong la thanh vien affiliate va khong co customer invited tra ve 0
	// hoac khong ton tai customer id tra ve 0
	// nguoc lai tra ve 1
	public function checkCustomer($customer_id)
	{   
		$result = 0;
		if($customer_id){
			
			$result = 1;
			$active = $this ->getActiveAffiliate($customer_id);
			$customer_invited = Mage::getModel('affiliate/affiliatecustomers')->load($customer_id)->getCustomerInvited();
			if(!$customer_invited) $customer_invited = 0;
			if($active == 0 && $customer_invited == 0 && $customer_invited == '') $result = 0;
		}
		
		return $result;
	}
	
	public function getEnabled()
	{
		return Mage::getStoreConfig('affiliate/config/enabled');
	}
	
	public function getEnabledStore($store_id)
	{   
		//$store_id = $order->getStoreId();
		//$store_id = Mage::app()->getStore()->getId();
		//Mage::helper('affiliate/data')->getEnabledStore($store_id);
		return Mage::getStoreConfig('affiliate/config/enabled',$store_id);
	}
	
	public function checkGroupExits($group_id)
	{
		$collectionFilter = Mage::getModel('affiliate/affiliategroup')->getCollection()
		                    	              ->addFieldToFilter('group_id',$group_id);

		return sizeof($collectionFilter);
	}
	
	public function setMemberDefaultGroupAffiliate($customer_id,$store_id=null)
    {
    	if($store_id == null) {
    		$store_id = Mage::app()->getStore()->getId();
    	}
    	$group_id = $this->getDefaultGroupAffiliateStore($store_id);
    	$check_group = $this->checkGroupExits($group_id);
    	if($check_group == 0) {
    		$group_id = 1;
    	}
    	$data = array(
    				  'customer_id'	=> $customer_id,
                      'group_id'	=> $group_id
    			);
    	
    	Mage::getModel('affiliate/affiliategroupmember')->setData($data)->save();
    }
    
	public function getTimeCookieStore($store_id)
	{
		/* return Mage::getStoreConfig('affiliate/config/affiliate_cookie',$store_id); */
		return Mage::getStoreConfig('affiliate/invitation/affiliate_cookie',$store_id);
	}
	
	public function getAffiliatePositionStore($store_id)
	{
		return Mage::getStoreConfig('affiliate/general/affiliate_position',$store_id);
	}
	
	public function getAffiliateDiscountStore($store_id)
	{
		return Mage::getStoreConfig('affiliate/general/affiliate_discount',$store_id);
	}
	
	public function getAffiliateTaxtStore($store_id)
	{
		return Mage::getStoreConfig('affiliate/general/affiliate_tax',$store_id);
	}
	
	public function getAutoApproveRegisterStore($store_id)
	{
		return Mage::getStoreConfig('affiliate/config/auto_approve',$store_id);
	}
	
	public function getDefaultGroupAffiliateStore($store_id)
	{
		return Mage::getStoreConfig('affiliate/config/default_group',$store_id);
	}
	
	public function setNewCustomerInvitedStore($store_id)
	{
		return Mage::getStoreConfig('affiliate/config/set_customerinvited',$store_id);
	}
	
	public function getAffiliateCommissionbyThemselves($store_id)
	{
		return Mage::getStoreConfig('affiliate/general/affiliate_commission',$store_id);
	}
	
	public function getStatusAddCommissionStore($store_id)
	{
		return Mage::getStoreConfig('affiliate/general/status_add_commission',$store_id);
	}
	
	public function getStatusSubtractCommissionStore($store_id)
	{
		return Mage::getStoreConfig('affiliate/general/status_subtract_commission',$store_id);
	}
	
	public function getDicountWhenRefundProductStore($store_id)
	{
		return Mage::getStoreConfig('affiliate/general/enabled_reward',$store_id);
	}
	
	public function getFeeStore($store_id)
	{
		return Mage::getStoreConfig('affiliate/money/affiliate_fee_taken',$store_id);
	}
	
	public function getWithdrawMinStore($store_id)
	{
		return Mage::getStoreConfig('affiliate/money/affiliate_withdraw_min',$store_id);
	}
	
	public function getWithdrawMaxStore($store_id)
	{
		return Mage::getStoreConfig('affiliate/money/affiliate_withdraw_max',$store_id);
	}
	
	public function getWithdrawnPeriodStore($store_id)
	{
		return Mage::getStoreConfig('affiliate/money/affiliate_withdrawn_period',$store_id);
	}
	
	public function getWithdrawnDayStore($store_id)
	{
		return Mage::getStoreConfig('affiliate/money/affiliate_withdrawn_day',$store_id);
	}
	
	public function getWithdrawnMonthStore($store_id)
	{
		return Mage::getStoreConfig('affiliate/money/affiliate_withdrawn_month',$store_id);
	}
	
	public function getLengthReferralCodeStore($store_id)
	{
		return Mage::getStoreConfig('affiliate/config/referral_code',$store_id);
	}
	
	public function getAutoSignUpAffiliateStore($store_id)
	{
		return Mage::getStoreConfig('affiliate/config/auto_signup_affiliate',$store_id);
	}
	
	public function getOverWriteRegisterStore($store_id)
	{
		return Mage::getStoreConfig('affiliate/config/overwrite_register',$store_id);
	}
	
	public function getShowReferralCodeRegisterStore($store_id)
	{
		return Mage::getStoreConfig('affiliate/config/show_referral_code_register',$store_id);
	}
	
	public function getShowSignUpFormAffiliateRegisterStore($store_id)
	{
		return Mage::getStoreConfig('affiliate/config/signup_affiliate',$store_id);
	}
	
	public function getShowReferralCodeCartStore($store_id)
	{
		return Mage::getStoreConfig('affiliate/config/show_referral_code_cart',$store_id);
	}
	
	public function getAffiliateShareStore($store_id)
	{
		return Mage::getStoreConfig('affiliate/config/affiliate_share',$store_id);
	}
	
	public function getGatewayStore()
	{
		return Mage::getStoreConfig('affiliate/money/gateway');
	}

	public function getLink(Mage_Customer_Model_Customer $customer)
	{   
		$Url = Mage::getBaseUrl();
		return trim($Url)."?mw_aref=".md5($customer->getEmail());
	}
	
	public function getLinkBanner(Mage_Customer_Model_Customer $customer,$link_banner)
	{
		return trim($link_banner)."?mw_aref=".md5($customer->getEmail());
	}
	
	public function getAffiliateLink()
	{
		return Mage::getUrl('customer/account');
	}
	
	public function getInvitationHistory()
	{
		$customer_id = (int)Mage::getSingleton("customer/session")->getCustomer()->getId();
		$collectionFilter = Mage::getModel('affiliate/affiliateinvitation')->getCollection()
                    			->addFieldToFilter('customer_id',$customer_id);
		return sizeof($collectionFilter);
	}
	
	public function getSizeAffiliateHistory()
	{
		$customer_id = (int)Mage::getSingleton("customer/session")->getCustomer()->getId();
		$collectionFilter = Mage::getModel('affiliate/affiliatehistory')->getCollection()
                    			->addFieldToFilter('customer_invited',$customer_id);
		return sizeof($collectionFilter);
	}
	
	public function getSizeWithdrawnHistory()
	{
		$customer_id = (int)Mage::getSingleton("customer/session")->getCustomer()->getId();
		$collectionFilter = Mage::getModel('affiliate/affiliatewithdrawn')->getCollection()
                    			->addFieldToFilter('customer_id',$customer_id);
		return sizeof($collectionFilter);
	}
	
	public function getProductName($product_id)
	{   
		return Mage::getModel('catalog/product')->load($product_id)->getName();
	}
	
	public function getProgramName($program_id)
	{   
		return Mage::getModel('affiliate/affiliateprogram')->load($program_id)->getProgramName();
	}
	
	public function getLabelPaymentGateway($payment_gateway)
	{   
		$label = '';
		$gateways = unserialize(Mage::helper('affiliate/data')->getGatewayStore());
		foreach ($gateways as $gateway) 
		{
			if($payment_gateway == $gateway['gateway_value']) $label = $gateway['gateway_title']; 
		}
		return $label;
	}
    
    public function getFeePaymentGateway($payment_gateway,$store_id){
        $gateways = unserialize(Mage::getStoreConfig('affiliate/money/gateway',$store_id));
        foreach($gateways as $gateway){
            if($payment_gateway == $gateway['gateway_value']){
                return $gateway['gateway_fee'];
            }
        }
    }
	
	public function getPaymentLevel()
	{   
		$result = array();
		$payment_levels = Mage::getStoreConfig('affiliate/config/affiliate_withdrawn_level');
		$payment_level= explode(',',$payment_levels);
		foreach ($payment_level as $_payment_level) {
			$result[] = array('value'=>$_payment_level, 'label'=>Mage::helper('affiliate')->__($_payment_level));
		}
		return $result;
	}
	
	public function getLinkCustomer($customer_id,$detail)
	{   
		$url = "adminhtml/customer/edit";
		$result='';
		$result = Mage::helper('affiliate')->__("<b><a href=\"%s\">%s</a></b>",Mage::helper('adminhtml')->getUrl($url,array('id'=>$customer_id)),$detail);
		return $result;
	}
	
	public function getWithdrawnPeriod()
	{     
		  $period='';
		  $store_id = Mage::app()->getStore()->getId();
		  $withdrawn_period = (int)Mage::helper('affiliate/data')->getWithdrawnPeriodStore($store_id);
	      if($withdrawn_period == 1)
	      {
	      	$withdrawn_days = (int)Mage::helper('affiliate/data')->getWithdrawnDayStore($store_id);
	      	$days = Mage::getModel('affiliate/days')->getLabel($withdrawn_days);
	      	$period = $this->__('Weekly, on %s',$days);
	      }
	      else if($withdrawn_period == 2)
	      {
	      	$withdrawn_month = (int)Mage::helper('affiliate/data')->getWithdrawnMonthStore($store_id);
	      	$period = $this->__('Monthly, Date %s',$withdrawn_month);
	      }
	      return $period;
	}
	
	/* Get all programs which customer is joining */
	public function getMemberProgram($customer_id) 
	{
		$program_ids = array();
		$customer_groups = Mage::getModel('affiliate/affiliategroupmember')->getCollection()
						   ->addFieldToFilter('customer_id',$customer_id);
		if(sizeof($customer_groups) >0)
		{
			$group_id = 0;
			foreach ($customer_groups as $customer_group) {
				$group_id = $customer_group ->getGroupId();
				break;
			}
			$customer_programs = Mage::getModel('affiliate/affiliategroupprogram')->getCollection()
								 ->addFieldToFilter('group_id',$group_id);
			foreach ($customer_programs as $customer_program) {
				$program_ids[] = $customer_program->getProgramId();
			}
		}
		$programs = Mage::getModel('affiliate/affiliateprogram')->getCollection()
				   ->addFieldtoFilter('program_id',array('in' => $program_ids))
				   ->addFieldtoFilter('status',MW_Affiliate_Model_Statusprogram::ENABLED);
		
		return $programs;
	}
	
	/* Get number of programs which customer is joining */
	public function getSizeMemberProgram()
	{
		$customer_id = (int)Mage::getSingleton("customer/session")->getCustomer()->getId();
		return sizeof($this->getMemberProgram($customer_id));
	}
	
	protected function _getSession()
    {
    	return Mage::getSingleton('core/session');
    }
    
	public function updateAffiliateInvition($customer_id, $cokie, $clientIP)
	{
		if($cokie!= 0 )
    	{
    		$email = Mage::getModel('customer/customer')->load($customer_id)->getEmail();
    		$collection = Mage::getModel('affiliate/affiliateinvitation')->getCollection()
							    				  ->addFieldToFilter('customer_id',$cokie)
						                    	  ->addFieldToFilter('email',$email);
		    // neu ban be dc moi dang ky lam thanh vien cua website ?
		    // voi email trung voi email moi se update lai trang thai 
		    if(sizeof($collection)>0)
		    {
		    	foreach ($collection as $obj) 
		    	{
		    		$obj->setStatus(MW_Affiliate_Model_Statusinvitation::REGISTER);
		    		$obj->setIp($clientIP);
		    		$obj->setInvitationTime(now());
		    		$obj->save();
		    	}
		    }
		    // nguoc lai luu moi vao csdl
		    else
		    {
		    	$historyData = array('customer_id'=>$cokie,
	                        		 'email'=>$email, 
	                        		 'status'=>MW_Affiliate_Model_Statusinvitation::REGISTER, 
	                        		 'ip'=>$clientIP,
	                        		 'invitation_time'=>now());
                Mage::getModel('affiliate/affiliateinvitation')->setData($historyData)->save();
		    }	
        }
	}
	
	public function updateAffiliateInvitionNew($customer_id, $cokie, $clientIP,$referral_from,$referral_from_domain,$referral_to,$invitation_type, $isSubscribed = null)
	{
		if($cokie!= 0 )
    	{
    		if($invitation_type == MW_Affiliate_Model_Typeinvitation::REFERRAL_CODE) {
    			$referral_from = '';
    			$referral_to = '';
    			$referral_from_domain = '';
    		}
    		$email = Mage::getModel('customer/customer')->load($customer_id)->getEmail();
    		$collection = Mage::getModel('affiliate/affiliateinvitation')->getCollection()
							    				  ->addFieldToFilter('customer_id', $cokie)
						                    	  ->addFieldToFilter('email', $email);
    		
		    // neu ban be dc moi dang ky lam thanh vien cua website ?
		    // voi email trung voi email moi se update lai trang thai 
		    if(sizeof($collection)>0)
		    {
		    	foreach ($collection as $obj) 
		    	{
		    		$obj->setStatus(MW_Affiliate_Model_Statusinvitation::REGISTER);
		    		$obj->setIp($clientIP);
		    		$obj->setInvitationTime(now());
		    		$obj->setCountClickLink(0);
		    		$obj->setCountRegister(1);
		    		$obj->setCountPurChase(0);
		    		$obj->setCountSubscribe(0);
		    		$obj->setReferralFrom($referral_from);
		    		$obj->setReferralFromDomain($referral_from_domain);
		    		$obj->setReferralTo($referral_to);
		    		$obj->setOrderId('');
		    		$obj->setInvitationType($invitation_type);
		    		$obj->save();
		    	}
		    }
		    else
		    {
		    	/*---------------------- These code right below is used for Affiliate v4.0----------------------*/
		    	
		    	/* Add commission in case of visitor sign-up */
		    	$store_id = Mage::app()->getStore()->getId();
		    	$referralSignupCommission = Mage::helper('affiliate')->getReferralSignupCommission($store_id);
		    	if($referralSignupCommission > 0)
		    	{
					$this->saveAffiliateTransactionReferral($customer_id,$referralSignupCommission,$cokie,$invitation_type,MW_Credit_Model_Transactiontype::REFERRAL_SIGNUP);
									
			    	$historyData = array(
			    						 'customer_id'			=> $cokie,
		                        		 'email'				=> $email, 
		                        		 'status'				=> MW_Affiliate_Model_Statusinvitation::REGISTER, 
		                        		 'ip'					=> $clientIP,
			    						 'count_click_link'		=> 0,
	                        			 'count_register'		=> 1, 
	                        			 'count_purchase'		=> 0,
										 'count_subscribe'		=> 0,		    						
	                                 	 'referral_from'		=> $referral_from, 
			    						 'referral_from_domain'	=> $referral_from_domain,
	                        			 'referral_to'			=> $referral_to,
	                        			 'order_id'				=> '',
			    	                     'invitation_type'		=> $invitation_type,
		                        		 'invitation_time'		=> now(),
			    						 'commission'			=> $referralSignupCommission
			    					);
	                Mage::getModel('affiliate/affiliateinvitation')->setData($historyData)->save();
	                
	                // Update total_commission in affiliate_customers
	                $affiliateCustomer = Mage::getModel('affiliate/affiliatecustomers')->load($cokie);
	                $currentCommission = $affiliateCustomer->getTotalCommission();
	                $affiliateCustomer->setTotalCommission($currentCommission + $referralSignupCommission);
	                $affiliateCustomer->save();
	                
	                // Update customer credit
	                $customerCredit = Mage::getModel('credit/creditcustomer')->load($cokie);
	                $currentCredit = $customerCredit->getCredit();
	                $newCredit = $currentCredit + $referralSignupCommission;
	                $customerCredit->setCredit($newCredit)->save();
	                
	                //Update credit history table
	                $creditHistoryData = array(
	                		'customer_id'			=> $cokie,
	                		'type_transaction'		=> MW_Credit_Model_Transactiontype::REFERRAL_SIGNUP,
	                		'status'				=> MW_Credit_Model_Orderstatus::COMPLETE,
	                		'transaction_detail'	=> $customer_id,
	                		'amount'				=> $referralSignupCommission,
	                		'beginning_transaction'	=> $currentCredit,
	                		'end_transaction'		=> $newCredit,
	                		'created_time'			=> now()
	                );
	                Mage::getModel("credit/credithistory")->setData($creditHistoryData)->save();
		    	}
                /* If visitor subscribe then add commission in case of subscription */
                $referralSubscribeCommission = Mage::helper('affiliate')->getReferralSubscribeCommission($store_id);
                if($isSubscribed && $referralSubscribeCommission >0) {
                	
                	if($referral_from_domain == '') $referral_from_domain[0] ='';
                	
                	$subscribeHistoryData = array(
                			'customer_id'			=> $cokie,
                			'email'					=> $email,
                			'status'				=> MW_Affiliate_Model_Statusinvitation::SUBSCRIBE,
                			'ip'					=> $clientIP,
                			'count_click_link'		=> 0,
                			'count_register'		=> 0,
                			'count_purchase'		=> 0,
                			'count_subscribe'		=> 1,
                			'referral_from'			=> $referral_from,
                			'referral_from_domain'	=> $referral_from_domain[0],
                			'referral_to'			=> $referral_to,
                			'order_id'				=> '',
                			'invitation_type'		=> $invitation_type,
                			'invitation_time'		=> now(),
                			'commission'			=> $referralSubscribeCommission
                	);
                	Mage::getModel('affiliate/affiliateinvitation')->setData($subscribeHistoryData)->save();
                	           
					$this->saveAffiliateTransactionReferral($customer_id,$referralSubscribeCommission,$cokie,$invitation_type,MW_Credit_Model_Transactiontype::REFERRAL_SUBSCRIBE);
                	
                	// Update total_commission in affiliate_customers
                	$affiliateCustomer = Mage::getModel('affiliate/affiliatecustomers')->load($cokie);
                	$currentCommission = $affiliateCustomer->getTotalCommission();
                	$affiliateCustomer->setTotalCommission($currentCommission + $referralSubscribeCommission);
                	$affiliateCustomer->save();
                
                	// Update customer credit
                	$customerCredit = Mage::getModel('credit/creditcustomer')->load($cokie);
                	$currentCredit = $customerCredit->getCredit();
                	$newCredit = $currentCredit + $referralSubscribeCommission;
                	$customerCredit->setCredit($newCredit)->save();
                
                	//Update credit history table
                	$creditHistoryData = array(
                			'customer_id'			=> $cokie,
                			'type_transaction'		=> MW_Credit_Model_Transactiontype::REFERRAL_SUBSCRIBE,
                			'status'				=> MW_Credit_Model_Orderstatus::COMPLETE,
                			'transaction_detail'	=> $customer_id,
                			'amount'				=> $referralSubscribeCommission,
                			'beginning_transaction'	=> $currentCredit,
                			'end_transaction'		=> $newCredit,
                			'created_time'			=> now()
                	);
                	Mage::getModel("credit/credithistory")->setData($creditHistoryData)->save();
                }
                /*------------------------------- End upgrade for Affiliate v4.0--------------------------------*/
                
                
		    }	
        }
	}
	public function saveAffiliateTransactionReferral($customer_id,$commission,$cokie,$invitation_type,$commission_type)
	{
		
		$transactionData = array(
								 'order_id'				=> '',
	    	                     'customer_id'          => $customer_id,
              					 'total_commission'		=> $commission,
    							 'total_discount'		=> 0,
								 'show_customer_invited'=> $cokie,
    							 'customer_invited'		=> 0,
		                         'invitation_type'		=> $invitation_type,
		                         'commission_type'	    => $commission_type,
    							 'status'				=> MW_Affiliate_Model_Status::COMPLETE,	
              					 'transaction_time'		=> now()
							);
		$transactionDataNew = array(
								 'order_id'				=> '',
	    	                     'customer_id'          => $customer_id,
              					 'total_commission'		=> $commission,
    							 'total_discount'		=> 0,
								 'show_customer_invited'=> 0,
    							 'customer_invited'		=> $cokie,
		                         'invitation_type'		=> $invitation_type,
		                         'commission_type'	    => $commission_type,
    							 'status'				=> MW_Affiliate_Model_Status::COMPLETE,	
              					 'transaction_time'		=> now()
							);
							
		if($commission >0) {
			Mage::getModel('affiliate/affiliatetransaction')->setData($transactionDataNew)->save();
			Mage::getModel('affiliate/affiliatetransaction')->setData($transactionData)->save();
		}
		
	}
	public function processWithdrawn($status, $withdrawn_ids)
	{
        $is_complete = 0;
        $is_cancel = 0;
        foreach($withdrawn_ids as $withdrawn_id)
        {
        	$affiliatedrawn = Mage::getModel('affiliate/affiliatewithdrawn')->load($withdrawn_id);
    		$withdrawn_status = $affiliatedrawn->getStatus();
    		if($withdrawn_status == MW_Affiliate_Model_Status::COMPLETE) {
    			$is_complete = 1;
    		}
    		else if($withdrawn_status == MW_Affiliate_Model_Status::CANCELED) {
    			$is_cancel = 1;
    		}
        }
        if($status == MW_Affiliate_Model_Status::COMPLETE)
        {   
        	if(($is_complete == 0) && ($is_cancel == 0))
        	{
        		foreach($withdrawn_ids as $withdrawn_id)
        		{
        			$affiliatedrawn = Mage::getModel('affiliate/affiliatewithdrawn')->load($withdrawn_id);
    				$withdrawn_amount = $affiliatedrawn->getWithdrawnAmount();
    				$customer_Id = $affiliatedrawn->getCustomerId();
    				
    				/* Handle Paypal Withdrawn by Paypal Masspay */
    				if($affiliatedrawn->getPaymentGateway() == 'paypal') 
    				{
    					$customer = Mage::getModel('customer/customer')->load($customer_Id);
    					$paypalParams = array(
    							'amount' 		=> $affiliatedrawn->getAmountReceive(),
    							'currency'  	=> Mage::app()->getStore()->getCurrentCurrencyCode(),
    							'customer_email'=> $affiliatedrawn->getPaymentEmail(),
    							'customer_name'	=> $customer->getName(),
    					);
    				
    					//zend_debug::dump($paypalParams);die();
    					$paypalResponse = $this->withdrawnPaypal($paypalParams);
    					if($paypalResponse['status'] !== 'success') {
    						Mage::getSingleton('adminhtml/session')->addError($this->__($paypalResponse['error']));
    						continue;
    					} else {
    						$affiliatedrawn->setStatus(MW_Affiliate_Model_Status::COMPLETE)->save();
    						$affiliatedrawn->setWithdrawnTime(now())->save();
    					}
    				}
    				else 
    				{
    					$affiliatedrawn->setStatus(MW_Affiliate_Model_Status::COMPLETE)->save();
    					$affiliatedrawn->setWithdrawnTime(now())->save();
    				}
    				
        			// gui mail cho khach hang khi rut tien thanh cong
        			$this->sendMailCustomerWithdrawnComplete($customer_Id, $withdrawn_amount);
        		
        			// cap nhat lai trang thai trong bang credit history
        			$collection = Mage::getModel('credit/credithistory')
        						  ->getCollection()
			                      ->addFieldToFilter('type_transaction', MW_Credit_Model_Transactiontype::WITHDRAWN)
			                      ->addFieldToFilter('customer_id', $customer_Id)
			                      ->addFieldToFilter('transaction_detail', $withdrawn_id)
			                      ->addFieldToFilter('status', MW_Credit_Model_Orderstatus::PENDING);
                    $affiliate_customer = Mage::getModel('affiliate/affiliatecustomers')->load($customer_Id);
		            foreach($collection as $credithistory)
		             {  
		             	/*
		             	 * this update has been processed right after affiliate withdrawn
		             	$oldTotalPaid = $affiliate_customer->getTotalPaid();
		             	$amount = $credithistory->getAmount();
		             	$newTotalPaid = $oldTotalPaid - $amount;
		             	$affiliate_customer->setData('total_paid',$newTotalPaid)->save();
		             	*/
		             	
		             	$status_credit = MW_Credit_Model_Orderstatus::COMPLETE;
						$credithistory->setStatus($status_credit)->save();
		             }
        		}
        		Mage::getSingleton('adminhtml/session')->addSuccess("You have successfully updated the withdrawn(s) status");
        	}
        	else if($is_complete == 1) {
        		Mage::getSingleton('adminhtml/session')->addError("Withdrawn_id you have chosen, having status: Complete");
        	}
        	else if($is_cancel == 1) {
        		Mage::getSingleton('adminhtml/session')->addError("Withdrawn_id you have chosen, having status: Canceled");
        	}
        	
        }
        else if($status== MW_Affiliate_Model_Status::CANCELED)
        {
        	if(($is_complete == 0) && ($is_cancel == 0))
        	{
        		foreach($withdrawn_ids as $withdrawn_id)
        		{
        			$affiliatedraw = Mage::getModel('affiliate/affiliatewithdrawn')->load($withdrawn_id);
    				$withdrawn_amount = $affiliatedraw->getWithdrawnAmount();
    				$customer_Id = $affiliatedrawn->getCustomerId();
        			$affiliatedraw->setStatus(MW_Affiliate_Model_Status::CANCELED)->save();
        			$affiliatedraw->setWithdrawnTime(now())->save();
        			
        			// gui mail cho khach hang khi rut tien that bai
        			$this ->sendMailCustomerWithdrawnCancel($customer_Id, $withdrawn_amount);
		    		
        			// cap nhat lai trang thai trong bang credit history, them kieu cancel withdrawn
        			$collection = Mage::getModel('credit/credithistory')
        						  ->getCollection()
	                    		  ->addFieldToFilter('type_transaction',MW_Credit_Model_Transactiontype::WITHDRAWN)
				                  ->addFieldToFilter('customer_id',$customer_Id)
				                  ->addFieldToFilter('transaction_detail',$withdrawn_id)
				                  ->addFieldToFilter('status',MW_Credit_Model_Orderstatus::PENDING);
                    
        			$creditcustomer = Mage::getModel('credit/creditcustomer')->load($customer_Id);
                    $oldcredit = $creditcustomer->getCredit();
		            foreach($collection as $credithistory)
		            {  
		             	$amount=$credithistory->getAmount();
						$newcredit = $oldcredit - $amount;
		             	$status_credit = MW_Credit_Model_Orderstatus::CANCELED;
		             	$credithistory->setStatus($status_credit)->save();
		             	$creditcustomer->setCredit($newcredit)->save();
		             	
						// luu them vao credit history kieu cancel withdraw
			       		$historyData = array(
			       							 'customer_id'			=> $customer_Id,
						 					 'type_transaction'		=> MW_Credit_Model_Transactiontype::CANCEL_WITHDRAWN, 
						 					 'status'				=> MW_Credit_Model_Orderstatus::COMPLETE,
							     		     'transaction_detail'	=> $withdrawn_id, 
							           		 'amount'				=> -$amount, 
							         		 'beginning_transaction'=> $oldcredit,
							        		 'end_transaction'		=> $newcredit,
							           	     'created_time'			=> now()
			       						);
			   			Mage::getModel("credit/credithistory")->setData($historyData)->save();
						
		             }
        		}
        		Mage::getSingleton('adminhtml/session')->addSuccess("You have successfully updated the Withdrawn(s) status");
        	}
        	else if($is_complete == 1) {
        		Mage::getSingleton('adminhtml/session')->addError("Withdrawn_id you have chosen, having status: Complete");
        	}
        	else if($is_cancel == 1) {
        		Mage::getSingleton('adminhtml/session')->addError("Withdrawn_id you have chosen, having status: Canceled");
        	}
        }
	}
	
	public function sendMailCustomerWithdrawnCancel($customer_id, $withdrawn_amount)
	{
		$storeId = Mage::getModel('customer/customer')->load($customer_id)->getStoreId();
        $store_name = Mage::getStoreConfig('general/store_information/name', $storeId);
    	$sender = Mage::getStoreConfig('affiliate/customer/email_sender', $storeId);
    	$email = Mage::getModel('customer/customer')->load($customer_id)->getEmail();
    	$name = Mage::getModel('customer/customer')->load($customer_id)->getName();
    	$teampale = 'affiliate/customer/email_template_withdrawn_cancel';
    	$sender_name = Mage::getStoreConfig('trans_email/ident_'.$sender.'/name', $storeId);
    	$link = Mage::app()->getStore($storeId)->getUrl('affiliate');
    	$data_mail['customer_name'] = $name;
    	$data_mail['amount'] = Mage::helper('core')->currency($withdrawn_amount,true,false);
    	$data_mail['sender_name'] = $sender_name;
    	$data_mail['store_name'] = $store_name;
    	$data_mail['link'] = $link;
    	$this ->_sendEmailTransactionNew($sender,$email,$name,$teampale,$data_mail,$storeId);
	}
	
	public function sendMailCustomerWithdrawnComplete($customer_id, $withdrawn_amount)
	{
		$storeId = Mage::getModel('customer/customer')->load($customer_id)->getStoreId();
        $store_name = Mage::getStoreConfig('general/store_information/name', $storeId);
    	$sender = Mage::getStoreConfig('affiliate/customer/email_sender', $storeId);
    	$email = Mage::getModel('customer/customer')->load($customer_id)->getEmail();
    	$name = Mage::getModel('customer/customer')->load($customer_id)->getName();
    	$teampale = 'affiliate/customer/email_template_withdrawn_complete';
    	$sender_name = Mage::getStoreConfig('trans_email/ident_'.$sender.'/name', $storeId);
    	$customer_withdrawal_link = Mage::app()->getStore($storeId)->getUrl('affiliate/index/withdrawn');
    	$data_mail['customer_name'] = $name;
    	$data_mail['amount'] = Mage::helper('core')->currency($withdrawn_amount,true,false);
    	$data_mail['sender_name'] = $sender_name;
    	$data_mail['store_name'] = $store_name;
    	$data_mail['customer_withdrawal_link'] = $customer_withdrawal_link;
    	$this->_sendEmailTransactionNew($sender,$email,$name,$teampale,$data_mail,$storeId);
	}
	
	public function sendMailCustomerRequestWithdrawn($customer_id, $withdrawn_amount)
	{
		$storeId = Mage::getModel('customer/customer')->load($customer_id)->getStoreId();
   		$store_name = Mage::getStoreConfig('general/store_information/name', $storeId);
    	$sender = Mage::getStoreConfig('affiliate/customer/email_sender', $storeId);
    	$email = Mage::getModel('customer/customer')->load($customer_id)->getEmail();
    	$name = Mage::getModel('customer/customer')->load($customer_id)->getName();
    	$teampale = 'affiliate/customer/email_template_withdrawn';
    	$sender_name = Mage::getStoreConfig('trans_email/ident_'.$sender.'/name', $storeId);
    	$customer_withdrawal_link = Mage::app()->getStore($storeId)->getUrl('affiliate/index/withdrawn');
    	$data_mail['customer_name'] = $name;
    	$data_mail['amount'] = Mage::helper('core')->currency($withdrawn_amount,true,false);
    	$data_mail['sender_name'] = $sender_name;
    	$data_mail['store_name'] = $store_name;
    	$data_mail['customer_withdrawal_link'] = $customer_withdrawal_link;
    	$this ->_sendEmailTransactionNew($sender,$email,$name,$teampale,$data_mail,$storeId);
	}
	
    public function sendEmailCustomerPending($customer_id)
    {
    	$storeId = Mage::getModel('customer/customer')->load($customer_id)->getStoreId();
    	$store_name = Mage::getStoreConfig('general/store_information/name', $storeId);
    	$sender = Mage::getStoreConfig('affiliate/customer/email_sender', $storeId);
    	$email = Mage::getModel('customer/customer')->load($customer_id)->getEmail();
    	$name = Mage::getModel('customer/customer')->load($customer_id)->getName();
    	$teampale = 'affiliate/customer/email_template';
    	$sender_name = Mage::getStoreConfig('trans_email/ident_'.$sender.'/name', $storeId);
    	$link = Mage::app()->getStore($storeId)->getUrl('affiliate');
    	$data_mail['customer_name'] = $name;
    	$data_mail['sender_name'] = $sender_name;
    	$data_mail['store_name'] = $store_name;
    	$data_mail['link'] = $link;
    	$this->_sendEmailTransactionNew($sender,$email,$name,$teampale,$data_mail,$storeId);
    }
    
    public function sendMailCustomerActiveAffiliate($customer_id)
    {
    	$storeId = Mage::getModel('customer/customer')->load($customer_id)->getStoreId();
        $store_name = Mage::getStoreConfig('general/store_information/name', $storeId);
    	$sender = Mage::getStoreConfig('affiliate/customer/email_sender', $storeId);
    	$email = Mage::getModel('customer/customer')->load($customer_id)->getEmail();
    	$name = Mage::getModel('customer/customer')->load($customer_id)->getName();
    	$teampale = 'affiliate/customer/email_template_successful';
    	$sender_name = Mage::getStoreConfig('trans_email/ident_'.$sender.'/name', $storeId);
    	$customer_affiliate_link = Mage::app()->getStore($storeId)->getUrl('affiliate');
    	$data_mail['customer_name'] = $name;
    	$data_mail['sender_name'] = $sender_name;
    	$data_mail['store_name'] = $store_name;
    	$data_mail['customer_affiliate_link'] = $customer_affiliate_link;
    	$this ->_sendEmailTransactionNew($sender,$email,$name,$teampale,$data_mail,$storeId);
    }
    
    public function sendMailCustomerNotActiveAffiliate($customer_id)
    {
    	$storeId = Mage::getModel('customer/customer')->load($customer_id)->getStoreId();
	    $store_name = Mage::getStoreConfig('general/store_information/name', $storeId);
	    $sender = Mage::getStoreConfig('affiliate/customer/email_sender', $storeId);
	    $email = Mage::getModel('customer/customer')->load($customer_id)->getEmail();
	    $name = Mage::getModel('customer/customer')->load($customer_id)->getName();
	    $teampale = 'affiliate/customer/email_template_unsuccessful';
	    $sender_name = Mage::getStoreConfig('trans_email/ident_'.$sender.'/name', $storeId);
	    $link = Mage::app()->getStore($storeId)->getUrl('affiliate');
	    $data_mail['customer_name'] = $name;
	    $data_mail['sender_name'] = $sender_name;
	    $data_mail['store_name'] = $store_name;
	    $data_mail['link'] = $link;
	    $this ->_sendEmailTransactionNew($sender,$email,$name,$teampale,$data_mail,$storeId);
    }
    
    public function sendEmailAdminActiveAffiliate($customer_id) 
    {
    	$storeId = Mage::getModel('customer/customer')->load($customer_id)->getStoreId();
		$store_name = Mage::getStoreConfig('general/store_information/name', $storeId);
    	$email = Mage::getModel('customer/customer')->load($customer_id)->getEmail();
    	$name = Mage::getModel('customer/customer')->load($customer_id)->getName();
    	$validator = new Zend_Validate_EmailAddress();
    	$sender_admin = Mage::getStoreConfig('affiliate/admin_customer/email_sender', $storeId);
    	$sender_name_admin = Mage::getStoreConfig('trans_email/ident_'.$sender_admin.'/name', $storeId);
    	$teampale_admin = 'affiliate/admin_customer/email_template';
    	$email_adminss = Mage::getStoreConfig('affiliate/admin_customer/email_to');
    	$name_admin = null;
    	$data_mail_admin['customer_name'] = $name;
    	$data_mail_admin['link_admin'] = Mage::getUrl('adminhtml');
    	$data_mail_admin['sender_name_admin'] = $sender_name_admin;
    	$data_mail_admin['store_name'] = $store_name;
    	$data_mail_admin['customer_email'] = $email;
    	if(substr_count($email_adminss,',')==0)
    	{
    		if($validator->isValid($email_adminss)) 
    		{
    			$this->_sendEmailTransactionNew($sender_admin,$email_adminss,$name_admin,$teampale_admin,$data_mail_admin,$storeId);
    		}
    	}
    	else if(substr_count($email_adminss,',') > 0)
    	{
    		$email_admins = explode(",",$email_adminss);
    		foreach ($email_admins as $email_admin) 
    		{
	    		if($validator->isValid($email_admin)) 
	    		{
	    			$this->_sendEmailTransactionNew($sender_admin,$email_admin,$name_admin,$teampale_admin,$data_mail_admin,$storeId);
	    		}
    		}
    	}
    }
    
	public function _sendEmailTransaction($sender, $emailto, $name, $template, $data)
   	{   
   		$data['subject'] = 'Affiliate system !';
		$storeId = Mage::app()->getStore()->getId();  
   		$templateId = Mage::getStoreConfig($template,$storeId);
		//$customer = $this->_getSession()->getCustomer();
	  	$translate  = Mage::getSingleton('core/translate');
	  	$translate->setTranslateInline(false);
		  //$sender = Mage::getStoreConfig(self::XML_PATH_EMAIL_IDENTITY, $storeId);
		 // if(Mage::getStoreConfig('affiliate/invitation/using_customer_email'))
		  //	$sender = array('name'=>$customer->getName(),'email'=>$customer->getEmail());
	  	try {
			Mage::getModel('core/email_template')
		    	->sendTransactional(
		      		  $templateId, 
				      $sender, 
				      $emailto, 
				      $name, 
				      $data, 
				      $storeId
		    	);
			$translate->setTranslateInline(true);
	  	} catch(Exception $e) {
	  		$this->_getSession()->addError($this->__("Email can not send !"));
	  	}
   	}
   	
	public function _sendEmailTransactionNew($sender, $emailto, $name, $template, $data, $storeId)
   	{   
   		$data['subject'] = 'Affiliate system !';
   		$templateId = Mage::getStoreConfig($template,$storeId);
	  	$translate  = Mage::getSingleton('core/translate');
	  	$translate->setTranslateInline(false);
		try{
	    	Mage::getModel('core/email_template')
			      ->sendTransactional(
			      		$templateId, 
			      		$sender, 
			      		$emailto, 
			      		$name, 
			      		$data, 
			      		$storeId
			     );
			$translate->setTranslateInline(true);
		} catch(Exception $e) {
		  		$this->_getSession()->addError($this->__("Email can not send !"));
	  	}
   	}
	
	public function myConfig()
    {
    	return self::MYCONFIG;
    }
	
	function disableConfig()
	{
		Mage::getSingleton('core/config')->saveConfig(Mage::helper('affiliate')->myConfig(),0);
		$websites  = Mage::getModel('core/website')->getCollection()->getData();
    	foreach($websites as $row)
    	{
    		if($row['code']!="admin")
    		Mage::getSingleton('core/config')->saveConfig(Mage::helper('affiliate')->myConfig(),0,'websites',$row['website_id']);
    	}   	  
    		
    	$stores  = Mage::getModel('core/store')->getCollection()->getData();
    	foreach($stores as $row)
    	{
    		if($row['code']!="admin") {
    			Mage::getSingleton('core/config')->saveConfig(Mage::helper('affiliate')->myConfig(),0,'stores',$row['store_id']);
    		}
    	}
    	Mage::getSingleton('core/config')->saveConfig(Mage::helper('affiliate')->myConfig(),0);
	}
	
	function enableConfig()
	{
		Mage::getSingleton('core/config')->saveConfig(Mage::helper('affiliate')->myConfig(),1);
		$websites  = Mage::getModel('core/website')->getCollection()->getData();
    	foreach($websites as $row)
    	{
    		if($row['code']!="admin") {
    			Mage::getSingleton('core/config')->saveConfig(Mage::helper('affiliate')->myConfig(),1,'websites',$row['website_id']);
    		}   
    	}	  
    		
    	$stores  = Mage::getModel('core/store')->getCollection()->getData();
    	foreach($stores as $row)
    	{
    		if($row['code']!="admin") {
    			Mage::getSingleton('core/config')->saveConfig(Mage::helper('affiliate')->myConfig(),1,'stores',$row['store_id']);
    		}
    	}
    	Mage::getSingleton('core/config')->saveConfig(Mage::helper('affiliate')->myConfig(),1);
	}
	

/*--------------------------------- NEW HELPER METHODS FOR AFFILIATE PRO v4.0 ---------------------------------------*/
/*-------------------------------------------------------------------------------------------------------------------*/
	
	/* Return commission if there is enough visitors (to get commission), otherwise return 0 */
	public function calculateReferralVisitorCommission($customerId, $pivot) 
	{
		/* Get the set of referral link click which started from pivot */ 
		$collection = Mage::getModel('affiliate/affiliateinvitation')->getCollection();
		$collection->addFieldToFilter('customer_id', array('eq' => $customerId));
		$collection->addFieldToFilter('count_click_link', array('eq' => '1'));
		$collection->addFieldToFilter('invitation_id', array('gt' => $pivot));
		
		/* Get current referral visitor number (to get commission) from config */ 
		$configValue =  Mage::getStoreConfig('affiliate/general/referral_visitor_commission');
		$configComponents = explode('/', $configValue);
		$commission = doubleval($configComponents[0]);
		$visitorNo  = intval($configComponents[1]);
		
		/* Plus 1 because we must include the new visit */
		if(sizeof($collection)+1 == $visitorNo) {
			return $commission;
		} else {
			return 0;
		}
	}
	
	public function getReferralVisitorCommission($storeId) 
	{
		$configValue =  Mage::getStoreConfig('affiliate/general/referral_visitor_commission', $storeId);
		$configComponents = explode('/', $configValue);
		
		$commission = doubleval($configComponents[0]);
		$visitorNo  = intval($configComponents[1]);
		
		return round($commission/$visitorNo, 2);
	}
	
	public function getReferralVisitorNumber($storeId) {
		$configValue =  Mage::getStoreConfig('affiliate/general/referral_visitor_commission', $storeId);
		$configComponents = explode('/', $configValue);
		
		return intval($configComponents[1]);
	}
	
	public function getReferralSignupCommission($storeId) {
		return Mage::getStoreConfig('affiliate/general/referral_signup_commission', $storeId);
	}

	public function getReferralSubscribeCommission($storeId) {
		return Mage::getStoreConfig('affiliate/general/referral_subscribe_commission', $storeId);
	}

	public function getWebsiteVerificationKey($url) 
	{
		$urlParsed = parse_url(Mage::helper('core/url')->getCurrentUrl());
		
		foreach($urlParsed as $key => $value) {
			if($key == 'host') {
				$hostName = $value;
			}
		}
		return '<meta name="' . $hostName . '-site-verification" content="' . md5($url) . '" />';
	}
	
	public function validateDomainUrl($url) {
		$pattern = '/^(http|https|ftp):\/\/([A-Z0-9][A-Z0-9_-]*(?:\.[A-Z0-9][A-Z0-9_-]*)+):?(\d+)?\/?/i';
		return preg_match($pattern, $url);
	}
	
	public function isDirectReferral($url) 
	{
		$urlComponents = parse_url($url);
		
		if(isset($urlComponents['scheme']) && $urlComponents['host']){
			$domain = $urlComponents['scheme'] . '://' . $urlComponents['host'];
		
			$collection = Mage::getModel('affiliate/affiliatewebsitemember')->getCollection()
						->addFieldToFilter('status', array('eq' => 1))
						->addFieldToFilter('domain_name', array('like' => '%'.$domain.'%'));
			
			if(sizeof($collection) > 0) {
				foreach($collection as $item) {
					$customerId = $item->getCustomerId();
				}
				$customer = Mage::getModel('customer/customer')->load($customerId);
				
				// Need return encrypted email => To use clickReferralLink() event
				return md5($customer->getEmail());
			} 
		}
		
		
		return false;
	}
	
	public function getBackendCustomerNameByEmail($customerEmail) 
	{
		return  Mage::getModel('customer/customer')
				->getCollection()
				->addFieldToFilter('email', array('eq' => $customerEmail))
				->addNameToSelect()
				->getFirstItem()
				->getName();
	}
	
	public function withdrawnPaypal($params) 
	{
		include_once 'lib/mw_affiliate/api/paypal/PaypalCallerService.php';
		
		$credentials['API_USERNAME']  = Mage::getStoreConfig('affiliate/paypal_credential/api_username');
		$credentials['API_PASSWORD']  = Mage::getStoreConfig('affiliate/paypal_credential/api_password');
		$credentials['API_SIGNATURE'] = Mage::getStoreConfig('affiliate/paypal_credential/api_signature');
		$credentials['API_ENDPOINT']  = (Mage::getStoreConfig('affiliate/paypal_credential/api_endpoint') > 0)
										? 'https://api-3t.paypal.com/nvp'	
										: 'https://api-3t.sandbox.paypal.com/nvp';

		$id         	= urlencode(time());
		$note      		= urlencode(Mage::helper('affiliate')->__(Mage::getStoreConfig('affiliate/paypal_credential/paypal_notification_note')));
		$subject  		= urlencode(Mage::helper('affiliate')->__(Mage::getStoreConfig('affiliate/paypal_credential/paypal_notification_subject')));
		$type      		= urlencode(Mage::helper('affiliate')->__('EmailAddress'));
		
		$amount  		= urlencode($params['amount']);
		$currency 		= urlencode($params['currency']);
		$customer_email = urlencode($params['customer_email']);
		 
		$base_call  = "&L_EMAIL0=".$customer_email.
				      "&L_AMT0=".$amount.
					  "&L_UNIQUEID0=".$id.
					  "&L_NOTE0=".$note.
				      "&EMAILSUBJECT=".$subject.
					  "&RECEIVERTYPE=".$type.
					  "&CURRENCYCODE=".$currency;
		 
		$PayPal = new PayPal_CallerService($credentials);
		 
		$status = $PayPal->callPayPal("MassPay", $base_call);
		if($status) {
			if($status['ACK'] == "Success") {
				$minimumBalance = doubleval(Mage::getStoreConfig('affiliate/paypal_credential/paypal_min_balance'));
				$notificationEmail = Mage::getStoreConfig('affiliate/paypal_credential/paypal_email_notification');

				/* If the balance (after debited) is lower than minimum balance (defined by admin) then sending email to notify */
				$balance = $PayPal->callPayPal("GetBalance", '');
				if($balance['L_AMT0'] < $minimumBalance) {
					$message = "The balance is low... Please replenish the funds... \n\n".$balance['L_AMT0']." ".$balance['L_CURRENCYCODE0'];
					mail($notificationEmail, 'PayPal Refund Balance Low',$message);
				} 
				return array('status' => 'success');
				
			} else if($status['ACK'] == "Failure"){
				 
				if($status['L_ERRORCODE0'] == '10321') {
					$error = "Insufficient Funds to Send Refund to: " . $params['customer_name'] . '('.$params['customer_email'].') via PayPal in the amount of: '.$params['amount'].' '.$params['currency'].'. ';
					 
				} else if($status['L_ERRORCODE0'] == '10004') {
					$error = "Invalid Amount of Refund to: ". $params['customer_name'] .'('.$params['customer_email'].') via PayPal in the amount of: '.$params['amount'].' '.$params['currency'].'. Must be more than 0 '.$params['currency'].'.';
					 
				} else {
					$error = "There was an unknown error when attempting to submit the payment.";
				}
				return array(
							'status' => 'failure',
							'error'	 => $error
						);
			}
		}
		return array(
					'status' => 'undefined',
					'error'	=> 'There was an unknown error when attempting to submit the payment.'
			   );
	}
	
}