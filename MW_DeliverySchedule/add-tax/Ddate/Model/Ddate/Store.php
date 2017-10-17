<?php

class MW_Ddate_Model_Ddate_Store extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('ddate/ddate_store');
    }

    /**
     * Save Delivery Data Store
     * @param  array $data
     * @return void
     */
    public function saveDdateStore($data)
    {
    	$resource = Mage::getSingleton('core/resource');
    	$sql = "INSERT INTO {$resource->getTableName('ddate/ddate_store')} (increment_id, ddate_id, ddate_comment, order_status) VALUES('{$data['increment_id']}', '{$data['ddate_id']}', '{$data['ddate_comment']}', '{$data['order_status']}')";
		$connection = Mage::getModel('core/resource')->getConnection('core_write');
 		$connection->query($sql);
    }
}
