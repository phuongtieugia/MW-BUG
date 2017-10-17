<?php
// http://test.golbazar.net/index.php/onestepcheckout/index/undateOlddata
public function undateOlddataAction(){
	$model = Mage::getModel('onestepcheckout/onestepcheckout');
	$orderonestep = $model->getCollection()->addFieldToFilter('mw_deliverydate_date', array('neq' => ''));
	$id = "";$date = "";$newdate="";
	foreach($orderonestep as $o){
		$id = $o->getData("mw_onestepcheckout_date_id");
		if($id){
			
			$date = $o->getData("mw_deliverydate_date");
			$dates = explode("/",$date);
			$newdate = $dates[2]."-".$dates[1]."-".$dates[0];
			if($dates[1] > 12){
				$newdate = $dates[2]."-".$dates[0]."-".$dates[1];
			}
			$model->load($id);
			/* $model->setMwDeliverydate($newdate); */
			$model->setMwDeliverydateDate($newdate);
			$model->save();
			
			echo $newdate;
			
		}
		
		echo "<br>";
	}
}

// MW_Onestepcheckout_Model_Observer Line 154
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
					if($deliverystatus=="late"){	
						$dates = explode("/",$deliverydate);
						$newdate = $dates[2]."-".$dates[1]."-".$dates[0];
						if($dates[1] > 12){
							$newdate = $dates[2]."-".$dates[0]."-".$dates[1];
						}
						$orderonestep->setMwDeliverydateDate($newdate);
						$orderonestep->setMwDeliverydateTime($deliverytime);
					}
			$orderonestep->save();	
		}						
		Mage::getSingleton('core/session')->unsDeliveryInforOrder();
}


// MW_Onestepcheckout_Model_Sales_Order LINE 71
if(Mage::getSingleton('core/session')->getDeliveryInforEmail())
{
	$deliveryInfo = Mage::getSingleton('core/session')->getDeliveryInforEmail();
		$this->setMwCustomercommentInfo($deliveryInfo[0]);						
		if($deliveryInfo[1]=="late")
		{
			$dates = explode("/",$deliveryInfo[2]);
			$newdate = $dates[2]."-".$dates[1]."-".$dates[0];
			if($dates[1] > 12){
				$newdate = $dates[2]."-".$dates[0]."-".$dates[1];
			}
			$this->setDeliverystatus('')
				->setMwdeliverydate($newdate)
				-> setMwdeliverytime($deliveryInfo[3]);
		}
		else 
		{
			$this->setDeliverystatus('As soon as possible')
				->setMwdeliverydate('')
				-> setMwdeliverytime('');
		}
		
		Mage::getSingleton('core/session')->unsDeliveryInforEmail();	
			//Zend_Debug::dump($deliveryInfo);
			//die();
}