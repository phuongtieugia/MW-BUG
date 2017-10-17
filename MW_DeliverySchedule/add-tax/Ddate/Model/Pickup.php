<?php

class MW_Ddate_Model_Pickup extends Mage_Core_Model_Abstract
{
    private $inexedDdates = null;
    
    public function _construct()
    {
        parent::_construct();
        $this->_init('ddate/pickup');
    }
}
