<?php

require_once Mage::getModuleDir('controllers', 'Mage_Adminhtml').DS.'Sales'.DS.'Order'.DS.'EditController.php';
class MW_Ddate_Adminhtml_Sales_Order_EditController extends Mage_Adminhtml_Sales_Order_EditController
{
	/**
     * Override saving quote and create order
     */
    public function saveAction()
    {
        try {
            $this->_processActionData('save');
            $paymentData = $this->getRequest()->getPost('payment');
            if ($paymentData) {
                $paymentData['checks'] = Mage_Payment_Model_Method_Abstract::CHECK_USE_INTERNAL
                    | Mage_Payment_Model_Method_Abstract::CHECK_USE_FOR_COUNTRY
                    | Mage_Payment_Model_Method_Abstract::CHECK_USE_FOR_CURRENCY
                    | Mage_Payment_Model_Method_Abstract::CHECK_ORDER_TOTAL_MIN_MAX
                    | Mage_Payment_Model_Method_Abstract::CHECK_ZERO_TOTAL;
                $this->_getOrderCreateModel()->setPaymentData($paymentData);
                $this->_getOrderCreateModel()->getQuote()->getPayment()->addData($paymentData);
            }

            $order = $this->_getOrderCreateModel()
                ->setIsValidate(true)
                ->importPostData($this->getRequest()->getPost('order'))
                ->createOrder();
            
            // Get and check relation parent real ID
            if($order->getRelationParentRealId()) {
                // This is the old order ID if edit order
                $deliveryDateStoreCollection = Mage::getModel('ddate/ddate_store')->getCollection();
                $deliveryDateStoreCollection->addFieldToFilter('increment_id', array('eq' => $order->getRelationParentRealId()));
                foreach ($deliveryDateStoreCollection as $deliveryDateStore) {
                    // Update order status of delivery date store to cancelled
                    $deliveryDateStore->setData('order_status', Mage_Sales_Model_Order::STATE_CANCELED);
                    $deliveryDateStore->save();

                    // Create new delivery date store for new order
                    $dDateStoreOfNewOrder = array(
                        'increment_id' => $order->getIncrementId(),
                        'ddate_id' => $deliveryDateStore->getDdateId(),
                        'ddate_comment' => $deliveryDateStore->getDdateComment(),
                        'order_status' => $order->getStatus()
                    );
                    $deliveryDateStoreModel = Mage::getModel('ddate/ddate_store');
                    $deliveryDateStoreModel->saveDdateStore($dDateStoreOfNewOrder);

                    // Update ordered column of delivery date
                    $deliveryDateModel = Mage::getModel('ddate/ddate')->load($dDateStoreOfNewOrder['ddate_id']);
                    $deliveryDateModel->setData('ordered', (int) $deliveryDateModel->getOrdered() + 1);
                    $deliveryDateModel->save();
                    break;
                }
            }

            $this->_getSession()->clear();
            Mage::getSingleton('adminhtml/session')->addSuccess($this->__('The order has been created.'));
            if (Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/view')) {
                $this->_redirect('*/sales_order/view', array('order_id' => $order->getId()));
            } else {
                $this->_redirect('*/sales_order/index');
            }
        } catch (Mage_Payment_Model_Info_Exception $e) {
            $this->_getOrderCreateModel()->saveQuote();
            $message = $e->getMessage();
            if(!empty($message)) {
                $this->_getSession()->addError($message);
            }
            $this->_redirect('*/*/');
        } catch (Mage_Core_Exception $e) {
            $message = $e->getMessage();
            if(!empty($message)) {
                $this->_getSession()->addError($message);
            }
            $this->_redirect('*/*/');
        }
        catch (Exception $e) {
            $this->_getSession()->addException($e, $this->__('Order saving error: %s', $e->getMessage()));
            $this->_redirect('*/*/');
        }
    }
}
