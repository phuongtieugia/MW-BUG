<?php
    /**
     * User: Anh TO
     * Date: 5/26/14
     * Time: 10:27 AM
     */

    class MW_RewardPoints_Model_Redeemtax extends Varien_Object
    {
        const BEFORE = 1;
        const AFTER  = 2;


        static public function toOptionArray()
        {
            return array(
                self::BEFORE => Mage::helper('rewardpoints')->__('After Redeempoint'),
                self::AFTER  => Mage::helper('rewardpoints')->__('Before Redeempoint'),
            );
        }
    }