<?php
/**
 * Created by PhpStorm.
 * User: manhnt
 * Date: 10/15/14
 * Time: 11:39 AM
 */
class MW_Onestepcheckout_Model_System_Config_Source_Heightbar
{
    const THIN = 1;
    const NORMAL  = 2;
    const BIGGER  = 3;
    const BIGGEST  = 4;
    public function toOptionArray()
    {
        return array(
            array(
                'value' => MW_Onestepcheckout_Model_System_Config_Source_Heightbar::THIN,
                'label' => Mage::helper('onestepcheckout')->__('Thin')
            ),
            array(
                'value'=> MW_Onestepcheckout_Model_System_Config_Source_Heightbar::NORMAL,
                'label'=>Mage::helper('onestepcheckout')->__('Normal')
            ),
            array(
                'value' => MW_Onestepcheckout_Model_System_Config_Source_Heightbar::BIGGER,
                'label' => Mage::helper('onestepcheckout')->__('Bigger')
            ),
            array(
                'value'=> MW_Onestepcheckout_Model_System_Config_Source_Heightbar::BIGGEST,
                'label'=>Mage::helper('onestepcheckout')->__('Biggest')
            )
        );
    }
}