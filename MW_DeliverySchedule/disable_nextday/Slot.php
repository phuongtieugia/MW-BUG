<?php 
class MW_Ddate_Model_System_Slot
{
    public function toOptionArray()
    {
    	$slots = Mage::getResourceModel('ddate/ddate')->getDtime();
		$rs = "";
		$i = 0;
		foreach ($slots as $slot){
			$rs[$i] = array('value'=>$slot->getDtimeId(), 'label'=>$slot->getDtime());
			$i++;
		}
        return $rs;
    }

}