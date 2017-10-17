<?php
    /**
     * User: Anh TO
     * Date: 6/18/14
     * Time: 2:19 PM
     */

    class MW_RewardPoints_Model_Productsellpoint extends Mage_Core_Model_Abstract
    {
        public function _construct()
        {
            parent::_construct();
            $this->_init('rewardpoints/productsellpoint');
        }
    }