<?php

    class MW_RewardPoints_Model_Mysql4_Rewardpointshistory_Redeemed_Collection extends MW_RewardPoints_Model_Mysql4_Rewardpointshistory_Collection
    {
        public function _construct()
        {
            parent::_construct();
            $this->_init('rewardpoints/rewardpointshistory');
        }

        public function setDateRange($from, $to)
        {
            $resource           = Mage::getModel('core/resource');
            $order_table        = $resource->getTableName('sales/order');
            $reward_order_table = $resource->getTableName('rewardpoints/rewardpointsorder');

            $this->_reset()->addFieldToFilter('main_table.transaction_time', array('from' => $from, 'to' => $to, 'datetime' => true));
            $this->addFieldToFilter('main_table.status', MW_RewardPoints_Model_Status::COMPLETE);
            $this->addFieldToFilter('status_check', array('neq' => MW_RewardPoints_Model_Status::REFUNDED));
            $this->addFieldToFilter('main_table.type_of_transaction', MW_RewardPoints_Model_Type::USE_TO_CHECKOUT);
            $this->getSelect()->joinLeft(
                array('reward_order_entity' => $reward_order_table), 'main_table.history_order_id = reward_order_entity.order_id', array('money'));

            $this->getSelect()->joinLeft(
                array('order_entity' => $order_table), 'main_table.history_order_id = order_entity.entity_id', array('base_grand_total'));

            $this->addExpressionFieldToSelect('total_redeemed_sum', 'sum(amount)', 'total_redeemed_sum');
            $this->addExpressionFieldToSelect('order_id_count', 'count( distinct if(history_order_id != 0,history_order_id,null))', 'order_id_count');
            $this->addExpressionFieldToSelect('avg_redeemed_per_order', 'sum(amount)/count( distinct if(history_order_id != 0 && status_check != '.MW_RewardPoints_Model_Status::REFUNDED.',history_order_id,null))', 'avg_redeemed_per_order');
            $this->addExpressionFieldToSelect('total_point_discount_sum', 'sum(money)', 'total_point_discount_sum');
            $this->addExpressionFieldToSelect('total_sales_sum', 'sum(base_grand_total)', 'total_sales_sum');

            return $this;
        }

        public function setStoreIds($storeIds)
        {
            return $this;
        }
    }