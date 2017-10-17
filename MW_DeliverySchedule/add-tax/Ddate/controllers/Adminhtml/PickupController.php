<?php

class MW_Ddate_Adminhtml_PickupController extends Mage_Adminhtml_Controller_Action
{
	protected function _initAction()
	{
		$this->loadLayout()
			->_setActiveMenu('pickup/items')
			->_addBreadcrumb(
				Mage::helper('adminhtml')->__('Pickup Scheduled Manager'), 
				Mage::helper('adminhtml')->__('Pickup Scheduled Manager')
			);
		
		return $this;
	}   

	public function indexAction()
	{
		$this->_initAction()->renderLayout();
	}

	public function editAction()
	{
		$id     = $this->getRequest()->getParam('pickup_id');
		$model  = Mage::getModel('ddate/pickup')->load($id);
		
		if ($model->getPickupId() || $id == 0) {
			$data = Mage::getSingleton('adminhtml/session')->getFormData(true);
			if (!empty($data)) {
				$model->setData($data);
			}

            Mage::register('pickup_data', $model);

			$this->loadLayout();
			$this->_setActiveMenu('pickup/items');
			$this->_addBreadcrumb(Mage::helper('adminhtml')->__('Item Manager'), Mage::helper('adminhtml')->__('Item Manager'));
			$this->_addBreadcrumb(Mage::helper('adminhtml')->__('Item News'), Mage::helper('adminhtml')->__('Item News'));
			$this->getLayout()->getBlock('head')->setCanLoadExtJs(true);

			$this->_addContent($this->getLayout()->createBlock('ddate/adminhtml_pickup_edit'))
				->_addLeft($this->getLayout()->createBlock('ddate/adminhtml_pickup_edit_tabs'));;

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
			if (!$this->getRequest()->getParam('pickup_id')) {
				$pickup = Mage::getModel('ddate/pickup_id')->getCollection()
						->addFieldToFilter('ddate', array('like'=>$data['ddate'].'%'))
						->addFieldToFilter('dtime', array('like'=>'%'.$data['dtime'].'%'));

				if ($pickup->count()) {
					Mage::getSingleton('adminhtml/session')->addError(
						Mage::helper('ddate')->__('Unable to save item. The delivery date and AM/PM must be unique.')
					);
					$this->_redirect('*/*/');
					return;
				}
			}
			
			$model = Mage::getModel('ddate/pickup');		
			$model->setData($data)
				->setPickupId($this->getRequest()->getParam('pickup_id'));
			
			try {
				$model->save();
				Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('ddate')->__('Item was successfully saved'));
				Mage::getSingleton('adminhtml/session')->setFormData(false);

				if ($this->getRequest()->getParam('back')) {
					$this->_redirect('*/*/edit', array('pickup_id' => $model->getPickupId()));
					return;
				}
				$this->_redirect('*/*/');
				return;
			} catch (Exception $e) {
				Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
				Mage::getSingleton('adminhtml/session')->setFormData($data);
				$this->_redirect('*/*/edit', array('pickup_id' => $this->getRequest()->getParam('pickup_id')));
				return;
			}
        }

        Mage::getSingleton('adminhtml/session')->addError(Mage::helper('ddate')->__('Unable to find item to save'));		
        $this->_redirect('*/*/');
	}

	public function deleteAction()
	{
		if ($this->getRequest()->getParam('ddate_id') > 0) {
			try {
				$model = Mage::getModel('ddate/ddate');
				$model->setDdateId($this->getRequest()->getParam('ddate_id'))
					->delete();
				Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__('Item was successfully deleted'));
				$this->_redirect('*/*/');
			} catch (Exception $e) {
				Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
				$this->_redirect('*/*/edit', array('ddate_id' => $this->getRequest()->getParam('ddate_id')));
			}
		}
		$this->_redirect('*/*/');
	}
}
