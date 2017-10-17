<?php

class MW_Ddate_Block_Adminhtml_Ddate_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
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
        $fieldset = $form->addFieldset('ddate_form', array('legend'=>Mage::helper('ddate')->__('Item information')));

        $fieldset->addField('ddate', 'date', array(
            'label'     => Mage::helper('ddate')->__('Delivery Date'),
            'class'     => 'required-entry',
            'required'  => true,
            'name'      => 'ddate',
            'image'     => $this->getSkinUrl('images/grid-cal.gif'),
            'format'    => Mage::helper('ddate')->php_date_format_M("-"),
            'disabled'  => true,
        ));

        $fieldset->addField('dtimetext', 'text', array(
            'label'     => Mage::helper('ddate')->__('Delivery Time'),
            'name'      => 'dtimetext',          
            'disabled'  => true,
        ));

        $fieldset->addField('ordered', 'text', array(			
            'label'     => Mage::helper('ddate')->__('Bookings'),
            'class'     => 'required-entry',
            'required'  => true,
            'name'      => 'ordered',
            'disabled'  => true,
        ));

        if(Mage::helper('ddate')->getDayoff()){
            $fieldset->addField('holiday', 'select', array(
                'label'     => Mage::helper('ddate')->__('Day off'),
                'name'      => 'holiday',
                'values'    => array(
                    array(
                        'value'     => 0,
                        'label'     => Mage::helper('ddate')->__('No'),
                    ),
                    array(
                        'value'     => 1,
                        'label'     => Mage::helper('ddate')->__('Yes'),
                    ),
                ),
            ));
        }

        if ( Mage::getSingleton('adminhtml/session')->getDdateData()) {
            $form->setValues(Mage::getSingleton('adminhtml/session')->getDdateData());
            Mage::getSingleton('adminhtml/session')->setDdateData(null);
        } elseif ( Mage::registry('ddate_data') ) {
            $form->setValues(Mage::registry('ddate_data')->getData());
            $ddate = Mage::registry('ddate_data');

            if($ddate->getDtime()){
                $dtime = Mage::getModel('ddate/dtime')->getCollection();
                $dtime->getSelect()
                    ->where("main_table.dtime like '".$ddate->getDtime()."'")
                    ->where("main_table.status=1");

                if(count($dtime->getData())>0){
                    foreach($dtime as $d){	
                        $form->getElement('dtime')->setValue($d['dtime_id']);
                    }
                }
            }
        }

        return parent::_prepareForm();
    }

    protected function _prepareLayout()
    {
        $this->setChild('grid', $this->getLayout()->createBlock('ddate/adminhtml_ddate_edit_tab_order','ddate.grid.order'));

        return parent::_prepareLayout();
    }
}
