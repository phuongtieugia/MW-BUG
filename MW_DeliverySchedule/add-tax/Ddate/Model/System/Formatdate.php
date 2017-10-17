<?php

class MW_Ddate_Model_System_Formatdate
{
    public function toOptionArray()
    {
        return array(
            array('value'=>'mdY', 'label'=>Mage::helper('adminhtml')->__('mm-dd-yyyy')),
            array('value'=>'dmY', 'label'=>Mage::helper('adminhtml')->__('dd-mm-yyyy')),
            array('value'=>'Ymd', 'label'=>Mage::helper('adminhtml')->__('yyyy-mm-dd')),
        );
    }
}
