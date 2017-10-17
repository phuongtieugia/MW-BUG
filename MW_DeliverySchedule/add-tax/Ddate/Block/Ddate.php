<?php

class MW_Ddate_Block_Ddate extends Mage_Core_Block_Template
{
	public function _prepareLayout()
    {
		return parent::_prepareLayout();
    }

     public function getDdate()
     {
        if (!$this->hasData('ddate')) {
            $this->setData('ddate', Mage::registry('ddate'));
        }

        return $this->getData('ddate');
    }

    public function getDtime()
    {
    	$dtimes = Mage::getModel('ddate/dtime')->getCollection();
    	$dtimes->join(
                'mwdtime_store',
                'mwdtime_store.dtime_id = main_table.dtime_id ',
                array('main_table.dtime_id')
            )
            ->where('dtime_store.dtime_id in (?)',array(0,Mage::app()->getStore()->getId()));

    	return $dtimes;
    }
}
