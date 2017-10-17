<?php

    class MW_RewardPoints_Model_Mysql4_Spendcartrules extends Mage_Core_Model_Mysql4_Abstract
    {
        public function _construct()
        {
            // Note that the rewardpoints_id refers to the key field in your database table.
            $this->_init('rewardpoints/spendcartrules', 'rule_id');
        }
    }