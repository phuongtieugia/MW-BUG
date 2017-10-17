<?php
    /**
     * User: Anh TO
     * Date: 6/18/14
     * Time: 2:40 PM
     */

    class MW_RewardPoints_Model_Mysql4_Productsellpoint_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
    {
        public function _construct()
        {
            parent::_construct();
            $this->_init('rewardpoints/productsellpoint');
        }
    }