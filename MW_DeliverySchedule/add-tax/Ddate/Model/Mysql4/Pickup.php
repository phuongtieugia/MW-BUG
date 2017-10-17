<?php

class MW_Ddate_Model_Mysql4_Pickup extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {
        // Note that the news_id refers to the key field in your database table.
        $this->_init('ddate/pickup', 'pickup_id');
    }
}
