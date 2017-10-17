<?php
class Mage_Core_Helper_Mwrewardpoints extends Mage_Core_Helper_Abstract
{
	public function formRewardShoppingCartRewardPoints()
	{
		if(Mage::helper('core')->isModuleEnabled('MW_RewardPoints'))  
			return Mage::helper('rewardpoints/data')->getRewardpointCartTemplate();
		else return '';
	}
	public function earnPointsOnepageReviewRewardPoints()
	{
		 if(Mage::helper('core')->isModuleEnabled('MW_RewardPoints')) 
		 	return Mage::helper('rewardpoints/data')->getRewardpointOnepageReviewTemplate();
		 else return '';
	}
	public function totalPointsRedeemOnepageReviewRewardPoints()
	{
		 if(Mage::helper('core')->isModuleEnabled('MW_RewardPoints')) 
		 	return Mage::helper('rewardpoints/data')->getTotalSpentPointOnepageReviewTemplate();
		 else return '';
	}
	public function pointsCreateAccountRewardPoints()
	{
		if(Mage::helper('core')->isModuleEnabled('MW_RewardPoints')) 
			return Mage::helper('rewardpoints/data')->getDisplayEarnpointCreateAccount();
		return '';
	}
	public function pointsProductReviewRewardPoints()
	{
		if(Mage::helper('core')->isModuleEnabled('MW_RewardPoints'))  
			return Mage::helper('rewardpoints/data')->getDisplayEarnpointSubmitProductReview();
		return '';
	}
	public function pointsSubmitPollRewardPoints()
	{
		if(Mage::helper('core')->isModuleEnabled('MW_RewardPoints'))  
			return Mage::helper('rewardpoints/data')->getDisplayEarnpointSubmitPoll();
		return '';
	}
	public function pointsSignUpNewLetterRewardPoints()
	{
		if(Mage::helper('core')->isModuleEnabled('MW_RewardPoints'))  
			return Mage::helper('rewardpoints/data')->getDisplayEarnpointSignUpNewLetter();
		return '';
	}
	public function pointsProductListRewardPoints($_product)
	{
		if(Mage::helper('core')->isModuleEnabled('MW_RewardPoints'))  
			return Mage::helper('rewardpoints/data')->getDisplayEarnpointListProduct($_product);
		return '';
	}
	public function pointsProductDetailRewardPoints($_product)
	{
		if(Mage::helper('core')->isModuleEnabled('MW_RewardPoints'))  
			return Mage::helper('rewardpoints/data')->getDisplayEarnpointViewProduct($_product);
		return '';
	}
	public function pointsTopLinkRewardPoints($_link)
	{
		if(Mage::helper('core')->isModuleEnabled('MW_RewardPoints'))  
			return Mage::helper('rewardpoints/data')->getPointCustomerShowTop($_link);
		return '';
	}
	public function pointsMyAccountRewardPoints($_link)
	{
		if(Mage::helper('core')->isModuleEnabled('MW_RewardPoints'))  
			return Mage::helper('rewardpoints/data')->getPointCustomerShowMyAccount($_link);
		return '';
	}
	public function showFacebookRewardPoints()
	{
		if(Mage::helper('core')->isModuleEnabled('MW_RewardPoints'))  
			return Mage::helper('rewardpoints/data')->getDisplayFacebookLike();
		return '';
	}
	public function pointsMiniCartRewardPoints()
	{
		if(Mage::helper('core')->isModuleEnabled('MW_RewardPoints'))  
			return Mage::helper('rewardpoints/data')->getDisplayEarnpointMiniCart();
		return '';
	}
	public function showInvitationRewardPoints()
	{
		if(Mage::helper('core')->isModuleEnabled('MW_RewardPoints'))  
			return Mage::helper('rewardpoints/data')->getInvitationTemplate();
		return '';
	}
	public function formRewardOnepageCheckoutRewardPoints()
	{
		if(Mage::helper('core')->isModuleEnabled('MW_RewardPoints'))  
			return '<div  id="mw-checkout-payment-rewardpoints">'.Mage::helper('rewardpoints/data')->getRewardpointOnepageTemplate().'</div>';
		else return '';
	}
}