<?php
/**
 * User: Anh TO
 * Date: 8/27/14
 * Time: 8:43 AM
 */

class MW_Onestepcheckout_Model_System_Config_Source_Style
{
    const STYLE_BASIC = 1;
    const STYLE_FLAT  = 2;
    const STYLE_CLASSIC = 3;
    public function toOptionArray()
    {
        return array(
            array(
                'value'=> MW_Onestepcheckout_Model_System_Config_Source_Style::STYLE_FLAT,
                'label'=>Mage::helper('onestepcheckout')->__('Flat')
            ),
            array(
                'value'=> MW_Onestepcheckout_Model_System_Config_Source_Style::STYLE_CLASSIC,
                'label'=>Mage::helper('onestepcheckout')->__('Classic')
            ),
        );
    }
}