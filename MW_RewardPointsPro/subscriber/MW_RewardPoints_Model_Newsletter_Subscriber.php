<?php

class MW_RewardPoints_Model_Newsletter_Subscriber extends Mage_Core_Model_Abstract
{
    public function newletterSaveBefore($argv)
    {
    	$subscriber = $argv->getSubscriber();
    	if($subscriber->getCustomerId()){
    		$customer_group_id = Mage::getModel('customer/customer')->load($subscriber->getCustomerId())->getGroupId();
    		$store_id = Mage::app()->getStore()->getId();
    		$type_of_transaction = MW_RewardPoints_Model_Type::SIGNING_UP_NEWLETTER;
	        //$rewardpoints = (double)Mage::getModel('rewardpoints/activerules')->getPointActiveRules($type_of_transaction,$customer_group_id,$store_id);
    		$rewardpoints = (int)Mage::helper('rewardpoints')->getPointNewletterSignUpConfigStore($store_id);
    		$friend_id = Mage::getModel('core/cookie')->get('friend') ? Mage::getModel('core/cookie')->get('friend') : 0;
    		Mage::helper('rewardpoints/data')->checkAndInsertCustomerId($subscriber->getCustomerId(), $friend_id);	
    		$_customer = Mage::getModel('rewardpoints/customer')->load($subscriber->getCustomerId());
    		
	    	if($subscriber->getId())
	    	{
	    		$old_subscriber = Mage::getModel('newsletter/subscriber')->load($subscriber->getId());
	    		if(($old_subscriber->getStatus() == Mage_Newsletter_Model_Subscriber::STATUS_NOT_ACTIVE) && ($subscriber->getStatus() == Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED))
	    		{
					if($rewardpoints){
						$_customer->addRewardPoint($rewardpoints);
						$historyData = array('type_of_transaction'=>MW_RewardPoints_Model_Type::SIGNING_UP_NEWLETTER,
											 'amount'=>(int)$rewardpoints, 
											 'balance'=>$_customer->getMwRewardPoint(),
											 'transaction_detail'=>'', 
											 'transaction_time'=>now(),
											 'expired_day'=>0,
								    		 'expired_time'=>null,
								    		 'point_remaining'=>0, 
											 'status'=>MW_RewardPoints_Model_Status::COMPLETE);
						$_customer->saveTransactionHistory($historyData);
						
						// send mail when points changed
						Mage::helper('rewardpoints')->sendEmailCustomerPointChanged($_customer->getId(),$historyData, $store_id);
					}
	    		}
	    	}else
	    	{
	    		if(($subscriber->getStatus() == Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED))
	    		{
					if($rewardpoints){
						$_customer->addRewardPoint($rewardpoints);
						$historyData = array('type_of_transaction'=>MW_RewardPoints_Model_Type::SIGNING_UP_NEWLETTER, 
											 'amount'=>(int)$rewardpoints, 
											 'balance'=>$_customer->getMwRewardPoint(), 
											 'transaction_detail'=>'', 
											 'transaction_time'=>now(), 
											 'expired_day'=>0,
								    		 'expired_time'=>null,
								    		 'point_remaining'=>0,
											 'status'=>MW_RewardPoints_Model_Status::COMPLETE);
						$_customer->saveTransactionHistory($historyData);
						
						// send mail when points changed
						Mage::helper('rewardpoints')->sendEmailCustomerPointChanged($_customer->getId(),$historyData, $store_id);
					}
	    		}
	    	}
    	}
    }
}
