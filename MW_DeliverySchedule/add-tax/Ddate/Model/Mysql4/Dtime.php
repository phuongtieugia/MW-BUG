<?php

class MW_Ddate_Model_Mysql4_Dtime extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {
        // Note that the news_id refers to the key field in your database table.
        $this->_init('ddate/dtime', 'dtime_id');
    }

    protected function _afterSave(Mage_Core_Model_Abstract $object)
    {
        $condition = $this->_getWriteAdapter()->quoteInto('dtime_id = ?', $object->getId());

        if (!$object->getData('dtime_stores')) {
            $read_adapter = $this->_getReadAdapter();
            $select = $read_adapter->select()
                ->from($this->getTable('dtime_store'))
                ->where($condition);
            $stores = $read_adapter->fetchAll($select);
            $dtime_stores = array();
            $i = 0;
            if (count($stores) != 0) {
                foreach ($stores as $store) {
                    $dtime_stores[$i] = $store['store_id'];
                    $i++;
                }
            }
            $object->setData('dtime_stores', $dtime_stores);
        }

        $this->_getWriteAdapter()->delete($this->getTable('dtime_store'), $condition);

        if (!$object->getData('dtime_stores')) {
            $storeArray = array();
            $storeArray['dtime_id'] = $object->getId();
            $storeArray['store_id'] = '0';
            $this->_getWriteAdapter()->insert($this->getTable('dtime_store'), $storeArray);
        } else {
            foreach ((array)$object->getData('dtime_stores') as $store) {
                $storeArray = array();
                $storeArray['dtime_id'] = $object->getId();
                $storeArray['store_id'] = $store;
                $this->_getWriteAdapter()->insert($this->getTable('dtime_store'), $storeArray);
            }
        }

        return parent::_afterSave($object);
    }

    protected function _beforeDelete(Mage_Core_Model_Abstract $object)
    {
        // Cleanup stats on blog delete
        $adapter = $this->_getReadAdapter();
        // 1. Delete testimonial/store
        $adapter->delete($this->getTable('ddate/dtime_store'), 'dtime_id=' . $object->getId());
    }
}
