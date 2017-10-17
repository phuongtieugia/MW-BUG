<?php
    class MW_RewardPoints_Block_Checkout_Cart_Message extends Mage_Core_Block_Template
    {
        public function _construct()
        {
            $this->setTemplate('mw_rewardpoints/checkout/cart/message.phtml');
        }

        public function _prepareLayout()
        {
            return parent::_prepareLayout();
        }

        public function getMessageRules()
        {
            $rule_message  = array();
            $quote         = Mage::getSingleton('checkout/session')->getQuote();
            $_rule_details = unserialize($quote->getMwRewardpointRuleMessage());
            foreach ($_rule_details as $_rule_detail)
            {
                $detail = '';
                $detail = Mage::getModel('rewardpoints/cartrules')->load($_rule_detail)->getPromotionMessage();
                if($detail != '')
                {
                    $rule_message[] = Mage::helper('rewardpoints')->__('%s', $detail);
                }
            }

            return $rule_message;
        }

        public function _toHtml()
        {
            $store_id = Mage::app()->getStore()->getId();
            if(!(Mage::helper('rewardpoints/data')->moduleEnabled()) || !sizeof($this->getMessageRules()) || !(Mage::helper('rewardpoints/data')->getEnablePromotionMessage($store_id)))
            {
                return '';
            }

            $html = $this->renderView();

            return $html;
        }
    }