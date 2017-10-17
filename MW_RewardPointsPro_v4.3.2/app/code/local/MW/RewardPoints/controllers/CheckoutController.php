<?php
    class MW_RewardPoints_CheckoutController extends Mage_Core_Controller_Front_Action
    {
        /**
         * Retrieve shopping cart model object
         *
         * @return Mage_Checkout_Model_Cart
         */
        protected function _getCart()
        {
            return Mage::getSingleton('checkout/cart');
        }

        /**
         * Get current active quote instance
         *
         * @return Mage_Sales_Model_Quote
         */
        protected function _getQuote()
        {
            return $this->_getCart()->getQuote();
        }

        /**
         * Get checkout session model instance
         *
         * @return Mage_Checkout_Model_Session
         */
        protected function _getSession()
        {
            return Mage::getSingleton('checkout/session');
        }

        protected function _getCustomer()
        {
            return Mage::getSingleton('customer/session')->getCustomer();
        }

        protected function _goBack()
        {
            if(!Mage::getStoreConfig('checkout/cart/redirect_to_cart')
                && !$this->getRequest()->getParam('in_cart')
                && $backUrl = $this->_getRefererUrl()
            )
            {

                $this->getResponse()->setRedirect($backUrl);
            }
            else
            {
                if(($this->getRequest()->getActionName() == 'add') && !$this->getRequest()->getParam('in_cart'))
                {
                    $this->_getSession()->setContinueShoppingUrl($this->_getRefererUrl());
                }
                $this->_redirect('checkout/cart');
            }

            return $this;
        }

        public function rewardpointspostAction()
        {
            $store_id = Mage::app()->getStore()->getId();
            $step     = Mage::helper('rewardpoints/data')->getPointStepConfig($store_id);
            if(!(Mage::helper('rewardpoints')->moduleEnabled()))
            {
                $this->norouteAction();

                return;
            }

            $rewardpoints = $this->getRequest()->getParam('rewardpoints');
            if($rewardpoints < 0)
            {
                $rewardpoints = -$rewardpoints;
            }

            $rewardpoints = round(($rewardpoints / $step), 0) * $step;
            if($rewardpoints >= 0)
            {
                Mage::helper('rewardpoints')->setPointToCheckOut($rewardpoints);
            }
            Mage::getSingleton('checkout/session')->getQuote()->collectTotals()->save();

            $result["html"]    = $this->_getReviewHtml();
            $result["mwpoint"] = Mage::getSingleton('checkout/session')->getQuote()->getMwRewardpoint();
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));

            return;
//    	$this->loadLayout();
//		$this->renderLayout();
        }

        protected function _getReviewHtml()
        {

            $layout = Mage::getModel('core/layout');
            $layout->getUpdate()->load('rewardpoints_checkout_rewardpointspost');
            $layout->generateXml();
            $layout->generateBlocks();

            return $layout->getOutput();
        }

        public function onepagepostAction()
        {
            $store_id = Mage::app()->getStore()->getId();
            $step     = Mage::helper('rewardpoints/data')->getPointStepConfig($store_id);
            if(!(Mage::helper('rewardpoints')->moduleEnabled()))
            {
                $this->norouteAction();

                return;
            }

            $rewardpoints = $this->getRequest()->getParam('rewardpoints');
            if($rewardpoints < 0)
            {
                $rewardpoints = -$rewardpoints;
            }
            $rewardpoints = round(($rewardpoints / $step), 0) * $step;
            if($rewardpoints >= 0)
            {
                Mage::helper('rewardpoints')->setPointToCheckOut($rewardpoints);
            }
            $this->_getSession()->getQuote()->collectTotals()->save();

            $this->loadLayout();
            $this->renderLayout();

        }

        public function rewardpointsAction()
        {
            $this->_getSession()->getQuote()->collectTotals()->save();
            //$this->getResponse()->setBody($this->_getSession()->getRewardPoints());
        }

        public function updaterulesAction()
        {
            $this->loadLayout();
            $this->renderLayout();
        }

        public function updateformrewardAction()
        {
            $this->_getSession()->getQuote()->collectTotals()->save();
            $this->loadLayout();
            $this->renderLayout();
        }

        public function couponPostAction()
        {
            $store_id = Mage::app()->getStore()->getId();
            if(!$this->_getCart()->getQuote()->getItemsCount())
            {
                $this->_goBack();

                return;
            }

            $couponCode = (string)$this->getRequest()->getParam('coupon_code');
            if($this->getRequest()->getParam('remove') == 1)
            {
                $couponCode = '';
            }
            $oldCouponCode = $this->_getQuote()->getCouponCode();

            if(!strlen($couponCode) && !strlen($oldCouponCode))
            {
                $this->_goBack();

                return;
            }

            try
            {
                $this->_getQuote()->getShippingAddress()->setCollectShippingRates(true);
                $this->_getQuote()->setCouponCode(strlen($couponCode) ? $couponCode : '')
                    ->collectTotals()
                    ->save();

                if($couponCode)
                {
                    //retrict other discount
                    if((Mage::getStoreConfig('rewardpoints/config/retrict_other_promotions') && $this->_getQuote()->getCouponCode() && $this->_getQuote()->getMwRewardpoint()))
                    {
                        $this->_getQuote()->setCouponCode("")->collectTotals()
                            ->save();
                        Mage::getSingleton('checkout/session')->addError(Mage::helper("rewardpoints")->__("You already use %s to checkout so you could not use other promotions", Mage::helper('rewardpoints')->getPointCurency($store_id)));
                    }
                    else
                    {
                        if($couponCode == $this->_getQuote()->getCouponCode())
                        {
                            $this->_getSession()->addSuccess(
                                $this->__('Coupon code "%s" was applied successfully.', Mage::helper('core')->htmlEscape($couponCode))
                            );
                        }
                        else
                        {
                            $this->_getSession()->addError(
                                $this->__('Coupon code "%s" is not valid.', Mage::helper('core')->htmlEscape($couponCode))
                            );
                        }
                    }
                }
                else
                {
                    $this->_getSession()->addSuccess($this->__('Coupon code was canceled successfully.'));
                }

            }
            catch (Mage_Core_Exception $e)
            {
                $this->_getSession()->addError($e->getMessage());
            }
            catch (Exception $e)
            {
                $this->_getSession()->addError($this->__('Can not apply coupon code.'));
            }

            $this->_goBack();
        }
    }