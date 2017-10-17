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
    protected $_sectionUpdateFunctions = array(
        'payment-method'  => '_getPaymentMethodsHtml',
        'shipping-method' => '_getShippingMethodsHtml',
        'review'          => '_getReviewHtml',
        'ddate'           => '_getDdateHtml',
    );

	public function ddateAction()
    {
        if(!Mage::helper('ddate')->isOSCRunning()){
            $this->loadLayout(false);
            $this->renderLayout();
        }
    }

	public function savePaymentAction()
    {
        if(Mage::getModel('ddate/dtime')->getCollection()->count() > 0 && Mage::helper('ddate')->haveAnySlotAvailable()) {

            $this->_expireAjax();
            if ($this->getRequest()->isPost()) {
                $data = $this->getRequest()->getPost('payment', array());

                /*
                * first to check payment information entered is correct or not
                */
                try {
                    $result = $this->getOnepage()->savePayment($data);
                } catch (Mage_Payment_Exception $e) {
                    if ($e->getFields()) {
                        $result['fields'] = $e->getFields();
                    }
                    $result['error'] = $e->getMessage();
                } catch (Exception $e) {
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
        } else {
            if ($this->_expireAjax()) {
                return;
            }
            try {
                if (!$this->getRequest()->isPost()) {
                    $this->_ajaxRedirectResponse();
                    return;
                }

                $data = $this->getRequest()->getPost('payment', array());
                $result = $this->getOnepage()->savePayment($data);

                // get section and redirect data
                $redirectUrl = $this->getOnepage()->getQuote()->getPayment()->getCheckoutRedirectUrl();
                if (empty($result['error']) && !$redirectUrl) {
                    $this->loadLayout('checkout_onepage_review');
                    $result['goto_section'] = 'review';
                    $result['update_section'] = array(
                        'name' => 'review',
                        'html' => $this->_getReviewHtml()
                    );
                }
                if ($redirectUrl) {
                    $result['redirect'] = $redirectUrl;
                }
            } catch (Mage_Payment_Exception $e) {
                if ($e->getFields()) {
                    $result['fields'] = $e->getFields();
                }
                $result['error'] = $e->getMessage();
            } catch (Mage_Core_Exception $e) {
                $result['error'] = $e->getMessage();
            } catch (Exception $e) {
                Mage::logException($e);
                $result['error'] = $this->__('Unable to set Payment Method.');
            }
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        }
    }

	protected function _getReviewHtml()
    {
        return $this->getLayout()->getBlock('root')->toHtml();
    }

    protected function _getDdateHtml()
    {
    	$layout = $this->getLayout();
        $update = $layout->getUpdate();
        $update->load('checkout_onepage_ddate');
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
            if (!$result) {
				$this->loadLayout('checkout_onepage_review');
                $result['goto_section'] = 'review';
                $result['update_section'] = array(
                    'name' => 'review',
                    'html' => $this->_getReviewHtml()
                );
			}
            $this->getResponse()->setBody(Zend_Json::encode($result));
        }
    }

	public function findDtimeAction()
    {
        if ($this->getRequest()->isPost()) {
            $delay = Mage::getStoreConfig('ddate/info/delay');

			$post = $this->getRequest()->getPost('deliverydate','');
			$day = explode('>', $post);
			$temp1 = array("sun", "mon", "tue", "wed", "thu", "fri", "sat");
			$slot = Mage::getModel('ddate/dtime')
                    ->getCollection()
                    ->addFieldToFilter($temp1[$day[1]], array('eq' => 1))
					->addFieldToFilter('status', array('eq' => 1));
					
            $slot = $slot->getItems();
            if (count($slot)) {
                foreach($slot as $key => $sl) {
                    $interval = $sl->getInterval();
                    if (Mage::helper('ddate')->isAvailableDay($interval, $day[0]) === FALSE) {
                        unset($slot[$key]);
                    }
                };
            }

			if (count($slot)) {
				$html  = "";
				$html .= '<select id="ddate:dtime" size="1" name="ddate[dtime]">';
                $html .= '<option value="">' . Mage::helper('ddate')->__('Select Time') . '</option>';
				foreach($slot as $sl){
					$html .= '<option value="'.$sl->getDtimeId().'">'.$sl->getDtime().'</option>';
				};
				$html .= '</select>';

				echo $html;
                return;
			} else {
                echo Mage::helper('ddate')->__('There is no delivery time slot available for choosed day');
            }
        }
    }
}
