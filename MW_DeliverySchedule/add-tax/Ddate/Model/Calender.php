<?php

class MW_Ddate_Model_Calender extends Varien_Object
{
    const CALENDER = 0;
    const DT_PICKER = 1;
    
    static public function toOptionArray()
    {
        return array(
            self::CALENDER => Mage::helper('ddate')->__('Calender'),
            self::DT_PICKER => Mage::helper('ddate')->__('Datetime Picker')
        );
    }
}
