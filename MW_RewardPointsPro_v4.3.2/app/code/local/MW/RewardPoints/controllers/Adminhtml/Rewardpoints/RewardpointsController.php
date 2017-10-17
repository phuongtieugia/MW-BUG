<?php

    class MW_RewardPoints_Adminhtml_Rewardpoints_RewardpointsController extends Mage_Adminhtml_Controller_Action
    {
        protected function _isAllowed()
        {
            return Mage::getSingleton('admin/session')->isAllowed('promo/rewardpoints/earn_rewardpoints/products');
        }
        /* protected function _initCustomer($idFieldName = 'id')
         {
             $customerId = (int) $this->getRequest()->getParam($idFieldName);
             $customer = Mage::getModel('customer/customer');

             if ($customerId) {
                 $customer->load($customerId);
             }

             Mage::register('current_customer', $customer);
             return $this;
         }*/
        protected function _initAction()
        {
            $this->loadLayout()
                ->_setActiveMenu('promo/rewardpoints')
                ->_addBreadcrumb(Mage::helper('rewardpoints')->__('Reward Points Manager'), Mage::helper('rewardpoints')->__('Reward Points Manager'));

            return $this;
        }

        protected function _sendUploadResponse($fileName, $content, $contentType = 'application/octet-stream')
        {
            $response = $this->getResponse();
            $response->setHeader('HTTP/1.1 200 OK', '');
            $response->setHeader('Pragma', 'public', true);
            $response->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true);
            $response->setHeader('Content-Disposition', 'attachment; filename=' . $fileName);
            $response->setHeader('Last-Modified', date('r'));
            $response->setHeader('Accept-Ranges', 'bytes');
            $response->setHeader('Content-Length', strlen($content));
            $response->setHeader('Content-type', $contentType);
            $response->setBody($content);
            $response->sendResponse();
            die;
        }

        /*public function transactionAction()
        {
            $this->_initCustomer();
            $subscriber = Mage::getModel('newsletter/subscriber')
                ->loadByCustomer(Mage::registry('current_customer'));

            Mage::register('subscriber', $subscriber);
            $this->getResponse()->setBody($this->getLayout()->createBlock('rewardpoints/adminhtml_customer_edit_tab_rewardpoints_grid')->toHtml());
        }*/
        public function indexAction()
        {
            //$collection = Mage::getModel('rewardpoints/customer')->getcollection();

            $this->_initAction();
            //$block = $this->getLayout()->createBlock('rewardpoints/adminhtml_rewardpoints');
            //$this->getLayout()->getBlock('content')->append($block);
            $this->renderLayout();
        }

        public function exportCsvAction()
        {
            $fileName = 'rewardpoints.csv';
            $content  = $this->getLayout()->createBlock('rewardpoints/adminhtml_rewardpoints_grid')
                ->getCsv();

            $this->_sendUploadResponse($fileName, $content);
        }

        /*    public function exportXmlAction()
            {
                $fileName   = 'rewardpoints.xml';
                $content    = $this->getLayout()->createBlock('rewardpoints/adminhtml_rewardpoints_grid')
                    ->getXml();

                $this->_sendUploadResponse($fileName, $content);
            }*/
        public function importAction()
        {
            $this->loadLayout()->_setActiveMenu('promo/rewardpoints');
            $this->_addContent($this->getLayout()->createBlock('rewardpoints/adminhtml_rewardpoints_edit'))
                ->_addLeft($this->getLayout()->createBlock('rewardpoints/adminhtml_rewardpoints_edit_tabs'));
            $this->renderLayout();
        }

        public function saveAction()
        {
            if($_FILES['filename']['name'] != '')
            {
                try
                {
                    /* Starting upload */
                    $uploader = new Varien_File_Uploader('filename');

                    // Any extention would work
                    $uploader->setAllowedExtensions(array('csv'));
                    $uploader->setAllowRenameFiles(false);

                    // Set the file upload mode
                    // false -> get the file directly in the specified folder
                    // true -> get the file in the product like folders
                    //	(file.jpg will go in something like /media/f/i/file.jpg)
                    $uploader->setFilesDispersion(false);

                    // We set media as the upload dir
                    $path = Mage::getBaseDir('media') . DS;
                    $uploader->save($path, $_FILES['filename']['name']);
                    $filename = $path . $uploader->getUploadedFileName();

                    $fp     = @fopen($filename, 'r');
                    $line   = 1;
                    $errors = array();
                    if($fp)
                    {
                        $website_id = $this->getRequest()->getParam('website_id');

                        while (!feof($fp))
                        {

                            $tmp = fgets($fp); //Reading a file line by line
                            if($line > 1)
                            {
                                $content      = str_replace('"', '', $tmp);
                                $customerInfo = explode(',', $content);
                                if(sizeof($customerInfo) >= 3 && sizeof($customerInfo) <= 4)
                                {
                                    $customer = Mage::getModel('customer/customer')->setWebsiteId($website_id)->loadByEmail($customerInfo[1]);
                                    if($customer->getId())
                                    {

                                        Mage::helper('rewardpoints/data')->checkAndInsertCustomerId($customer->getId(), 0);
                                        $_customer       = Mage::getModel('rewardpoints/customer')->load($customer->getId());
                                        $store_id        = Mage::getModel('customer/customer')->load($customer->getId())->getStoreId();
                                        $customerInfo[2] = (int)trim($customerInfo[2], "\n");

                                        $detail        = 'Imported by Administrator';
                                        $detail_config = Mage::helper('rewardpoints/data')->getDefaultCommentConfig($store_id);
                                        if($detail_config != '')
                                        {
                                            $detail = $detail_config;
                                        }
                                        if(sizeof($customerInfo) == 4)
                                        {
                                            $customerInfo[3] = trim($customerInfo[3], "\n");
                                            if(isset($customerInfo[3]) && $customerInfo[3] != '')
                                            {
                                                $detail = $customerInfo[3];
                                            }
                                        }

                                        if(is_numeric($customerInfo[2]))
                                        {
                                            $oldPoints = $_customer->getMwRewardPoint();
                                            $newPoints = $oldPoints + $customerInfo[2];

                                            if($newPoints < 0)
                                            {
                                                $newPoints = 0;
                                            }
                                            $amount = abs($newPoints - $oldPoints);

                                            if($amount > 0)
                                            {
                                                $results         = Mage::helper('rewardpoints/data')->getTransactionExpiredPoints($amount, $store_id);
                                                $expired_day     = $results[0];
                                                $expired_time    = $results[1];
                                                $point_remaining = $results[2];

                                                $_customer->setData('mw_reward_point', $newPoints);
                                                $_customer->save();
                                                $balance     = $_customer->getMwRewardPoint();
                                                $historyData = array('type_of_transaction' => ($customerInfo[2] > 0) ? MW_RewardPoints_Model_Type::ADMIN_ADDITION : MW_RewardPoints_Model_Type::ADMIN_SUBTRACT,
                                                                     'amount'              => $amount,
                                                                     'balance'             => $balance,
                                                                     'transaction_detail'  => $detail,
                                                                     'transaction_time'    => Mage::getSingleton('core/date')->gmtDate(),
                                                                     'expired_day'         => $expired_day,
                                                                     'expired_time'        => $expired_time,
                                                                     'point_remaining'     => $point_remaining,
                                                                     'status'              => MW_RewardPoints_Model_Status::COMPLETE);
                                                $_customer->saveTransactionHistory($historyData);

                                                // process expired points when spent point
                                                if($customerInfo[2] < 0)
                                                {
                                                    Mage::helper('rewardpoints/data')->processExpiredPointsWhenSpentPoints($_customer->getId(), $amount);
                                                }
                                                // send mail when points changed
                                                Mage::helper('rewardpoints')->sendEmailCustomerPointChanged($_customer->getId(), $historyData, $store_id);
                                            }
                                            //$_customer->setData('mw_reward_point',$customerInfo[2]);
                                            //$_customer->save();
                                        }
                                        else
                                        {
                                            $errors[] = Mage::helper('rewardpoints')->__('At rows %s reward points must be numeric', $line);
                                        }
                                    }
                                    else
                                    {
                                        $errors[] = Mage::helper('rewardpoints')->__('At rows %s customer is not avaiable', $line);
                                    }
                                }
                            }
                            $line++;
                        }

                        if(sizeof($errors))
                        {
                            $err = Mage::helper('rewardpoints')->__("Some errors occur while importing points") . "<br>";
                            foreach ($errors as $error)
                                $err .= $error . "<br>";
                            Mage::getSingleton('adminhtml/session')->addError($err);
                        }
                        fclose($fp);
                        @unlink($filename);

                        Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('rewardpoints')->__('Your file was imported successfuly'));
                        $this->_redirect("*/*/");
                    }
                }
                catch (Exception $e)
                {
                    Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                    $this->_redirect("*/*/import");
                }
            }
            else
            {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('rewardpoints')->__("Please select a file to import"));
                $this->_redirect("*/*/import");
            }
        }
    }