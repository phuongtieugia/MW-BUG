<?php

    class MW_RewardPoints_Adminhtml_Rewardpoints_SpendcartrulesController extends Mage_Adminhtml_Controller_Action
    {
        protected function _isAllowed()
        {
            return Mage::getSingleton('admin/session')->isAllowed('promo/rewardpoints/spend_rewardpoints');
        }
        protected function _initAction()
        {
            $this->loadLayout()
                ->_setActiveMenu('promo/rewardpoints')
                ->_addBreadcrumb(Mage::helper('adminhtml')->__('Shopping Cart Spending Rules'), Mage::helper('adminhtml')->__('Shopping Cart Spending Rules'));

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
            $model = Mage::getModel('rewardpoints/spendcartrules')->load($id);
            if($model->getId() || $id == 0)
            {
                $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
                if(!empty($data))
                {
                    $model->setData($data);
                }
                Mage::getModel('rewardpoints/spendcartrules')->getConditions()->setJsFormObject('rule_conditions_fieldset');
                Mage::getModel('rewardpoints/spendcartrules')->getActions()->setJsFormObject('rule_actions_fieldset');
                Mage::register('data_cart_rules', $model);

                $this->loadLayout();
                $this->_setActiveMenu('promo/rewardpoints');

                $this->_addBreadcrumb(Mage::helper('adminhtml')->__('Item Manager'), Mage::helper('adminhtml')->__('Item Manager'));
                $this->_addBreadcrumb(Mage::helper('adminhtml')->__('Item News'), Mage::helper('adminhtml')->__('Item News'));

                $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);

                $this->_addContent($this->getLayout()->createBlock('rewardpoints/adminhtml_spendcartrules_edit'))
                    ->_addLeft($this->getLayout()->createBlock('rewardpoints/adminhtml_spendcartrules_edit_tabs'));

                $this->renderLayout();
            }
            else
            {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('rewardpoints')->__('Rule does not exist'));
                $this->_redirect('*/*/');
            }
        }

        public function newAction()
        {
            $this->_forward('edit');
        }

        public function saveAction()
        {
            if($data = $this->getRequest()->getPost())
            {
                //Zend_Debug::dump($data);die();
                $data       = $this->getRequest()->getPost();
                $program_id = $this->getRequest()->getParam('id');
                $model      = Mage::getModel('rewardpoints/spendcartrules');
                try
                {
                    if(!$data["reward_step"])
                    {
                        $data["reward_step"] = 0;
                    }
                    $customer_group_ids = "";
                    $store_view         = "";
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
                    if($data['rule_position'] == '')
                    {
                        $data['rule_position'] = 0;
                    }
                    if($program_id != '')
                    {

                        if(Mage::app()->isSingleStoreMode())
                        {
                            $data['store_view'] = '0';
                        }

                        $model->setData($data)->setId($program_id);
                        $model->save();
                        // save conditions
                        if(isset($data['rule']['conditions']))
                        {
                            $data['conditions'] = $data['rule']['conditions'];
                        }
                        if(isset($data['rule']['actions']))
                        {
                            $data['actions'] = $data['rule']['actions'];
                        }
                        $model->load($program_id);
                        unset($data['rule']);
                        $model->loadPost($data);
                        $model->save();
                    }
                    if($program_id == '')
                    {

                        if(Mage::app()->isSingleStoreMode())
                        {
                            $data['store_view'] = '0';
                        }

                        //Zend_Debug::dump($data);die();
                        $model->setData($data)->save();
                        // save conditions
                        if(isset($data['rule']['conditions']))
                        {
                            $data['conditions'] = $data['rule']['conditions'];
                        }
                        if(isset($data['rule']['actions']))
                        {
                            $data['actions'] = $data['rule']['actions'];
                        }
                        unset($data['rule']);
                        $model->loadPost($data);
                        $model->save();

                    }

                    Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('rewardpoints')->__('The rule has successfully saved'));
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
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('rewardpoints')->__('Unable to find rule to save'));
            $this->_redirect('*/*/');
        }

        public function deleteAction()
        {
            if($this->getRequest()->getParam('id') > 0)
            {
                try
                {
                    $model = Mage::getModel('rewardpoints/spendcartrules');

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
            $ruleIds = $this->getRequest()->getParam('cart_rule_Grid');
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
                        $rule = Mage::getModel('rewardpoints/spendcartrules')->load($ruleId);
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
            $ruleIds = $this->getRequest()->getParam('cart_rule_Grid');
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
                        /*
                        $rule = Mage::getSingleton('rewardpoints/spendcartrules')
                            ->load($ruleId)
                            ->setStatus($this->getRequest()->getParam('status'))
                            ->setIsMassupdate(true)
                            ->save();
                        */
                        $ruleId   = (int)$ruleId;
                        $status   = $this->getRequest()->getParam('status');
                        $resource = Mage::getSingleton('core/resource');
                        $query    = "UPDATE  {$resource->getTableName('rewardpoints/spendcartrules')} SET status=" . $status . " where rule_id = " . $ruleId . ";";
                        $conn     = $resource->getConnection('core_write');
                        $conn->query($query);
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
            $fileName = 'spend_shopping_cart_reward_rules.csv';
            $content  = $this->getLayout()->createBlock('rewardpoints/adminhtml_spendcartrules_grid')
                ->getCsv();

            $this->_sendUploadResponse($fileName, $content);
        }

        public function exportXmlAction()
        {
            $fileName = 'spend_shopping_cart_reward_rules.xml';
            $content  = $this->getLayout()->createBlock('rewardpoints/adminhtml_spendcartrules_grid')
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