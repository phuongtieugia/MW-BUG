<?php

class MW_Ddate_Block_Adminhtml_Dtime_Edit_Tab_Days extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form();
        $this->setForm($form);
        $fieldset = $form->addFieldset('days_form', array(
            'legend' => Mage::helper('ddate')->__('Days Delivery')
        ));
        
        $values = array(
            array(
                'value' => 0,
                'label' => Mage::helper('ddate')->__('No')
            ),
            array(
                'value' => 1,
                'label' => Mage::helper('ddate')->__('Yes')
            )
        );
        
        $fieldset->addField('mon', 'select', array(
            'label' => Mage::helper('ddate')->__('Monday'),
            'name' => 'mon',
            'values' => $values
        ));
        
        $fieldset->addField('tue', 'select', array(
            'label' => Mage::helper('ddate')->__('Tuesday'),
            'name' => 'tue',
            'values' => $values
        ));
        
        $fieldset->addField('wed', 'select', array(
            'label' => Mage::helper('ddate')->__('Wednesday'),
            'name' => 'wed',
            'values' => $values
        ));
        
        $fieldset->addField('thu', 'select', array(
            'label' => Mage::helper('ddate')->__('Thursday'),
            'name' => 'thu',
            'values' => $values
        ));
        
        $fieldset->addField('fri', 'select', array(
            'label' => Mage::helper('ddate')->__('Friday'),
            'name' => 'fri',
            'values' => $values
        ));
        
        $fieldset->addField('sat', 'select', array(
            'label' => Mage::helper('ddate')->__('Saturday'),
            'name' => 'sat',
            'values' => $values
        ));
        
        $fieldset->addField('sun', 'select', array(
            'label' => Mage::helper('ddate')->__('Sunday'),
            'name' => 'sun',
            'values' => $values
        ));
        
        $fieldset->addField('specialday', 'select', array(
            'label' => Mage::helper('ddate')->__('Special days'),
            'name' => 'specialday',
            'values' => $values
        ));
        
        if(Mage::getSingleton('adminhtml/session')->getDtimeData()) {
            $form->setValues(Mage::getSingleton('adminhtml/session')->getDtimeData());
            Mage::getSingleton('adminhtml/session')->setDtimeData(null);
        } elseif(Mage::registry('dtime_data')) {
            $form->setValues(Mage::registry('dtime_data')->getData());
        }

        return parent::_prepareForm();
    }
}
