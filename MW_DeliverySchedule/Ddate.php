<?php

class MW_Ddate_Model_Ddate extends Mage_Core_Model_Abstract
{
    private $inexedDdates = null;
    
    public function _construct()
    {
        parent::_construct();
        $this->_init('ddate/ddate');
    }
    
    public function getNumberOrderFromNow()
    {
        if(is_null($this->inexedDdates)) {
            $timeFilter = strtotime('- 1 day');
            /* $collection = $this->getCollection()->addFieldToFilter('UNIX_TIMESTAMP(ddate)', array('gteq' => $timeFilter)); */
				
			// ptg custom code	
			
			$resource = Mage::getSingleton('core/resource');

			$writeConnection = $resource->getConnection('core_read');
			$table = $resource->getTableName('ddate/ddate');
			$query = "SELECT * FROM {$table} WHERE (UNIX_TIMESTAMP('ddate') >= $timeFilter)";
			$collection = $writeConnection->fetchAll($query);
			
			// end ptg custom code	
            $ddateArray = array();
            foreach ($collection as $ddate) {
                $ddateArray[$ddate->getDtime()][$ddate->getDdate()] = $ddate;
            }
            
            $this->inexedDdates = $ddateArray;
        }
        
        return $this->inexedDdates;
    }
}
