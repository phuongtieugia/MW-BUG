<?php
class MW_Ddate_Block_Config_Postcodespecial
    extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    public function _prepareToRender()
    {
        $this->addColumn('postcodespecial', array(
			'label' => Mage::helper('ddate')->__('Post code'),
            'style' => 'width:100px',
        ));
		$this->addColumn('slotid', array(
            'label' => Mage::helper('ddate')->__('Slot ID'),
            'renderer' => $this->_getRenderer(),
        ));
        

        $this->_addAfter = false;
		$this->_addButtonLabel = Mage::helper('ddate')->__('Add Postcode Special');
    }
	protected function  _getRenderer() 
    {
        if (!$this->_itemRenderer) {
            $this->_itemRenderer = $this->getLayout()->createBlock(
                'ddate/config_timeslot', '',
                array('is_render_to_js_template' => true)
            );
        }
        return $this->_itemRenderer;
    }
	protected function _prepareArrayRow(Varien_Object $row)
    {
        $row->setData(
            'option_extra_attr_' . $this->_getRenderer()
                ->calcOptionHash($row->getData('slotid')),
            'selected="selected"'
        );
    }
}