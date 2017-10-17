<?php



class MW_FollowUpEmail_Helper_Coupon extends Mage_Core_Helper_Abstract

{

    const MYSQL_DATETIME_FORMAT = 'Y-m-d';



    public function generateCode($rule,$email = '')

    {

		if($rule != null){			

			if($rule['coupon_status'] == 1){				

				return $this->saveCoupon($rule,$email);			

			}

		}

    	

    }



    public function saveCoupon($rule,$email = '')

    {		

		$coupon = Mage::getModel('followupemail/coupons');

		

        $day = ((int)$rule['coupon_expire_days'] * 24) * 3600;

		

        $expires = date(MW_FollowUpEmail_Model_Mysql4_Emailqueue::MYSQL_DATETIME_FORMAT, $day + time()); 

		

        $createdate = date(MW_FollowUpEmail_Model_Mysql4_Emailqueue::MYSQL_DATETIME_FORMAT, time());

		

        $uniqueCode = $rule['coupon_prefix'] . dechex($rule['rule_id']) . 'X' . strtoupper(uniqid());                                    

		

		$coupon->setCouponId(null)

				->setRuleId($rule['rule_id'])

               ->setSaleRuleId($rule['coupon_sales_rule_id'])

			   ->setCode($uniqueCode)

			   ->setUseCustomer($email)

			   ->setTimesUsed(0)

               ->setExpirationDate(null)               

               ->setCreatedAt(null)               

               ->setCouponStatus(MW_FollowUpEmail_Model_System_Config_Statuscoupon::COUPON_STATUS_PENDING);

		$coupon->save();    

		$data_to_shoppingcartrule = array(
        'coupon_type' => 2,
        'use_auto_generation' => 1,
        'coupon_code' => $uniqueCode,
      );

      // set the shopingcart_rule in question
      $shopingcart_rule = Mage::getModel('salesrule/rule')->load($rule['coupon_sales_rule_id']);
      if(isset($shopingcart_rule)){

        $shopingcart_rule->loadPost($data_to_shoppingcartrule);
        $shopingcart_rule->save();

        $data_saveto_salesrule_coupon = array(
          'rule_id' => $rule['coupon_sales_rule_id'],
          'code' => $uniqueCode,
          'type' => 1
        );

        //set the salesrule_coupon in question
        $salesrule_coupon = Mage::getModel('salesrule/coupon');
        $salesrule_coupon->setData($data_saveto_salesrule_coupon);
        $salesrule_coupon->save();
      }    

       return $uniqueCode;

    }



}

