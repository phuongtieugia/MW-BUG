<?php
class MW_RewardPoints_Block_Adminhtml_System_Config_Rwprates extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
   public function _prepareToRender()
    {
        $this->addColumn('rwpratesfrom', array(
			'label' => Mage::helper('rewardpoints')->__('Redeem up from'),
            'style' => 'width:100px',
        ));
        $this->addColumn('rwpratesto', array(
            'label' => Mage::helper('rewardpoints')->__('Redeem up to'),
            'style' => 'width:100px',
        ));
		$this->addColumn('Rate', array(
            'label' => Mage::helper('rewardpoints')->__('You get'),
            'style' => 'width:100px',
        ));

        $this->_addAfter = false;
        $this->_addButtonLabel = Mage::helper('rewardpoints')->__('Add');
    }
}