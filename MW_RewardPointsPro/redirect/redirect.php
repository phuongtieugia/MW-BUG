$a = Mage::getBaseUrl()."customer/account/login";
                $observer->getEvent()->getFront()->getResponse()->setRedirect($a);