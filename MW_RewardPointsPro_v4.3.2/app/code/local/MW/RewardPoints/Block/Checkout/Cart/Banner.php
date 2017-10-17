<?php
    class MW_RewardPoints_Block_Checkout_Cart_Banner extends Mage_Core_Block_Template
    {
        public function _construct()
        {
            $this->setTemplate('mw_rewardpoints/checkout/cart/banner.phtml');
        }

        public function _prepareLayout()
        {
            return parent::_prepareLayout();
        }

        public function getBannerRules()
        {
            $rule_banner   = array();
            $quote         = Mage::getSingleton('checkout/session')->getQuote();
            $_rule_details = unserialize($quote->getMwRewardpointRuleMessage());
            foreach ($_rule_details as $_rule_detail)
            {
                $detail = '';
                $detail = Mage::getModel('rewardpoints/cartrules')->load($_rule_detail)->getPromotionImage();
                if($detail != '')
                {
                    $rule_banner[] = $detail;
                }
            }

            return $rule_banner;
        }

        public function _toHtml()
        {
            $store_id = Mage::app()->getStore()->getId();
            if(!(Mage::helper('rewardpoints/data')->moduleEnabled()) || !sizeof($this->getBannerRules()) || !(Mage::helper('rewardpoints/data')->getEnablePromotionBanner($store_id)))
            {
                return '';
            }
            if(!sizeof($this->getBannerRules()))
            {
                return '';
            }
            $html = $this->renderView();

            return $html;
        }

        public function resizeImg($fileName, $width, $height = '', $folderResized = "resized")
        {
            $folderURL = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA);
            $imageURL  = $folderURL . $fileName;

            $basePath = Mage::getBaseDir(Mage_Core_Model_Store::URL_TYPE_MEDIA) . DS . $fileName;
            $newPath  = Mage::getBaseDir(Mage_Core_Model_Store::URL_TYPE_MEDIA) . DS . $folderResized . '/' . $fileName;
            if($width != '')
            {
                $imageObj = new Varien_Image($basePath);
                $imageObj->constrainOnly(true);
                $imageObj->keepAspectRatio(false);
                $imageObj->keepFrame(false);
                $imageObj->resize($width, $height);
                $imageObj->save($newPath);
                $resizedURL = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . $folderResized . '/' . $fileName;
            }

            return $resizedURL;
        }
    }