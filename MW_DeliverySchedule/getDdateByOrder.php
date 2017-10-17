<?php

public function getDdateByOrder($incrementId){
		if($incrementId != null){
			$ddates = Mage::getModel('ddate/ddate')->getCollection();
            $ddates->getSelect()
                ->join(
                    array('date_store'=>$ddates->getTable('ddate_store')),
                    'date_store.ddate_id = main_table.ddate_id',
                    array('date_store.ddate_id','date_store.ddate_comment')
                );
            $ddates->addFieldToFilter('date_store.increment_id', array('eq' => $incrementId));
			if (count($ddates->getData())>0){
				return $ddates->getFirstItem();
			}
		}
		return null;
	}