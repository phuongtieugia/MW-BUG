<?php
    include_once 'lib/mw_rewardpoints/openinviter.php';
    class MW_RewardPoints_InvitationController extends Mage_Core_Controller_Front_Action
    {
        const EMAIL_TO_RECIPIENT_TEMPLATE_XML_PATH = 'rewardpoints/email_notifications/invitation_email';
        const XML_PATH_EMAIL_IDENTITY              = 'rewardpoints/email_notifications/email_sender';

        /**
         * Action predispatch
         *
         * Check customer authentication for some actions
         */
        public function preDispatch()
        {
            parent::preDispatch();
            if(!$this->getRequest()->isDispatched())
            {
                return;
            }
            if(!Mage::getSingleton('customer/session')->isLoggedIn())
            {
                $this->_redirectUrl(Mage::helper('customer')->getAccountUrl());
            }
        }

        /**
         * Retrieve customer session model object
         *
         * @return Mage_Customer_Model_Session
         */
        protected function _getSession()
        {
            return Mage::getSingleton('customer/session');
        }

        protected function getStringBetween($string, $startStr, $endStr)
        {
            $startStrIndex = strpos($string, $startStr);
            if($startStrIndex === false)
            {
                return false;
            }
            $startStrIndex++;
            $endStrIndex = strpos($string, $endStr, $startStrIndex);
            if($endStrIndex === false)
            {
                return false;
            }

            return substr($string, $startStrIndex, $endStrIndex - $startStrIndex);
        }

        protected function _sendEmailTransaction($emailto, $name, $template, $data)
        {
            $storeId    = Mage::app()->getStore()->getId();
            $templateId = Mage::getStoreConfig($template, $storeId);
            $customer   = $this->_getSession()->getCustomer();
            $translate  = Mage::getSingleton('core/translate');
            $translate->setTranslateInline(false);
            $sender = Mage::getStoreConfig(self::XML_PATH_EMAIL_IDENTITY, $storeId);

            try
            {
                Mage::getModel('core/email_template')
                    ->sendTransactional(
                        $templateId,
                        $sender,
                        $emailto,
                        $name,
                        $data,
                        $storeId);
                $translate->setTranslateInline(true);
            }
            catch (Exception $e)
            {
                $this->_getSession()->addError($this->__("Email can not send !"));
            }
        }

        public function indexAction()
        {
            $this->loadLayout();
            $this->_initLayoutMessages('customer/session');
            $this->_initLayoutMessages('checkout/session');
            $this->renderLayout();
        }

        public function loginmailAction()
        {
            $this->loadLayout();
            $this->_initLayoutMessages('customer/session');
            $this->renderLayout();

        }

        public function submitmailAction()
        {
            $ket_qua  = implode(",", $this->getRequest()->getPost('mw_contact_mail'));
            $ket_qua  = "'" . $ket_qua . "'";
            $contents = "";
            $contents = "<script type='text/javascript'>
    				//<![CDATA[
    					var value_old = window.opener.document.getElementById('email').value;
    					if(value_old !='')value_old = value_old+',';
    					window.opener.document.getElementById('email').value= value_old+$ket_qua;
            			window.close();
            		//]]>
					</script>";
            echo $contents;
            //zend_debug::dump($ket_qua);die();
        }

        public function processmailAction()
        {
            $ers         = array();
            $inviter     = new OpenInviter();
            $oi_services = $inviter->getPlugins();

            $email_box    = $this->getRequest()->getPost('email_box');
            $password_box = $this->getRequest()->getPost('password_box');
            $provider_box = $this->getRequest()->getPost('provider_box');
            $inviter->startPlugin($provider_box);
            $internal = $inviter->getInternalError();
            if($internal)
            {
                $ers['inviter'] = $internal;
            }
            elseif(!$inviter->login($email_box, $password_box))
            {
                $internal     = $inviter->getInternalError();
                $ers['login'] = ($internal ? $internal : $this->__("Login failed. Please check the email and password you have provided and try again later !"));
            }
            elseif(false === $contacts = $inviter->getMyContacts())
            {
                $ers['contacts'] = $this->__("Unable to get contacts !");
            }
            else
            {
                $this->loadLayout();
                $this->_initLayoutMessages('customer/session');
                $this->renderLayout();
            }
            if(sizeof($ers))
            {
                $err = implode("<br>", $ers);
                $this->_getSession()->addError($this->__("%s<br>", $err));
                $this->_redirect('rewardpoints/invitation/loginmail');
            }
        }

        public function inviteAction()
        {
            $post   = $this->getRequest()->getPost('email');
            $post   = trim($post, " ,");
            $emails = explode(',', $post);

            $validator = new Zend_Validate_EmailAddress();
            $error     = array();
            foreach ($emails as $email)
            {
                $name   = $email;
                $_name  = $this->getStringBetween($email, '"', '"');
                $_email = $this->getStringBetween($email, '<', '>');

                if($_email !== false && $_name !== false)
                {
                    $email = $_email;
                    $name  = $_name;
                }
                else if($_email !== false && $_name === false)
                {
                    if(strpos($email, '"') === false)
                    {
                        $email = $_email;
                        $name  = $email;
                    }
                }
                $email = trim($email);
                if($validator->isValid($email))
                {
                    // Send email to friend
                    $store_id   = Mage::app()->getStore()->getId();
                    $store_name = Mage::getStoreConfig('general/store_information/name', $store_id);
                    $template   = self::EMAIL_TO_RECIPIENT_TEMPLATE_XML_PATH;
                    $postObject = new Varien_Object();
                    $customer   = $this->_getSession()->getCustomer();
                    $postObject->setSender($customer);
                    $postObject->setMessage($this->getRequest()->getPost('message'));
                    $postObject->setData('invitation_link', Mage::helper('rewardpoints')->getLink($customer));
                    $postObject->setStoreName($store_name);
                    $this->_sendEmailTransaction($email, $name, $template, $postObject->getData());
                }
                else
                {
                    $error[] = $email;
                }
            }
            if(sizeof($error))
            {
                $err = implode("<br>", $error);
                $this->_getSession()->addError($this->__("These emails are invalid, the invitation message will not be sent to:<br>%s", $err));
            }
            $msg = $this->__("Your email was sent success");
            if(sizeof($emails) > 1)
            {
                $msg = $this->__("Your Emails were sent successfully");
            }
            if(sizeof($emails) > sizeof($error))
            {
                $this->_getSession()->addSuccess($this->__($msg));
            }
            $this->_redirect('rewardpoints/invitation/index');
        }

        public function inviteajaxAction()
        {
            $url     = trim($_POST["url_link"]);
            $post    = trim($_POST["email"]);
            $message = trim($_POST["message"]);
            if($post == "" || $message == "")
            {
                header('content-type: text/javascript');
                $mw_email   = 1;
                $mw_message = 1;
                if($post == "")
                {
                    $mw_email = 0;
                }
                if($message == "")
                {
                    $mw_message = 0;
                }
                $jsondata = array("message" => $mw_message,
                                  "email"   => $mw_email,
                                  "error"   => 0,
                                  "success" => 0);

                echo json_encode($jsondata);
                die();
            }
            $post   = trim($post, " ,");
            $emails = explode(',', $post);

            $validator = new Zend_Validate_EmailAddress();
            $error     = array();
            foreach ($emails as $email)
            {
                $name   = $email;
                $_name  = $this->getStringBetween($email, '"', '"');
                $_email = $this->getStringBetween($email, '<', '>');

                if($_email !== false && $_name !== false)
                {
                    $email = $_email;
                    $name  = $_name;
                }
                else if($_email !== false && $_name === false)
                {
                    if(strpos($email, '"') === false)
                    {
                        $email = $_email;
                        $name  = $email;
                    }
                }
                $email = trim($email);
                if($validator->isValid($email))
                {
                    // Send email to friend
                    $store_id   = Mage::app()->getStore()->getId();
                    $store_name = Mage::getStoreConfig('general/store_information/name', $store_id);
                    $template   = self::EMAIL_TO_RECIPIENT_TEMPLATE_XML_PATH;
                    $postObject = new Varien_Object();
                    $customer   = $this->_getSession()->getCustomer();
                    $postObject->setSender($customer);
                    $postObject->setMessage($message);
                    $postObject->setData('invitation_link', $url);
                    $postObject->setStoreName($store_name);
                    $this->_sendEmailTransaction($email, $name, $template, $postObject->getData());
                }
                else
                {
                    $error[] = $email;
                }
            }
            if(sizeof($error))
            {
                $err      = implode("<br>", $error);
                $mw_error = $this->__("These emails are invalid, the invitation message will not be sent to:<br>%s", $err);
                header('content-type: text/javascript');

                $jsondata = array("message" => 1,
                                  "email"   => 1,
                                  "error"   => $mw_error,
                                  "success" => 0);
                echo json_encode($jsondata);
                die();
            }
            $msg = 1;
            if(sizeof($emails) > 1)
            {
                $msg = 2;
            } //$msg = "Your Emails were sent successfully";
            if(sizeof($emails) > sizeof($error))
            {
                header('content-type: text/javascript');

                $jsondata = array("message" => 1,
                                  "email"   => 1,
                                  "error"   => 0,
                                  "success" => $msg);
                echo json_encode($jsondata);
                die();
            }
        }

        public function widgetAction()
        {
            $body = '<html><head><script type="text/javascript" src="https://www.plaxo.com/ab_chooser/abc_comm.jsdyn"></script></head><body></body></html>';
            $this->getResponse()->setBody($body);
        }

        public function checkAction()
        {
            if(Mage::helper('rewardpoints')->getInvitationModule())
            {
                $this->getResponse()->setBody("1");
            }
            else
            {
                $this->getResponse()->setBody("0");
            }
        }

        public function autologinAction()
        {
            $rule_encrypt = trim(Mage::app()->getRequest()->getParam('mw_ref'));
            if($rule_encrypt)
            {
                $data          = base64_decode($rule_encrypt);
                $datas         = explode(",", $data);
                $rule_decrypt  = $datas[1];
                $email_decrypt = $datas[2];
                //zend_debug::dump($datas);die();
                if($rule_decrypt && $email_decrypt)
                {
                    $customer = Mage::getModel('customer/customer')->setWebsiteId(Mage::app()->getStore()->getWebsiteId());
                    $customer = $customer->loadByEmail($email_decrypt);
                    if($customer->getId())
                    {
                        $customer_id         = $customer->getId();
                        $rule_id             = $rule_decrypt;
                        $type_of_transaction = MW_RewardPoints_Model_Type::CUSTOM_RULE;
                        $transactions        = Mage::getModel('rewardpoints/rewardpointshistory')->getCollection()
                            ->addFieldToFilter('customer_id', $customer_id)
                            ->addFieldToFilter('type_of_transaction', $type_of_transaction)
                            ->addFieldToFilter('transaction_detail', $rule_id)
                            ->addFieldToFilter('status', MW_RewardPoints_Model_Status::COMPLETE);

                        if(!sizeof($transactions))
                        {
                            Mage::helper('rewardpoints/data')->checkAndInsertCustomerId($customer_id, 0);
                            $_customer         = Mage::getModel('rewardpoints/customer')->load($customer_id);
                            $customer_group_id = Mage::getModel('customer/customer')->load($customer_id)->getGroupId();
                            $store_id          = Mage::app()->getStore()->getId();

                            $results         = Mage::getModel('rewardpoints/activerules')->getPointByRuleId($rule_id, $customer_group_id, $store_id);
                            $points          = $results[0];
                            $expired_day     = $results[1];
                            $expired_time    = $results[2];
                            $point_remaining = $results[3];
                            if($points)
                            {
                                $_customer->addRewardPoint($points);
                                $historyData = array('type_of_transaction' => $type_of_transaction,
                                                     'amount'              => $points,
                                                     'balance'             => $_customer->getMwRewardPoint(),
                                                     'transaction_detail'  => $rule_id,
                                                     'transaction_time'    => Mage::getSingleton('core/date')->gmtDate(),
                                                     'expired_day'         => $expired_day,
                                                     'expired_time'        => $expired_time,
                                                     'point_remaining'     => $point_remaining,
                                                     'status'              => MW_RewardPoints_Model_Status::COMPLETE);
                                $_customer->saveTransactionHistory($historyData);

                                // send mail when points changed
                                Mage::helper('rewardpoints')->sendEmailCustomerPointChanged($_customer->getId(), $historyData, $store_id);

                                //auto login
                                Mage::getSingleton('customer/session')->setCustomerAsLoggedIn($customer);

                                $request = Mage::app()->getRequest();
                                $request->setModuleName('rewardpoints')
                                    ->setControllerName('rewardpoints')
                                    ->setActionName('index')
                                    ->setDispatched(false);

                                Mage::getSingleton('core/session')->addSuccess(Mage::helper('rewardpoints')->__('Congratulation! %s Reward Points have been added to your account', $points));

                            }
                        }
                    }

                }

            }
        }

        public function testAction()
        {
            $invite = Mage::app()->getRequest()->getParam('mw_reward');
            if($invite)
            {
                echo $invite;
                die();
                $customer = Mage::getModel('customer/customer')->setWebsiteId(Mage::app()->getStore()->getWebsiteId());
                $customer = $customer->loadByEmail('phuoctha@gmail.com');
                Mage::getSingleton('customer/session')->setCustomerAsLoggedIn($customer);
                //Mage::getSingleton('customer/session')->renewSession();
                $request = Mage::app()->getRequest();
                $request->setModuleName('rewardpoints')
                    ->setControllerName('rewardpoints')
                    ->setActionName('index')
                    ->setDispatched(false);
                Mage::getSingleton('core/session')->addSuccess(Mage::helper('rewardpoints')->__('Thank you for visiting our site'));
            }

        }

    }