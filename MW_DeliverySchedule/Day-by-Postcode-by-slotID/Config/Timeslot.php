<?php
class MW_Ddate_Block_Config_Timeslot extends Mage_Core_Block_Html_Select
{
    public function _toHtml()
    {
		$dtimes = Mage::getModel('ddate/dtime')->getCollection();
        $dtimes->getSelect('dtime_id,dtime');
        foreach ($dtimes as $dtime) {
            $this->addOption($dtime['dtime_id'], $dtime['dtime']);
        }
 
        return parent::_toHtml();
    }
 
    public function setInputName($value)
    {
        return $this->setName($value);
    }
}