<?php

    class MW_RewardPoints_Model_Mysql4_Activerules_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
    {
        public function _construct()
        {
            parent::_construct();
            $this->_init('rewardpoints/activerules');
        }

        protected function _afterLoad()
        {
            foreach ($this as $item)
            {
                $storeview = $item->getStoreView(); // chuoi storeview
                $store     = explode(",", $storeview); // 1,2 => array(1,2)
                $item->setData('store_view', $store);
            }

            parent::_afterLoad();
        }

    }