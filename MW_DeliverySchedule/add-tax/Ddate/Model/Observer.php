<?php

class MW_Ddate_Model_Observer extends Mage_Core_Model_Abstract
{
	public function cancelOrderAfter($observer)
    {
        $cancel = $observer->getItem();
        $order = $cancel ->getOrder();
        $order_id=$order->getIncrementId();
        $resource = Mage::getSingleton('core/resource');
        $selection = "SELECT * FROM `{$resource->getTableName('ddate/ddate_store')}` WHERE increment_id='{$order_id}'";
        $con = Mage::getModel('core/resource')->getConnection('core_read');
        $result = $con->query($selection);
        $results = $result->fetchAll();

        foreach ($results as $row) {
        	if ($row['order_status'] != 'canceled') {
        		//subtract Ordered value
        		$ddate = Mage::getModel('ddate/ddate')->load($row['ddate_id']);
        		$ordered = intval($ddate->getOrdered()) - 1 ;
        		$ddate->setOrdered($ordered);
        		$ddate->save();
        		
        		//change order_status for order in the table ddate_store
        		$sql = "UPDATE {$resource->getTableName('ddate/ddate_store')} SET order_status='canceled' WHERE ddate_store_id={$row['ddate_store_id']}";
                $con2 = Mage::getModel('core/resource')->getConnection('core_write');
        		$con2->query($sql);
        	}
        }
	}
	
	public function checkLicense($o)
	{
		$modules = Mage::getConfig()->getNode('modules')->children();
		$modulesArray = (array)$modules; 
		$modules2 = array_keys((array)Mage::getConfig()->getNode('modules')->children()); 
		if (!in_array('MW_Mcore', $modules2) 
            || !$modulesArray['MW_Mcore']->is('active') 
            || Mage::getStoreConfig('mcore/config/enabled') != 1)
		{
			Mage::helper('ddate')->disableConfig();
		}
	}
	
    /**
     * Event controller_action_layout_generate_blocks_before
     * @return void
     */
    public function checkMissingLayout()
    {
        $helper = Mage::helper('ddate');
        // Check is backend or frontend
        if (Mage::app()->getStore()->isAdmin() || Mage::getDesign()->getArea() == 'adminhtml') {
            $currentControllerName = Mage::app()->getRequest()->getControllerName();
            if ($currentControllerName == 'system_config') {
                if (Mage::app()->getRequest()->getParam('section') == 'ddate') {
                    $helper->checkMissingLayoutThemesViaBackend();
                }
            }
        } else {
            $helper->checkMissingLayoutCurrentThemeViaFrontend();
        }
    }
}