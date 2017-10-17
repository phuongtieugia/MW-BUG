<?php

    class MW_RewardPoints_Model_Statusrule extends Varien_Object
    {
        const ENABLED = 1; //haven't change points yet
        const DISABLED = 2;


        static public function getOptionArray()
        {
            return array(
                self::ENABLED  => Mage::helper('rewardpoints')->__('Enabled'),
                self::DISABLED => Mage::helper('rewardpoints')->__('Disabled'),
            );
        }

        static public function getLabel($status)
        {
            $options = self::getOptionArray();

            return $options[$status];
        }
    }