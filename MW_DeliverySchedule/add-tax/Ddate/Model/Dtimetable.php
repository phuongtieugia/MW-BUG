<?php

class MW_Ddate_Model_Dtimetable extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('ddate/dtimetable');
    }
    
    public function _getDtime()
    {
    	$dtimes = Mage::getModel('ddate/dtime')->getCollection()->addFieldToFilter('dtimetable_id',$this->getId());
    	if ($dtimes) {
	    	$arr_dtimes = array();
	    	foreach($dtimes as $key=>$dtime) {
	    		$a['day'] = $dtime['day'];
	    		$a['dtime'] = $dtime['dtime'];
	    		$arr_dtimes[$key] = $a;
	    	}

	    	return $arr_dtimes;
    	}

    	return false;
    }
}
