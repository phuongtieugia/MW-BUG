<?php
class MW_RewardPoints_Model_Checkout extends Mage_Core_Model_Abstract
{
    /* public function placeAfter($argv)
    {
        if(Mage::helper('rewardpoints')->moduleEnabled())
        {
            $order  = $argv->getOrder();
            $orders = $argv->getOrders();
            $quote  = $argv->getQuote();
            if(empty($order))
            {
               
                if(count($orders) > 0)
                {
                    foreach ($orders as $order)
                    {
                        $this->perOrder($quote, $order, true);
                    }
                    Mage::getSingleton('core/session')->unsQuoteAddressRewardpoint();
                }
            }
            else
            {
                $this->perOrder($quote, $order);
            }
            Mage::getSingleton('core/session')->unsMwRewardpointAfterDrop();
        }
    } */
	public function placeAfter($argv)
{
	if(Mage::helper('rewardpoints')->moduleEnabled())
	{
		$order = $argv->getOrder();
		//$quote = Mage::getSingleton('checkout/session')->getQuote();
		
		//$quote = $order->getQuote();
		$quote = $argv->getQuote();
        if (!$quote instanceof Mage_Sales_Model_Quote) {
            $quote = Mage::getModel('sales/quote')
                    ->setSharedStoreIds(array($order->getStoreId()))
                    ->load($order->getQuoteId());
        }
        
	 	if($quote->getCheckoutMethod(true) == Mage_Sales_Model_Quote::CHECKOUT_METHOD_REGISTER){
			Mage::getModel('rewardpoints/customer')->customerSaveAfterRegister($order);
		}
		
		
		$customer = Mage::getModel('customer/customer')->load($order->getCustomerId());
		$store_id = Mage::app()->getStore()->getId();
    	if($customer->getId()){
			
			/* Mage::log("mw_rewardpoint = ".$quote->getMwRewardpoint(),null,"ptg.log");
			Mage::log("mw_rewardpoint_discount = ".$quote->getMwRewardpointDiscount(),null,"ptg.log"); */

    		
    		Mage::helper('rewardpoints/data')->checkAndInsertCustomerId($customer->getId(), 0);	
			$_customer = Mage::getModel('rewardpoints/customer')->load($customer->getId());
			$earn_rewardpoint = (int)$quote->getEarnRewardpoint();
			$rewardpoints = (int)$quote->getMwRewardpoint();
			
			if($rewardpoints > 0){
				$mwRewardpointDiscount = $quote->getMwRewardpointDiscount();
                $totalDiscountAmount = Mage::helper('core')->currency($mwRewardpointDiscount, false, false);
				$order->setMwRewardpoint($rewardpoints);
				$order->setMwRewardpointDiscount($mwRewardpointDiscount);
				$order->setMwRewardpointDiscountShow($totalDiscountAmount);
				$order->save();
			}
			

    		//Subtract reward points of customer and save reward points to order if customer use this point to checkout
			$rewardpoints_new = $rewardpoints + (int)$quote->getMwRewardpointSellProduct();
            if($rewardpoints_new || $earn_rewardpoint)
            {
            	if($rewardpoints_new)
            	{
	            	$expired_day = (int)Mage::helper('rewardpoints/data')->getExpirationDaysPoint($store_id);
	            	//Subtract reward points of customer
	            	$_customer->addRewardPoint(-$rewardpoints_new);
	            	$historyData = array('type_of_transaction'=>MW_RewardPoints_Model_Type::USE_TO_CHECKOUT, 
						            	 'amount'=>(int)$rewardpoints_new, 
						            	 'balance'=>$_customer->getMwRewardPoint(), 
						            	 'transaction_detail'=>$order->getIncrementId(), 
						            	 'transaction_time'=>now(), 
	            						 'expired_day'=>$expired_day,
								    	 'expired_time'=>null,
								         'point_remaining'=>0,
	            	                     'history_order_id'=>$order->getId(),
						            	 'status'=>MW_RewardPoints_Model_Status::COMPLETE);
	            	
	           		$_customer->saveTransactionHistory($historyData);
	           		Mage::helper('rewardpoints')->sendEmailCustomerPointChanged($_customer->getId(),$historyData, $store_id);
	           		
	           		// process expired points when spent point
		           	Mage::helper('rewardpoints/data')->processExpiredPointsWhenSpentPoints($_customer->getId(), $rewardpoints_new);
            	}
           		//$rate = Mage::helper('core')->currency(1,false);
           		$discount = $quote->getMwRewardpointDiscount();
           		//Save reward point for order
           		$orderData =  array('order_id'=>$order->getId(),
					           		'reward_point'=>$rewardpoints, 
           							'rewardpoint_sell_product'=>(int)$quote->getMwRewardpointSellProduct(),
           							'earn_rewardpoint'=> $earn_rewardpoint,
					           		'money'=>$discount,
					           		'reward_point_money_rate'=>Mage::helper('rewardpoints')->getPointMoneyRateConfig($store_id));
           		
           		$_order = Mage::getModel('rewardpoints/rewardpointsorder');
           		$_order->saveRewardOrder($orderData);
            }
            if($earn_rewardpoint >0){
            	$detail = array();
            	$_detail_rule_result = array();
            	$_detail_rules = array();
            	$_detail_products = array();
	            $_detail_rules = unserialize($quote->getMwRewardpointDetail());
		        foreach ($_detail_rules as $key => $_detail_rule) {
		        	if($_detail_rule > 0){
		        		$rule_description = '';
			    		$rule_description =  Mage::getModel('rewardpoints/cartrules')->load($key)->getDescription();
			    		$_detail_rule_result[] = Mage::helper('rewardpoints')->__('%s | %s',$_detail_rule,$rule_description);
		        	}
		    	}
	            foreach($quote->getAllVisibleItems() as $item)
	            {
	            	$product_id = $item->getProduct()->getId();
	            	$mw_reward_point = Mage::getModel('rewardpoints/catalogrules')->getPointCatalogRulue($product_id);
	            	if($mw_reward_point > 0)
	            	{
	            		$product_name = Mage::getModel('catalog/product')->load($product_id)->getName();
	            		$_detail_products[] = Mage::helper('rewardpoints')->__('%s |%s',$mw_reward_point * $item->getQty(),$product_name);
	            	}
	            }
				$detail[1] = $_detail_rule_result;
				$detail[2] = $_detail_products;
				
				$results = Mage::helper('rewardpoints/data')->getTransactionExpiredPoints($earn_rewardpoint,$store_id);
				$expired_day = $results[0];
				$expired_time = $results[1] ;
				$point_remaining = $results[2];
	
            	$historyData = array('type_of_transaction'=>MW_RewardPoints_Model_Type::CHECKOUT_ORDER_NEW,
					           		 'amount'=>$earn_rewardpoint,
					           		 'balance'=>$_customer->getMwRewardPoint(), 
					           		 'transaction_detail'=>$order->getIncrementId()."||".serialize($detail), 
					           		 'transaction_time'=>now(),
            						 'expired_day'=>$expired_day,
									 'expired_time'=>$expired_time,
            						 'point_remaining'=>$point_remaining,
            						 'history_order_id'=>$order->getId(),
					           		 'status'=>MW_RewardPoints_Model_Status::PENDING);
           		
		        $_customer->saveTransactionHistory($historyData);
            }

            //Reward points to friend if this is first purchase
            $orders = Mage::getModel("sales/order")->getCollection()
                ->addFieldToFilter('customer_id', $customer->getId());
            if($_customer->getMwFriendId())
            {
                Mage::helper('rewardpoints/data')->checkAndInsertCustomerId($_customer->getMwFriendId(), 0);
            }
            $friend              = $_customer->getFriend();
            $customer_group_id   = Mage::getModel('customer/customer')->load($customer->getId())->getGroupId();
            $type_of_transaction = MW_RewardPoints_Model_Type::FRIEND_FIRST_PURCHASE;
            $results             = Mage::getModel('rewardpoints/activerules')->getResultActiveRulesExpiredPoints($type_of_transaction, $customer_group_id, $store_id);
            $point               = explode('/', $results[0]);
            if((sizeof($orders) == 1) && ($friend) && Mage::helper('rewardpoints')->checkCustomerMaxBalance($friend->getCustomerId(), $store_id, $point))
            {

                $expired_day     = $results[1];
                $expired_time    = $results[2];
                $point_remaining = $results[3];
                $_point          = $point[0];
                if(sizeof($point) == 2)
                {
                    $total  = $order->getBaseGrandTotal();
                    $_point = ((int)($total / $point[1])) * $point[0];
                }
                if($_point)
                {
                    $historyData = array('type_of_transaction' => MW_RewardPoints_Model_Type::FRIEND_FIRST_PURCHASE,
                                         'amount'              => (int)$_point,
                                         'balance'             => $friend->getMwRewardPoint(),
                                         'transaction_detail'  => $customer->getId() . "|" . $order->getId(),
                                         'transaction_time'    => Mage::getSingleton('core/date')->gmtDate(),
                                         'expired_day'         => $expired_day,
                                         'expired_time'        => $expired_time,
                                         'point_remaining'     => $point_remaining,
                                         'history_order_id'    => $order->getId(),
                                         'status'              => MW_RewardPoints_Model_Status::PENDING);
                    $friend->saveTransactionHistory($historyData);
                }
            }
            else if($friend && Mage::helper('rewardpoints')->checkCustomerMaxBalance($friend->getCustomerId(), $store_id, $point))
            {
                //Reward points to friend if this is next purchase
                //$point = explode('/',Mage::getStoreConfig('rewardpoints/config/reward_point_for_friend_next_purchase'));
                $customer_group_id   = Mage::getModel('customer/customer')->load($customer->getId())->getGroupId();
                $type_of_transaction = MW_RewardPoints_Model_Type::FRIEND_NEXT_PURCHASE;
                //$point = explode('/',Mage::getModel('rewardpoints/activerules')->getPointActiveRules($type_of_transaction,$customer_group_id,$store_id));
                $results         = Mage::getModel('rewardpoints/activerules')->getResultActiveRulesExpiredPoints($type_of_transaction, $customer_group_id, $store_id);
                $point           = explode('/', $results[0]);
                $expired_day     = $results[1];
                $expired_time    = $results[2];
                $point_remaining = $results[3];
                $_point          = $point[0];
                if(sizeof($point) == 2)
                {
                    $total  = $order->getBaseGrandTotal();
                    $_point = ((int)($total / $point[1])) * $point[0];
                }
                if($_point)
                {
                    $historyData = array('type_of_transaction' => MW_RewardPoints_Model_Type::FRIEND_NEXT_PURCHASE,
                                         'amount'              => (int)$_point,
                                         'balance'             => $friend->getMwRewardPoint(),
                                         'transaction_detail'  => $customer->getId() . "|" . $order->getId(),
                                         'transaction_time'    => Mage::getSingleton('core/date')->gmtDate(),
                                         'expired_day'         => $expired_day,
                                         'expired_time'        => $expired_time,
                                         'point_remaining'     => $point_remaining,
                                         'history_order_id'    => $order->getId(),
                                         'status'              => MW_RewardPoints_Model_Status::PENDING);
                    $friend->saveTransactionHistory($historyData);
                }
            }
        }
        else
        {
            $friend_id = Mage::getModel('core/cookie')->get('friend');
            $friend    = Mage::getModel('rewardpoints/customer')->load($friend_id);

            if($friend)
            {
                $type_of_transaction = MW_RewardPoints_Model_Type::FRIEND_FIRST_PURCHASE;

                $results         = Mage::getModel('rewardpoints/activerules')->getResultActiveRulesExpiredPoints($type_of_transaction, 0, $store_id);
                $point           = explode('/', $results[0]);
                $expired_day     = $results[1];
                $expired_time    = $results[2];
                $point_remaining = $results[3];
                $_point          = $point[0];
                if(sizeof($point) == 2)
                {
                    $total  = $order->getBaseGrandTotal();
                    $_point = ((int)($total / $point[1])) * $point[0];
                }

                if($_point)
                {

                    $historyData = array('type_of_transaction' => MW_RewardPoints_Model_Type::FRIEND_FIRST_PURCHASE,
                                         'amount'              => (int)$_point,
                                         'balance'             => $friend->getMwRewardPoint() + (int)$_point,
                                         'transaction_detail'  => "Guest|" . $order->getId(),
                                         'transaction_time'    => Mage::getSingleton('core/date')->gmtDate(),
                                         'expired_day'         => $expired_day,
                                         'expired_time'        => $expired_time,
                                         'point_remaining'     => $point_remaining,
                                         'history_order_id'    => $order->getId(),
                                         'status'              => MW_RewardPoints_Model_Status::COMPLETE);
                    $friend->saveTransactionHistory($historyData);
                    $friend->addRewardPoint((int)$_point);
                }
            }
    	}
	}
}

    protected function perOrder($quote, $order, $multi = false)
    {
        $address = $quote->isVirtual() ? $quote->getBillingAddress() : $quote->getShippingAddress();

        if(!$quote instanceof Mage_Sales_Model_Quote)
        {
            $quote = Mage::getModel('sales/quote')
                ->setSharedStoreIds(array($order->getStoreId()))
                ->load($order->getQuoteId());
        }

        if($quote->getCheckoutMethod(true) == Mage_Sales_Model_Quote::CHECKOUT_METHOD_REGISTER)
        {
            Mage::getModel('rewardpoints/customer')->customerSaveAfterRegister($order);
        }


        $customer = Mage::getModel('customer/customer')->load($order->getCustomerId());
        $store_id = Mage::app()->getStore()->getId();
        if($customer->getId())
        {
            Mage::helper('rewardpoints/data')->checkAndInsertCustomerId($customer->getId(), 0);
            $_customer = Mage::getModel('rewardpoints/customer')->load($customer->getId());

            if($multi)
            {
                $address_rewardpoints = Mage::getSingleton('core/session')->getQuoteAddressRewardpoint();
                $address_earnpoints   = Mage::getSingleton('core/session')->getQuoteAddressEarnpoint();
                $rewardpoints         = (int)$address_rewardpoints[$order->getShippingAddress()->getQuoteAddressId()]["rewardpoints"];
                $earn_rewardpoint     = (int)$address_earnpoints[$order->getShippingAddress()->getQuoteAddressId()]["earnpoints"];
                $discount             = $address_rewardpoints[$order->getShippingAddress()->getQuoteAddressId()]["rewardpoints_discount"];
            }
            else
            {
                $rewardpoints     = (int)$quote->getMwRewardpoint();
                $earn_rewardpoint = (int)$quote->getEarnRewardpoint();
                $discount         = $quote->getMwRewardpointDiscount();
            }

            //Subtract reward points of customer and save reward points to order if customer use this point to checkout
            $rewardpoints_new = $rewardpoints + (int)$quote->getMwRewardpointSellProduct();
            if($rewardpoints_new || $earn_rewardpoint)
            {
                if($rewardpoints_new)
                {
                    $expired_day = (int)Mage::helper('rewardpoints/data')->getExpirationDaysPoint($store_id);
                    //Subtract reward points of customer
                    $_customer->addRewardPoint(-$rewardpoints_new);
                    $historyData = array('type_of_transaction' => MW_RewardPoints_Model_Type::USE_TO_CHECKOUT,
                                         'amount'              => (int)$rewardpoints_new,
                                         'balance'             => $_customer->getMwRewardPoint(),
                                         'transaction_detail'  => $order->getIncrementId(),
                                         'transaction_time'    => Mage::getSingleton('core/date')->gmtDate(),
                                         'expired_day'         => $expired_day,
                                         'expired_time'        => null,
                                         'point_remaining'     => 0,
                                         'history_order_id'    => $order->getId(),
                                         'status'              => MW_RewardPoints_Model_Status::COMPLETE);

                    $_customer->saveTransactionHistory($historyData);
                    Mage::helper('rewardpoints')->sendEmailCustomerPointChanged($_customer->getId(), $historyData, $store_id);

                    // process expired points when spent point
                    Mage::helper('rewardpoints/data')->processExpiredPointsWhenSpentPoints($_customer->getId(), $rewardpoints_new);
                }

                //Save reward point for order
                $orderData = array('order_id'                 => $order->getId(),
                                   'reward_point'             => $rewardpoints,
                                   'rewardpoint_sell_product' => (int)$quote->getMwRewardpointSellProduct(),
                                   'earn_rewardpoint'         => $earn_rewardpoint,
                                   'money'                    => $discount,
                                   'reward_point_money_rate'  => Mage::helper('rewardpoints')->getPointMoneyRateConfig($store_id));

                $_order = Mage::getModel('rewardpoints/rewardpointsorder');
                $_order->saveRewardOrder($orderData);
            }
            if($earn_rewardpoint > 0)
            {
                $detail              = array();
                $_detail_rule_result = array();
                $_detail_rules       = array();
                $_detail_products    = array();
                $_detail_rules       = unserialize($quote->getMwRewardpointDetail());
                foreach ($_detail_rules as $key => $_detail_rule)
                {
                    if($_detail_rule > 0)
                    {
                        $rule_description      = '';
                        $rule_description      = Mage::getModel('rewardpoints/cartrules')->load($key)->getDescription();
                        $_detail_rule_result[] = Mage::helper('rewardpoints')->__('%s | %s', $_detail_rule, $rule_description);
                    }
                }
                foreach ($quote->getAllVisibleItems() as $item)
                {
                    $product_id      = $item->getProduct()->getId();
                    $mw_reward_point = Mage::getModel('rewardpoints/catalogrules')->getPointCatalogRulue($product_id);
                    if($mw_reward_point > 0)
                    {
                        $product_name       = Mage::getModel('catalog/product')->load($product_id)->getName();
                        $_detail_products[] = Mage::helper('rewardpoints')->__('%s |%s', $mw_reward_point * $item->getQty(), $product_name);
                    }
                }
                $detail[1] = $_detail_rule_result;
                $detail[2] = $_detail_products;

                $results         = Mage::helper('rewardpoints/data')->getTransactionExpiredPoints($earn_rewardpoint, $store_id);
                $expired_day     = $results[0];
                $expired_time    = $results[1];
                $point_remaining = $results[2];

                $historyData = array('type_of_transaction' => MW_RewardPoints_Model_Type::CHECKOUT_ORDER_NEW,
                                     'amount'              => $earn_rewardpoint,
                                     'balance'             => $_customer->getMwRewardPoint(),
                                     'transaction_detail'  => $order->getIncrementId() . "||" . serialize($detail),
                                     'transaction_time'    => Mage::getSingleton('core/date')->gmtDate(),
                                     'expired_day'         => $expired_day,
                                     'expired_time'        => $expired_time,
                                     'point_remaining'     => $point_remaining,
                                     'history_order_id'    => $order->getId(),
                                     'status'              => MW_RewardPoints_Model_Status::PENDING);

                $_customer->saveTransactionHistory($historyData);
            }

            //Reward points to friend if this is first purchase
            $orders = Mage::getModel("sales/order")->getCollection()
                ->addFieldToFilter('customer_id', $customer->getId());
            if($_customer->getMwFriendId())
            {
                Mage::helper('rewardpoints/data')->checkAndInsertCustomerId($_customer->getMwFriendId(), 0);
            }
            $friend              = $_customer->getFriend();
            $customer_group_id   = Mage::getModel('customer/customer')->load($customer->getId())->getGroupId();
            $type_of_transaction = MW_RewardPoints_Model_Type::FRIEND_FIRST_PURCHASE;
            $results             = Mage::getModel('rewardpoints/activerules')->getResultActiveRulesExpiredPoints($type_of_transaction, $customer_group_id, $store_id);
            $point               = explode('/', $results[0]);
            if((sizeof($orders) == 1) && ($friend) && Mage::helper('rewardpoints')->checkCustomerMaxBalance($friend->getCustomerId(), $store_id, $point))
            {

                $expired_day     = $results[1];
                $expired_time    = $results[2];
                $point_remaining = $results[3];
                $_point          = $point[0];
                if(sizeof($point) == 2)
                {
                    $total  = $order->getBaseGrandTotal();
                    $_point = ((int)($total / $point[1])) * $point[0];
                }
                if($_point)
                {
                    $historyData = array('type_of_transaction' => MW_RewardPoints_Model_Type::FRIEND_FIRST_PURCHASE,
                                         'amount'              => (int)$_point,
                                         'balance'             => $friend->getMwRewardPoint(),
                                         'transaction_detail'  => $customer->getId() . "|" . $order->getId(),
                                         'transaction_time'    => Mage::getSingleton('core/date')->gmtDate(),
                                         'expired_day'         => $expired_day,
                                         'expired_time'        => $expired_time,
                                         'point_remaining'     => $point_remaining,
                                         'history_order_id'    => $order->getId(),
                                         'status'              => MW_RewardPoints_Model_Status::PENDING);
                    $friend->saveTransactionHistory($historyData);
                }
            }
            else if($friend && Mage::helper('rewardpoints')->checkCustomerMaxBalance($friend->getCustomerId(), $store_id, $point))
            {
                //Reward points to friend if this is next purchase
                //$point = explode('/',Mage::getStoreConfig('rewardpoints/config/reward_point_for_friend_next_purchase'));
                $customer_group_id   = Mage::getModel('customer/customer')->load($customer->getId())->getGroupId();
                $type_of_transaction = MW_RewardPoints_Model_Type::FRIEND_NEXT_PURCHASE;
                //$point = explode('/',Mage::getModel('rewardpoints/activerules')->getPointActiveRules($type_of_transaction,$customer_group_id,$store_id));
                $results         = Mage::getModel('rewardpoints/activerules')->getResultActiveRulesExpiredPoints($type_of_transaction, $customer_group_id, $store_id);
                $point           = explode('/', $results[0]);
                $expired_day     = $results[1];
                $expired_time    = $results[2];
                $point_remaining = $results[3];
                $_point          = $point[0];
                if(sizeof($point) == 2)
                {
                    $total  = $order->getBaseGrandTotal();
                    $_point = ((int)($total / $point[1])) * $point[0];
                }
                if($_point)
                {
                    $historyData = array('type_of_transaction' => MW_RewardPoints_Model_Type::FRIEND_NEXT_PURCHASE,
                                         'amount'              => (int)$_point,
                                         'balance'             => $friend->getMwRewardPoint(),
                                         'transaction_detail'  => $customer->getId() . "|" . $order->getId(),
                                         'transaction_time'    => Mage::getSingleton('core/date')->gmtDate(),
                                         'expired_day'         => $expired_day,
                                         'expired_time'        => $expired_time,
                                         'point_remaining'     => $point_remaining,
                                         'history_order_id'    => $order->getId(),
                                         'status'              => MW_RewardPoints_Model_Status::PENDING);
                    $friend->saveTransactionHistory($historyData);
                }
            }
        }
        else
        {
            $friend_id = Mage::getModel('core/cookie')->get('friend');
            $friend    = Mage::getModel('rewardpoints/customer')->load($friend_id);

            if($friend)
            {
                $type_of_transaction = MW_RewardPoints_Model_Type::FRIEND_FIRST_PURCHASE;

                $results         = Mage::getModel('rewardpoints/activerules')->getResultActiveRulesExpiredPoints($type_of_transaction, 0, $store_id);
                $point           = explode('/', $results[0]);
                $expired_day     = $results[1];
                $expired_time    = $results[2];
                $point_remaining = $results[3];
                $_point          = $point[0];
                if(sizeof($point) == 2)
                {
                    $total  = $order->getBaseGrandTotal();
                    $_point = ((int)($total / $point[1])) * $point[0];
                }

                if($_point)
                {

                    $historyData = array('type_of_transaction' => MW_RewardPoints_Model_Type::FRIEND_FIRST_PURCHASE,
                                         'amount'              => (int)$_point,
                                         'balance'             => $friend->getMwRewardPoint() + (int)$_point,
                                         'transaction_detail'  => "Guest|" . $order->getId(),
                                         'transaction_time'    => Mage::getSingleton('core/date')->gmtDate(),
                                         'expired_day'         => $expired_day,
                                         'expired_time'        => $expired_time,
                                         'point_remaining'     => $point_remaining,
                                         'history_order_id'    => $order->getId(),
                                         'status'              => MW_RewardPoints_Model_Status::COMPLETE);
                    $friend->saveTransactionHistory($historyData);
                    $friend->addRewardPoint((int)$_point);
                }
            }
        }
        //end not cusstomer

        Mage::helper('rewardpoints')->setEmptyRewardpoint();
    }

    public function processOrderCreationData(Varien_Event_Observer $observer)
    {
        $model   = $observer->getEvent()->getOrderCreateModel();
        $request = $observer->getEvent()->getRequest();
        $quote   = $model->getQuote();
        if(isset($request['mw_rewardpoint_add']) && isset($request['customer_id']))
        {
            $rewardpoints = (int)$request['mw_rewardpoint_add'];
            $customer_id  = $request['customer_id'];
            if(isset($request['store_id']))
            {
                $store_id = $request['store_id'];
            }
            else
            {
                $store_id = Mage::app()->getStore()->getId();
            }

            try
            {

                if($rewardpoints < 0)
                {
                    $rewardpoints = -$rewardpoints;
                }

                if($rewardpoints >= 0)
                {
                    $baseGrandTotal = $quote->getBaseGrandTotal();
                    Mage::helper('rewardpoints')->setPointToCheckOut($rewardpoints, $quote, $customer_id, $store_id, $baseGrandTotal);
                    $quote->collectTotals()->save();
                }
                else
                {
                    Mage::getSingleton('adminhtml/session_quote')->addError(
                        $this->__('Cannot apply Reward Points')
                    );
                }

                $quote->collectTotals()->save();
            }
            catch (Mage_Core_Exception $e)
            {
                Mage::getSingleton('adminhtml/session_quote')->addError(
                    $e->getMessage()
                );
            }
            catch (Exception $e)
            {
                Mage::getSingleton('adminhtml/session_quote')->addException(
                    $e,
                    $this->__('Cannot apply Reward Points')
                );
            }
        }

        if(isset($request['mw_rewardpoint_remove']))
        {
            $code = $request['mw_rewardpoint_remove'];

            try
            {

                $quote->setMwRewardpoint(0)->setMwRewardpointDiscount(0)->save();
                $quote->collectTotals()->save();

            }
            catch (Mage_Core_Exception $e)
            {
                Mage::getSingleton('adminhtml/session_quote')->addError(
                    $e->getMessage()
                );
            }
            catch (Exception $e)
            {
                Mage::getSingleton('adminhtml/session_quote')->addException(
                    $e,
                    $this->__('Cannot remove Reward Points')
                );
            }
        }

        return $this;
    }

    // canceled payment
    public function cancelOrder($arvgs)
    {
        $order = $arvgs->getOrder();

        $customerId = $order->getCustomerId();
        $store_id   = Mage::getModel('customer/customer')->load($customerId)->getStoreId();
        $customer   = Mage::getModel('rewardpoints/customer')->load($customerId);

        $data = array('status_check' => MW_RewardPoints_Model_Status::REFUNDED);
        $model = Mage::getModel('rewardpoints/rewardpointshistory')->getCollection()->addFieldToFilter('history_order_id', $order->getData('entity_id'));

        foreach($model->getData() as $_data)
        {
            $_model = Mage::getModel('rewardpoints/rewardpointshistory')->load($_data['history_id'])->addData($data);
            $_model->setId($_data['history_id'])->save();
        }

        $new_transactions = Mage::getModel('rewardpoints/rewardpointshistory')->getCollection()
            ->addFieldToFilter('customer_id', $customerId)
            ->addFieldToFilter('transaction_detail', array('like' => "%" . $order->getIncrementId() . "%"))
            ->addFieldToFilter('status', array('in' => array(MW_RewardPoints_Model_Status::COMPLETE)))
            ->addOrder('transaction_time', 'ASC')
            ->addOrder('history_id', 'ASC');

        foreach ($new_transactions as $new_transaction)
        {
            switch ($new_transaction->getTypeOfTransaction())
            {
                //Use points to check out
                case MW_RewardPoints_Model_Type::USE_TO_CHECKOUT:
                    if($new_transaction->getTransactionDetail() != $order->getIncrementId())
                    {
                        continue;
                    }
                    // cap nhat lai trang thai uncomplete cho transaction do
                    /*$status = MW_RewardPoints_Model_Status::UNCOMPLETE;
                    $new_transaction->setTransactionTime(Mage::getSingleton('core/date')->gmtDate())->setBalance($customer->getRewardPoint());
                    $new_transaction->setStatus($status)->save();*/

                    $transactions_refund = Mage::getModel('rewardpoints/rewardpointshistory')->getCollection()
                        ->addFieldToFilter('type_of_transaction', MW_RewardPoints_Model_Type::ORDER_CANCELLED_ADD_POINTS)
                        ->addFieldToFilter('transaction_detail', $order->getIncrementId());

                    if(sizeof($transactions_refund) > 0)
                    {
                        continue;
                    }

                    $customer->addRewardPoint($new_transaction->getAmount());

                    $expired_day     = $new_transaction->getExpiredDay();
                    $results         = Mage::helper('rewardpoints/data')->getTransactionByExpiredDayAndPoints((int)$new_transaction->getAmount(), $expired_day);
                    $expired_time    = $results[0];
                    $point_remaining = $results[1];

                    $historyData = array('type_of_transaction' => MW_RewardPoints_Model_Type::ORDER_CANCELLED_ADD_POINTS,
                                         'amount'              => (int)$new_transaction->getAmount(),
                                         'balance'             => $customer->getMwRewardPoint(),
                                         'transaction_detail'  => $order->getIncrementId(),
                                         'transaction_time'    => Mage::getSingleton('core/date')->gmtDate(),
                                         'expired_day'         => $expired_day,
                                         'expired_time'        => $expired_time,
                                         'point_remaining'     => $point_remaining,
                                         'status'              => MW_RewardPoints_Model_Status::COMPLETE,
                                         'status_check'        => MW_RewardPoints_Model_Status::REFUNDED);

                    $customer->saveTransactionHistory($historyData);

                    // send mail when points changed
                    Mage::helper('rewardpoints')->sendEmailCustomerPointChanged($customer->getId(), $historyData, $store_id);
                    break;
            }
        }

        $transactions = Mage::getModel('rewardpoints/rewardpointshistory')->getCollection()
            ->addFieldToFilter('customer_id', $customerId)
            ->addFieldToFilter('transaction_detail', array('like' => "%" . $order->getIncrementId() . "%"))
            ->addFieldToFilter('status', array('in' => array(MW_RewardPoints_Model_Status::PENDING)))
            ->addOrder('transaction_time', 'ASC')
            ->addOrder('history_id', 'ASC');

        foreach ($transactions as $transaction)
        {
            switch ($transaction->getTypeOfTransaction())
            {
                //Points for product
                case MW_RewardPoints_Model_Type::PURCHASE_PRODUCT:
                    $detail = explode("|", $transaction->getTransactionDetail());
                    if($detail[1] != $order->getId())
                    {
                        continue;
                    }

                    $status = MW_RewardPoints_Model_Status::UNCOMPLETE;
                    $transaction->setTransactionTime(Mage::getSingleton('core/date')->gmtDate())->setBalance($customer->getRewardPoint());
                    $transaction->setStatus($status)->save();
                    break;

                //Add points when first purchase, next purchase
                case MW_RewardPoints_Model_Type::FRIEND_FIRST_PURCHASE:
                case MW_RewardPoints_Model_Type::FRIEND_NEXT_PURCHASE:
                    $detail = explode("|", $transaction->getTransactionDetail());
                if($detail[1] != $order->getId())
                {
                    continue;
                }
                    $status = MW_RewardPoints_Model_Status::UNCOMPLETE;
                    $transaction->setBalance($customer->getFriend()->getRewardPoint())->setTransactionTime(Mage::getSingleton('core/date')->gmtDate());
                    $transaction->setStatus($status)->save();
                    break;

                //Use points to check out
                case MW_RewardPoints_Model_Type::USE_TO_CHECKOUT:
                    if($transaction->getTransactionDetail() != $order->getIncrementId())
                    {
                        continue;
                    }
                    // cap nhat lai trang thai uncomplete cho transaction do
                    /* $status = MW_RewardPoints_Model_Status::UNCOMPLETE;
                    $transaction->setTransactionTime(Mage::getSingleton('core/date')->gmtDate())->setBalance($customer->getRewardPoint());
                    $transaction->setStatus($status)->save();*/

                    $transactions_refund = Mage::getModel('rewardpoints/rewardpointshistory')->getCollection()
                        ->addFieldToFilter('type_of_transaction', MW_RewardPoints_Model_Type::ORDER_CANCELLED_ADD_POINTS)
                        ->addFieldToFilter('transaction_detail', $order->getIncrementId());

                    if(sizeof($transactions_refund) > 0)
                    {
                        continue;
                    }

                    $customer->addRewardPoint($transaction->getAmount());

                    $expired_day     = $transaction->getExpiredDay();
                    $results         = Mage::helper('rewardpoints/data')->getTransactionByExpiredDayAndPoints((int)$transaction->getAmount(), $expired_day);
                    $expired_time    = $results[0];
                    $point_remaining = $results[1];

                    $historyData = array('type_of_transaction' => MW_RewardPoints_Model_Type::ORDER_CANCELLED_ADD_POINTS,
                                         'amount'              => (int)$transaction->getAmount(),
                                         'balance'             => $customer->getMwRewardPoint(),
                                         'transaction_detail'  => $order->getIncrementId(),
                                         'transaction_time'    => Mage::getSingleton('core/date')->gmtDate(),
                                         'expired_day'         => $expired_day,
                                         'expired_time'        => $expired_time,
                                         'point_remaining'     => $point_remaining,
                                         'status'              => MW_RewardPoints_Model_Status::COMPLETE,
                                         'status_check'        => MW_RewardPoints_Model_Status::REFUNDED);

                    $customer->saveTransactionHistory($historyData);

                    // send mail when points changed
                    Mage::helper('rewardpoints')->sendEmailCustomerPointChanged($customer->getId(), $historyData, $store_id);
                    break;

                //Reward points for order
                case MW_RewardPoints_Model_Type::CHECKOUT_ORDER:
                    if($transaction->getTransactionDetail() != $order->getIncrementId())
                    {
                        continue;
                    }

                    $status = MW_RewardPoints_Model_Status::UNCOMPLETE;
                    $transaction->setBalance($customer->getRewardPoint())->setTransactionTime(Mage::getSingleton('core/date')->gmtDate());
                    $transaction->setStatus($status)->save();
                    break;
                case MW_RewardPoints_Model_Type::CHECKOUT_ORDER_NEW:
                    $detail = explode("||", $transaction->getTransactionDetail());
                    if($detail[0] != $order->getIncrementId())
                    {
                        continue;
                    }

                    $status = MW_RewardPoints_Model_Status::UNCOMPLETE;
                    $transaction->setBalance($customer->getRewardPoint())->setTransactionTime(Mage::getSingleton('core/date')->gmtDate());
                    $transaction->setStatus($status)->save();
                    break;

            }
        }
        $customer_friend_id = Mage::getModel('rewardpoints/customer')->load($customerId)->getMwFriendId();
        if($customer_friend_id)
        {
            //update transaction status for friend
            $transactions = Mage::getModel('rewardpoints/rewardpointshistory')->getCollection()
                ->addFieldToFilter('customer_id', $customer_friend_id)
                ->addFieldToFilter('status', MW_RewardPoints_Model_Status::PENDING)
                ->addFieldToFilter('type_of_transaction', array('in' => array(MW_RewardPoints_Model_Type::FRIEND_FIRST_PURCHASE, MW_RewardPoints_Model_Type::FRIEND_NEXT_PURCHASE)))
                ->addOrder('transaction_time', 'ASC')
                ->addOrder('history_id', 'ASC');
            foreach ($transactions as $transaction)
            {
                $detail = explode("|", $transaction->getTransactionDetail());
                if($detail[1] != $order->getId())
                {
                    continue;
                }
                $status = MW_RewardPoints_Model_Status::UNCOMPLETE;
                $transaction->setBalance(Mage::getModel('rewardpoints/customer')->load($customer_friend_id)->getRewardPoint())->setTransactionTime(Mage::getSingleton('core/date')->gmtDate());
                $transaction->setStatus($status)->save();
            }
        }
    }

    public function orderSaveAfter($arvgs)
    {
        $order                   = $arvgs->getOrder();
        $store_id                = $order->getStoreId();
        $status_add_reward_point = Mage::helper('rewardpoints/data')->getStatusAddRewardPointStore($store_id);
        //$status_subtract_reward_point  = Mage::helper('rewardpoints/data')->getStatusSubtractRewardPointStore($store_id);
        if($order->getStatus() == 'canceled')
        {
            $this->cancelOrder($arvgs);
        }
        if($order->getStatus() == $status_add_reward_point)
        {
            $this->completeOrder($arvgs);
        }
        // trang thai refund product
        if($order->getStatus() == 'closed')
        {
            $this->refundOrder($arvgs);
        }
    }

    public function completeOrder($arvgs)
    {
        $order      = $arvgs->getOrder();
        $customerId = $order->getCustomerId();
        $store_id   = Mage::getModel('customer/customer')->load($customerId)->getStoreId();
        $customer   = Mage::getModel('rewardpoints/customer')->load($customerId);

        $transactions = Mage::getModel('rewardpoints/rewardpointshistory')->getCollection()
            ->addFieldToFilter('customer_id', $customerId)
            ->addFieldToFilter('status', MW_RewardPoints_Model_Status::PENDING)
            ->addOrder('transaction_time', 'ASC')
            ->addOrder('history_id', 'ASC');
        foreach ($transactions as $transaction)
        {
            switch ($transaction->getTypeOfTransaction())
            {
                //Points for product
                case MW_RewardPoints_Model_Type::PURCHASE_PRODUCT:
                    $detail = explode("|", $transaction->getTransactionDetail());
                    if($detail[1] != $order->getIncrementId())
                    {
                        continue;
                    }

                    $customer->addRewardPoint($transaction->getAmount());
                    $status = MW_RewardPoints_Model_Status::COMPLETE;
                    $transaction->setTransactionTime(Mage::getSingleton('core/date')->gmtDate())->setBalance($customer->getRewardPoint());
                    $transaction->setStatus($status)->save();

                    // send mail when points changed
                    $historyData = array('type_of_transaction' => $transaction->getTypeOfTransaction(),
                                         'amount'              => (int)$transaction->getAmount(),
                                         'balance'             => $transaction->getBalance(),
                                         'transaction_detail'  => $transaction->getTransactionDetail(),
                                         'transaction_time'    => $transaction->getTransactionTime(),
                                         'status'              => $transaction->getStatus());

                    Mage::helper('rewardpoints')->sendEmailCustomerPointChanged($customer->getId(), $historyData, $store_id);
                    break;

                //Add points when first purchase, next purchase
                /*
                case MW_RewardPoints_Model_Type::FRIEND_FIRST_PURCHASE:
                case MW_RewardPoints_Model_Type::FRIEND_NEXT_PURCHASE:
                    $detail = explode("|",$transaction->getTransactionDetail());
                    if($detail[1]!= $order->getId()) continue;
                    $order = Mage::getModel('sales/order')->load($detail[1]);

                    $status = MW_RewardPoints_Model_Status::COMPLETE;
                    $customer->getFriend()->addRewardPoint($transaction->getAmount());

                    $expired_day = $transaction->getExpiredDay();
                    $results = Mage::helper('rewardpoints/data')->getTransactionByExpiredDayAndPoints((int)$transaction->getAmount(),$expired_day);
                    $expired_time = $results[0] ;
                    $point_remaining = $results[1];
                    $transaction->setExpiredTime($expired_time)->setPointRemaining($point_remaining);

                    $transaction->setBalance($customer->getFriend()->getMwRewardPoint())->setTransactionTime(Mage::getSingleton('core/date')->gmtDate());
                    $transaction->setStatus($status)->save();

                    // send mail when points changed
                    $historyData = array('type_of_transaction'=>$transaction->getTypeOfTransaction(),
                                         'amount'=>(int)$transaction->getAmount(),
                                         'balance'=>$transaction->getBalance(),
                                         'transaction_detail'=>$transaction->getTransactionDetail(),
                                         'transaction_time'=>$transaction->getTransactionTime(),
                                         'status'=>$transaction->getStatus());

                    Mage::helper('rewardpoints')->sendEmailCustomerPointChanged($customer->getFriend()->getId(),$historyData, $store_id);
                    break;
                    */
                //Use points to check out
                case MW_RewardPoints_Model_Type::USE_TO_CHECKOUT:
                    $order = Mage::getModel("sales/order")->loadByIncrementId($transaction->getTransactionDetail());
                    if($transaction->getTransactionDetail() != $order->getIncrementId())
                    {
                        continue;
                    }

                    $status = MW_RewardPoints_Model_Status::COMPLETE;
                    $transaction->setTransactionTime(Mage::getSingleton('core/date')->gmtDate());
                    $transaction->setStatus($status)->save();
                    break;

                //Reward points for order
                case MW_RewardPoints_Model_Type::CHECKOUT_ORDER:
                    if($transaction->getTransactionDetail() != $order->getIncrementId())
                    {
                        continue;
                    }

                    $status = MW_RewardPoints_Model_Status::COMPLETE;
                    $customer->addRewardPoint($transaction->getAmount());
                    $transaction->setBalance($customer->getRewardPoint())->setTransactionTime(Mage::getSingleton('core/date')->gmtDate());
                    $transaction->setStatus($status)->save();

                    // send mail when points changed
                    $historyData = array('type_of_transaction' => $transaction->getTypeOfTransaction(),
                                         'amount'              => (int)$transaction->getAmount(),
                                         'balance'             => $transaction->getBalance(),
                                         'transaction_detail'  => $transaction->getTransactionDetail(),
                                         'transaction_time'    => $transaction->getTransactionTime(),
                                         'status'              => $transaction->getStatus());

                    Mage::helper('rewardpoints')->sendEmailCustomerPointChanged($customer->getId(), $historyData, $store_id);
                    break;

                case MW_RewardPoints_Model_Type::CHECKOUT_ORDER_NEW:

                    $detail = explode("||", $transaction->getTransactionDetail());
                    if($detail[0] != $order->getIncrementId())
                    {
                        continue;
                    }

                    $status = MW_RewardPoints_Model_Status::COMPLETE;
                    $customer->addRewardPoint($transaction->getAmount());

                    $expired_day     = $transaction->getExpiredDay();
                    $results         = Mage::helper('rewardpoints/data')->getTransactionByExpiredDayAndPoints((int)$transaction->getAmount(), $expired_day);
                    $expired_time    = $results[0];
                    $point_remaining = $results[1];
                    $transaction->setExpiredTime($expired_time)->setPointRemaining($point_remaining);

                    $transaction->setBalance($customer->getRewardPoint())->setTransactionTime(Mage::getSingleton('core/date')->gmtDate());
                    $transaction->setStatus($status)->save();

                    // send mail when points changed
                    $historyData = array('type_of_transaction' => $transaction->getTypeOfTransaction(),
                                         'amount'              => (int)$transaction->getAmount(),
                                         'balance'             => $transaction->getBalance(),
                                         'transaction_detail'  => $transaction->getTransactionDetail(),
                                         'transaction_time'    => $transaction->getTransactionTime(),
                                         'status'              => $transaction->getStatus());

                    Mage::helper('rewardpoints')->sendEmailCustomerPointChanged($customer->getId(), $historyData, $store_id);
                    break;
            }
        }
        $customer_friend_id = Mage::getModel('rewardpoints/customer')->load($customerId)->getMwFriendId();
        if($customer_friend_id)
        {
            //update transaction status for friend
            $transactions = Mage::getModel('rewardpoints/rewardpointshistory')->getCollection()
                ->addFieldToFilter('customer_id', $customer_friend_id)
                ->addFieldToFilter('status', MW_RewardPoints_Model_Status::PENDING)
                ->addFieldToFilter('type_of_transaction', array('in' => array(MW_RewardPoints_Model_Type::FRIEND_FIRST_PURCHASE, MW_RewardPoints_Model_Type::FRIEND_NEXT_PURCHASE)))
                ->addOrder('transaction_time', 'ASC')
                ->addOrder('history_id', 'ASC');
            foreach ($transactions as $transaction)
            {
                $detail = explode("|", $transaction->getTransactionDetail());
                if($detail[1] != $order->getId())
                {
                    continue;
                }
                Mage::getModel('rewardpoints/customer')->load($customer_friend_id)->addRewardPoint($transaction->getAmount());
                $status = MW_RewardPoints_Model_Status::COMPLETE;

                $expired_day     = $transaction->getExpiredDay();
                $results         = Mage::helper('rewardpoints/data')->getTransactionByExpiredDayAndPoints((int)$transaction->getAmount(), $expired_day);
                $expired_time    = $results[0];
                $point_remaining = $results[1];
                $transaction->setExpiredTime($expired_time)->setPointRemaining($point_remaining);

                $transaction->setBalance(Mage::getModel('rewardpoints/customer')->load($customer_friend_id)->getRewardPoint())->setTransactionTime(Mage::getSingleton('core/date')->gmtDate());
                $transaction->setStatus($status)->save();

                // send mail when points changed
                $historyData = array('type_of_transaction' => $transaction->getTypeOfTransaction(),
                                     'amount'              => (int)$transaction->getAmount(),
                                     'balance'             => $transaction->getBalance(),
                                     'transaction_detail'  => $transaction->getTransactionDetail(),
                                     'transaction_time'    => $transaction->getTransactionTime(),
                                     'status'              => $transaction->getStatus());

                $store_id = Mage::getModel('customer/customer')->load($customer_friend_id)->getStoreId();
                Mage::helper('rewardpoints')->sendEmailCustomerPointChanged($customer_friend_id, $historyData, $store_id);
            }
        }

    }

    public function refundOrder($arvgs)
    {
        $order        = $arvgs->getOrder();
        $transactions = Mage::getModel('rewardpoints/rewardpointshistory')->getCollection()
            ->addFieldToFilter('transaction_detail', array('like' => "%" . $order->getIncrementId() . "%"))
            ->addFieldToFilter('status', array('in' => array(MW_RewardPoints_Model_Status::COMPLETE)));

        if(sizeof($transactions))
        {
            foreach ($transactions as $transaction)
            {
                Mage::log($transaction);
                Mage::log($transaction->getTypeOfTransaction());
                $customer              = $transaction->getCustomer();
                $store_id              = Mage::getModel('customer/customer')->load($customer->getId())->getStoreId();
                $subtract_reward_point = Mage::helper('rewardpoints')->getSubtractPointWhenRefundConfigStore($store_id);
                $restore_spent_points  = Mage::helper('rewardpoints')->getRestoreSpentPointsWhenRefundConfigStore($store_id);
                switch ($transaction->getTypeOfTransaction())
                {
                    //Points for product
                    case MW_RewardPoints_Model_Type::PURCHASE_PRODUCT:
                        //$transaction->setTransactionTime(Mage::getSingleton('core/date')->gmtDate())->setBalance($customer->getRewardPoint());
                        if($transaction->getStatus() == MW_RewardPoints_Model_Status::COMPLETE)
                        {
                            $customer->addRewardPoint(-$transaction->getAmount());

                            $historyData = array('type_of_transaction' => MW_RewardPoints_Model_Type::REFUND_ORDER_SUBTRACT_PRODUCT_POINTS,
                                                 'amount'              => (int)$transaction->getAmount(),
                                                 'balance'             => $customer->getMwRewardPoint(),
                                                 'transaction_detail'  => $transaction->getTransactionDetail(),
                                                 'transaction_time'    => Mage::getSingleton('core/date')->gmtDate(),
                                                 'status'              => MW_RewardPoints_Model_Status::COMPLETE,
                                                 'status_check'         => MW_RewardPoints_Model_Status::REFUNDED);

                            $customer->saveTransactionHistory($historyData);

                            //send mail when points changed
                            Mage::helper('rewardpoints')->sendEmailCustomerPointChanged($customer->getId(), $historyData, $store_id);
                        }
                        //else{
                        //$transaction->setStatus(MW_RewardPoints_Model_Status::UNCOMPLETE)->save();
                        //}
                        break;

                    //Add points when first purchase, next purchase
                    /*
                    case MW_RewardPoints_Model_Type::FRIEND_FIRST_PURCHASE:
                    case MW_RewardPoints_Model_Type::FRIEND_NEXT_PURCHASE:
                        $detail = explode("|",$transaction->getTransactionDetail());
                        if($detail[1]!= $order->getId()) continue;
                        $transaction->setStatus(MW_RewardPoints_Model_Status::UNCOMPLETE)->save();

                        break;
                    */
                    //Use points to check out
                    case MW_RewardPoints_Model_Type::USE_TO_CHECKOUT:
                        if($transaction->getTransactionDetail() != $order->getIncrementId())
                        {
                            continue;
                        }

                        $transactions_refund = Mage::getModel('rewardpoints/rewardpointshistory')->getCollection()
                            ->addFieldToFilter('type_of_transaction', MW_RewardPoints_Model_Type::REFUND_ORDER_ADD_POINTS)
                            ->addFieldToFilter('transaction_detail', $order->getIncrementId());

                        if(sizeof($transactions_refund) > 0)
                        {
                            continue;
                        }

                        if($restore_spent_points)
                        {
                            $customer->addRewardPoint($transaction->getAmount());

                            $expired_day     = $transaction->getExpiredDay();
                            $results         = Mage::helper('rewardpoints/data')->getTransactionByExpiredDayAndPoints((int)$transaction->getAmount(), $expired_day);
                            $expired_time    = $results[0];
                            $point_remaining = $results[1];

                            $historyData = array('type_of_transaction' => MW_RewardPoints_Model_Type::REFUND_ORDER_ADD_POINTS,
                                                 'amount'              => (int)$transaction->getAmount(),
                                                 'balance'             => $customer->getMwRewardPoint(),
                                                 'transaction_detail'  => $order->getIncrementId(),
                                                 'transaction_time'    => Mage::getSingleton('core/date')->gmtDate(),
                                                 'expired_day'         => $expired_day,
                                                 'expired_time'        => $expired_time,
                                                 'point_remaining'     => $point_remaining,
                                                 'status'              => MW_RewardPoints_Model_Status::COMPLETE,
                                                 'status_check'         => MW_RewardPoints_Model_Status::REFUNDED);
                            $customer->saveTransactionHistory($historyData);

                            //send mail when points changed
                            Mage::helper('rewardpoints')->sendEmailCustomerPointChanged($customer->getId(), $historyData, $store_id);
                        }

                        break;

                    //Reward points for order
                    case MW_RewardPoints_Model_Type::CHECKOUT_ORDER:
                        if($transaction->getTransactionDetail() != $order->getIncrementId())
                        {
                            continue;
                        }

                        $transactions_refund = Mage::getModel('rewardpoints/rewardpointshistory')->getCollection()
                            ->addFieldToFilter('type_of_transaction', MW_RewardPoints_Model_Type::REFUND_ORDER_SUBTRACT_POINTS)
                            ->addFieldToFilter('transaction_detail', $order->getIncrementId());

                        if(sizeof($transactions_refund) > 0)
                        {
                            continue;
                        }

                        if($transaction->getStatus() == MW_RewardPoints_Model_Status::COMPLETE && $subtract_reward_point)
                        {
                            $customer->addRewardPoint(-$transaction->getAmount());

                            $historyData = array('type_of_transaction' => MW_RewardPoints_Model_Type::REFUND_ORDER_SUBTRACT_POINTS,
                                                 'amount'              => (int)$transaction->getAmount(),
                                                 'balance'             => $customer->getMwRewardPoint(),
                                                 'transaction_detail'  => $order->getIncrementId(),
                                                 'transaction_time'    => Mage::getSingleton('core/date')->gmtDate(),
                                                 'status'              => MW_RewardPoints_Model_Status::COMPLETE,
                                                 'status_check'         => MW_RewardPoints_Model_Status::REFUNDED);
                            $customer->saveTransactionHistory($historyData);

                            //send mail when points changed
                            Mage::helper('rewardpoints')->sendEmailCustomerPointChanged($customer->getId(), $historyData, $store_id);

                        }
                        //else
                        //$transaction->setStatus(MW_RewardPoints_Model_Status::UNCOMPLETE)->save();
                        break;
                    //Reward points for order
                    case MW_RewardPoints_Model_Type::CHECKOUT_ORDER_NEW:

                        $detail = explode("||", $transaction->getTransactionDetail());
                        if($detail[0] != $order->getIncrementId())
                        {
                            continue;
                        }

                        $transactions_refund = Mage::getModel('rewardpoints/rewardpointshistory')->getCollection()
                            ->addFieldToFilter('type_of_transaction', MW_RewardPoints_Model_Type::REFUND_ORDER_SUBTRACT_POINTS)
                            ->addFieldToFilter('transaction_detail', $order->getIncrementId());

                        if(sizeof($transactions_refund) > 0)
                        {
                            continue;
                        }

                        if($transaction->getStatus() == MW_RewardPoints_Model_Status::COMPLETE && $subtract_reward_point)
                        {
                            $customer->addRewardPoint(-$transaction->getAmount());

                            $historyData = array('type_of_transaction' => MW_RewardPoints_Model_Type::REFUND_ORDER_SUBTRACT_POINTS,
                                                 'amount'              => (int)$transaction->getAmount(),
                                                 'balance'             => $customer->getMwRewardPoint(),
                                                 'transaction_detail'  => $order->getIncrementId(),
                                                 'transaction_time'    => Mage::getSingleton('core/date')->gmtDate(),
                                                 'status'              => MW_RewardPoints_Model_Status::COMPLETE,
                                                 'status_check'         => MW_RewardPoints_Model_Status::REFUNDED);
                            $customer->saveTransactionHistory($historyData);

                            // process expired points when spent point
                            Mage::helper('rewardpoints/data')->processExpiredPointsWhenSpentPoints($customer->getId(), $transaction->getAmount());

                            //send mail when points changed
                            Mage::helper('rewardpoints')->sendEmailCustomerPointChanged($customer->getId(), $historyData, $store_id);


                        }
                        //else
                        //$transaction->setStatus(MW_RewardPoints_Model_Status::UNCOMPLETE)->save();
                        break;

                }
            }
        }
        $customerId         = $order->getCustomerId();
        $customer_friend_id = Mage::getModel('rewardpoints/customer')->load($customerId)->getMwFriendId();
        if($customer_friend_id)
        {
            //update transaction status for friend
            $transactions = Mage::getModel('rewardpoints/rewardpointshistory')->getCollection()
                ->addFieldToFilter('customer_id', $customer_friend_id)
                ->addFieldToFilter('status', MW_RewardPoints_Model_Status::COMPLETE)
                ->addFieldToFilter('type_of_transaction', array('in' => array(MW_RewardPoints_Model_Type::FRIEND_FIRST_PURCHASE, MW_RewardPoints_Model_Type::FRIEND_NEXT_PURCHASE)))
                ->addOrder('transaction_time', 'ASC')
                ->addOrder('history_id', 'ASC');
            foreach ($transactions as $transaction)
            {
                $store_id              = Mage::getModel('customer/customer')->load($customer_friend_id)->getStoreId();
                $subtract_reward_point = Mage::helper('rewardpoints')->getSubtractPointWhenRefundConfigStore($store_id);

                $detail = explode("|", $transaction->getTransactionDetail());
                if($detail[1] != $order->getId())
                {
                    continue;
                }

                $transactions_refund = Mage::getModel('rewardpoints/rewardpointshistory')->getCollection()
                    ->addFieldToFilter('type_of_transaction', MW_RewardPoints_Model_Type::REFUND_ORDER_FREND_PURCHASE)
                    ->addFieldToFilter('transaction_detail', $transaction->getTransactionDetail());

                if(sizeof($transactions_refund) > 0)
                {
                    continue;
                }


                if($subtract_reward_point)
                {
                    //$transaction->setStatus(MW_RewardPoints_Model_Status::UNCOMPLETE)->save();
                    Mage::getModel('rewardpoints/customer')->load($customer_friend_id)->addRewardPoint(-$transaction->getAmount());

                    $historyData = array('type_of_transaction' => MW_RewardPoints_Model_Type::REFUND_ORDER_FREND_PURCHASE,
                                         'amount'              => (int)$transaction->getAmount(),
                                         'balance'             => Mage::getModel('rewardpoints/customer')->load($customer_friend_id)->getMwRewardPoint(),
                                         'transaction_detail'  => $transaction->getTransactionDetail(),
                                         'transaction_time'    => Mage::getSingleton('core/date')->gmtDate(),
                                         'status'              => MW_RewardPoints_Model_Status::COMPLETE,
                                         'status_check'         => MW_RewardPoints_Model_Status::REFUNDED);
                    Mage::getModel('rewardpoints/customer')->load($customer_friend_id)->saveTransactionHistory($historyData);

                    // process expired points when spent point
                    Mage::helper('rewardpoints/data')->processExpiredPointsWhenSpentPoints($customer_friend_id, $transaction->getAmount());

                    //send mail when points changed
                    Mage::helper('rewardpoints')->sendEmailCustomerPointChanged($customer_friend_id, $historyData, $store_id);
                }
            }
        }
    }

    public function salesConvertQuoteAdressToOrderAddress($argv)
    {
        if(Mage::helper('rewardpoints')->moduleEnabled())
        {
            $address      = $argv->getAddress();
            $orderAddress = $argv->getOrderAddress();
            $orderAddress->setQuoteAddressId($address->getAddressId());
        }
    }
}