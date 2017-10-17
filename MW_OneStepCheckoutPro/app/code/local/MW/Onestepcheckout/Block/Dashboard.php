<?php
/**
 * User: Anh TO
 * Date: 3/10/14
 * Time: 4:39 PM
 */

class MW_Onestepcheckout_Block_Dashboard extends Mage_Core_Block_Template{
    public function _construct()
    {
        $this->setTemplate('mw_onestepcheckout/daskboard.phtml');
    }
    public function _prepareLayout()
    {
        if(Mage::helper('onestepcheckout')->isDDateRunning())
        {
           $select= $this->getLayout()->createBlock('ddate/onepage_ddate')
                    ->setTemplate('ddate/checkout/onepage/ddate_osc.phtml')
                    ->setName('ddate')
                    ->setId('ddate')
                    ->setTitle('Delivery Times')
                    ->setClass('delivery mw-osc-block-content');
                    
           $this->setChild('ddate',$select);
        }
        
        return parent::_prepareLayout();
    }
    public function _toHtml()
    {
        $store_id = Mage::app()->getStore()->getStoreId();

        if (!Mage::getStoreConfig('onestepcheckout/config/enabled', $store_id))
            return '';
        if(Mage::getSingleton('core/session')->getOs() == 'change')
            return '';
        $html = $this->renderView();
        return $html;
    }
}