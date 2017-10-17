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
require_once 'Mage/Checkout/controllers/OnepageController.php';

class MW_Ddate_Checkout_OnepageController extends Mage_Checkout_OnepageController
{	
	public function ddateAction()
    {        
        $this->loadLayout(false);
        $this->renderLayout();
    }
 	protected $_sectionUpdateFunctions = array(
        'payment-method'  => '_getPaymentMethodsHtml',
        'shipping-method' => '_getShippingMethodsHtml',
        'review'          => '_getReviewHtml',
 		'ddate'			  => '_getDdateHtml',
    );
	public function saveShippingMethodAction()
    {
        if ($this->_expireAjax()) {
            return;
        }
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost('shipping_method', '');
            $result = $this->getOnepage()->saveShippingMethod($data);
            // $result will contain error data if shipping method is empty
            if (!$result) {
                Mage::dispatchEvent(
                    'checkout_controller_onepage_save_shipping_method',
                     array(
                          'request' => $this->getRequest(),
                          'quote'   => $this->getOnepage()->getQuote()));
                $this->getOnepage()->getQuote()->collectTotals();
                $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
				
				$ship_list=unserialize(Mage::getStoreConfig('ddate/customcode/shiplist'));
				$sl=array();
				foreach ($ship_list as $ship){
					$sl[]= $ship['shipvalue'];
				};	
				if(in_array($data, $sl)){
					$result['goto_section'] = 'ddate'; 
					
				}else{
					$result['goto_section'] = 'payment';
					$result['update_section'] = array(
						'name' => 'payment-method',
						'html' => $this->_getPaymentMethodsHtml()
					);	
				}

                //$result['goto_section'] = 'ddate';
            }
            $this->getOnepage()->getQuote()->collectTotals()->save();
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        }
    }
	/* public function savePaymentAction()
    {
        $this->_expireAjax();
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost('payment', array());
            try {
                $result = $this->getOnepage()->savePayment($data);
            }
            catch (Mage_Payment_Exception $e) {
                if ($e->getFields()) {
                    $result['fields'] = $e->getFields();
                }
                $result['error'] = $e->getMessage();
            }
            catch (Exception $e) {
                $result['error'] = $e->getMessage();
            }
            $redirectUrl = $this->getOnePage()->getQuote()->getPayment()->getCheckoutRedirectUrl();
            if (empty($result['error']) && !$redirectUrl) {
            	
            	
                $result['goto_section'] = 'ddate';
		     	
            }
			
			
            if ($redirectUrl) {
                $result['redirect'] = $redirectUrl;
            }
            
            $this->getResponse()->setBody(Zend_Json::encode($result));
        }
    } */
		
	protected function _getReviewHtml()
    {
        return $this->getLayout()->getBlock('root')->toHtml();
    }
    
    protected function _getDdateHtml(){
    	$layout = $this->getLayout();
        $update = $layout->getUpdate();
        $update->load('checkout_onepage_ddate');
        $layout->generateXml();
        $layout->generateBlocks();
        $output = $layout->getOutput();
        return $output;
    }
	protected function _getPaymentMethodsHtml()
    {
        $layout = $this->getLayout();
        $update = $layout->getUpdate();
        $update->load('checkout_onepage_paymentmethod');
        $layout->generateXml();
        $layout->generateBlocks();
        $output = $layout->getOutput();
        return $output;
    }
    
    public function saveDdateAction()
    {
        $this->_expireAjax();
        if ($this->getRequest()->isPost()) {
        	$data = $this->getRequest()->getPost('ddate', '');
			$result = $this->getOnepage()->saveDdate($data);			
            if(!$result) {		
				/* $this->loadLayout('checkout_onepage_review');
                $result['goto_section'] = 'payment';
                $result['update_section'] = array(
                    'name' => 'payment-method',
                    'html' => $this->_getPaymentMethodsHtml()
                ); */
				Mage::dispatchEvent(
                    'checkout_controller_onepage_save_shipping_method',
                     array(
                          'request' => $this->getRequest(),
                          'quote'   => $this->getOnepage()->getQuote()));
                $this->getOnepage()->getQuote()->collectTotals();
                $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));

                $result['goto_section'] = 'payment';
                $result['update_section'] = array(
                    'name' => 'payment-method',
                    'html' => $this->_getPaymentMethodsHtml()
                );
			}
            $this->getResponse()->setBody(Zend_Json::encode($result));
			
        }

    }
		 public function findDtimeAction()
    {
        //$this->_expireAjax();
        if ($this->getRequest()->isPost()) {
			$post = $this->getRequest()->getPost('deliverydate','');
			$day= explode('>',$post);		
			 $temp1=array("sun","mon","tue","wed","thu","fri","sat");
			$slot = Mage::getModel('ddate/dtime')->getCollection()
			->addFieldToFilter($temp1[$day[1]],array('eq'=>1))
			;
			if(count($slot)){
				$html ="";		
				 $html=$html.'<select id="ddate:dtime" size="1" name="ddate[dtime]" >	'
				.'<option value="">Select Time</option>';
				foreach($slot as $sl){
					$html=$html.'<option value="'.$sl->getDtimeId().'">'.$sl->getDtime().'</option>';
				};
				$html=$html.'</select>';					
				echo $html; 
			return;	
			}else echo "There is no delivery time slot available for choosed day";
        }

    }

}

?>