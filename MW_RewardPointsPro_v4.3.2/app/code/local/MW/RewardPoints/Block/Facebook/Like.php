<?php
    class MW_RewardPoints_Block_Facebook_Like extends Mage_Core_Block_Template
    {
        public function getCustomerId()
        {
            return Mage::getSingleton('customer/session')->getCustomer()->getId();
        }

        public function getEnable()
        {
            $store_id = Mage::app()->getStore()->getId();

            return Mage::helper('rewardpoints/data')->getFacebookLikeEnable($store_id);
        }

        public function getType()
        {
            $store_id = Mage::app()->getStore()->getId();

            return Mage::helper('rewardpoints/data')->getFacebookLikeType($store_id);
        }

        public function getSiteName()
        {
            $store_name = '';
            $store_id   = Mage::app()->getStore()->getId();
            $store_name = Mage::getStoreConfig('general/store_information/name', $store_id);

            //return Mage::helper('rewardpoints/data')->getFacebookLikeSiteName($store_id);
            return $store_name;
        }

        public function getAppId()
        {
            $store_id = Mage::app()->getStore()->getId();

            return Mage::helper('rewardpoints/data')->getFacebookLikeAppId($store_id);
        }

        public function getShowSend()
        {
            return true;
            /*
            $store_id = Mage::app()->getStore()->getId();
            $show_send = (int)Mage::helper('rewardpoints/data')->getFacebookSend($store_id);
            if($show_send) return true;
            return false;
            */
        }

        public function getLang()
        {
            //$store_id = Mage::app()->getStore()->getId();
            //$lang = '';
            //$lang = Mage::helper('rewardpoints/data')->getFacebookLikeLang($store_id);
            //if($lang == '') $lang = 'en_US';
            $lang = 'en_US';

            return $lang;
        }

        public function getCurrentUrl()
        {
            //$url = 'http://dev17.good-demo.com/electronics/cell-phones/nokia-2610-phone.html';
            $url = Mage::helper('core/url')->getCurrentUrl();

            return $url;
        }

        public function getEncodedUrl()
        {
            //$url = 'http://dev17.good-demo.com/electronics/cell-phones/nokia-2610-phone.html';
            $url = Mage::helper('core/url')->getCurrentUrl();

            return urlencode($url);
        }

        public function getIconFace()
        {
            return Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA).'mw_rewardpoint/mw-icon-facebook.png';
        }
    }