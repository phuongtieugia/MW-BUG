<?php

class MW_Ddate_Block_Adminhtml_Pickup_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('ddate/info.phtml');
    }

    protected function _prepareForm()
    {
        $form = new Varien_Data_Form();
        $this->setForm($form);
        $fieldset = $form->addFieldset('pickup_form', array(
            'legend' => Mage::helper('ddate')->__('Item information')
        ));

        $fieldset->addField('ddate', 'date', array(
            'label' => Mage::helper('ddate')->__('Delivery Date'),
            'class' => 'required-entry',
            'required' => true,
            'name' => 'ddate',
            'image' => $this->getSkinUrl('images/grid-cal.gif'),
            'format' => 'yyyy-M-d'
        ));

        $slots = Mage::getModel('ddate/dtime')->getCollection();
        $slots->getSelect()->where('main_table.status=1');
        $slot_times = array();
        foreach ($slots as $slot) {
            $slot_times[$slot->getDtimeId()] = $slot->getDtime();
        }
        
        $fieldset->addField('dtime', 'select', array(
            'label' => Mage::helper('ddate')->__('Delivery Time'),
            'name' => 'dtime',
            'values' => $slot_times
        ));
        
        $fieldset->addField('ordered', 'text', array(
            'label' => Mage::helper('ddate')->__('Bookings'),
            'class' => 'required-entry',
            'required' => true,
            'name' => 'ordered'
        ));

        if (Mage::helper('ddate')->getDayoff()) {
            $fieldset->addField('holiday', 'select', array(
                'label' => Mage::helper('ddate')->__('Day off'),
                'name' => 'holiday',
                'values' => array(
                    array(
                        'value' => 0,
                        'label' => Mage::helper('ddate')->__('No')
                    ),
                    array(
                        'value' => 1,
                        'label' => Mage::helper('ddate')->__('Yes')
                    )
                )
            ));
        }
        
        if (Mage::getSingleton('adminhtml/session')->getPickupData()) {
            $form->setValues(Mage::getSingleton('adminhtml/session')->getPickupData());
            Mage::getSingleton('adminhtml/session')->setPickupData(null);
        } elseif (Mage::registry('pickup_data')) {
            $form->setValues(Mage::registry('pickup_data')->getData());
            $pickup = Mage::registry('pickup_data');
            if ($pickup->getDtime()) {
                $dtime = Mage::getModel('ddate/dtime')->getCollection();
                $dtime->getSelect()->where("main_table.dtime like '" . $pickup->getDtime() . "'")->where("main_table.status=1");
                if (count($dtime->getData()) > 0) {
                    foreach ($dtime as $d) {
                        $form->getElement('dtime')->setValue($d['dtime_id']);
                    }
                }
            }
        }

        return parent::_prepareForm();
    }

    protected function _prepareLayout()
    {
        return parent::_prepareLayout();
    }
}
