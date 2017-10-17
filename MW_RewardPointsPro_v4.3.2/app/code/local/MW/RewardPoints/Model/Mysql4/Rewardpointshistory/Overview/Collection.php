<?php

    class MW_RewardPoints_Model_Mysql4_Rewardpointshistory_Overview_Collection extends MW_RewardPoints_Model_Mysql4_Rewardpointshistory_Collection
    {
        protected $use_type = array(1, 2, 3, 4, 5, 6, 14, 8, 30, 15, 16, 12, 18, 21, 32, 25, 29, 26, 27, 19, 50, 51, 52, 53);
        protected $group_signup = array(1);
        protected $group_review = array(2);
        protected $group_order = array(3, 8, 30);
        protected $group_birthday = array(26);
        protected $group_newsletter = array(16);
        protected $group_tag = array();
        protected $group_social = array();
        protected $group_referal = array(4, 5, 6, 14);
        protected $group_other = array(25, 29, 15, 12, 18, 21, 32, 27, 19, 50, 51, 53, 52);

        public function _construct()
        {
            parent::_construct();
            $this->_init('rewardpoints/rewardpointshistory');
        }

        public function setDateRange($from, $to)
        {
            $type_add_point      = MW_RewardPoints_Model_Type::getAddPointArray();
            $type_subtract_point = MW_RewardPoints_Model_Type::getSubtractPointArray();
            //zend_debug::dump($type_add_point);die();
            $this->_reset()->addFieldToFilter('transaction_time', array('from' => $from, 'to' => $to, 'datetime' => true));
            $this->addFieldToFilter('status', MW_RewardPoints_Model_Status::COMPLETE);
            $this->addFieldToFilter('status_check', array('neq' => MW_RewardPoints_Model_Status::REFUNDED));
            //$this ->addFieldToFilter('type_of_transaction',array('in'=>array($type_subtract_point)));

            $this->addExpressionFieldToSelect('total_rewarded_sum', 'sum(if(type_of_transaction IN ('.implode(",", $this->use_type).'),amount,0))', 'total_rewarded_sum');
            $this->addExpressionFieldToSelect('total_redeemed_sum', 'sum(if(type_of_transaction IN ('.MW_RewardPoints_Model_Type::USE_TO_CHECKOUT.') && status_check != '.MW_RewardPoints_Model_Status::REFUNDED.',amount,0))', 'total_redeemed_sum');
            $this->addExpressionFieldToSelect('sign_up_count', 'count(distinct if(type_of_transaction IN (1),history_id,null))', 'sign_up_count');
            $this->addExpressionFieldToSelect('order_id_count', 'count(distinct if(type_of_transaction IN (6,14,8,11,30) and history_order_id != 0,history_order_id,null))', 'order_id_count');
            $this->addExpressionFieldToSelect('customer_id_count', 'count( distinct customer_id)', 'customer_id_count');

            //$this->getSelect()->group(array('customer_id'));
            // echo $this->getSelect();die();
            return $this;
        }

        public function setDateRange123($from, $to)
        {
            $this->_reset()->addFieldToFilter('transaction_time', array('from' => $from, 'to' => $to, 'datetime' => true));
            $this->addFieldToFilter('status', MW_RewardPoints_Model_Status::COMPLETE);
            $this->addExpressionFieldToSelect('product_id_count', 'count(product_id)', 'product_id_count');
            $this->addExpressionFieldToSelect('customer_id_count', 'count( distinct customer_id)', 'customer_id_count');
            $this->addExpressionFieldToSelect('order_id_count', 'count( distinct order_id)', 'order_id_count');
            $this->addExpressionFieldToSelect('total_amount_sum', 'sum(total_amount)', 'total_amount_sum');
            $this->addExpressionFieldToSelect('history_commission_sum', 'sum(history_commission)', 'history_commission_sum');
            $this->addExpressionFieldToSelect('history_discount_sum', 'sum(history_discount)', 'history_discount_sum');
            $this->getSelect()->group(array('customer_id'));

            return $this;
        }

        public function setStoreIds($storeIds)
        {
            return $this;
        }
    }