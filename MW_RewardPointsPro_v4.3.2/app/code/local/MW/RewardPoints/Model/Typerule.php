<?php

    class MW_RewardPoints_Model_Typerule extends Varien_Object
    {
        const FIXED = 1; //haven't change points yet
        const BUY_X_GET_Y            = 2;
        const FIXED_WHOLE_CART       = 3;
        const BUY_X_GET_Y_WHOLE_CART = 4;


        static public function getOptionArray()
        {
            return array(
                self::FIXED       => Mage::helper('rewardpoints')->__('Fixed Reward Points (X)'),
                self::BUY_X_GET_Y => Mage::helper('rewardpoints')->__('Spend Y Get X Reward Points'),
                //self::BUY_X_GET_Y  			=> Mage::helper('rewardpoints')->__('Buy X get Y Reward Points'),
            );
        }

        static public function getOptionArrayCart()
        {
            return array(
                self::FIXED                  => Mage::helper('rewardpoints')->__('Fixed Reward Points (X)'),
                self::FIXED_WHOLE_CART       => Mage::helper('rewardpoints')->__('Fixed Reward Points (X) for Whole Cart'),
                self::BUY_X_GET_Y            => Mage::helper('rewardpoints')->__('Spend Y Get X Reward Points'),
                //self::BUY_X_GET_Y  			    => Mage::helper('rewardpoints')->__('Buy X get Y Reward Points'),
                self::BUY_X_GET_Y_WHOLE_CART => Mage::helper('rewardpoints')->__('Spend Y Get X Reward Points for Whole Cart'),
            );
        }

        static public function getLabel($status)
        {
            $options = self::getOptionArray();

            return $options[$status];
        }
    }