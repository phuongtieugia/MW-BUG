<?php

class MW_Ddate_Model_Mysql4_Pickup_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('ddate/pickup');
    }
}
