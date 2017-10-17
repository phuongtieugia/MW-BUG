<?php
    class MW_Rewardpoints_Adminhtml_Rewardpoints_ProductsController extends Mage_Adminhtml_Controller_Action
    {
        protected function _isAllowed()
        {
            return Mage::getSingleton('admin/session')->isAllowed('promo/rewardpoints/earn_rewardpoints/products');
        }
        protected function _initAction()
        {
            $this->loadLayout()
                ->_setActiveMenu('promo/rewardpoints')
                ->_addBreadcrumb(Mage::helper('adminhtml')->__('Items Manager'), Mage::helper('adminhtml')->__('Item Manager'));

            return $this;
        }

        public function indexAction()
        {
            $this->loadLayout()->_setActiveMenu('promo/rewardpoints');
            $this->_addContent($this->getLayout()->createBlock('rewardpoints/adminhtml_products_edit'))
                ->_addLeft($this->getLayout()->createBlock('rewardpoints/adminhtml_products_edit_tabs'));
            $this->renderLayout();
        }

        public function importAction()
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
                                $content     = str_replace('"', '', $tmp);
                                $productInfo = explode(',', $content);
                                if(sizeof($productInfo) == 3)
                                {
                                    if($productInfo[0] && $productInfo[0] != '')
                                    {
                                        $product = Mage::getModel('catalog/product')->setWebsiteId($website_id)->load($productInfo[0]);
                                    }
                                    else if($productInfo[1] && $productInfo[1] != '')
                                    {
                                        $product = Mage::getModel('catalog/product')->setWebsiteId($website_id)->loadByAttribute('sku', $productInfo[1]);
                                    }
                                    if($product->getId())
                                    {
                                        $productInfo[2] = (int)trim($productInfo[2], "\n");
                                        if(is_numeric($productInfo[2]) && $productInfo[2] >= 0)
                                        {
                                            if($productInfo[2] == 0)
                                            {
                                                $productInfo[2] = '';
                                            }
                                            $attributesData = array('reward_point_product' => $productInfo[2]);
                                            Mage::getSingleton('catalog/product_action')->updateAttributes(array($product->getId()), $attributesData, 0);
                                        }
                                        else
                                        {
                                            $errors[] = Mage::helper('rewardpoints')->__('At rows %s reward points must be numeric', $line);
                                        }
                                    }
                                    else
                                    {
                                        $errors[] = Mage::helper('rewardpoints')->__('At rows %s product is not avaiable', $line);
                                    };
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
                        $this->_redirect("*/*/importProductPoints");
                    }
                }
                catch (Exception $e)
                {
                    Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                    $this->_redirect("*/*/importProductPoints");
                }
            }
            else
            {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('rewardpoints')->__("Please select a file to import"));
                $this->_redirect("*/*/importProductPoints");
            }
        }

        public function saveAction()
        {
            $data = $this->getRequest()->getPost();
            if($data)
            {
                try
                {
                    foreach ($data['reward_point_product'] as $key => $value)
                    {
                        if(substr_count($key, 'mw_') == 1 && $value != '')
                        {
                            $product    = explode('mw_', $key);
                            $product_id = $product[1];
                            if($value == 0)
                            {
                                $value = '';
                            }
                            //Mage::getModel('catalog/product')->load($product_id)->setRewardPointProduct($value)->save();
                            $attributesData = array('reward_point_product' => $value);

                            Mage::getSingleton('catalog/product_action')->updateAttributes(array($product_id), $attributesData, 0);

                        }

                    }
                    Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('rewardpoints')->__('The reward points has been saved successfully!'));
                    Mage::getSingleton('adminhtml/session')->setFormData(false);

                    $this->_redirect('*/*/index');

                    return;
                }
                catch (Exception $e)
                {
                    Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                    Mage::getSingleton('adminhtml/session')->setFormData($data);
                    //$this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                    $this->_redirect('*/*/index');

                    return;
                }
            }
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('rewardpoints')->__('Unable to find product to save'));
            $this->_redirect('*/*/index');
        }

        public function productGridAction()
        {
            $this->getResponse()->setBody(
                $this->getLayout()->createBlock('rewardpoints/adminhtml_products_edit_tab_grid', 'admin.rewardpoints.products')->toHtml()
            );
        }

        public function importProductPointsAction()
        {
            $this->loadLayout()->_setActiveMenu('promo/rewardpoints');
            $this->_addContent($this->getLayout()->createBlock('rewardpoints/adminhtml_products_import_edit'))
                ->_addLeft($this->getLayout()->createBlock('rewardpoints/adminhtml_products_import_edit_tabs'));
            $this->renderLayout();
        }

        public function sellAction()
        {
            $this->loadLayout()->_setActiveMenu('promo/rewardpoints');
            $this->_addContent($this->getLayout()->createBlock('rewardpoints/adminhtml_sellproducts_edit'))
                ->_addLeft($this->getLayout()->createBlock('rewardpoints/adminhtml_sellproducts_edit_tabs'));
            $this->renderLayout();
        }

        public function sellProductGridAction()
        {
            $this->getResponse()->setBody(
                $this->getLayout()->createBlock('rewardpoints/adminhtml_sellproducts_edit_tab_grid', 'admin.rewardpoints.sellproducts')->toHtml() .
                $this->getLayout()->createBlock('rewardpoints/adminhtml_sellproducts_edit_tab_headjs')->setTemplate('mw_rewardpoints/sellproducts/edit/tab/headjs.phtml')->toHtml()
            );
        }

        public function saveSellAction()
        {
            $data = $this->getRequest()->getPost();
            if($data)
            {
                try
                {
                    //zend_debug::dump($data['mw_reward_point_sell_product']);die();
                    foreach ($data['mw_reward_point_sell_product'] as $key => $value)
                    {
                        if(substr_count($key, 'mw_') == 1 && $value != '')
                        {
                            $product    = explode('mw_', $key);
                            $product_id = $product[1];
                            if($value == 0)
                            {
                                $value = '';
                            }
                            $attributesData = array('mw_reward_point_sell_product' => $value);

                            Mage::getSingleton('catalog/product_action')->updateAttributes(array($product_id), $attributesData, 0);

                        }

                    }
                    Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('rewardpoints')->__('The reward points has been saved successfully!'));
                    Mage::getSingleton('adminhtml/session')->setFormData(false);

                    $this->_redirect('*/*/sell');

                    return;
                }
                catch (Exception $e)
                {
                    Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                    Mage::getSingleton('adminhtml/session')->setFormData($data);
                    //$this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                    $this->_redirect('*/*/sell');

                    return;
                }
            }
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('rewardpoints')->__('Unable to find product to save'));
            $this->_redirect('*/*/sell');
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

        public function getChildProductAction()
        {
            $params = $this->getRequest()->getParams();
            switch ($params['type_id'])
            {
                case 'bundle':
                    $bundled_product = new Mage_Catalog_Model_Product();
                    $bundled_product->load($params['product_id']);
                    $selectionCollection = $bundled_product->getTypeInstance(true)->getSelectionsCollection(
                        $bundled_product->getTypeInstance(true)->getOptionsIds($bundled_product), $bundled_product
                    );
                    $bundled_items       = array();
                    foreach ($selectionCollection as $option)
                    {
                        $product = Mage::getModel('catalog/product')->load($option->product_id);
                    }
                    break;
            }
        }
    }