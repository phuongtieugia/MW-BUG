<?php
    /**
     * User: Anh TO
     * Date: 4/25/14
     * Time: 5:00 PM
     */
    class MW_RewardPoints_Model_Quote_Address_Total_Earnpoints extends Mage_Sales_Model_Quote_Address_Total_Abstract
    {
        public function __construct()
        {
            $this->setCode('earn_points');
        }

        public function fetch(Mage_Sales_Model_Quote_Address $address)
        {
            return $this;
        }
    }