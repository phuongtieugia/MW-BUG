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
 * @category   Mage
 * @package    Mage_Checkout
 * @copyright  Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

# Controllers are not autoloaded so we will have to do it manually:
require_once 'Mage/Checkout/controllers/MultishippingController.php';
class MW_Ddate_Checkout_MultishippingController extends Mage_Checkout_MultishippingController
{
	public function shippingPostAction()
    {
		if($this->missing_delivery_infor($this->getRequest()->getPost('ddate'))) {
			$this->_getCheckoutSession()->addError(
                Mage::helper('ddate')->__('One of multiple shipping address missed delivery date information')
            );
            $this->_redirect('*/*/shipping');
            return;
		}

        $shippingMethods = $this->getRequest()->getPost('shipping_method');

        try {
            Mage::dispatchEvent(
                'checkout_controller_multishipping_shipping_post',
                array(
                    'request'=>$this->getRequest(),
                    'quote'=>$this->_getCheckout()->getQuote()
                )
            );
            $this->_getCheckout()->setShippingMethods($shippingMethods);
            $this->_getState()->setActiveStep(
                Mage_Checkout_Model_Type_Multishipping_State::STEP_BILLING
            );
            $this->_getState()->setCompleteStep(
                Mage_Checkout_Model_Type_Multishipping_State::STEP_SHIPPING
            );
			Mage::getSingleton('customer/session')->setDdateinfo($this->getRequest()->getPost('ddate'));
            $this->_redirect('*/*/billing');
        }
        catch (Exception $e) {
            $this->_getCheckoutSession()->addError($e->getMessage());
            $this->_redirect('*/*/shipping');
        }
    }

	public function missing_delivery_infor($ddate)
    {
		foreach ($ddate as $date) {					
			if (empty($date)) {
                return true;
            }
		}
		return false;
	}
}
