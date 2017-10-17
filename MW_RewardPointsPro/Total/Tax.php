<?php

    class MW_RewardPoints_Model_Quote_Address_Total_Tax extends Mage_Tax_Model_Sales_Total_Quote_Tax
    {

        public function collect(Mage_Sales_Model_Quote_Address $address)
        {
            parent::collect($address);

            return $this;
        }


    }