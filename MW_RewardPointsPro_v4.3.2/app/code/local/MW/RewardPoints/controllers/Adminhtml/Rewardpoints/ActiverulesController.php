<?php

    class MW_RewardPoints_Adminhtml_Rewardpoints_ActiverulesController extends Mage_Adminhtml_Controller_Action
    {
        protected function _isAllowed()
        {
            return Mage::getSingleton('admin/session')->isAllowed('promo/rewardpoints/earn_rewardpoints/active_rules');
        }
        protected function _initAction()
        {
            $this->loadLayout()
                ->_setActiveMenu('promo/rewardpoints')
                ->_addBreadcrumb(Mage::helper('adminhtml')->__('Manager Members'), Mage::helper('adminhtml')->__('Manage Customer Behavior Rules'));

            return $this;
        }

        public function indexAction()
        {
            $this->_initAction()
                ->renderLayout();
        }

        public function editAction()
        {
            $id    = $this->getRequest()->getParam('id');
            $model = Mage::getModel('rewardpoints/activerules')->load($id);

            if($model->getId() || $id == 0)
            {
                $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
                if(!empty($data))
                {
                    $model->setData($data);
                }

                Mage::register('data_activerules', $model);

                $this->loadLayout();
                $this->_setActiveMenu('promo/rewardpoints');

                $this->_addBreadcrumb(Mage::helper('adminhtml')->__('Item Manager'), Mage::helper('adminhtml')->__('Item Manager'));
                $this->_addBreadcrumb(Mage::helper('adminhtml')->__('Item News'), Mage::helper('adminhtml')->__('Item News'));

                $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);

                $this->_addContent($this->getLayout()->createBlock('rewardpoints/adminhtml_activerules_edit'))
                    ->_addLeft($this->getLayout()->createBlock('rewardpoints/adminhtml_activerules_edit_tabs'));

                $this->renderLayout();
            }
            else
            {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('rewardpoints')->__('Rules does not exist'));
                $this->_redirect('*/*/');
            }
        }

        public function newAction()
        {
            $this->_forward('edit');
        }

        public function saveAction()
        {
            $data = $this->getRequest()->getPost();
            if($data)
            {
                //Zend_Debug::dump($data);die();
                $data    = $this->getRequest()->getPost();
                $rule_id = $this->getRequest()->getParam('id');
                $model   = Mage::getModel('rewardpoints/activerules');
                try
                {
                    $coupon_code        = "";
                    $store_view         = "";
                    $date_event         = "";
                    $comment            = "";
                    $customer_group_ids = "";
                    $expired_day        = 0;
                    $default_expired    = 0;

                    if(!isset($data['coupon_code']))
                    {
                        $data['coupon_code'] = $coupon_code;
                    }

                    if($data['type_of_transaction'] == MW_RewardPoints_Model_Type::CUSTOM_RULE && $data['coupon_code'] != '')
                    {
                        if($rule_id)
                        {
                            $active_points = Mage::getModel('rewardpoints/activerules')->getCollection()
                                ->addFieldToFilter('rule_id', array('nin' => array($rule_id)))
                                ->addFieldToFilter('coupon_code', $data['coupon_code']);

                        }
                        else
                        {
                            $active_points = Mage::getModel('rewardpoints/activerules')->getCollection()
                                ->addFieldToFilter('coupon_code', $data['coupon_code']);

                        }
                        if(sizeof($active_points) > 0)
                        {
                            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('rewardpoints')->__('The coupon code invalid'));
                            Mage::getSingleton('adminhtml/session')->setFormData($data);
                            $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));

                            return;
                        }


                    }


                    if(!isset($data['default_expired']))
                    {
                        $data['default_expired'] = $default_expired;
                    }
                    if(!isset($data['expired_day']) || $data['expired_day'] == '')
                    {
                        $data['expired_day'] = $expired_day;
                    }

                    if(!isset($data['date_event']))
                    {
                        $data['date_event'] = $date_event;
                    }
                    if(!isset($data['comment']))
                    {
                        $data['comment'] = $comment;
                    }
                    if(isset($data["customer_group_ids"]))
                    {
                        $customer_group_ids = implode(",", $data["customer_group_ids"]);
                    }
                    $data["customer_group_ids"] = $customer_group_ids;
                    if(isset($data["store_view"]))
                    {
                        if(in_array("0", $data["store_view"]))
                        {
                            $store_view = '0';
                        }
                        else
                        {
                            $store_view = implode(",", $data["store_view"]);
                        }
                    }
                    $data["store_view"] = $store_view;

                    if(Mage::app()->isSingleStoreMode())
                    {
                        $data['store_view'] = '0';
                    }

                    //zend_debug::dump($data);die();
                    $model->setData($data)->setId($rule_id);
                    $model->save();

                    Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('rewardpoints')->__('The rules has successfully saved'));
                    Mage::getSingleton('adminhtml/session')->setFormData(false);

                    if($this->getRequest()->getParam('back'))
                    {
                        $this->_redirect('*/*/edit', array('id' => $model->getId()));

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
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('rewardpoints')->__('Unable to find rules to save'));
            $this->_redirect('*/*/');
        }

        public function deleteAction()
        {
            if($this->getRequest()->getParam('id') > 0)
            {
                try
                {
                    $model = Mage::getModel('rewardpoints/activerules');

                    $model->setId($this->getRequest()->getParam('id'))
                        ->delete();

                    Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__('The rule has successfully deleted'));
                    $this->_redirect('*/*/');
                }
                catch (Exception $e)
                {
                    Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                    $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                }
            }
            $this->_redirect('*/*/');
        }

        public function massDeleteAction()
        {
            //var_dump($this->getRequest()->getParam('affiliateprogramGrid'));exit;
            $ruleIds = $this->getRequest()->getParam('activerules_grid');
            if(!is_array($ruleIds))
            {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select rule(s)'));
            }
            else
            {
                try
                {
                    foreach ($ruleIds as $ruleId)
                    {
                        $rule = Mage::getModel('rewardpoints/activerules')->load($ruleId);
                        $rule->delete();

                    }
                    Mage::getSingleton('adminhtml/session')->addSuccess(
                        Mage::helper('adminhtml')->__(
                            'Total of %d record(s) were successfully deleted', count($ruleIds)
                        )
                    );
                }
                catch (Exception $e)
                {
                    Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                }
            }
            $this->_redirect('*/*/index');
        }

        public function massStatusAction()
        {
            //echo $this->getRequest()->getParam('status_program');exit;
            $ruleIds = $this->getRequest()->getParam('activerules_grid');
            if(!is_array($ruleIds))
            {
                Mage::getSingleton('adminhtml/session')->addError($this->__('Please select rule(s)'));
            }
            else
            {
                try
                {
                    foreach ($ruleIds as $ruleId)
                    {
                        $rule = Mage::getSingleton('rewardpoints/activerules')
                            ->load($ruleId)
                            ->setStatus($this->getRequest()->getParam('status'))
                            ->setIsMassupdate(true)
                            ->save();
                    }
                    $this->_getSession()->addSuccess(
                        $this->__('Total of %d record(s) were successfully updated', count($ruleIds))
                    );
                }
                catch (Exception $e)
                {
                    $this->_getSession()->addError($e->getMessage());
                }
            }
            $this->_redirect('*/*/index');
        }

        public function exportCsvAction()
        {
            $fileName = 'activit_rules.csv';
            $content  = $this->getLayout()->createBlock('rewardpoints/adminhtml_activerules_grid')
                ->getCsv();

            $this->_sendUploadResponse($fileName, $content);
        }

        public function exportXmlAction()
        {
            $fileName = 'activit_rules.xml';
            $content  = $this->getLayout()->createBlock('rewardpoints/adminhtml_activerules_grid')
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