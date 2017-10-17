<?php

class MW_Ddate_Model_Demail extends Mage_Core_Model_Abstract
{   
    public function _construct()
    {
        parent::_construct();
        $this->_init('ddate/demail');
    }
}
