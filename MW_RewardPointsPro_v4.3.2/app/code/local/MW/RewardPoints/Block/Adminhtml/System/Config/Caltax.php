<?php
/**
 * User: Anh TO
 * Date: 7/18/14
 * Time: 3:54 PM
 */

class MW_RewardPoints_Block_Adminhtml_System_Config_Caltax extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $this->setElement($element);
        $html = '<select id="'.$element->getHtmlId().'" name="'.$element->getName().'" class="select">';
        foreach(MW_RewardPoints_Model_Redeemtax::toOptionArray() as $value => $option_name)
        {
            $html .= '<option value="'.$value.'" '.($element->getValue() == $value ? 'selected="selected"' : '').'>'.$option_name.'</option>';
        }
        return $html."<script type='text/javascript'>var mwCaltax_config = {element: '".$element->getHtmlId()."', BEFORE_VALUE: '".MW_RewardPoints_Model_Redeemtax::BEFORE."', AFTER_VALUE: '".MW_RewardPoints_Model_Redeemtax::AFTER."'};</script>";
    }
}