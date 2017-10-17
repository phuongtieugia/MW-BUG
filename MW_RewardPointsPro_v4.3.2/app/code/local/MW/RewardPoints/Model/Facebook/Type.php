<?php

    class MW_RewardPoints_Model_Facebook_Type extends Varien_Object
    {
        const XFBML  = 1;
        const IFRAME = 2;


        static public function toOptionArray()
        {
            return array(
                self::XFBML  => Mage::helper('rewardpoints')->__('XFBML'),
                self::IFRAME => Mage::helper('rewardpoints')->__('IFRAME'),
            );
        }
    }