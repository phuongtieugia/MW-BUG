<?php
class MW_Onestepcheckout_Model_System_Config_Source_Poll
{
    public function toOptionArray()
    {
    	 $arrpoll = array();
    	 $arrpoll[0] = "Disable";
    	 $poll = Mage::getModel('poll/poll')->getCollection()->addFieldToFilter('closed',0)->getData();
    	 if($poll)
    	 {
	    	 foreach($poll as $po)
	    	 	{
	    	 		$arrpoll[$po['poll_id']] = $po['poll_title'];
	    	 	}
    	 }
        return $arrpoll;
    }
    
}
