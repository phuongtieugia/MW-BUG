<?php

class MW_Ddate_Block_Adminhtml_Dtime_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form();
        $this->setForm($form);
        $fieldset = $form->addFieldset('general_form', array(
            'legend' => Mage::helper('ddate')->__('General information')
        ));
        
        if (!Mage::app()->isSingleStoreMode()) {
            $fieldset->addField('store_id', 'multiselect', array(
                'name' => 'dtime_stores[]',
                'label' => Mage::helper('ddate')->__('Store View'),
                'title' => Mage::helper('ddate')->__('Store View'),
                'required' => true,
                'values' => Mage::getSingleton('adminhtml/system_store')->getStoreValuesForForm(false, true)
            ));
        }
        
        $fieldset->addField('dtime', 'text', array(
            'label' => Mage::helper('ddate')->__('Title'),
            'required' => true,
            'name' => 'dtime'
        ));
        
        $fieldset->addField('status', 'select', array(
            'label' => Mage::helper('ddate')->__('Is Active'),
            'name' => 'status',
            'values' => array(
                array(
                    'value' => 1,
                    'label' => Mage::helper('ddate')->__('Enabled')
                ),
                array(
                    'value' => 2,
                    'label' => Mage::helper('ddate')->__('Disabled')
                )
            )
        ));
        
        $fieldset->addField('maximum_booking', 'text', array(
            'label' => Mage::helper('ddate')->__('Max Deliveries Per Slot'),
            'name' => 'maximum_booking',
            'required' => true
        ));
        
        $fieldset->addField('special_day', 'text', array(
            'label' => Mage::helper('ddate')->__('Special Day'),
            'name' => 'special_day',
            'class' => 'validate_special_day',
            'note' => Mage::helper('ddate')->__('Example: 2011-11-09;12-08')
        ));
        
        $fieldset->addField('interval', 'text', array(
            'label' => Mage::helper('ddate')->__('Active Time'),
            'name' => 'interval',
            'class' => 'validate-time-interval',
            'note' => Mage::helper('ddate')->__('Example: 8:00-14:30'),
            'required' => true
        ));

        $fieldset->addField('dtimesort', 'text', array(
            'label' => Mage::helper('ddate')->__('Sorting'),
            'name' => 'dtimesort',
            'class' => 'text',
            'required' => true
        ));
		$fieldset->addField('dtime_tax', 'text', array(
			'label' => Mage::getStoreConfig("ddate/info/dtax"),
            'name' => 'dtime_tax',
            'class' => 'text',
            'required' => false
        ));
        
        if (Mage::getSingleton('adminhtml/session')->getDtimeData()) {
            $form->setValues(Mage::getSingleton('adminhtml/session')->getDtimeData());
            Mage::getSingleton('adminhtml/session')->setCategoryData(null);
        } elseif (Mage::registry('dtime_data')) {
            $form->setValues(Mage::registry('dtime_data')->getData());
            $catc = Mage::registry('dtime_data');

            if (!Mage::app()->isSingleStoreMode()) {
                if ($catc->getDtimeId()) {
                    // get array of selected store_id 
                    $collection = Mage::getModel('ddate/dtime')->getCollection();
                    $collection->getSelect()->join(
                        array(
                            'mwdtime_store' => $collection->getTable('dtime_store')
                        ),
                        'mwdtime_store.dtime_id = main_table.dtime_id AND main_table.dtime_id = ' . $catc->getDtimeId(),
                        array(
                            'mwdtime_store.store_id'
                        )
                    );
                    
                    if ($collection->getData()) {
                        $arrStoreId = array();
                        foreach ($collection->getData() as $col) {
                            $arrStoreId[] = $col['store_id'];
                        }
                        // set value for store view selected:
                        $form->getElement('store_id')->setValue($arrStoreId);
                    }
                }
            }
        }

        if (!$form->getElement("maximum_booking")->hasValue()) {
            $form->getElement("maximum_booking")->setValue(Mage::getStoreConfig('ddate/info/maximum_bookings'));
        }

        return parent::_prepareForm();
    }
}
