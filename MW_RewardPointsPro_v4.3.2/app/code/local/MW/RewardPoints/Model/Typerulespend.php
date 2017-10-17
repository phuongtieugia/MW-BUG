<?php

    class MW_RewardPoints_Model_Typerulespend extends Varien_Object
    {
        const FIXED = 1; //haven't change points yet
        const BUY_X_USE_Y          = 2;
        const USE_UNLIMIT_POINTS   = 3;
        const NOT_ALLOW_USE_POINTS = 4;


        static public function getOptionArray()
        {
            return array(
                self::NOT_ALLOW_USE_POINTS => Mage::helper('rewardpoints')->__('Do Not Allow to Use Reward Points'),
                self::USE_UNLIMIT_POINTS   => Mage::helper('rewardpoints')->__('Allow to Use Unlimited Points'),
                self::FIXED                => Mage::helper('rewardpoints')->__('Allow to use fixed Reward Points (X) per order'),
                self::BUY_X_USE_Y          => Mage::helper('rewardpoints')->__('Spend (Y) to allow to use Reward Points (X)'),


            );
        }

        static public function getLabel($status)
        {
            $options = self::getOptionArray();

            return $options[$status];
        }
    }