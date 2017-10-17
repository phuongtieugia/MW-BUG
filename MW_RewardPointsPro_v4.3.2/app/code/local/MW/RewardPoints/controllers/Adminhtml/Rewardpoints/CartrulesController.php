<?php

    class MW_RewardPoints_Adminhtml_Rewardpoints_CartrulesController extends Mage_Adminhtml_Controller_Action
    {
        protected function _isAllowed()
        {
            return Mage::getSingleton('admin/session')->isAllowed('promo/rewardpoints/earn_rewardpoints/cart_rules');
        }
        protected function _initAction()
        {
            $this->loadLayout()
                ->_setActiveMenu('promo/rewardpoints')
                ->_addBreadcrumb(Mage::helper('adminhtml')->__('Shopping Cart Reward Rule'), Mage::helper('adminhtml')->__('Shopping Cart Reward Rules'));

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
            $model = Mage::getModel('rewardpoints/cartrules')->load($id);
            if($model->getId() || $id == 0)
            {
                $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
                if(!empty($data))
                {
                    $model->setData($data);
                }
                $model->getConditions()->setJsFormObject('rule_conditions_fieldset');
                $model->getActions()->setJsFormObject('rule_actions_fieldset');
                Mage::register('data_cart_rules', $model);

                $this->loadLayout();
                $this->_setActiveMenu('promo/rewardpoints');

                $this->_addBreadcrumb(Mage::helper('adminhtml')->__('Item Manager'), Mage::helper('adminhtml')->__('Item Manager'));
                $this->_addBreadcrumb(Mage::helper('adminhtml')->__('Item News'), Mage::helper('adminhtml')->__('Item News'));

                $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);

                $this->_addContent($this->getLayout()->createBlock('rewardpoints/adminhtml_cartrules_edit'))
                    ->_addLeft($this->getLayout()->createBlock('rewardpoints/adminhtml_cartrules_edit_tabs'));

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
                //var_dump($this->getRequest()->getParam('status'));exit;
                $data       = $this->getRequest()->getPost();
                $program_id = $this->getRequest()->getParam('id');
                $model      = Mage::getModel('rewardpoints/cartrules');
                try
                {
                    if(isset($_FILES['promotion_image']['name']) && $_FILES['promotion_image']['name'] != '')
                    {
                        try
                        {
                            /* Starting upload */
                            $uploader = new Varien_File_Uploader('promotion_image');

                            // Any extention would work
                            $uploader->setAllowedExtensions(array('jpg', 'jpeg', 'gif', 'png', 'bmp'));
                            $uploader->setAllowRenameFiles(true);

                            // Set the file upload mode
                            // false -> get the file directly in the specified folder
                            // true -> get the file in the product like folders
                            //	(file.jpg will go in something like /media/f/i/file.jpg)
                            $uploader->setFilesDispersion(false);
                            $file_name = $uploader->getCorrectFileName($_FILES['promotion_image']['name']);
                            // We set media as the upload dir
                            $path = Mage::getBaseDir('media') . DS . "mw_rewardpoint";
                            $uploader->save($path, $file_name);

                        }
                        catch (Exception $e)
                        {

                        }

                        //this way the name is saved in DB
                        //$data['image_name'] = 'mw_affiliate/'.$_FILES['image_name']['name'];
                        $data['promotion_image'] = 'mw_rewardpoint/' . $file_name;
                    }
                    else
                    {
                        if(isset($data['promotion_image']['delete']) && $data['promotion_image']['delete'] == 1)
                        {
                            $data['promotion_image'] = '';
                        }
                        else
                        {
                            unset($data['promotion_image']);
                        }
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
                    if(!$data["reward_step"])
                    {
                        $data["reward_step"] = 0;
                    }
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
                    $model = Mage::getModel('rewardpoints/cartrules');

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
                        $rule = Mage::getModel('rewardpoints/cartrules')->load($ruleId);
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
                        /* $rule = Mage::getSingleton('rewardpoints/cartrules')
                             ->load($ruleId)
                             ->setStatus($this->getRequest()->getParam('status'))
                             ->setIsMassupdate(true)
                             ->save();*/
                        $ruleId   = (int)$ruleId;
                        $status   = $this->getRequest()->getParam('status');
                        $resource = Mage::getSingleton('core/resource');
                        $query    = "UPDATE  {$resource->getTableName('rewardpoints/cartrules')} SET status=" . $status . " where rule_id = " . $ruleId . ";";
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
            $fileName = 'shopping_cart_reward_rules.csv';
            $content  = $this->getLayout()->createBlock('rewardpoints/adminhtml_cartrules_grid')
                ->getCsv();

            $this->_sendUploadResponse($fileName, $content);
        }

        public function exportXmlAction()
        {
            $fileName = 'shopping_cart_reward_rules.xml';
            $content  = $this->getLayout()->createBlock('rewardpoints/adminhtml_cartrules_grid')
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