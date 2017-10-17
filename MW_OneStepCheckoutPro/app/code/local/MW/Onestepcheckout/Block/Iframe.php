<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Paypal
 * @copyright   Copyright (c) 2012 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * HSS iframe block
 *
 * @category   Mage
 * @package    Mage_Paypal
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class MW_Onestepcheckout_Block_Iframe extends Mage_Paypal_Block_Iframe
{
    /**
     * Whether the block should be eventually rendered
     *
     * @var bool
     */
    protected $_shouldRender = false;

    /**
     * Order object
     *
     * @var Mage_Sales_Model_Order
     */
    protected $_order;

    /**
     * Payment method code
     *
     * @var string
     */
    protected $_paymentMethodCode;

    /**
     * Current iframe block instance
     *
     * @var Mage_Payment_Block_Form
     */
    protected $_block;

    /**
     * Internal constructor
     * Set info template for payment step
     *
     */
    protected function _construct()
    {
        parent::_construct();
        $paymentCode = $this->_getCheckout()
            ->getQuote()
            ->getPayment()
            ->getMethod();
        if (in_array($paymentCode, $this->helper('paypal/hss')->getHssMethods())) {
            $this->_paymentMethodCode = $paymentCode;
            $templatePath = str_replace('_', '', $paymentCode);
            
            $store = Mage::app()->getStore();           
            if(Mage::getStoreConfig('onestepcheckout/config/enabled',$store->getId())==1) 				 
				   $templateFile = "mw_onestepcheckout/paypal/{$templatePath}/iframe.phtml"; 
            else			  
                $templateFile = "paypal/{$templatePath}/iframe.phtml";			 
           
            if (file_exists(Mage::getDesign()->getTemplateFilename($templateFile))) {
                $this->setTemplate($templateFile);
            } else {
            	
            	if(Mage::getStoreConfig('onestepcheckout/config/enabled',$store->getId())==1)					
                           $this->setTemplate('mw_onestepcheckout/paypal/hss/iframe.phtml');					
                else					
                           $this->setTemplate('paypal/hss/iframe.phtml');
					
            }
        }
    }

   
}
