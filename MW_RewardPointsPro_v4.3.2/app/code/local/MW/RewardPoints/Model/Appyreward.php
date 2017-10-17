<?php

    class MW_RewardPoints_Model_Appyreward extends Varien_Object
    {
        const BEFORE = 1;
        const AFTER  = 2;


        static public function toOptionArray()
        {
            return array(
                self::BEFORE => Mage::helper('rewardpoints')->__('Before Discount'),
                self::AFTER  => Mage::helper('rewardpoints')->__('After Discount'),
            );
        }
    }