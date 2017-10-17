<?php
/**
 * User: Anh TO
 * Date: 7/18/14
 * Time: 4:49 PM
 */

class MW_RewardPoints_Block_Adminhtml_System_Config_Redship extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $this->setElement($element);
        return parent::_getElementHtml($element)."<script type='text/javascript'>var mwRWPCaltax = new MW.RewardPoint.SystemConfig.Caltax(mwCaltax_config)</script>";
    }
}