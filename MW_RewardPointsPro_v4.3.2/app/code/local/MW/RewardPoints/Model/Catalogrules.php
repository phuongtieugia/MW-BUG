<?php
//class MW_RewardPoints_Model_Catalogrules extends Mage_Rule_Model_Abstract
    class MW_RewardPoints_Model_Catalogrules extends Mage_Core_Model_Abstract
//class MW_RewardPoints_Model_Catalogrules extends Mage_Rule_Model_Rule Mage_Rule_Model_Abstract
    {
        const XML_NODE_RELATED_CACHE = 'global/catalogrule/related_cache_types';
        protected $_conditions;
        protected $_actions;
        protected $_form;

        /**
         * Is model deleteable
         *
         * @var boolean
         */
        protected $_isDeleteable = true;

        /**
         * Is model readonly
         *
         * @var boolean
         */
        protected $_isReadonly = false;
        /**
         * Prefix of model events names
         *
         * @var string
         */
        protected $_eventPrefix = 'catalogrule_rule';

        /**
         * Parameter name in event
         *
         * In observe method you can use $observer->getEvent()->getRule() in this case
         *
         * @var string
         */
        protected $_eventObject = 'rule';

        /**
         * Matched product ids array
         *
         * @var array
         */
        protected $_productIds;

        protected $_now;

        /**
         * Cached data of prices calculated by price rules
         *
         * @var array
         */
        protected static $_priceRulesData = array();

        /**
         * Init resource model and id field
         */
        protected function _construct()
        {
            parent::_construct();
            $this->_init('rewardpoints/catalogrules');
            $this->setIdFieldName('rule_id');
        }

        public function getConditionsInstance()
        {
            return Mage::getModel('catalogrule/rule_condition_combine');
        }

        public function _resetConditions($conditions = null)
        {
            if(is_null($conditions))
            {
                $conditions = $this->getConditionsInstance();
            }
            $conditions->setRule($this)->setId('1')->setPrefix('conditions');
            $this->setConditions($conditions);

            return $this;
        }

        public function setConditions($conditions)
        {
            $this->_conditions = $conditions;

            return $this;
        }

        public function getConditions()
        {
            if(empty($this->_conditions))
            {
                $this->_resetConditions();
            }

            return $this->_conditions;
        }

        public function getActionsInstance()
        {
            return Mage::getModel('catalogrule/rule_action_collection');
        }

        public function _resetActions($actions = null)
        {
            if(is_null($actions))
            {
                $actions = $this->getActionsInstance();
            }
            $actions->setRule($this)->setId('1')->setPrefix('actions');
            $this->setActions($actions);

            return $this;
        }

        public function setActions($actions)
        {
            $this->_actions = $actions;

            return $this;
        }

        public function getActions()
        {
            if(!$this->_actions)
            {
                $this->_resetActions();
            }

            return $this->_actions;
        }

        public function getForm()
        {
            if(!$this->_form)
            {
                $this->_form = new Varien_Data_Form();
            }

            return $this->_form;
        }

        public function loadPost(array $rule)
        {
            $arr = $this->_convertFlatToRecursive($rule);
            if(isset($arr['conditions']))
            {
                $this->getConditions()->setConditions(array())->loadArray($arr['conditions'][1]);
            }
            if(isset($arr['actions']))
            {
                $this->getActions()->setActions(array())->loadArray($arr['actions'][1]);
            }

            return $this;
        }

        protected function _convertFlatToRecursive(array $rule)
        {
            $arr = array();
            foreach ($rule as $key => $value)
            {
                if(($key === 'conditions' || $key === 'actions') && is_array($value))
                {
                    foreach ($value as $id => $data)
                    {
                        $path = explode('--', $id);
                        $node =& $arr;
                        for ($i = 0, $l = sizeof($path); $i < $l; $i++)
                        {
                            if(!isset($node[$key][$path[$i]]))
                            {
                                $node[$key][$path[$i]] = array();
                            }
                            $node =& $node[$key][$path[$i]];
                        }
                        foreach ($data as $k => $v)
                        {
                            $node[$k] = $v;
                        }
                    }
                }
                else
                {
                    /**
                     * convert dates into Zend_Date
                     */
                    if(in_array($key, array('from_date', 'to_date')) && $value)
                    {
                        $value = Mage::app()->getLocale()->date(
                            $value,
                            Varien_Date::DATE_INTERNAL_FORMAT,
                            null,
                            false
                        );
                    }
                    $this->setData($key, $value);
                }
            }

            return $arr;
        }

        public function asArray(array $arrAttributes = array())
        {
            $out = array(
                'name'        => $this->getName(),
                'start_at'    => $this->getStartAt(),
                'expire_at'   => $this->getExpireAt(),
                'description' => $this->getDescription(),
                'conditions'  => $this->getConditions()->asArray(),
                'actions'     => $this->getActions()->asArray(),
            );

            return $out;
        }

        public function validate(Varien_Object $object)
        {
            return $this->getConditions()->validate($object);
        }

        public function afterLoad()
        {
            $this->_afterLoad();
        }

        protected function _afterLoad()
        {
            parent::_afterLoad();
            $conditionsArr = unserialize($this->getConditionsSerialized());
            if(!empty($conditionsArr) && is_array($conditionsArr))
            {
                $this->getConditions()->loadArray($conditionsArr);
            }

            $actionsArr = unserialize($this->getActionsSerialized());
            if(!empty($actionsArr) && is_array($actionsArr))
            {
                $this->getActions()->loadArray($actionsArr);
            }
            /*
            $websiteIds = $this->_getData('website_ids');
            if (is_string($websiteIds)) {
                $this->setWebsiteIds(explode(',', $websiteIds));
            }
            $groupIds = $this->getCustomerGroupIds();
            if (is_string($groupIds)) {
                $this->setCustomerGroupIds(explode(',', $groupIds));
            }*/
        }

        /**
         * Prepare data before saving
         *
         * @return Mage_Rule_Model_Rule
         */
        protected function _beforeSave()
        {
            // check if discount amount > 0
            /* if ((int)$this->getDiscountAmount() < 0) {
                 Mage::throwException(Mage::helper('rule')->__('Invalid discount amount.'));
             }*/


            if($this->getConditions())
            {
                $this->setConditionsSerialized(serialize($this->getConditions()->asArray()));
                $this->unsConditions();
            }
            if($this->getActions())
            {
                $this->setActionsSerialized(serialize($this->getActions()->asArray()));
                $this->unsActions();
            }
            /*
            $this->_prepareWebsiteIds();

            if (is_array($this->getCustomerGroupIds())) {
                $this->setCustomerGroupIds(join(',', $this->getCustomerGroupIds()));
            }
            */
            parent::_beforeSave();
        }

        /*
            public function getMage::getSingleton('core/date')->gmtDate()
            {
                if (!$this->_now) {
                    return Mage::getSingleton('core/date')->gmtDate();
                }
                return $this->_now;
            }

            public function setNow($now)
            {
                $this->_now = $now;
            }


            public function toString($format='')
            {
                $str = Mage::helper('catalogrule')->__("Name: %s", $this->getName()) ."\n"
                     . Mage::helper('catalogrule')->__("Start at: %s", $this->getStartAt()) ."\n"
                     . Mage::helper('catalogrule')->__("Expire at: %s", $this->getExpireAt()) ."\n"
                     . Mage::helper('catalogrule')->__("Customer Registered: %s", $this->getCustomerRegistered()) ."\n"
                     . Mage::helper('catalogrule')->__("Customer is a New Buyer: %s", $this->getCustomerNewBuyer()) ."\n"
                     . Mage::helper('catalogrule')->__("Description: %s", $this->getDescription()) ."\n\n"
                     . $this->getConditions()->toStringRecursive() ."\n\n"
                     . $this->getActions()->toStringRecursive() ."\n\n";
                return $str;
            }
        */
        /**
         * Returns rule as an array for admin interface
         *
         * Output example:
         * array(
         *   'name'=>'Example rule',
         *   'conditions'=>{condition_combine::toArray}
         *   'actions'=>{action_collection::toArray}
         * )
         *
         * @return array
         */
        public function toArray(array $arrAttributes = array())
        {
            $out                        = parent::toArray($arrAttributes);
            $out['customer_registered'] = $this->getCustomerRegistered();
            $out['customer_new_buyer']  = $this->getCustomerNewBuyer();

            return $out;
        }

        /**
         * Invalidate related cache types
         *
         * @return Mage_CatalogRule_Model_Rule
         */
        protected function _invalidateCache()
        {
            $types = Mage::getConfig()->getNode(self::XML_NODE_RELATED_CACHE);
            if($types)
            {
                $types = $types->asArray();
                Mage::app()->getCacheInstance()->invalidateType(array_keys($types));
            }

            return $this;
        }

        /**
         * Process rule related data after rule save
         *
         * @return Mage_CatalogRule_Model_Rule
         */
//    protected function _afterSave()
//    {
//        $this->_getResource()->updateRuleProductData($this);
//        parent::_afterSave();
//    }

        /**
         * Get array of product ids which are matched by rule
         *
         * @return array
         */
        /*
        public function getMatchingProductIds()
        {
            if (is_null($this->_productIds)) {
                $this->_productIds = array();
                $this->setCollectedAttributes(array());
                $websiteIds = explode(',', $this->getWebsiteIds());

                if ($websiteIds) {
                    $productCollection = Mage::getResourceModel('catalog/product_collection');

                    $productCollection->addWebsiteFilter($websiteIds);
                    $this->getConditions()->collectValidatedAttributes($productCollection);

                    Mage::getSingleton('core/resource_iterator')->walk(
                        $productCollection->getSelect(),
                        array(array($this, 'callbackValidateProduct')),
                        array(
                            'attributes' => $this->getCollectedAttributes(),
                            'product'    => Mage::getModel('catalog/product'),
                        )
                    );
                }
            }

            return $this->_productIds;
        }
    */
        /**
         * Callback function for product matching
         *
         * @param $args
         * @return void
         */
        public function callbackValidateProduct($args)
        {
            $product = clone $args['product'];
            $product->setData($args['row']);

            if($this->getConditions()->validate($product))
            {
                $this->_productIds[] = $product->getId();
            }
        }

        /**
         * Apply rule to product
         *
         * @param int|Mage_Catalog_Model_Product $product
         * @param array $websiteIds
         * @return void
         */
        public function applyToProduct($product, $websiteIds = null)
        {
            if(is_numeric($product))
            {
                $product = Mage::getModel('catalog/product')->load($product);
            }
            if(is_null($websiteIds))
            {
                $websiteIds = explode(',', $this->getWebsiteIds());
            }
            $this->getResource()->applyToProduct($this, $product, $websiteIds);
        }

        /**
         * Get array of assigned customer group ids
         *
         * @return array
         */
        /* public function getCustomerGroupIds()
         {
             $ids = $this->getData('customer_group_ids');
             if (($ids && !$this->getCustomerGroupChecked()) || is_string($ids)) {
                 if (is_string($ids)) {
                     $ids = explode(',', $ids);
                 }

                 $groupIds = Mage::getModel('customer/group')->getCollection()->getAllIds();
                 $ids = array_intersect($ids, $groupIds);
                 $this->setData('customer_group_ids', $ids);
                 $this->setCustomerGroupChecked(true);
             }
             return $ids;
         }
         */
        /**
         * Apply all price rules, invalidate related cache and refresh price index
         *
         * @return Mage_CatalogRule_Model_Rule
         */
        public function applyAll()
        {
            $this->_getResource()->applyAllRulesForDateRange();
            $this->_invalidateCache();
            $indexProcess = Mage::getSingleton('index/indexer')->getProcessByCode('catalog_product_price');
            if($indexProcess)
            {
                $indexProcess->reindexAll();
            }
        }

        /**
         * Apply all price rules to product
         *
         * @param int|Mage_Catalog_Model_Product $product
         * @return Mage_CatalogRule_Model_Rule
         */
        public function applyAllRulesToProduct($product)
        {
            $this->_getResource()->applyAllRulesForDateRange(null, null, $product);
            $this->_invalidateCache();
            $indexProcess = Mage::getSingleton('index/indexer')->getProcessByCode('catalog_product_price');
            if($indexProcess)
            {
                $indexProcess->reindexAll();
            }
        }

        /**
         * Calculate price using catalog price rule of product
         *
         * @param Mage_Catalog_Model_Product $product
         * @param float $price
         * @return float|null
         */
        public function calcProductPriceRule(Mage_Catalog_Model_Product $product, $price)
        {
            $priceRules      = null;
            $productId       = $product->getId();
            $storeId         = $product->getStoreId();
            $websiteId       = Mage::app()->getStore($storeId)->getWebsiteId();
            $customerGroupId = null;
            if($product->hasCustomerGroupId())
            {
                $customerGroupId = $product->getCustomerGroupId();
            }
            else
            {
                $customerGroupId = Mage::getSingleton('customer/session')->getCustomerGroupId();
            }
            $dateTs   = Mage::app()->getLocale()->storeTimeStamp($storeId);
            $cacheKey = date('Y-m-d', $dateTs) . "|$websiteId|$customerGroupId|$productId|$price";

            if(!array_key_exists($cacheKey, self::$_priceRulesData))
            {
                $rulesData = $this->_getResource()->getRulesFromProduct($dateTs, $websiteId, $customerGroupId, $productId);
                if($rulesData)
                {
                    foreach ($rulesData as $ruleData)
                    {
                        $priceRules = Mage::helper('catalogrule')->calcPriceRule(
                            $ruleData['simple_action'],
                            $ruleData['discount_amount'],
                            $priceRules ? $priceRules : $price);
                        if($ruleData['stop_rules_processing'])
                        {
                            break;
                        }
                    }

                    return self::$_priceRulesData[$cacheKey] = $priceRules;
                }
                else
                {
                    self::$_priceRulesData[$cacheKey] = null;
                }
            }
            else
            {
                return self::$_priceRulesData[$cacheKey];
            }

            return null;
        }

        public function getPointCatalogRulue($product_id)
        {
            $store_id = Mage::app()->getStore()->getId();
            //$product_rewardpoint = (int)Mage::helper('rewardpoints/data')->getEnableProductRewardPointStore($store_id);
            //if(!$product_rewardpoint) return 0;
            $reward_point_rule      = 0;
            $reward_point_attribute = 0;
            $rule_id                = 0;
            $product_points         = Mage::getModel('rewardpoints/productpoint')->getCollection()->addFieldToFilter('product_id', $product_id);
            if(sizeof($product_points) > 0)
            {
                foreach ($product_points as $product_point)
                {
                    $rule_id      = $product_point->getRuleId();
                    $reward_point = (int)$product_point->getRewardPoint();
                    if($rule_id != 0)
                    {
                        $ruleModel = Mage::getModel('rewardpoints/catalogrules')->load($rule_id);
                        $check_enable         = $this->checkCatalogRulesByEnable($ruleModel);
                        $check_time           = $this->checkCatalogRulesByTime($ruleModel);
                        $check_store_view     = $this->checkCatalogRulesStoreView($ruleModel);
                        $check_customer_group = $this->checkCatalogRulesCustomerGroup($ruleModel);
                        if($check_enable && $check_time && $check_store_view && $check_customer_group)
                        {
                            $reward_point_rule = $reward_point_rule + $reward_point;
                        }
                    }
                }

            }
            if(Mage::getModel('catalog/product')->load($product_id)->getData('reward_point_product'))
            {
                $reward_point_attribute = (int)Mage::getModel('catalog/product')->load($product_id)->getData('reward_point_product');
            }
            if($reward_point_attribute)
            {
                return $reward_point_attribute;
            }
            else
            {
                return $reward_point_rule;
            }

        }

        public function checkCatalogRulesByTime($ruleModel)
        {
            if(Mage::app()->getLocale()->isStoreDateInInterval(null, $ruleModel->getStartDate(), $ruleModel->getEndDate()))
            {
                return true;
            }
            else
            {
                return false;
            }
        }

        public function checkCatalogRulesByEnable($ruleModel)
        {
            if($ruleModel->getStatus() == MW_RewardPoints_Model_Statusrule::ENABLED)
            {
                return true;
            }
            else
            {
                return false;
            }

        }

        public function checkCatalogRulesStoreView($ruleModel)
        {
            $store_id    = Mage::app()->getStore()->getId();
            $store_view  = $ruleModel->getStoreView();
            $store_views = explode(',', $store_view);
            if(in_array($store_id, $store_views) OR $store_views[0] == '0')
            {
                return true;
            }
            else
            {
                return false;
            }

        }

        public function checkCatalogRulesCustomerGroup($ruleModel)
        {
            $login = Mage::getSingleton('customer/session')->isLoggedIn();
            if($login)
            {
                $group_id = Mage::getSingleton('customer/session')->getCustomerGroupId();
            }
            else
            {
                // check for create on admin
                /*$_cus = Mage::getSingleton('adminhtml/session_quote')->getCustomer();
                if($_cus){
                    $group_id = $_cus['group_id'];
                }*/
                $group_id = 0;
            }

            $customer_group  = $ruleModel->getCustomerGroupIds();
            $customer_groups = explode(',', $customer_group);
            if(in_array($group_id, $customer_groups))
            {
                return true;
            }
            else
            {
                return false;
            }

        }

    }