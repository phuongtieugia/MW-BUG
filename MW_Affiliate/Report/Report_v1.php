<?php

/**
 * @author Tuanlv
 * @copyright 2014
 */

class MW_Affiliate_Model_Report extends Mage_Core_Model_Abstract
{
    protected $all_months = 0;
    
    public function prepareCollectionFrontend($data){
        $resource = Mage::getModel('core/resource');
        $aff_customer_table = $resource->getTableName('affiliate/affiliatecustomers');
        $customer_table = Mage::getSingleton('core/resource')->getTableName('customer_entity');
        $order_table =  Mage::getSingleton('core/resource')->getTableName('sales/order');
        
        /** Query to get my group customer_id*/ 
        switch($data['report_range'])
        {
            case MW_Affiliate_Model_Admin_Const::REPORT_RAGE_LAST_24H:
            case MW_Affiliate_Model_Admin_Const::REPORT_RAGE_LAST_7DAYS:
            case MW_Affiliate_Model_Admin_Const::REPORT_RAGE_LAST_30DAYS:
                $date = date('Y-m-d H:i:s');
            break;
            case MW_Affiliate_Model_Admin_Const::REPORT_RAGE_LAST_WEEK:
                $date = date('Y-m-d H:i:s',strtotime("Sunday Last Week"));
            break;
            case MW_Affiliate_Model_Admin_Const::REPORT_RAGE_LAST_MONTH:
                $date = date('Y-m-d H:i:s',strtotime("last day of last month"));
            break;
            case MW_Affiliate_Model_Admin_Const::REPORT_RAGE_CUSTOM:
                $date = date('Y-m-d H:i:s',strtotime($data['to']));
            break;
        }
        
        $array_members = array();
        array_push($array_members, $data['customer_id']);        
        $this->_getArrayCustomers($data['customer_id'],$date,$array_members);

        $store_id = Mage::app()->getStore()->getStoreId();
        $order_status_add_commission = Mage::getStoreConfig('affiliate/general/status_add_commission',$store_id);
        
        switch($order_status_add_commission){
            case 'pending':
                $order_status_add = array('pending','processing','complete');
            break;
            case 'processing':
                $order_status_add = array('processing','complete');
            break;
            case 'complete':
                $order_status_add = array('complete');
            break;
            default:
                $order_status_add = array($order_status_add_commission,'complete');
            break;
        }
        
        /** Query to get My Total Affiliate Child*/
        $count_childs = 0;
        $this->_countAffiliateChilds($data['customer_id'], $date, $count_childs);

        /** Query to get my Sales */ 
        $collection = Mage::getModel('sales/order')->getCollection();
        $collection->removeAllFieldsFromSelect();
        $collection->addFieldToFilter('main_table.customer_id', array('in' => $array_members));
        $collection->addFieldToFilter('main_table.status',array('in' => $order_status_add)); 
        $collection->addExpressionFieldToSelect('total_sales_sum', 'sum(main_table.subtotal)', 'total_sales_sum');
        $this->_buildCollection($collection, $data, true, 'updated_at');
        $collection_sales_sum = $collection;
        
        /** Query to get my Commission */ 
        $collection = Mage::getModel('affiliate/affiliatetransaction')->getCollection();
        $collection->removeAllFieldsFromSelect();
        $collection->addFieldToFilter('main_table.status', MW_Affiliate_Model_Status::COMPLETE);
        $collection->addFieldToFilter('main_table.customer_invited', $data['customer_id']);
        $collection->addExpressionFieldToSelect('total_commission_sum', 'SUM(total_commission)', 'total_commission_sum');
        $this->_buildCollection($collection, $data, true, 'transaction_time');
        $collection_commission_sum = $collection;

        /** Query to get My Total Sales*/
        $collection = Mage::getModel('sales/order')->getCollection();
        $collection->removeAllFieldsFromSelect();
        $collection->addFieldToFilter('main_table.status', array('in' => $order_status_add));
        $collection->addFieldToFilter('main_table.customer_id', $data['customer_id']);
        $collection->addExpressionFieldToSelect('total_affiliate_sales', 'main_table.subtotal', 'total_affiliate_sales');
        $this->_buildCollection($collection, $data, false, 'updated_at');
        $collection_my_sales = $collection;
        
        /** Query to get My Total Commission*/
        $collection = Mage::getModel('affiliate/affiliatetransaction')->getCollection();
        $collection->removeAllFieldsFromSelect();
        $collection->addFieldToFilter('main_table.status', MW_Affiliate_Model_Status::COMPLETE);
        $collection->addFieldToFilter('main_table.customer_invited', $data['customer_id']);
        $collection->addExpressionFieldToSelect('total_affiliate_commission', 'if(sum(total_commission),sum(total_commission),0)','total_affiliate_commission');
        $this->_buildCollection($collection, $data, false, 'transaction_time');
        $collection_my_commission = $collection->getFirstItem();
     
        switch($data['report_range'])
        {
            case MW_Affiliate_Model_Admin_Const::REPORT_RAGE_LAST_24H:
                    $_time = $this->getPreviousDateTime(24);
                    $start_24h_time = Mage::helper('core')->formatDate(date('Y-m-d h:i:s', $_time), 'medium', true);
                    $start_24h_time = strtotime($start_24h_time);
                    $start_time = array(
                        'h'   => (int)date('H', $start_24h_time),
                        'd'   => (int)date('d', $start_24h_time),
                        'm'   => (int)date('m', $start_24h_time),
                        'y'   => (int)date('Y', $start_24h_time),
                    );
                    $rangeDate = $this->_buildArrayDate(MW_Affiliate_Model_Admin_Const::REPORT_RAGE_LAST_24H, $start_time['h'], $start_time['h'] + 24, $start_time);

                    $_data = $this->_buildResult($collection_sales_sum,$collection_discount_sum,$collection_commission_sum, 'hour', $rangeDate);
                    $_data['report']['date_start'] = $start_time;
            break;
            case MW_Affiliate_Model_Admin_Const::REPORT_RAGE_LAST_WEEK:
                    $start_time = strtotime("-6 day", strtotime("Sunday Last Week"));
                    $startDay = date('d', $start_time);
                    $endDay = date('d',strtotime("Sunday Last Week"));
                    $rangeDate = $this->_buildArrayDate(MW_Affiliate_Model_Admin_Const::REPORT_RAGE_LAST_WEEK, $startDay, $rangeDate);
                    $_data = $this->_buildResult($collection_sales_sum,$collection_discount_sum,$collection_commission_sum, 'day', $rangeDate);
                    $_data['report']['date_start'] = array(
                        'd'   => (int)date('d', $start_time),
                        'm'   => (int)date('m', $start_time),
                        'y'   => (int)date('Y', $start_time),
                    );

            break;
            case MW_Affiliate_Model_Admin_Const::REPORT_RAGE_LAST_MONTH:
                    $last_month_time = strtotime($this->_getLastMonthTime());
                    $last_month = date('m', $last_month_time);
                    $start_day = 1;
                    $end_day = $this->_days_in_month($last_month);
                    $rangeDate = $this->_buildArrayDate(MW_Affiliate_Model_Admin_Const::REPORT_RAGE_LAST_MONTH, $start_day, $end_day);

                    $_data = $this->_buildResult($collection_sales_sum,$collection_discount_sum,$collection_commission_sum, 'day', $rangeDate);
                    $_data['report']['date_start'] = array(
                        'd'   => $start_day,
                        'm'   => (int)$last_month,
                        'y'   => (int)date('Y', $last_month_time),
                        'total_day' => $end_day
                    );

            break;
            case MW_Affiliate_Model_Admin_Const::REPORT_RAGE_LAST_7DAYS:
            case MW_Affiliate_Model_Admin_Const::REPORT_RAGE_LAST_30DAYS:
                    if($data['report_range'] == MW_Affiliate_Model_Admin_Const::REPORT_RAGE_LAST_7DAYS)
                    {
                        $last_x_day = 7;
                    }
                    else if($data['report_range'] == MW_Affiliate_Model_Admin_Const::REPORT_RAGE_LAST_30DAYS)
                    {
                        $last_x_day = 30;
                    }

                    $start_day = date('Y-m-d h:i:s', strtotime('-'.$last_x_day.' day', Mage::getModel('core/date')->gmtTimestamp()));
                    $end_day = date('Y-m-d h:i:s', strtotime("-1 day"));

                    $original_time = array(
                        'from'  => $start_day,
                        'to'    => $end_day
                    );
                    $rangeDate = $this->_buildArrayDate(MW_Affiliate_Model_Admin_Const::REPORT_RAGE_CUSTOM, 0, 0, $original_time);
                    $_data = $this->_buildResult($collection_sales_sum,$collection_discount_sum,$collection_commission_sum, 'multiday', $rangeDate, $original_time);
            break;
            case MW_Affiliate_Model_Admin_Const::REPORT_RAGE_CUSTOM:
                    $original_time = array(
                        'from'  => $data['from'],
                        'to'    => $data['to']
                    );
                    $rangeDate = $this->_buildArrayDate(MW_Affiliate_Model_Admin_Const::REPORT_RAGE_CUSTOM, 0, 0, $original_time);
                    $_data = $this->_buildResult($collection_sales_sum,$collection_discount_sum,$collection_commission_sum, 'multiday', $rangeDate, $original_time);
            break;
        }
        $total_affiliate_sales = null;
        $total_affiliate_sales = null;
        foreach($collection_my_sales as $order){
            $total_affiliate_sales += $order->getData('total_affiliate_sales');
            $total_affiliate_order += 1;
        }
        $_data['statistics']['total_affiliate_sales'] = ($total_affiliate_sales == null) ? Mage::helper('core')->currency(0) :  Mage::helper('core')->currency($total_affiliate_sales);   
        $_data['statistics']['total_affiliate_commission'] = ($collection_my_commission->getData('total_affiliate_commission') == null) ? Mage::helper('core')->currency(0) :  Mage::helper('core')->currency($collection_my_commission->getData('total_affiliate_commission'));
        $_data['statistics']['total_affiliate_order'] =  ($total_affiliate_order == null) ? 0 :  number_format($total_affiliate_order, 0, '.', ',');   
        $_data['statistics']['total_affiliate_child'] =  number_format($count_childs, 0, '.', ',');     
        $piechart = $this->preapareCollectionPieChartFrontend($data, $collection_my_commission->getData('total_affiliate_commission'));       
        $_data['report_commission_by_members'] = json_encode($piechart['commission_by_members']);
        $_data['report_commission_by_programs'] = json_encode($piechart['commission_by_programs']);
        $_data['report']['title'] = Mage::helper('affiliate')->__('My Total Sales / My Commission ');
        $_data['report']['curency'] = Mage::app()->getLocale()->currency(Mage::app()->getStore()->getCurrentCurrencyCode())->getSymbol();
        
        return json_encode($_data);
    }
    
    public function prepareCollection($data){
        $resource = Mage::getModel('core/resource');
        $aff_customer_table = $resource->getTableName('affiliate/affiliatecustomers');
        $customer_table = Mage::getSingleton('core/resource')->getTableName('customer_entity');
        $order_table =  Mage::getSingleton('core/resource')->getTableName('sales/order');
        
        /** Query to get Affiliate*/
        switch($data['report_range'])
        {
            case MW_Affiliate_Model_Admin_Const::REPORT_RAGE_LAST_24H:
            case MW_Affiliate_Model_Admin_Const::REPORT_RAGE_LAST_7DAYS:
            case MW_Affiliate_Model_Admin_Const::REPORT_RAGE_LAST_30DAYS:
                $date = date('Y-m-d H:i:s');
            break;
            case MW_Affiliate_Model_Admin_Const::REPORT_RAGE_LAST_WEEK:
                $date = date('Y-m-d H:i:s',strtotime("Sunday Last Week"));
            break;
            case MW_Affiliate_Model_Admin_Const::REPORT_RAGE_LAST_MONTH:
                $date = date('Y-m-d H:i:s',strtotime("last day of last month"));
            break;
            case MW_Affiliate_Model_Admin_Const::REPORT_RAGE_CUSTOM:
                $date = date('Y-m-d H:i:s',strtotime($data['to']));
            break;
        }
        /** Count affiliate*/
        $collection = Mage::getModel('affiliate/affiliatecustomers')->getCollection();
        $collection->addFieldToFilter('main_table.status', 1);
        $collection->addFieldToFilter('customer_time',array('from' => null,'to' => $date));
        $collection->addFieldToFilter('referral_code',array('neq'=>''));
        $count_affiliate = count($collection->getData());
		
		 /** Get array affiliate*/
        $collection = Mage::getModel('affiliate/affiliatecustomers')->getCollection();
        $collection->addFieldToFilter('main_table.status', 1);
        $collection->addFieldToFilter('customer_time',array('from' => null,'to' => $date));
        $collection->addFieldToFilter('referral_code',array('neq'=>''));
        $affIds = array();
        foreach($collection->getData() as $aff){
            array_push($affIds,$aff['customer_id']);
        }
		
        /** Get array customer*/
        $collection = Mage::getModel('affiliate/affiliatecustomers')->getCollection();
        $collection->addFieldToFilter('main_table.status', 1);
        $collection->addFieldToFilter('customer_time',array('from' => null,'to' => $date));
        $affiliateIds = array();
        foreach($collection->getData() as $aff){
            array_push($affiliateIds,$aff['customer_id']);
        }
        
        $store_id = Mage::app()->getStore()->getStoreId();
        $order_status_add_commission = Mage::getStoreConfig('affiliate/general/status_add_commission',$store_id);
        
        switch($order_status_add_commission){
            case 'pending':
                $order_status_add = array('pending','processing','complete');
            break;
            case 'processing':
                $order_status_add = array('processing','complete');
            break;
            case 'complete':
                $order_status_add = array('complete');
            break;
            default:
                $order_status_add = array($order_status_add_commission);
            break;
        }
        /** Query to get order */ 
        $collection = Mage::getModel('sales/order')->getCollection();
        $collection->removeAllFieldsFromSelect();
        $collection->addFieldToFilter('main_table.status',array('in' => $order_status_add));
        $collection->addFieldToFilter('main_table.customer_id',array('in' => $affiliateIds)); 
        $this->_buildCollection($collection, $data, false, 'updated_at');
        $number_order = count($collection->getData());
        
        /** Query to get Sales */ 
        $collection = Mage::getModel('sales/order')->getCollection();
        $collection->removeAllFieldsFromSelect();
        $collection->addFieldToFilter('main_table.status',array('in' => $order_status_add));
        $collection->addFieldToFilter('main_table.customer_id',array('in' => $affiliateIds));        
        $collection->addExpressionFieldToSelect('total_sales_sum', 'sum(main_table.subtotal)', 'total_sales_sum');
        $this->_buildCollection($collection, $data, true, 'updated_at');
        $collection_sales_sum = $collection;
        /** Query to get Discount */
        $collection = Mage::getModel('affiliate/affiliatetransaction')->getCollection();
        $collection->removeAllFieldsFromSelect();
        $collection->addFieldToFilter('main_table.status', MW_Affiliate_Model_Status::COMPLETE);
        $collection->addFieldtoFilter('main_table.customer_invited',0);
        $collection->addExpressionFieldToSelect('total_discount_sum', 'SUM(total_discount)', 'total_discount_sum');
        $this->_buildCollection($collection, $data, true, 'transaction_time');
        $collection_discount_sum = $collection;
        
        /** Query to get Commission */
        $collection = Mage::getModel('affiliate/affiliatetransaction')->getCollection();
        $collection->removeAllFieldsFromSelect();
        $collection->addFieldToFilter('main_table.status', MW_Affiliate_Model_Status::COMPLETE);
        $collection->addFieldtoFilter('main_table.customer_invited',0);
        $collection->addExpressionFieldToSelect('total_commission_sum', 'SUM(total_commission)', 'total_commission_sum');

        $this->_buildCollection($collection, $data, true, 'transaction_time');
        $collection_affiliate = $collection;
        
        /** Query to statistic withdrawal*/
        $collection = Mage::getModel('affiliate/affiliatewithdrawn')->getCollection();
        $collection->removeAllFieldsFromSelect(); 
        $collection->addFieldToFilter('main_table.status', MW_Affiliate_Model_Status::COMPLETE);
        $collection->addExpressionFieldToSelect('total_withdrawn','if(sum(withdrawn_amount),sum(withdrawn_amount),0)','total_withdrawn');
        $collection->addExpressionFieldToSelect('total_fee','if(sum(fee),sum(fee),0)','total_fee'); 
        $collection->addExpressionFieldToSelect('total_count','sum(1)','total_count');       
        
        $this->_buildCollection($collection, $data, false, 'withdrawn_time');
        $collection_stats_withdrawn = $collection->getFirstItem();
        
        /** Query to statistic total transactions*/   
        $collection = Mage::getModel('affiliate/affiliatetransaction')->getCollection();
        $collection->removeAllFieldsFromSelect(); 
        $collection->addFieldToFilter('customer_invited',array('neq' => 0));
        $collection->addExpressionFieldToSelect('total_transaction','sum(if(main_table.status = 4,2,1))','total_transaction');       
        $this->_buildCollection($collection, $data, false, 'transaction_time');
        $collection_stats_transaction = $collection->getFirstItem();
        
        $collection = Mage::getModel('affiliate/affiliatewithdrawn')->getCollection();
        $collection->removeAllFieldsFromSelect(); 
        $collection->addExpressionFieldToSelect('total_count','sum(if(main_table.status = 3,2,1))','total_count');      
        $this->_buildCollection($collection, $data, false, 'withdrawn_time');
        $collection_transaction_withdrawn = $collection->getFirstItem();
        
        $total_transaction = $collection_stats_transaction->getData('total_transaction')+ $collection_transaction_withdrawn->getData('total_count');

        /** Query to get Orders*/
        $collection = Mage::getModel('affiliate/affiliatehistory')->getCollection();
        $collection->addFieldToFilter('main_table.status', MW_Affiliate_Model_Status::COMPLETE);
        $collection->getSelect()->group(array('order_id'));
        $this->_buildCollection($collection, $data, false, 'transaction_time');
        $array_orders = array();
        foreach($collection as $order){
            array_push($array_orders,$order->getData('order_id'));
        }        
        /** Statistic top afiliate sales*/        
        /** Calculate Sales in one Level for each Affiliate*/
        $array_affiliates_sales = array();
        foreach($affIds as $affId){
            $array_affiliates_sales[$affId] = $this->getTotalAffiliateSales($affId,$data,$order_status_add,$date);
        }
        arsort($array_affiliates_sales);
        $collections_aff = Mage::getModel('customer/customer')->getCollection(); 
        $collections_aff->addFieldToFilter('entity_id',array('in'=>$affIds));
        $collections_aff = $collections_aff->getData();
        
        $aff_sales = array();
        $count = 0;  
        foreach($array_affiliates_sales as $key => $value){
            if($count == 15) break;
            $buff = array();
            $totalSales = 0;
            foreach($collections_aff as $aff){
                if($aff['entity_id'] == $key){
                     $buff['affiliate'] = $aff['email'];
                }
            }
            $buff['affiliate_id'] = $key;            
            $buff['total_affiliate_sales'] = $value;         
            array_push($aff_sales,$buff);
            $count = $count + 1;
        }
        
        /** Query to statistic top afiliate commission*/
        $collection = Mage::getModel('affiliate/affiliatetransaction')->getCollection();
        $collection->removeAllFieldsFromSelect();
        $collection->addFieldToFilter('main_table.status', MW_Affiliate_Model_Status::COMPLETE);
        $collection->addFieldToFilter('main_table.status', MW_Affiliate_Model_Status::COMPLETE);
        $collection->getSelect()->join(array('customer_entity' => $customer_table), 'main_table.customer_invited = customer_entity.entity_id', array());
        $collection->addExpressionFieldToSelect('affiliate','customer_entity.email','affiliate');
        $collection->addExpressionFieldToSelect('total_affiliate_sales','sum(0)','total_affiliate_sales');
        $collection->addExpressionFieldToSelect('affiliate_id','main_table.customer_invited','affiliate_id');
        $collection->addExpressionFieldToSelect('total_affiliate_commission', 'if(sum(total_commission),sum(total_commission),0)','total_affiliate_commission');
        $collection->getSelect()->group(array('main_table.customer_invited'));
        $collection->getSelect()->order('total_affiliate_commission DESC');
        $this->_buildCollection($collection, $data, false, 'transaction_time');
        $collection_top_affiliate_commission = $collection;
        
        /** Processing Top Affiliate */
        $array_top_sales = array();
        $array_top_commission = array();

        $collection_top_affiliate = $aff_sales;
        $collection_top_affiliate_commission = $collection_top_affiliate_commission->getData();

        foreach($collection_top_affiliate as $affiliate){
            array_push($array_top_sales,$affiliate['affiliate_id']);
        }
        foreach($collection_top_affiliate_commission as $affiliate){
            array_push($array_top_commission,$affiliate['affiliate_id']);
        }
        
        if(sizeof($collection_top_affiliate) == 0){
                $collection_top_affiliate = $collection_top_affiliate_commission;
        }else{
            $top_commission_add_more = array_diff($array_top_commission,$array_top_sales);
            foreach($collection_top_affiliate as &$affiliate){
                $total_commission = 0;
                foreach($collection_top_affiliate_commission as $aff){
                    if($affiliate['affiliate_id'] == $aff['affiliate_id']){
                        $total_commission = $aff['total_affiliate_commission'];
                        break;
                    }
                }
                $affiliate['total_affiliate_commission'] = $total_commission;
            }  
            foreach($top_commission_add_more as $affiliate_id){
                $aff_add = array();
                $aff_add['entity_id'] = 0;
                $aff_add['total_affiliate_sales'] = 0;
                $aff_add['affiliate_id'] = $affiliate_id;
                foreach($collection_top_affiliate_commission as $aff){
                    if($affiliate_id == $aff['affiliate_id']){
                        $aff_add['affiliate'] = $aff['affiliate'];
                        $aff_add['total_affiliate_commission'] = $aff['total_affiliate_commission'];
                        break;
                    }
                }            
                array_push($collection_top_affiliate,$aff_add);
            }
        }
                
        switch($data['report_range'])
        {
            case MW_Affiliate_Model_Admin_Const::REPORT_RAGE_LAST_24H:
                    $_time = $this->getPreviousDateTime(24);
                    $start_24h_time = Mage::helper('core')->formatDate(date('Y-m-d h:i:s', $_time), 'medium', true);
                    $start_24h_time = strtotime($start_24h_time);
                    $start_time = array(
                        'h'   => (int)date('H', $start_24h_time),
                        'd'   => (int)date('d', $start_24h_time),
                        'm'   => (int)date('m', $start_24h_time),
                        'y'   => (int)date('Y', $start_24h_time),
                    );
                    $rangeDate = $this->_buildArrayDate(MW_Affiliate_Model_Admin_Const::REPORT_RAGE_LAST_24H, $start_time['h'], $start_time['h'] + 24, $start_time);

                    $_data = $this->_buildResult($collection_sales_sum,$collection_discount_sum,$collection_affiliate, 'hour', $rangeDate);
                    $_data['report']['date_start'] = $start_time;
            break;
            case MW_Affiliate_Model_Admin_Const::REPORT_RAGE_LAST_WEEK:
                    $start_time = strtotime("-6 day", strtotime("Sunday Last Week"));
                    $startDay = date('d', $start_time);
                    $endDay = date('d',strtotime("Sunday Last Week"));
                    $rangeDate = $this->_buildArrayDate(MW_Affiliate_Model_Admin_Const::REPORT_RAGE_LAST_WEEK, $startDay, $rangeDate);
                    $_data = $this->_buildResult($collection_sales_sum,$collection_discount_sum,$collection_affiliate, 'day', $rangeDate);
                    $_data['report']['date_start'] = array(
                        'd'   => (int)date('d', $start_time),
                        'm'   => (int)date('m', $start_time),
                        'y'   => (int)date('Y', $start_time),
                    );

            break;
            case MW_Affiliate_Model_Admin_Const::REPORT_RAGE_LAST_MONTH:
                    $last_month_time = strtotime($this->_getLastMonthTime());
                    $last_month = date('m', $last_month_time);
                    $start_day = 1;
                    $end_day = $this->_days_in_month($last_month);
                    $rangeDate = $this->_buildArrayDate(MW_Affiliate_Model_Admin_Const::REPORT_RAGE_LAST_MONTH, $start_day, $end_day);

                    $_data = $this->_buildResult($collection_sales_sum,$collection_discount_sum,$collection_affiliate, 'day', $rangeDate);
                    $_data['report']['date_start'] = array(
                        'd'   => $start_day,
                        'm'   => (int)$last_month,
                        'y'   => (int)date('Y', $last_month_time),
                        'total_day' => $end_day
                    );

            break;
            case MW_Affiliate_Model_Admin_Const::REPORT_RAGE_LAST_7DAYS:
            case MW_Affiliate_Model_Admin_Const::REPORT_RAGE_LAST_30DAYS:
                    if($data['report_range'] == MW_Affiliate_Model_Admin_Const::REPORT_RAGE_LAST_7DAYS)
                    {
                        $last_x_day = 7;
                    }
                    else if($data['report_range'] == MW_Affiliate_Model_Admin_Const::REPORT_RAGE_LAST_30DAYS)
                    {
                        $last_x_day = 30;
                    }

                    $start_day = date('Y-m-d h:i:s', strtotime('-'.$last_x_day.' day', Mage::getModel('core/date')->gmtTimestamp()));
                    $end_day = date('Y-m-d h:i:s', strtotime("-1 day"));

                    $original_time = array(
                        'from'  => $start_day,
                        'to'    => $end_day
                    );
                    $rangeDate = $this->_buildArrayDate(MW_Affiliate_Model_Admin_Const::REPORT_RAGE_CUSTOM, 0, 0, $original_time);

                    $_data = $this->_buildResult($collection_sales_sum,$collection_discount_sum,$collection_affiliate, 'multiday', $rangeDate, $original_time);
            break;
            case MW_Affiliate_Model_Admin_Const::REPORT_RAGE_CUSTOM:
                    $original_time = array(
                        'from'  => $data['from'],
                        'to'    => $data['to']
                    );
                    $rangeDate = $this->_buildArrayDate(MW_Affiliate_Model_Admin_Const::REPORT_RAGE_CUSTOM, 0, 0, $original_time);
                    $_data = $this->_buildResult($collection_sales_sum,$collection_discount_sum,$collection_affiliate, 'multiday', $rangeDate, $original_time);
            break;
        }
        
        $_data['statistics']['total_affiliate_sales'] =  Mage::helper('core')->currency(0);   
        $_data['statistics']['total_affiliate_commission'] =  Mage::helper('core')->currency(0);
        $_data['statistics']['avg_commission_per_order'] =  Mage::helper('core')->currency(0);   
        $_data['statistics']['avg_commission_per_affiliate'] =  Mage::helper('core')->currency(0);
        $_data['statistics']['avg_sales_per_order'] =  Mage::helper('core')->currency(0);   
        $_data['statistics']['avg_sales_per_affiliate'] =  Mage::helper('core')->currency(0);
        $_data['statistics']['total_affiliate_order'] =  0;
        $_data['statistics']['total_affiliate']['all_actived'] = $count_affiliate;
        
        $collection_stats_sales = array();
        $collection_stats_sales['total_affiliate_order'] = ($number_order == null) ? 0 : $number_order ;
        $collection_stats_sales['total_affiliate_sales'] = 0;
        $collection_stats_sales['total_affiliate_commission'] = 0;
        $collection_stats_sales['avg_commission_per_order'] = 0;
        $collection_stats_sales['avg_commission_per_affiliate'] = 0;
        $collection_stats_sales['avg_sales_per_order'] = 0;
        $collection_stats_sales['avg_sales_per_affiliate'] = 0;

        foreach($collection_sales_sum as $order){
            $collection_stats_sales['total_affiliate_sales'] += floatval($order->getTotalSalesSum());
        }
        foreach($collection_affiliate as $commission){
            $collection_stats_sales['total_affiliate_commission'] += floatval($commission->getTotalCommissionSum());
        } 
        $collection_stats_sales['avg_commission_per_order'] = $collection_stats_sales['total_affiliate_commission']/$collection_stats_sales['total_affiliate_order'];  
        $collection_stats_sales['avg_commission_per_affiliate'] = $collection_stats_sales['total_affiliate_commission']/$count_affiliate;
        $collection_stats_sales['avg_sales_per_order'] = $collection_stats_sales['total_affiliate_sales']/$collection_stats_sales['total_affiliate_order'];  
        $collection_stats_sales['avg_sales_per_affiliate'] = $collection_stats_sales['total_affiliate_sales']/$count_affiliate;
        
        foreach($collection_stats_sales as $key => $stat)
        {            
            switch($key){
                case "total_affiliate_order":
                    $_data['statistics'][$key] = ($stat == null) ? 0 : number_format($stat, 0, '.', ',');
                break;
                case "total_affiliate_sales":
                case "total_affiliate_commission":
                case "avg_commission_per_order":
                case "avg_commission_per_affiliate":
                case "avg_sales_per_order":
                case "avg_sales_per_affiliate":
                    $_data['statistics'][$key] = ($stat == null) ?  Mage::helper('core')->currency(0) :  Mage::helper('core')->currency($stat);//number_format($stat, 0, '.', ',');
                break;
            }
        }
        $_data['statistics']['total_withdrawn'] =  Mage::helper('core')->currency(0);   
        $_data['statistics']['total_fee'] =  Mage::helper('core')->currency(0);    
        foreach($collection_stats_withdrawn->getData() as $key => $stat)
        {
            switch($key){
                case "total_withdrawn":
                case "total_fee":
                    $_data['statistics'][$key] = ($stat == null) ?  Mage::helper('core')->currency(0) : Mage::helper('core')->currency($stat);//number_format($stat, 2, '.', ',');
                break;   
            }                
        } 
        
        $_data['topaffiliate'] = null;
        $count = 0;        
        
        foreach($collection_top_affiliate as &$affiliate){
            foreach($affiliate as $key => $value){
                switch($key){
                    case "affiliate":
                        $_data['topaffiliate'][$count][$key] = ($value == null) ? '' : $value;
                    break;
                    case "total_affiliate_sales":
                        $_data['topaffiliate'][$count][$key] = ($value == null) ? Mage::helper('core')->currency(0) : Mage::helper('core')->currency($value);
                    break;                                   
                    case "total_affiliate_commission":
                        $_data['topaffiliate'][$count][$key] = ($value == null) ? Mage::helper('core')->currency(0) : Mage::helper('core')->currency($value);
                    break;
                }
            }
            
            $count = $count +1;
            if(count == 10) break;
        }
        
        $piechart = $this->preapareCollectionPieChart($data, $collection_stats_sales['total_affiliate_sales'], $collection_stats_sales['total_affiliate_commission']);   
        $_data['statistics']['total_transaction'] =  number_format($total_transaction, 0, '.', ',');
        $_data['report_sales_by_programs'] = json_encode($piechart['sales']);
        $_data['report_commission_by_programs'] = json_encode($piechart['commission']);
        $_data['report']['title'] = Mage::helper('affiliate')->__('Sales Generated By Affiliates / Affiliate Commission / Referred Customer Discount');
        $_data['report']['curency'] = Mage::app()->getLocale()->currency(Mage::app()->getStore()->getCurrentCurrencyCode())->getSymbol();
        
        return json_encode($_data);
    }
    protected function _buildResult($collection_sales_sum,$collection_discount_sum,$collection_affiliate,$type, $rangeDate, $original_time = null)
    {
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
                                $_data['report']['commission'][$year."-".$month."-".$day]  = array($year, $month, $day, 0);
                            }
                            foreach($days as $day)
                            {
                                $_data['report']['sales'][$year."-".$month."-".$day]  = array($year, $month, $day, 0);
                            }
                            foreach($days as $day)
                            {
                                $_data['report']['discount'][$year."-".$month."-".$day]  = array($year, $month, $day, 0);
                            }

                            foreach($collection_affiliate as $commission)
                            {
                                if($commission->getMonth() == $month)
                                {
                                    foreach($days as $day)
                                    {

                                        if($commission->getDay() == $day)
                                        {
                                            $_data['report']['commission'][$year."-".$month."-".$day]  = array($year, $month, $day, floatval($commission->getTotalCommissionSum()));
                                        }
                                    }
                                }
                            }
                            foreach($collection_sales_sum as $sale)
                            {
                                if($sale->getMonth() == $month)
                                {
                                    foreach($days as $day)
                                    {
                                        if($sale->getDay() == $day)
                                        {   
                                            $_data['report']['sales'][$year."-".$month."-".$day][3] += floatval($sale->getTotalSalesSum());
                                        }
                                    }
                                }
                            }
                            foreach($collection_discount_sum as $discount)
                            {
                                if($discount->getMonth() == $month)
                                {
                                    foreach($days as $day)
                                    {
                                        if($discount->getDay() == $day)
                                        {
                                            $_data['report']['discount'][$year."-".$month."-".$day][3]  += floatval($discount->getTotalDiscountSum());
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

                        $_data['report']['commission'][$i] = 0;
                        $_data['report']['sales'][$i] = 0;
                        $_data['report']['discount'][$i] = 0;
                        foreach($collection_affiliate as $commission)
                        {
                            if((int)$commission->{"get$type"}() == $count)
                            {
                                if(isset($date['day']) && $date['day'] == (int)$commission->getDay())
                                {
                                    $_data['report']['commission'][$i] = floatval($commission->getTotalCommissionSum());
                                }
                                else if(!isset($date['day']))
                                {
                                    $_data['report']['commission'][$i] = floatval($commission->getTotalCommissionSum());
                                }
                            }
                        }
                        foreach($collection_sales_sum as $sale)
                        {
                            if((int)$sale->{"get$type"}() == $count)
                            {
                                if(isset($date['day']) && $date['day'] == (int)$sale->getDay())
                                {
                                    $_data['report']['sales'][$i] += floatval($sale->getTotalSalesSum());
                                }
                                else if(!isset($date['day']))
                                {
                                    $_data['report']['sales'][$i] += floatval($sale->getTotalSalesSum());
                                }
                            }
                        }
                        
                        foreach($collection_discount_sum as $discount)
                        {
                            if((int)$discount->{"get$type"}() == $count)
                            {
                                if(isset($date['day']) && $date['day'] == (int)$discount->getDay())
                                {
                                    $_data['report']['discount'][$i] += floatval($discount->getTotalDiscountSum());
                                }
                                else if(!isset($date['day']))
                                {
                                    $_data['report']['discount'][$i] += floatval($discount->getTotalDiscountSum());
                                }
                            }
                        }

                        $i++;
                    }
                }

                $_data['report']['commission'] = array_values($_data['report']['commission']);
                $_data['report']['sales'] = array_values($_data['report']['sales']);
                $_data['report']['discount'] = array_values($_data['report']['discount']);
            }
            catch(Exception $e){}

            return $_data;
    }
    protected function _buildCollection(&$collection, $data, $group = true, $str_time)
    {
            switch($data['report_range'])
            {
                case MW_Affiliate_Model_Admin_Const::REPORT_RAGE_LAST_24H:
                    /* Last 24h */
                    $_hour = date('Y-m-d h:i:s', strtotime('-1 day', Mage::getModel('core/date')->gmtTimestamp()));
                    $start_hour = Mage::helper('core')->formatDate($_hour, 'medium', true);
                    $_hour = date('Y-m-d h:i:s', strtotime("now"));
                    $end_hour = Mage::helper('core')->formatDate($_hour, 'medium', true);

                    if($group == true)
                    {
                        $collection->addExpressionFieldToSelect('hour', 'HOUR(CONVERT_TZ('.$str_time.', \'+00:00\', \'+'.$this->_calOffsetHourGMT().':00\'))', 'hour');
                        $collection->addExpressionFieldToSelect('day', 'DAY(CONVERT_TZ('.$str_time.', \'+00:00\', \'+'.$this->_calOffsetHourGMT().':00\'))', 'day');
                        $collection->getSelect()->group(array('hour'));
                    }

                    $where = 'CONVERT_TZ(main_table.'.$str_time.', \'+00:00\', \'+'.$this->_calOffsetHourGMT().':00\')';
                    $collection->getSelect()->where($where.' >= "'.$start_hour.'" AND '.$where.' <= "'.$end_hour.'"');
                    break;
                case MW_Affiliate_Model_Admin_Const::REPORT_RAGE_LAST_WEEK:
                    /* Last week */
                    $start_day = date('Y-m-d',strtotime("-7 day", strtotime("Sunday Last Week")));
                    $end_day = date('Y-m-d',strtotime("Sunday Last Week"));
                    if($group == true)
                    {
                        $collection->addExpressionFieldToSelect('day', 'DAY('.$str_time.')', 'day');
                        $collection->getSelect()->group(array('day'));
                    }

                    $where = 'CONVERT_TZ(main_table.'.$str_time.', \'+00:00\', \'+'.$this->_calOffsetHourGMT().':00\')';
                    $collection->getSelect()->where($where.' >= "'.$start_day.'" AND '.$where.' <= "'.$end_day.'"');
                    break;
                case MW_Affiliate_Model_Admin_Const::REPORT_RAGE_LAST_MONTH:
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
                        $collection->addExpressionFieldToSelect('day', 'DAY('.$str_time.')', 'day');
                        $collection->getSelect()->group(array('day'));
                    }

                    $where = 'CONVERT_TZ(main_table.'.$str_time.', \'+00:00\', \'+'.$this->_calOffsetHourGMT().':00\')';
                    $collection->getSelect()->where($where.' >= "'.$start_day.'" AND '.$where.' <= "'.$end_day.'"');
                    break;
                case MW_Affiliate_Model_Admin_Const::REPORT_RAGE_LAST_7DAYS:
                case MW_Affiliate_Model_Admin_Const::REPORT_RAGE_LAST_30DAYS:
                    /** Last X days */
                    if($data['report_range'] == MW_Affiliate_Model_Admin_Const::REPORT_RAGE_LAST_7DAYS)
                    {
                        $last_x_day = 7;
                    }
                    else if($data['report_range'] == MW_Affiliate_Model_Admin_Const::REPORT_RAGE_LAST_30DAYS)
                    {
                        $last_x_day = 30;
                    }
                    $start_day = date('Y-m-d h:i:s', strtotime('-'.$last_x_day.' day', Mage::getModel('core/date')->gmtTimestamp()));
                    $end_day = date('Y-m-d h:i:s', strtotime("-1 day"));
                    if($group == true)
                    {
                        $collection->getSelect()->group(array('day'));
                    }

                    $collection->addExpressionFieldToSelect('month', 'MONTH('.$str_time.')', 'month');
                    $collection->addExpressionFieldToSelect('day', 'DAY('.$str_time.')', 'day');
                    $collection->addExpressionFieldToSelect('year', 'YEAR('.$str_time.')', 'year');

                    $where = 'CONVERT_TZ(main_table.'.$str_time.', \'+00:00\', \'+'.$this->_calOffsetHourGMT().':00\')';
                    $collection->getSelect()->where($where.' >= "'.$start_day.'" AND '.$where.' <= "'.$end_day.'"');
                    break;
                case MW_Affiliate_Model_Admin_Const::REPORT_RAGE_CUSTOM:
                    /* Custom range */

                    if($group == true)
                    {
                        $collection->addExpressionFieldToSelect('month', 'MONTH('.$str_time.')', 'month');
                        $collection->addExpressionFieldToSelect('day', 'DAY('.$str_time.')', 'day');
                        $collection->addExpressionFieldToSelect('year', 'YEAR('.$str_time.')', 'year');
                        $collection->getSelect()->group(array('day'));
                    }

                    $where = 'CONVERT_TZ(main_table.'.$str_time.', \'+00:00\', \'+'.$this->_calOffsetHourGMT().':00\')';
                    $collection->getSelect()->where($where.' >= "'.$data['from'].'" AND '.$where.' <= "'.$data['to'].'"');
                    break;
            }
    }
    
    protected function _buildArrayDate($type, $from = 0, $to = 23, $original_time = null)
    {
            switch($type)
            {
                case MW_Affiliate_Model_Admin_Const::REPORT_RAGE_LAST_24H:
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
                case  MW_Affiliate_Model_Admin_Const::REPORT_RAGE_LAST_WEEK:
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
                case  MW_Affiliate_Model_Admin_Const::REPORT_RAGE_LAST_MONTH:
                    for($i = (int)$from; $i <= $to; $i++)
                    {
                        $data[$i]['native_day'] = (int)$i;
                    }
                    break;
                case  MW_Affiliate_Model_Admin_Const::REPORT_RAGE_CUSTOM:
                    $total_days = $this->_dateDiff($original_time['from'], $original_time['to']);
                    if($total_days > 365)
                    {

                    }
                    else
                    {
                        $all_months = $this->_get_months($original_time['from'], $original_time['to']);
                        $start_time = strtotime($original_time['from']);
                        $start_day  = (int)date('d', $start_time);
                        $count      = 0;
                        $data       = array();

                        $end_day_time = strtotime($original_time['to']);

                        $end_day = array(
                            'm' => (int)date('m', $end_day_time),
                            'd' => (int)date('d', $end_day_time),
                            'y' => (int)date('Y', $end_day_time)
                        );
                        
                        foreach($all_months as $month)
                        {
                            $day_in_month = $this->_days_in_month($month['m'], $month['y']);
                            for($day = ($count == 0 ? $start_day : 1); $day <= $day_in_month; $day++)
                            {
                                if($day > $end_day['d'] && $month['m'] == $end_day['m'] && $month['y'] == $end_day['y']){
                                    continue;
                                }
                                $data[$month['y']][$month['m']][$day] = $day;
                            }
                            $count++;
                        }
                    }
                    break;
            }
            return $data;
    }
    
    protected function _get_months($start, $end){
        $start = $start=='' ? time() : strtotime($start);
        $end = $end=='' ? time() : strtotime($end);
        $months = array();
        $data = array();
        
        for ($i = $start; $i <= $end; $i = $this->get_next_month($i)) {
            $data['m'] = (int)date('m', $i);
            $data['y'] = (int)date('Y', $i);
            array_push($months,$data);
        }

        return $months;
    }
        
    protected function get_next_month($tstamp) {
        return (strtotime('+1 months', strtotime(date('Y-m-01', $tstamp))));
    }
        
    protected function _calOffsetHourGMT()
    {
        return Mage::getSingleton('core/date')->calculateOffset(Mage::app()->getStore()->getConfig('general/locale/timezone'))/60/60;
    }
    
    protected function _getLastMonthTime()
    {
        return  date('Y-m-d', strtotime("-1 month"));
    }
    
    protected function _days_in_month($month, $year)
    {
        $year = (!$year) ? date('Y', Mage::getSingleton('core/date')->gmtTimestamp()) : $year;
        return $month == 2 ? ($year % 4 ? 28 : ($year % 100 ? 29 : ($year % 400 ? 28 : 29))) : (($month - 1) % 7 % 2 ? 30 : 31);
    }
    
    protected function _validationDate($data)
    {
        if(strtotime($data['from']) > strtotime($data['to']))
            return false;
        return true;
    }
    
    protected function getPreviousDateTime($hour)
    {
        return Mage::getModel('core/date')->gmtTimestamp() - (3600 * $hour);
    }
    
    protected function _dateDiff($d1, $d2)
    {
        // Return the number of days between the two dates:
        return round(abs(strtotime($d1) - strtotime($d2))/86400);
    }
    
    protected function preapareCollectionPieChart($data, $totalsales, $totalcommission){ 
        $resource = Mage::getModel('core/resource');
        $program_table = $resource->getTableName('affiliate/affiliateprogram');
        $order_table =  Mage::getSingleton('core/resource')->getTableName('sales/order');
         /** Query to piechart */
        $collection = Mage::getModel('affiliate/affiliatehistory')->getCollection();
        $collection->removeAllFieldsFromSelect();
        $collection->addFieldToFilter('main_table.status', MW_Affiliate_Model_Status::COMPLETE);
        $collection->getSelect()->join(array('programs_entity' => $program_table), 'main_table.program_id = programs_entity.program_id', array());
        $collection->addExpressionFieldToSelect('program','programs_entity.program_name','program');
        $collection->addExpressionFieldToSelect('total_affiliate_commission', 'if(sum(main_table.history_commission),sum(main_table.history_commission),0)', 'total_affiliate_commission');
        $collection->getSelect()->group(array('main_table.program_id'));
        $this->_buildCollection($collection, $data, false, 'transaction_time');
        $collection_piechart = $collection;
        
        /** Query to piechart sales by Programs */
        $collection = Mage::getModel('affiliate/affiliatehistory')->getCollection();
        $collection->removeAllFieldsFromSelect();
        $collection->addFieldToFilter('main_table.status', MW_Affiliate_Model_Status::COMPLETE);
        $collection->getSelect()->group(array('main_table.program_id'));
        $collection->getSelect()->group(array('main_table.order_id'));
        $collection->getSelect()->join(array('programs_entity' => $program_table), 'main_table.program_id = programs_entity.program_id', array());
        $collection->getSelect()->join(array('sales_order' => $order_table), 'main_table.order_id = sales_order.increment_id', array());
        $collection->addExpressionFieldToSelect('program','programs_entity.program_name','program');
        $collection->addExpressionFieldToSelect('sales_order_total','sales_order.subtotal','sales_order_total');
        $this->_buildCollection($collection, $data, false, 'transaction_time');
        $collection_piechart_sales = $collection;
        
        $dta_sales_buf = array();
        $dta_sales = array();        
        $dta_commission = array();
        
        foreach($collection_piechart_sales as $key => $order){
            $dta_sales_buf[$order->getData('program')] += $order->getData('sales_order_total');
        }
        if($totalsales > 0){
            $dta_sales['Non Programs'] = 100;
            foreach($dta_sales_buf as $key => $value){
                $dta_sales[$key] = $value/$totalsales * 100;
                $dta_sales['Non Programs'] = $dta_sales['Non Programs'] -  $dta_sales[$key];
            }
        }
        if($totalcommission > 0){
            $dta_commission['Non Programs'] = 100;
            foreach($collection_piechart as $key => $program){
                $dta_commission[$program->getData('program')] = $program->getData('total_affiliate_commission')/$totalcommission * 100;
                $dta_commission['Non Programs'] = $dta_commission['Non Programs'] - $dta_commission[$program->getData('program')];
            }
        }
        
        $sales = array();
        $commission = array();

        foreach($dta_sales as $key => $percent ){
            if($percent > 0.1){
                $sales[]= array(Mage::helper('affiliate')->__(ucfirst($key)), $percent);
            }
        }

        foreach($dta_commission as $key => $percent ){
            if($percent > 0.1){
                $commission[]= array(Mage::helper('affiliate')->__(ucfirst($key)), $percent);
            }
        }
        
        $_data['sales'] = $sales;
        $_data['commission'] = $commission;
        return $_data;
    }
    
    protected function preapareCollectionPieChartFrontend($data, $totalcommission){
        $resource = Mage::getModel('core/resource');
        $program_table = $resource->getTableName('affiliate/affiliateprogram');
         /** Query to piechart */        
        $collection = Mage::getModel('affiliate/affiliatehistory')->getCollection();
        $collection->removeAllFieldsFromSelect();
        $collection->addFieldToFilter('main_table.status', MW_Affiliate_Model_Status::COMPLETE);
        $collection->addFieldToFilter('main_table.customer_invited', $data['customer_id']);
        $collection->getSelect()->join(array('programs_entity' => $program_table), 'main_table.program_id = programs_entity.program_id', array());
        $collection->addExpressionFieldToSelect('program','programs_entity.program_name','program');
        $collection->addExpressionFieldToSelect('total_affiliate_commission', 'if(sum(history_commission),sum(history_commission),0)','total_affiliate_commission');
        $collection->getSelect()->group(array('main_table.program_id'));
        $this->_buildCollection($collection, $data, false, 'transaction_time');
        
        $collection_piechart_by_programs = $collection;
        
        /** Query Commission by my sales */
        $collection = Mage::getModel('affiliate/affiliatetransaction')->getCollection();
        $collection->removeAllFieldsFromSelect();
        $collection->addFieldToFilter('main_table.status', MW_Affiliate_Model_Status::COMPLETE);
        $collection->addFieldToFilter('main_table.customer_invited', $data['customer_id']);
        $collection->addFieldToFilter('main_table.customer_id', $data['customer_id']);
        $collection->addFieldToFilter('main_table.order_id', array('neq' => ''));
        $collection->addExpressionFieldToSelect('self_commission', 'if(sum(total_commission),sum(total_commission),0)','self_commission');
        $this->_buildCollection($collection, $data, false, 'transaction_time');
        $collection_self_commission = $collection->getFirstItem();
        
        /** Query Commission by other Actions */
        $collection = Mage::getModel('affiliate/affiliatetransaction')->getCollection();
        $collection->removeAllFieldsFromSelect();
        $collection->addFieldToFilter('main_table.status', MW_Affiliate_Model_Status::COMPLETE);
        $collection->addFieldToFilter('main_table.customer_invited', $data['customer_id']);
        $collection->addFieldToFilter('main_table.order_id', '');
        $collection->addExpressionFieldToSelect('refferrallink_commission', 'if(sum(total_commission),sum(total_commission),0)','refferrallink_commission');
        $this->_buildCollection($collection, $data, false, 'transaction_time');
        $collection_other_commission = $collection->getFirstItem();        
        
        $dta_commission_by_programs = array();
        $dta_commission_by_members = array();    
 
        if($totalcommission > 0){
            $dta_commission_by_programs['Non Programs'] = 100;
            foreach($collection_piechart_by_programs as $key => $program){
                $dta_commission_by_programs[$program->getData('program')] = $program->getData('total_affiliate_commission')/$totalcommission * 100;
                $dta_commission_by_programs['Non Programs'] = $dta_commission_by_programs['Non Programs'] - $dta_commission_by_programs[$program->getData('program')];
            }
        }        

        if($totalcommission > 0){
            $dta_commission_by_members['From My Purchases'] = $collection_self_commission->getData('self_commission')/$totalcommission * 100;
            $dta_commission_by_members['Other Sources'] = $collection_other_commission->getData('refferrallink_commission')/$totalcommission * 100;
            $dta_commission_by_members['From My Group Sale'] = 100 - $dta_commission_by_members['From My Purchases'] - $dta_commission_by_members['Other Sources'];
        }
        $commission_by_programs = array();
        $commission_by_members = array();
        
        foreach($dta_commission_by_members as $key => $percent ){
            if($percent > 0.1){
                $commission_by_members[]= array(Mage::helper('affiliate')->__(ucfirst($key)), $percent);
            }
        }
        foreach($dta_commission_by_programs as $key => $percent ){
            if($percent > 0.1){
                $commission_by_programs[]= array(Mage::helper('affiliate')->__(ucfirst($key)), $percent);
            }
        }
        $_data['commission_by_members'] = $commission_by_members;
        $_data['commission_by_programs'] = $commission_by_programs;
        return $_data;
    }
    
    public function _countAffiliateChilds($aff_id, $date, &$count){
        $collection = Mage::getModel('affiliate/affiliatecustomers')->getCollection();
        $collection->addFieldToFilter('main_table.status', 1);
        $collection->addFieldToFilter('main_table.customer_invited', $aff_id);
        $collection->addFieldToFilter('main_table.referral_code', array('neq'=>''));
        $collection->addFieldToFilter('customer_time',array('from' => null,'to' => $date));
    
        if(count($collection->getData()) == 0){
            return true;
        }else{
            $count += count($collection->getData());
            foreach($collection as $aff){
               $this->_countAffiliateChilds($aff->getData('customer_id'),$data, $count);
            }
        }
    }
    
    public function _getArrayCustomers($aff_id, $date, &$array_members){
        $collection = Mage::getModel('affiliate/affiliatecustomers')->getCollection();
        $collection->removeAllFieldsFromSelect();
        $collection->addFieldToFilter('main_table.status', 1);
        $collection->addFieldToFilter('main_table.customer_invited', $aff_id);
        $collection->addFieldToFilter('customer_time',array('from' => null,'to' => $date));
        $collection->addExpressionFieldToSelect('customer_id', 'main_table.customer_id', 'customer_id');
        
        if(count($collection->getData()) == 0){
            return true;
        }else{
            foreach($collection as $aff){
                array_push($array_members,$aff->getData('customer_id'));
            }
            
            foreach($collection as $aff){
                $this->_getArrayCustomers($aff->getData('customer_id'),$date,$array_members);
            }
        }
    }
    
    public function getTotalAffiliateSales($aff_id, $data, $order_status_add, $date){        
        //Get Sales by this affiliate
        $collection = Mage::getModel('sales/order')->getCollection(); 
        $collection->addFieldToFilter('main_table.status',array('in' => $order_status_add));
        $collection->addFieldToFilter('main_table.customer_id',$aff_id);
        $this->_buildCollection($collection, $data, false, 'updated_at');

        $collection->removeAllFieldsFromSelect();
        $collection->addExpressionFieldToSelect('total_sales','sum(main_table.subtotal)','total_sales');   
        $myselfSale = $collection->getFirstItem()->getData('total_sales');  
        
        $totalSales = 0;
        $totalSales = $totalSales + $myselfSale;    
        //Get Array Customer of this affiliate
        $collection = Mage::getModel('affiliate/affiliatecustomers')->getCollection();
        $collection->removeAllFieldsFromSelect();
        $collection->addFieldToFilter('main_table.status', 1);
        $collection->addFieldToFilter('main_table.customer_invited', $aff_id);
        $collection->addFieldToFilter('main_table.referral_code', '');
        $collection->addFieldToFilter('customer_time',array('from' => null,'to' => $date));
        $collection->addExpressionFieldToSelect('customer_id', 'main_table.customer_id', 'customer_id');
        
        $total_sale_customer = 0;
        foreach($collection->getData() as $aff){
            $total_sale_customer += $this->getSalesByCustomer($aff['customer_id'],$order_status_add,$data);
        }  
        
        $totalSales = $totalSales + $total_sale_customer;
        
        return $totalSales;         
    }
    
    public function getSalesByCustomer($custom_id,$order_status_add,$data){
        $collection = Mage::getModel('sales/order')->getCollection(); 
        $collection->addFieldToFilter('main_table.status',array('in' => $order_status_add));
        $collection->addFieldToFilter('main_table.customer_id',$custom_id);
        $this->_buildCollection($collection, $data, false, 'updated_at');

        $collection->removeAllFieldsFromSelect();
        $collection->addExpressionFieldToSelect('total_sales','sum(main_table.subtotal)','total_sales');   
        return $collection->getFirstItem()->getData('total_sales');  
    }
}
