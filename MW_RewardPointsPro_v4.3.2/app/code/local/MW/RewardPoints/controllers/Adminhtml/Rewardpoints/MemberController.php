<?php

    class MW_RewardPoints_Adminhtml_Rewardpoints_MemberController extends Mage_Adminhtml_Controller_Action
    {
        protected function _isAllowed()
        {
            return Mage::getSingleton('admin/session')->isAllowed('promo/rewardpoints/member');
        }
        protected function _initAction()
        {
            $this->loadLayout()
                ->_setActiveMenu('promo/rewardpoints')
                ->_addBreadcrumb(Mage::helper('adminhtml')->__('Manager Members'), Mage::helper('adminhtml')->__('Manager Member'));

            return $this;
        }

        public function indexAction()
        {
            $this->_initAction()
                ->renderLayout();
        }

        public function editAction()
        {
            $id = $this->getRequest()->getParam('id');

            Mage::helper('rewardpoints/data')->checkAndInsertCustomerId($id, 0);

            $model = Mage::getModel('rewardpoints/customer')->load($id);

            if($model->getId() || $id == 0)
            {
                $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
                if(!empty($data))
                {
                    $model->setData($data);
                }

                Mage::register('rewardpoints_data_member', $model);

                $this->loadLayout();
                $this->_setActiveMenu('promo/rewardpoints');

                $this->_addBreadcrumb(Mage::helper('adminhtml')->__('Item Manager'), Mage::helper('adminhtml')->__('Item Manager'));
                $this->_addBreadcrumb(Mage::helper('adminhtml')->__('Item News'), Mage::helper('adminhtml')->__('Item News'));

                $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);

                $this->_addContent($this->getLayout()->createBlock('rewardpoints/adminhtml_member_edit'))
                    ->_addLeft($this->getLayout()->createBlock('rewardpoints/adminhtml_member_edit_tabs'));

                $this->renderLayout();
            }
            else
            {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('rewardpoints')->__('Member does not exist'));
                $this->_redirect('*/*/');
            }
        }

//	public function newAction() {
//		$this->_forward('edit');
//	}
        public function transactionAction()
        {
            $this->getResponse()->setBody($this->getLayout()->createBlock('rewardpoints/adminhtml_member_edit_tab_transaction')->toHtml());
        }

        public function saveAction()
        {
            $data = $this->getRequest()->getPost();
            if($data)
            {
                //Zend_Debug::dump($data);die();
                //var_dump($this->getRequest()->getParam('status'));exit;
                $data      = $this->getRequest()->getPost();
                $member_id = $this->getRequest()->getParam('id');
                //$model = Mage::getModel('rewardpoints/customer');
                try
                {
                    if($member_id != '')
                    {

                        $_customer = Mage::getModel('rewardpoints/customer')->load($member_id);
                        $store_id  = Mage::getModel('customer/customer')->load($_customer->getId())->getStoreId();
                        $oldPoints = $_customer->getMwRewardPoint();
                        $amount    = $data['reward_points_amount'];
                        $action    = $data['reward_points_action'];
                        $comment   = $data['reward_points_comment'];
                        $newPoints = $oldPoints + $amount * $action;

                        if($newPoints < 0)
                        {
                            $newPoints = 0;
                        }
                        $amount = abs($newPoints - $oldPoints);

                        if($amount > 0)
                        {
                            $detail = $comment;
                            $_customer->setData('mw_reward_point', $newPoints);
                            $_customer->save();
                            $balance = $_customer->getMwRewardPoint();

                            $results         = Mage::helper('rewardpoints/data')->getTransactionExpiredPoints($amount, $store_id);
                            $expired_day     = $results[0];
                            $expired_time    = $results[1];
                            $point_remaining = $results[2];

                            $historyData = array('type_of_transaction' => ($action > 0) ? MW_RewardPoints_Model_Type::ADMIN_ADDITION : MW_RewardPoints_Model_Type::ADMIN_SUBTRACT,
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
                            if($action < 0)
                            {
                                Mage::helper('rewardpoints/data')->processExpiredPointsWhenSpentPoints($_customer->getId(), $amount);
                            }

                            // send mail when points changed
                            Mage::helper('rewardpoints')->sendEmailCustomerPointChanged($_customer->getId(), $historyData, $store_id);
                        }

                    }

                    Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('rewardpoints')->__('The member has successfully saved'));
                    Mage::getSingleton('adminhtml/session')->setFormData(false);

                    if($this->getRequest()->getParam('back'))
                    {
                        $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));

                        return;
                    }
                    $this->_redirect('*/*/');

                    return;
                }
                catch (Exception $e)
                {
                    Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                    Mage::getSingleton('adminhtml/session')->setFormData($data);
                    $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));

                    return;
                }
            }
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('rewardpoints')->__('Unable to find member to save'));
            $this->_redirect('*/*/');
        }

        public function exportCsvAction()
        {
            $fileName = 'rewardpoints_member.csv';
            $content  = $this->getLayout()->createBlock('rewardpoints/adminhtml_member_grid')
                ->getCsv();

            $this->_sendUploadResponse($fileName, $content);
        }

        public function exportXmlAction()
        {
            $fileName = 'rewardpoints_member.xml';
            $content  = $this->getLayout()->createBlock('rewardpoints/adminhtml_member_grid')
                ->getXml();

            $this->_sendUploadResponse($fileName, $content);
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
    }