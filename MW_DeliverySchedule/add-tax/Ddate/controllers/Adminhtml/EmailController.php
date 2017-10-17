<?php

class MW_Ddate_Adminhtml_EmailController extends Mage_Adminhtml_Controller_Action
{
	protected function _initAction()
	{
		$this->loadLayout()
			->_setActiveMenu('dtime/items')
			->_addBreadcrumb(Mage::helper('adminhtml')->__('Items Manager'), Mage::helper('adminhtml')->__('Item Manager'));

		return $this;
	}   

	public function indexAction()
	{
		$this->_initAction()->renderLayout();
	}

	public function editAction()
	{
		$id     = $this->getRequest()->getParam('dtime_id');
		$model  = Mage::getModel('ddate/dtime')->load($id);

		if ($model->getDtimeId() || $id == 0) {
			$data = Mage::getSingleton('adminhtml/session')->getFormData(true);
			if (!empty($data)) {
				$model->setData($data);
			}

            Mage::register('dtime_data', $model);

			$this->loadLayout();
			$this->_setActiveMenu('dtime/items');

			$this->_addBreadcrumb(Mage::helper('adminhtml')->__('Item Manager'), Mage::helper('adminhtml')->__('Item Manager'));
			$this->_addBreadcrumb(Mage::helper('adminhtml')->__('Item News'), Mage::helper('adminhtml')->__('Item News'));

			$this->getLayout()->getBlock('head')->setCanLoadExtJs(true);

			$this->_addContent($this->getLayout()->createBlock('ddate/adminhtml_dtime_edit'))
				->_addLeft($this->getLayout()->createBlock('ddate/adminhtml_dtime_edit_tabs'));;

			$this->renderLayout();
		} else {
			Mage::getSingleton('adminhtml/session')->addError(Mage::helper('ddate')->__('Item does not exist'));
			$this->_redirect('*/*/');
		}
	}

	public function newAction()
	{
		$this->_forward('edit');
	}

	public function saveAction()
	{
		if ($data = $this->getRequest()->getPost()) {					
			if (!isset($data['dtime_stores'])) {
				$data['dtime_stores'] = array(0=>'0');
			}

			$model = Mage::getModel('ddate/dtime');		
			$model->setData($data)
				->setDtimeId($this->getRequest()->getParam('dtime_id'));
			try {
				$model->save();
				Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('ddate')->__('Item was successfully saved'));
				Mage::getSingleton('adminhtml/session')->setFormData(false);

				if ($this->getRequest()->getParam('back')) {
					$this->_redirect('*/*/edit', array('dtime_id' => $model->getDtimeId()));
					return;
				}
				$this->_redirect('*/*/');
				return;
			} catch (Exception $e) {
				Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
				Mage::getSingleton('adminhtml/session')->setFormData($data);
				$this->_redirect('*/*/edit', array('dtime_id' => $this->getRequest()->getParam('dtime_id')));
				return;
			}
        }

        Mage::getSingleton('adminhtml/session')->addError(Mage::helper('ddate')->__('Unable to find item to save'));		
        $this->_redirect('*/*/');
	}
 
	public function deleteAction()
	{
		if($this->getRequest()->getParam('dtime_id') > 0) {
			try {
				$ddate = Mage::getModel('ddate/ddate')
						->getCollection()
						->addFieldToFilter('dtime',$this->getRequest()->getParam('dtime_id'));
                if (count($ddate->getData())!= 0) {
                	$dtime = Mage::getModel('ddate/dtime')->load($this->getRequest()->getParam('dtime_id'));
                	$dtime->delete();
                	Mage::getSingleton('adminhtml/session')->addSuccess(
                		Mage::helper('adminhtml')->__('Item was successfully deleted')
                	);
                } else {
                	Mage::getSingleton('adminhtml/session')->addError(
                		Mage::helper('adminhtml')->__('You can not delete this item')
                	);
                	$this->_redirect('*/*/edit', array('dtime_id' => $this->getRequest()->getParam('dtime_id')));
                }
				$this->_redirect('*/*/');
			} catch (Exception $e) {
				Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
				$this->_redirect('*/*/edit', array('dtime_id' => $this->getRequest()->getParam('dtime_id')));
			}
		}

		$this->_redirect('*/*/');
	}

    public function massDeleteAction()
    {
        $ddateIds = $this->getRequest()->getParam('dtime_id');
        $unbreak = "";
        $deleted = 0;
        if (!is_array($ddateIds)) {
			Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select item(s)'));
        } else {
            try {
                foreach ($ddateIds as $ddateId) {
                    $ddate = Mage::getModel('ddate/ddate')->getCollection()->addFieldToFilter('dtime',$ddateId);
                    if (count($ddate->getData())!=0) {
                    	$dtime = Mage::getModel('ddate/dtime')->load($ddateId);
                    	$dtime->delete();
                    	$deleted ++;
                    } else {
                    	$unbreak .= $ddateId.", ";
                    }
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__(
                        'Total of %d record(s) were successfully deleted', $deleted
                    )
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }

        $this->_redirect('*/*/index');
    }

    public function massStatusAction()
    {
        $ddateIds = $this->getRequest()->getParam('dtime_id');
        if(!is_array($ddateIds)) {
            Mage::getSingleton('adminhtml/session')->addError($this->__('Please select item(s)'));
        } else {
            try {
				$status = $this->getRequest()->getParam('status') == 1 ? 1 : 0;
                $i=0;
                foreach ($ddateIds as $ddateId) {
					$ddate = Mage::getModel('ddate/dtime')
						->load($ddateId)
						->setStatus($status)
						->save();
					$i++;
                }
                $this->_getSession()->addSuccess(
                    $this->__('Total of %d record(s) were successfully updated', $i)
                );
            } catch (Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            }
        }

        $this->_redirect('*/*/index');
    }
  
	public function exportCsvAction()
    {
        $fileName   = 'dtime.csv';
        $content    = $this->getLayout()->createBlock('ddate/adminhtml_dtime_grid')->getCsv();

        $this->_sendUploadResponse($fileName, $content);
    }

    public function exportXmlAction()
    {
        $fileName   = 'dtime.xml';
        $content    = $this->getLayout()->createBlock('ddate/adminhtml_dtime_grid')->getXml();

        $this->_sendUploadResponse($fileName, $content);
    }

	protected function _sendUploadResponse($fileName, $content, $contentType='application/octet-stream')
    {
        $response = $this->getResponse();
        $response->setHeader('HTTP/1.1 200 OK','');
        $response->setHeader('Pragma', 'public', true);
        $response->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true);
        $response->setHeader('Content-Disposition', 'attachment; filename='.$fileName);
        $response->setHeader('Last-Modified', date('r'));
        $response->setHeader('Accept-Ranges', 'bytes');
        $response->setHeader('Content-Length', strlen($content));
        $response->setHeader('Content-type', $contentType);
        $response->setBody($content);
        $response->sendResponse();
        die;
    }
}
