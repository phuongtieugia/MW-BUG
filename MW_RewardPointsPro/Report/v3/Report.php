<?php
    /**
     * User: Anh TO
     * Date: 8/6/14
     * Time: 11:53 AM
     */

    class MW_RewardPoints_Model_Report extends Mage_Core_Model_Abstract
    {
        protected $all_months = 0;
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
        public function prepareCollection($data)
        {
            $resource           = Mage::getModel('core/resource');
            $reward_order_table = $resource->getTableName('rewardpoints/rewardpointsorder');
            $customer_table = $resource->getTableName('rewardpoints/customer');

            if($data['report_range'] == MW_RewardPoints_Model_Admin_Const::REPORT_RAGE_CUSTOM)
            {
                if($this->_validationDate($data) == false)
                {
                    return;
                }
                /** Get all month between two dates */
                $this->all_months = $this->_get_months( $data['from'], $data['to']);
            }
            $users = array();
            $collection = Mage::getModel('customer/customer')->getCollection();
            foreach($collection->getData() as $user)
            {
                $users[] = $user['entity_id'];
            }

            /** Query to get total balance of customers */
            $collection = Mage::getModel('rewardpoints/customer')->getCollection();
            $collection->removeAllFieldsFromSelect();
            $collection->addFieldToFilter('customer_id', array('in' => $users));
            $collection->addExpressionFieldToSelect('total_point', 'SUM(mw_reward_point)', 'total_point');
            $collection_customer = $collection->getFirstItem();

            $collection = Mage::getModel('rewardpoints/rewardpointshistory')->getCollection();

            /** Query to get redeemd */
            $collection->removeAllFieldsFromSelect();
            $collection->addFieldToFilter('main_table.status', MW_RewardPoints_Model_Status::COMPLETE);
            $collection->addFieldToFilter('main_table.status_check', array('neq' => MW_RewardPoints_Model_Status::REFUNDED));
            $collection->addFieldToFilter('main_table.type_of_transaction', MW_RewardPoints_Model_Type::USE_TO_CHECKOUT);
            $collection->getSelect()->joinLeft(array('reward_order_entity' => $reward_order_table), 'main_table.history_order_id = reward_order_entity.order_id', array());
            $collection->addExpressionFieldToSelect('total_redeemed_sum', 'SUM(amount)', 'total_redeemed_sum');
			
			$total_redeemed_sum = $collection->getFirstItem();

            $this->_buildCollection($collection, $data);

            $collection_redeemd = $collection;


            /** Query to get reward */
            $collection = Mage::getModel('rewardpoints/rewardpointshistory')->getCollection();

            $collection->removeAllFieldsFromSelect();
            $collection->addFieldToFilter('main_table.status', MW_RewardPoints_Model_Status::COMPLETE);
            $collection->addFieldToFilter('main_table.status_check', array('neq' => MW_RewardPoints_Model_Status::REFUNDED));
            $collection->addExpressionFieldToSelect('total_rewarded_sum', 'sum(if(type_of_transaction IN ('.implode(",", $this->use_type).'),amount,0))', 'total_rewarded_sum');
			
			$_total_rewarded_sum = $collection->getFirstItem();

            $this->_buildCollection($collection, $data);
            $collection_reward = $collection;
            /** Query to statistic */
            $collection = Mage::getModel('rewardpoints/rewardpointshistory')->getCollection();

            $collection->removeAllFieldsFromSelect();
            $collection->addFieldToFilter('main_table.status', MW_RewardPoints_Model_Status::COMPLETE);
            $collection->addFieldToFilter('main_table.status_check', array('neq' => MW_RewardPoints_Model_Status::REFUNDED));
            $collection->addExpressionFieldToSelect('total_rewarded_sum', 'sum(if(type_of_transaction IN ('.implode(",", $this->use_type).'),amount,0))', 'total_rewarded_sum');
            $collection->addExpressionFieldToSelect('total_rewarded_on_order_sum', 'sum(if(type_of_transaction IN ('.implode(",", $this->group_order).'),amount,0))', 'total_rewarded_on_order_sum');
            $collection->addExpressionFieldToSelect('total_redeemed_sum', 'sum(if(type_of_transaction IN ('.MW_RewardPoints_Model_Type::USE_TO_CHECKOUT.') && status_check != '.MW_RewardPoints_Model_Status::REFUNDED.',amount,0))', 'total_redeemed_sum');
            $collection->addExpressionFieldToSelect('avg_reward_per_customer', 'sum(if(type_of_transaction IN ('.implode(",", $this->use_type).'),amount,0))/count( distinct if(customer_id != 0 && type_of_transaction NOT IN ('.MW_RewardPoints_Model_Type::USE_TO_CHECKOUT.', 17) && status_check != '.MW_RewardPoints_Model_Status::REFUNDED.', customer_id,null))', 'avg_redeemed_per_customer');
            $collection->addExpressionFieldToSelect('avg_redeemed_per_order', 'sum(if(type_of_transaction IN ('.MW_RewardPoints_Model_Type::USE_TO_CHECKOUT.'),amount,0))/count( distinct if(history_order_id != 0 && type_of_transaction = '.MW_RewardPoints_Model_Type::USE_TO_CHECKOUT.' && status_check != '.MW_RewardPoints_Model_Status::REFUNDED.', history_order_id, null))', 'avg_redeemed_per_order');
            $collection->addExpressionFieldToSelect('avg_rewarded_per_order', 'sum(if(type_of_transaction IN ('.implode(",", $this->group_order).'),amount,0))/count( distinct if(history_order_id != 0 && status_check != '.MW_RewardPoints_Model_Status::REFUNDED.' && type_of_transaction != '.MW_RewardPoints_Model_Type::USE_TO_CHECKOUT.',history_order_id,null))', 'avg_rewarded_per_order');
            $collection->addExpressionFieldToSelect('total_order', 'count( distinct if(history_order_id != 0 && status_check != '.MW_RewardPoints_Model_Status::REFUNDED.',history_order_id,null))', 'total_order');
			
			$_collection = $collection->getFirstItem();
			
			//var_dump($_collection->getData() );die;
			
			
            $this->_buildCollection($collection, $data, false);
            $collection_stats = $collection;
            /** Query to get number of orders */
            $collection = Mage::getModel('rewardpoints/rewardpointshistory')->getCollection();

            $collection->getSelect()->reset(Zend_Db_Select::COLUMNS);

            $collection->getSelect()->columns('count( distinct if(history_order_id != 0, history_order_id, null)) as total_orders');
            $collection->addFieldToFilter('main_table.status', MW_RewardPoints_Model_Status::COMPLETE);
            $collection->addFieldToFilter('main_table.status_check', array('neq' => MW_RewardPoints_Model_Status::REFUNDED));

            $collection->addFieldToFilter('main_table.history_order_id', array('gt' => 0));
            $this->_buildCollection($collection, $data);

            $collection_order = $collection;

            switch($data['report_range'])
            {
                case MW_RewardPoints_Model_Admin_Const::REPORT_RAGE_LAST_24H:
                    $_time = $this->getPreviousDateTime(24);
                    $start_24h_time = Mage::helper('core')->formatDate(date('Y-m-d h:i:s', $_time), 'medium', true);
                    $start_24h_time = strtotime($start_24h_time);
                    $start_time = array(
                        'h'   => (int)date('H', $start_24h_time),
                        'd'   => (int)date('d', $start_24h_time),
                        'm'   => (int)date('m', $start_24h_time),
                        'y'   => (int)date('Y', $start_24h_time),
                    );
                    $rangeDate = $this->_buildArrayDate(MW_RewardPoints_Model_Admin_Const::REPORT_RAGE_LAST_24H, $start_time['h'], $start_time['h'] + 24, $start_time);

                    $_data = $this->_buildResult($collection_redeemd, $collection_reward, $collection_order, 'hour', $rangeDate);
                    $_data['report']['date_start'] = $start_time;
                    break;
                case MW_RewardPoints_Model_Admin_Const::REPORT_RAGE_LAST_WEEK:
                    $start_time = strtotime("-6 day", strtotime("Sunday Last Week"));
                    $startDay = date('d', $start_time);
                    $endDay = date('d',strtotime("Sunday Last Week"));
                    $rangeDate = $this->_buildArrayDate(MW_RewardPoints_Model_Admin_Const::REPORT_RAGE_LAST_WEEK, $startDay, $rangeDate);
                    $_data = $this->_buildResult($collection_redeemd, $collection_reward, $collection_order, 'day', $rangeDate);
                    $_data['report']['date_start'] = array(
                        'd'   => (int)date('d', $start_time),
                        'm'   => (int)date('m', $start_time),
                        'y'   => (int)date('Y', $start_time),
                    );

                    break;
                case MW_RewardPoints_Model_Admin_Const::REPORT_RAGE_LAST_MONTH:
                    $last_month_time = strtotime($this->_getLastMonthTime());
                    $last_month = date('m', $last_month_time);
                    $start_day = 1;
                    $end_day = $this->_days_in_month($last_month);
                    $rangeDate = $this->_buildArrayDate(MW_RewardPoints_Model_Admin_Const::REPORT_RAGE_LAST_MONTH, $start_day, $end_day);

                    $_data = $this->_buildResult($collection_redeemd, $collection_reward, $collection_order, 'day', $rangeDate);
                    $_data['report']['date_start'] = array(
                        'd'   => $start_day,
                        'm'   => (int)$last_month,
                        'y'   => (int)date('Y', $last_month_time),
                        'total_day' => $end_day
                    );

                    break;
                case MW_RewardPoints_Model_Admin_Const::REPORT_RAGE_LAST_7DAYS:
                case MW_RewardPoints_Model_Admin_Const::REPORT_RAGE_LAST_30DAYS:
                    if($data['report_range'] == MW_RewardPoints_Model_Admin_Const::REPORT_RAGE_LAST_7DAYS)
                    {
                        $last_x_day = 7;
                    }
                    else if($data['report_range'] == MW_RewardPoints_Model_Admin_Const::REPORT_RAGE_LAST_30DAYS)
                    {
                        $last_x_day = 30;
                    }

                    $start_day = date('Y-m-d h:i:s', strtotime('-'.$last_x_day.' day', Mage::getModel('core/date')->gmtTimestamp()));
                    $end_day = date('Y-m-d h:i:s', strtotime("-1 day"));

                    $original_time = array(
                        'from'  => $start_day,
                        'to'    => $end_day
                    );
                    $rangeDate = $this->_buildArrayDate(MW_RewardPoints_Model_Admin_Const::REPORT_RAGE_CUSTOM, 0, 0, $original_time);

                    $_data = $this->_buildResult($collection_redeemd, $collection_reward, $collection_order, 'multiday', $rangeDate, $original_time);
                    break;
                case MW_RewardPoints_Model_Admin_Const::REPORT_RAGE_CUSTOM:
                    $original_time = array(
                        'from'  => $data['from'],
                        'to'    => $data['to']
                    );
                    $rangeDate = $this->_buildArrayDate(MW_RewardPoints_Model_Admin_Const::REPORT_RAGE_CUSTOM, 0, 0, $original_time);

                    $_data = $this->_buildResult($collection_redeemd, $collection_reward, $collection_order, 'multiday', $rangeDate, $original_time);
                    break;
            }
            $_data['title'] = Mage::helper('rewardpoints')->__('Rewarded / Redeemed Points');

            $_data['report_activities'] = $this->preapareCollectionPieChart($data);
            foreach($collection_stats->getFirstItem()->getData() as $key => $stat)
            {
                $_data['statistics'][$key] = ($stat == null) ? 0 : number_format($stat, 0, '.', ',');
            }
			//var_dump($_data);die;

            $_data['statistics']['total_point_customer'] =  number_format($collection_customer->getData('total_point'), 0, '.', ',');
            //$_data['statistics']['total_redeemed_sum'] =  number_format($total_redeemed_sum->getData('total_redeemed_sum'), 0, '.', ',');
            //$_data['statistics']['total_rewarded_sum'] =  number_format($_total_rewarded_sum->getData('total_rewarded_sum'), 0, '.', ',');
            return json_encode($_data);
        }
        public function preapareCollectionPieChart($data)
        {
            if($data['report_range'] == MW_RewardPoints_Model_Admin_Const::REPORT_RAGE_CUSTOM)
            {
                if($this->_validationDate($data) == false)
                {
                    return;
                }
                /** Get all month between two dates */
                $this->all_months = $this->_get_months( $data['from'], $data['to']);
            }

            /** Query to get total rewards */
            $collection = Mage::getModel('rewardpoints/rewardpointshistory')->getCollection();

            $collection->removeAllFieldsFromSelect();
            $collection->addFieldToFilter('main_table.status', MW_RewardPoints_Model_Status::COMPLETE);
            $collection->addFieldToFilter('main_table.status_check', array('neq' => MW_RewardPoints_Model_Status::REFUNDED));
            $collection->addExpressionFieldToSelect('total_rewarded_sum', 'sum(if(type_of_transaction IN ('.implode(",", $this->use_type).'),amount,0))', 'total_rewarded_sum');
            $this->_buildCollection($collection, $data, false);

            $collection_reward = $collection;

            /** Query to get total rewards per type */
            $collection = Mage::getModel('rewardpoints/rewardpointshistory')->getCollection();

            $collection->removeAllFieldsFromSelect();

            $collection->getSelect()->reset(Zend_Db_Select::COLUMNS);
            $collection->addFieldToSelect('type_of_transaction');

            $collection->addFieldToFilter('main_table.status', MW_RewardPoints_Model_Status::COMPLETE);
            $collection->addFieldToFilter('main_table.status_check', array('neq' => MW_RewardPoints_Model_Status::REFUNDED));

            $collection->addFieldToFilter('main_table.type_of_transaction', array('neq' => MW_RewardPoints_Model_Type::USE_TO_CHECKOUT));
            $collection->addExpressionFieldToSelect('total_rewarded_sum', 'sum(if(type_of_transaction IN ('.implode(",", $this->use_type).'),amount,0))', 'total_rewarded_sum');
            $collection->getSelect()->group(array('type_of_transaction'));
            $this->_buildCollection($collection, $data);
            $total_rewarded_sum = $collection_reward->getFirstItem()->getData('total_rewarded_sum');
            $data = array();
            foreach($this->use_type as $type)
            {
                $text = $this->_returnTextType($type);
                $data[$type] = array(Mage::helper('rewardpoints')->__($text), 0);
            }

            foreach($collection as $item)
            {
                if(in_array($item->getData('type_of_transaction'), $this->group_signup))
                {
                    $_data['signup'] += $item->getData('total_rewarded_sum');
                }

                if(in_array($item->getData('type_of_transaction'), $this->group_order))
                {
                    $_data['purchase'] += $item->getData('total_rewarded_sum');
                }

                if(in_array($item->getData('type_of_transaction'), $this->group_birthday))
                {
                    $_data['birthday'] += $item->getData('total_rewarded_sum');
                }

                if(in_array($item->getData('type_of_transaction'), $this->group_referal))
                {
                    $_data['referal'] += $item->getData('total_rewarded_sum');
                }

                if(in_array($item->getData('type_of_transaction'), $this->group_newsletter))
                {
                    $_data['newsletter'] += $item->getData('total_rewarded_sum');
                }

                if(in_array($item->getData('type_of_transaction'), $this->group_review))
                {
                    $_data['review'] += $item->getData('total_rewarded_sum');
                }

                if(in_array($item->getData('type_of_transaction'), $this->group_tag))
                {
                    $_data['tags'] += $item->getData('total_rewarded_sum');
                }

                if(in_array($item->getData('type_of_transaction'), $this->group_social))
                {
                    $_data['social'] += $item->getData('total_rewarded_sum');
                }

                if(in_array($item->getData('type_of_transaction'), $this->group_other))
                {
                    $_data['others'] += $item->getData('total_rewarded_sum');
                }
            }

            $data = array();
            foreach($_data as $key => $value)
            {
                $percent = $value/$total_rewarded_sum * 100;
                if($percent > 0.1)
                {
                    $data[]= array(Mage::helper('rewardpoints')->__(ucfirst($key)), $percent);
                }
            }

            return json_encode($data);
        }
        public function prepareCollectionMostUserPoint()
        {
            /**
             * Get the resource model
             */
            $resource = Mage::getSingleton('core/resource');

            /**
             * Retrieve the read connection
             */
            $readConnection = $resource->getConnection('core_read');

            $query = "
                SELECT
                    rwc.mw_reward_point, customer_id, @curRank := @curRank + 1 AS rank
                FROM ".$resource->getTableName('rewardpoints/customer')." AS rwc
                LEFT JOIN ".$resource->getTableName('customer/entity')." AS ce ON rwc.customer_id = ce.entity_id, (SELECT @curRank := 0) r
                WHERE ce.entity_id > 0
                ORDER BY mw_reward_point DESC
                LIMIT 0, 5";

            /**
             * Execute the query and store the results in $results
             */
            $results = $readConnection->fetchAll($query);

            return $results;
        }
        protected function _buildResult($collection_redeemd, $collection_reward, $collection_order, $type, $rangeDate, $original_time = null)
        {
            //var_dump($collection_reward->getData());die;
            $_data = array();
            try
            {
                if($type == 'multiday')
                {
                    foreach($rangeDate as $year => $months)
                    {
                        foreach($months as $month => $days)
                        {
                            foreach($days as $day)
                            {
                                $_data['report']['redeemed'][$year."-".$month."-".$day]  = array($year, $month, $day, 0);
                            }
                            foreach($days as $day)
                            {
                                $_data['report']['rewarded'][$year."-".$month."-".$day]  = array($year, $month, $day, 0);
                            }

                            foreach($collection_redeemd->getData() as $redeemd)
                            {
                                if($redeemd["month"] == $month)
                                {
                                    foreach($days as $day)
                                    {

                                        if($redeemd["day"] == $day)
                                        {
                                            $_data['report']['redeemed'][$year."-".$month."-".$day]  = array($year, $month, $day, (int)$redeemd["total_redeemed_sum"]);
                                        }
                                    }
                                }
                            }
                            foreach($collection_reward->getData() as $reward)
                            {
                                if($reward["month"] == $month)
                                {
                                    foreach($days as $day)
                                    {
                                        if($reward["day"] == $day)
                                        {
                                            $_data['report']['rewarded'][$year."-".$month."-".$day]  = array($year, $month, $day, (int)$reward["total_rewarded_sum"]);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                else
                {
                    switch($type )
                    {
                        case 'hour':
                            $rangeTempDate = reset($rangeDate);
                            $i = $rangeTempDate['incr_hour'];
                            break;
                        case 'day':
                            $rangeTempDate = reset($rangeDate);
                            $i = $rangeTempDate['count_day'];
                            break;
                    }

                    foreach($rangeDate as $date)
                    {
                        switch($type )
                        {
                            case 'hour':
                                $count = $date['native_hour'];
                                break;
                            case 'day':
                                $count = $date['native_day'];
                                break;
                        }

                        $_data['report']['redeemed'][$i] = 0;
                        $_data['report']['rewarded'][$i] = 0;
                        $_data['report']['order'][$i] = 0;

                        foreach($collection_redeemd->getData() as $redeemd)
                        {
                            if((int)$redeemd->{"get$type"}() == $count)
                            {
                                if(isset($date['day']) && $date['day'] == (int)$redeemd['day'])
                                {
                                    $_data['report']['redeemed'][$i] = (int)$redeemd['total_redeemed_sum'];
                                }
                                else if(!isset($date['day']))
                                {
                                    $_data['report']['redeemed'][$i] = (int)$redeemd['total_redeemed_sum'];
                                }
                            }
                        }

                        foreach($collection_reward->getData() as $reward)
                        {
                            if((int)$reward->{"get$type"}() == $count)
                            {
                                if(isset($date['day']) && $date['day'] == (int)$reward['day'])
                                {
                                    $_data['report']['rewarded'][$i] = (int)$reward['total_rewarded_sum'] ;
                                }
                                else if(!isset($date['day']))
                                {
                                    $_data['report']['rewarded'][$i] = (int)$reward['total_rewarded_sum'] ;
                                }
                            }
                        }

                        foreach($collection_order as $order)
                        {
                            if((int)$order->{"get$type"}() == $count)
                            {
                                $_data['report']['order'][$i] = (int)$order->getTotalOrders();
                            }
                        }
                        $i++;
                    }
                }

                $_data['report']['redeemed'] = array_values($_data['report']['redeemed']);
                $_data['report']['rewarded'] = array_values($_data['report']['rewarded']);
                $_data['report']['order'] = array_values($_data['report']['order']);
            }
            catch(Exception $e){}

            return $_data;
        }
        protected function _buildCollection(&$collection, $data, $group = true)
        {
            switch($data['report_range'])
            {
                case MW_RewardPoints_Model_Admin_Const::REPORT_RAGE_LAST_24H:
                    /* Last 24h */
                    $_hour = date('Y-m-d h:i:s', strtotime('-1 day', Mage::getModel('core/date')->gmtTimestamp()));
                    $start_hour = Mage::helper('core')->formatDate($_hour, 'medium', true);
                    $_hour = date('Y-m-d h:i:s', strtotime("now"));
                    $end_hour = Mage::helper('core')->formatDate($_hour, 'medium', true);

                    if($group == true)
                    {
                        $collection->addExpressionFieldToSelect('hour', 'HOUR(CONVERT_TZ(transaction_time, \'+00:00\', \''.$this->_calOffsetHourGMT().':00\'))', 'hour');
                        $collection->addExpressionFieldToSelect('day', 'DAY(CONVERT_TZ(transaction_time, \'+00:00\', \''.$this->_calOffsetHourGMT().':00\'))', 'day');
                        $collection->getSelect()->group(array('hour'));
                    }

                    //$collection->addFieldToFilter('CONVERT_TZ(main_table.transaction_time, \'+00:00\', \'+'.$this->_calOffsetHourGMT().':00\')', array('from' => "'".$start_hour."'", 'to' => "'".$end_hour."'", 'datetime' => true));
					$collection->getSelect()->where('CONVERT_TZ(main_table.transaction_time, \'+00:00\', \''.$this->_calOffsetHourGMT().':00\') >= \'' . $start_hour.'\'');
					$collection->getSelect()->where('CONVERT_TZ(main_table.transaction_time, \'+00:00\', \''.$this->_calOffsetHourGMT().':00\') <= \'' . $end_hour.'\'');
                    break;
                case MW_RewardPoints_Model_Admin_Const::REPORT_RAGE_LAST_WEEK:
                    /* Last week */
                    $start_day = date('Y-m-d',strtotime("-7 day", strtotime("Sunday Last Week")));
                    $end_day = date('Y-m-d',strtotime("Sunday Last Week"));
                    if($group == true)
                    {
                        $collection->addExpressionFieldToSelect('day', 'DAY(transaction_time)', 'day');
                        $collection->getSelect()->group(array('day'));
                    }

                    //$collection->addFieldToFilter('CONVERT_TZ(main_table.transaction_time, \'+00:00\', \'+'.$this->_calOffsetHourGMT().':00\')', array('from' => "'".$start_day."'", 'to' => "'".$end_day."'", 'datetime' => true));
					$collection->getSelect()->where('CONVERT_TZ(main_table.transaction_time, \'+00:00\', \''.$this->_calOffsetHourGMT().':00\') >= \'' . $start_day.'\'');
					$collection->getSelect()->where('CONVERT_TZ(main_table.transaction_time, \'+00:00\', \''.$this->_calOffsetHourGMT().':00\') <= \'' . $end_day.'\'');
                    break;
                case MW_RewardPoints_Model_Admin_Const::REPORT_RAGE_LAST_MONTH:
                    /* Last month */
                    $last_month_time = $this->_getLastMonthTime();
                    $last_month = date('m', strtotime($last_month_time));
                    $start_day = date('Y', strtotime($last_month_time))."-".$last_month."-1";
                    $end_day = date('Y', strtotime($last_month_time))."-".$last_month."-".$this->_days_in_month($last_month);

                    /** Fix bug next one day */
                    $end_day = strtotime($end_day.' +1 day');
                    $end_day = date('Y', $end_day)."-".date('m', $end_day)."-".date('d', $end_day);

                    if($group == true)
                    {
                        $collection->addExpressionFieldToSelect('day', 'DAY(transaction_time)', 'day');
                        $collection->getSelect()->group(array('day'));
                    }

                    //$collection->addFieldToFilter('CONVERT_TZ(main_table.transaction_time, \'+00:00\', \'+'.$this->_calOffsetHourGMT().':00\')', array('from' => "'".$start_day."'", 'to' => "'".$end_day."'", 'datetime' => true));
					$collection->getSelect()->where('CONVERT_TZ(main_table.transaction_time, \'+00:00\', \''.$this->_calOffsetHourGMT().':00\') >= \'' . $start_day.'\'');
					$collection->getSelect()->where('CONVERT_TZ(main_table.transaction_time, \'+00:00\', \''.$this->_calOffsetHourGMT().':00\') <= \'' . $end_day.'\'');
                    break;
                case MW_RewardPoints_Model_Admin_Const::REPORT_RAGE_LAST_7DAYS:
                case MW_RewardPoints_Model_Admin_Const::REPORT_RAGE_LAST_30DAYS:
                    /** Last X days */
                    if($data['report_range'] == MW_RewardPoints_Model_Admin_Const::REPORT_RAGE_LAST_7DAYS)
                    {
                        $last_x_day = 7;
                    }
                    else if($data['report_range'] == MW_RewardPoints_Model_Admin_Const::REPORT_RAGE_LAST_30DAYS)
                    {
                        $last_x_day = 30;
                    }
                    $start_day = date('Y-m-d h:i:s', strtotime('-'.$last_x_day.' day', Mage::getModel('core/date')->gmtTimestamp()));
                    $end_day = date('Y-m-d h:i:s', strtotime("-1 day"));
                    if($group == true)
                    {
                        $collection->getSelect()->group(array('day'));
                    }

                    $collection->addExpressionFieldToSelect('month', 'MONTH(transaction_time)', 'month');
                    $collection->addExpressionFieldToSelect('day', 'DAY(transaction_time)', 'day');
                    $collection->addExpressionFieldToSelect('year', 'YEAR(transaction_time)', 'year');


                    //$collection->addFieldToFilter('CONVERT_TZ(main_table.transaction_time, \'+00:00\', \'+'.$this->_calOffsetHourGMT().':00\')', array('from' => "'".$start_day."'", 'to' => "'".$end_day."'", 'datetime' => true));
					$collection->getSelect()->where('CONVERT_TZ(main_table.transaction_time, \'+00:00\', \''.$this->_calOffsetHourGMT().':00\') >= \'' . $start_day.'\'');
					$collection->getSelect()->where('CONVERT_TZ(main_table.transaction_time, \'+00:00\', \''.$this->_calOffsetHourGMT().':00\') <= \'' . $end_day.'\'');
                    break;
                case MW_RewardPoints_Model_Admin_Const::REPORT_RAGE_CUSTOM:
                    /* Custom range */

                    if($group == true)
                    {
                        $collection->addExpressionFieldToSelect('month', 'MONTH(transaction_time)', 'month');
                        $collection->addExpressionFieldToSelect('day', 'DAY(transaction_time)', 'day');
                        $collection->addExpressionFieldToSelect('year', 'YEAR(transaction_time)', 'year');
                        $collection->getSelect()->group(array('day'));
                    }

                    //$collection->addFieldToFilter('CONVERT_TZ(main_table.transaction_time, \'+00:00\', \'+'.$this->_calOffsetHourGMT().':00\')', array('from' => "'".$data['from']."'", 'to' => "'".$data['to']."'", 'datetime' => true));
					$collection->getSelect()->where('CONVERT_TZ(main_table.transaction_time, \'+00:00\', \''.$this->_calOffsetHourGMT().':00\') >= \'' . $data['from'].'\'');
					$collection->getSelect()->where('CONVERT_TZ(main_table.transaction_time, \'+00:00\', \''.$this->_calOffsetHourGMT().':00\') <= \'' . $data['to'].'\'');
                    break;
            }
        }
        protected function _getLastMonthTime()
        {
            return  date('Y-m-d', strtotime("-1 month"));
        }
        protected function _buildArrayDate($type, $from = 0, $to = 23, $original_time = null)
        {
            switch($type)
            {
                case MW_RewardPoints_Model_Admin_Const::REPORT_RAGE_LAST_24H:
                    $start_day = $original_time['d'];
                    for($i = $from; $i <= $to; $i++)
                    {
                        $data[$i]['incr_hour'] = $i;
                        $data[$i]['native_hour'] = ($i > 24) ? $i - 24 : $i;
                        $data[$i]['day'] = $start_day;

                        if($i == 23)
                        {
                            $start_day++;
                        }
                    }
                    break;
                case  MW_RewardPoints_Model_Admin_Const::REPORT_RAGE_LAST_WEEK:
                    $data = array();
                    $day_in_month = $this->_days_in_month(date('m'), date('Y'));
                    $clone_from = $from;
                    $reset = false;
                    for($i = 1; $i <=7; $i++)
                    {
                        if($from > $day_in_month && !$reset){
                            $clone_from = 1;
                            $reset = true;
                        }
                        $data[$i]['count_day'] = $from;
                        $data[$i]['native_day'] = $clone_from;
                        $from++;
                        $clone_from++;
                    }

                    break;
                case  MW_RewardPoints_Model_Admin_Const::REPORT_RAGE_LAST_MONTH:
                    for($i = (int)$from; $i <= $to; $i++)
                    {
                        $data[$i]['native_day'] = (int)$i;
                    }
                    break;
                case  MW_RewardPoints_Model_Admin_Const::REPORT_RAGE_CUSTOM:
                    $total_days = $this->_dateDiff($original_time['from'], $original_time['to']);
                    if($total_days > 365)
                    {

                    }
                    else
                    {
                        $all_months = $this->_get_months($original_time['from'], $original_time['to']);
                        $start_time = strtotime($original_time['from']);
                        $start_day  = (int)date('d', $start_time);
                        $year       = (int)date('Y', $start_time);
                        $count      = 0;
                        $data       = array();

                        $end_day_time = strtotime($original_time['to']);

                        $end_day = array(
                            'm' => (int)date('m', $end_day_time),
                            'd' => (int)date('d', $end_day_time)
                        );

                        foreach($all_months as $month)
                        {
                            $day_in_month = $this->_days_in_month($month, (int)date('Y', $start_time));

                            for($day = ($count == 0 ? $start_day : 1); $day <= $day_in_month; $day++)
                            {
                                if($day > $end_day['d'] && $month == $end_day['m']){
                                    continue;
                                }
                                $data[$year][$month][$day] = $day;
                            }
                            $count++;
                        }
                    }
                    break;
            }
            return $data;
        }
        protected function _days_in_month($month, $year)
        {
            $year = (!$year) ? date('Y', Mage::getSingleton('core/date')->gmtTimestamp()) : $year;
            return $month == 2 ? ($year % 4 ? 28 : ($year % 100 ? 29 : ($year % 400 ? 28 : 29))) : (($month - 1) % 7 % 2 ? 30 : 31);
        }
        protected function _dateDiff($d1, $d2)
        {
            // Return the number of days between the two dates:
            return round(abs(strtotime($d1) - strtotime($d2))/86400);
        }
        protected function _validationDate($data)
        {
            if(strtotime($data['from']) > strtotime($data['to']))
                return false;
            return true;
        }
        protected function _get_months($start, $end){
            $start = $start=='' ? time() : strtotime($start);
            $end = $end=='' ? time() : strtotime($end);
            $months = array();

            for ($i = $start; $i <= $end; $i = $this->get_next_month($i)) {
                $months[] = (int)date('m', $i);
            }

            return $months;
        }
        protected function get_next_month($tstamp) {
            return (strtotime('+1 months', strtotime(date('Y-m-01', $tstamp))));
        }
        protected function getPreviousDateTime($hour)
        {
            return Mage::getModel('core/date')->gmtTimestamp() - (3600 * $hour);
        }
        protected function convertNumberToMOnth($num)
        {
            $months = array(1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec');
            return $months[$num];
        }
        protected function _returnTextType($type)
        {
            $data = array();
            if(count($this->const_type_rw) == 0)
            {
                $ref = new ReflectionClass ('MW_RewardPoints_Model_Type');
                $this->const_type_rw = $ref->getConstants();
            }
            foreach($this->const_type_rw as $const => $value)
            {
                if($type == $value)
                {
                    $text = str_replace("_", " ", $const);
                    $text = ucwords(strtolower($text));
                    return $text;
                }
            }
        }
        protected function _calOffsetHourGMT()
        {
            //return Mage::getSingleton('core/date')->calculateOffset(Mage::app()->getStore()->getConfig('general/locale/timezone'))/60/60;
            $offset = round(Mage::getSingleton('core/date')->calculateOffset(Mage::app()->getStore()->getConfig('general/locale/timezone'))/60/60);

            if ($offset >= 0) {
                $offset = '+' . $offset;
            }

            return $offset;
        }
    }