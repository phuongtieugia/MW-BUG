<?php

class MW_Onestepcheckout_Model_Subscriber extends Mage_Newsletter_Model_Subscriber
{
    /**
     * Load subscriber info by customer
     *
     * @param Mage_Customer_Model_Customer $customer
     * @return Mage_Newsletter_Model_Subscriber
     */
    public function loadByCustomer(Mage_Customer_Model_Customer $customer)
    {
        $data = $this->getResource()->loadByCustomer($customer);
        $this->addData($data);
        if (!empty($data) && $customer->getId() && !$this->getCustomerId()) {
            $this->setCustomerId($customer->getId());
            $this->setSubscriberConfirmCode($this->randomSequence());
			
			//this code fix for subscribe while registerring new account using Onestepcheckout
			$email_subscribe=$this->getResource()->loadByEmail($customer->getEmail());
			if(!empty($email_subscribe['subscriber_confirm_code']))
			$this->setSubscriberConfirmCode($email_subscribe['subscriber_confirm_code']);
			//end
			
            if ($this->getStatus()==self::STATUS_NOT_ACTIVE) {
                $this->setStatus($customer->getIsSubscribed() ? self::STATUS_SUBSCRIBED : self::STATUS_UNSUBSCRIBED);
            }
            $this->save();
        }
        return $this;
    }
}