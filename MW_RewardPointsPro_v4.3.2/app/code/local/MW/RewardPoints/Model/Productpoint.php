<?php

    class MW_RewardPoints_Model_Productpoint extends Mage_Core_Model_Abstract
    {
        public function _construct()
        {
            parent::_construct();
            $this->_init('rewardpoints/productpoint');
        }

    }