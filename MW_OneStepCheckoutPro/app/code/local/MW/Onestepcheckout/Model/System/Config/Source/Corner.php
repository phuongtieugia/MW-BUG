<?php
/**
 * Created by PhpStorm.
 * User: manhnt
 * Date: 10/15/14
 * Time: 10:42 AM
 */

class MW_Onestepcheckout_Model_System_Config_Source_Corner
{
    const ROUND_CORNER = 1;
    const NOT_CORNER  = 2;
    public function toOptionArray()
    {
        return array(
            array(
                'value' => MW_Onestepcheckout_Model_System_Config_Source_Corner::ROUND_CORNER,
                'label' => Mage::helper('onestepcheckout')->__('Enable')
            ),
            array(
                'value'=> MW_Onestepcheckout_Model_System_Config_Source_Corner::NOT_CORNER,
                'label'=>Mage::helper('onestepcheckout')->__('Disable')
            ),
        );
    }
}