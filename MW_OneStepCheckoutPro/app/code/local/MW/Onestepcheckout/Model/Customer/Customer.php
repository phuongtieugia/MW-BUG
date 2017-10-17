<?php
class MW_Onestepcheckout_Model_Customer_Customer extends Mage_Customer_Model_Customer 
{
    public function validate()
    {
        $errors = array();
        $customerHelper = Mage::helper('customer');		
			
		if (!Zend_Validate::is( trim($this->getFirstname()) , 'NotEmpty')) {
			$errors[] = $customerHelper->__('The first name cannot be empty.');
		}
		if (!Zend_Validate::is( trim($this->getLastname()) , 'NotEmpty')) {
			$errors[] = $customerHelper->__('The last name cannot be empty.');
		}
	
		if (!Zend_Validate::is($this->getEmail(), 'EmailAddress')) {
			$errors[] = $customerHelper->__('Invalid email address "%s".', $this->getEmail());
		}
		
		if (empty($errors)) {
			return true;
		}
		return $errors;
		
    	
    }
}