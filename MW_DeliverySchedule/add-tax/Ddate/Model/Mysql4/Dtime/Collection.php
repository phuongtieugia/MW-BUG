<?php

class MW_Ddate_Model_Mysql4_Dtime_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('ddate/dtime');
    }

    public function getStore($dtime_id)
    {
        $this->getSelect()
            ->join(array(
                "mwdtime_store"=>$this->getTable('ddate/dtime_store')),
                'mwdtime_store.dtime_id = main_table.dtime_id',
                array('mwdtime_store.store_id')
            )
            ->where('mwdtime_store.dtime_id = ' . $dtime_id);

        return $this;
    }

    public function addFieldToFilter($attribute, $condition=null)
    {
        if ($attribute == 'store_id') {
            if ($condition['eq'] != '0') {
                $this->getSelect()
                    ->join(array(
                        "mwdtime_store"=>$this->getTable('ddate/dtime_store')),
                        'mwdtime_store.dtime_id = main_table.dtime_id',
                        array('mwdtime_store.store_id')
                    )
                    ->where('mwdtime_store.store_id = ?', $condition['eq']);
            }
            return $this;
        }

        if ($attribute == 'dtime') {
            $attribute = 'main_table.' . $attribute;
        }

        return parent::addFieldToFilter($attribute, $condition);
    }
}
