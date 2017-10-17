<?php

class MW_Ddate_Model_Mysql4_Ddate_Store extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {    
        // Note that the news_id refers to the key field in your database table.
        $this->_init('ddate/ddate_store', 'increment_id');
    }
}
