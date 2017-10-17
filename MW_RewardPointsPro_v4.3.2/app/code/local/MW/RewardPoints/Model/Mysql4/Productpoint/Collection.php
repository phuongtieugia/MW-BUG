<?php

    class MW_RewardPoints_Model_Mysql4_Productpoint_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
    {
        public function _construct()
        {
            parent::_construct();
            $this->_init('rewardpoints/productpoint');
        }
    }