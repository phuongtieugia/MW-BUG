<?php

class MW_Ddate_Adminhtml_DdateController extends Mage_Adminhtml_Controller_Action
{
	protected function _initAction()
	{
		$this->loadLayout()
			->_setActiveMenu('ddate/items')
			->_addBreadcrumb(Mage::helper('adminhtml')->__('Items Manager'), Mage::helper('adminhtml')->__('Item Manager'));
		
		return $this;
	}   

	public function indexAction()
	{
		$this->_initAction()->renderLayout();
	}

 	public function gridAction()
    {
        $history = $this->getLayout()->createBlock('ddate/adminhtml_ddate_edit_tab_order', 'ddate.grid.order');
        $this->getResponse()->setBody($history->toHtml());
    }

	public function editAction()
	{
		$id     = $this->getRequest()->getParam('ddate_id');
		$model  = Mage::getModel('ddate/ddate')->load($id);

		if ($model->getDdateId() || $id == 0) {
			$data = Mage::getSingleton('adminhtml/session')->getFormData(true);
			if (!empty($data)) {
				$model->setData($data);
			}

			Mage::register('ddate_data', $model);

			$this->loadLayout();
			$this->_setActiveMenu('ddate/items');
			$this->_addBreadcrumb(Mage::helper('adminhtml')->__('Item Manager'), Mage::helper('adminhtml')->__('Item Manager'));
			$this->_addBreadcrumb(Mage::helper('adminhtml')->__('Item News'), Mage::helper('adminhtml')->__('Item News'));

			$this->getLayout()->getBlock('head')->setCanLoadExtJs(true);

			$this->_addContent($this->getLayout()->createBlock('ddate/adminhtml_ddate_edit'))
				->_addLeft($this->getLayout()->createBlock('ddate/adminhtml_ddate_edit_tabs'));

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
			if(!$this->getRequest()->getParam('ddate_id')) {
				$ddates = Mage::getModel('ddate/ddate')->getCollection()
						->addFieldToFilter('ddate', array('like'=>$data['ddate'].'%'))
						->addFieldToFilter('dtime', array('like'=>'%'.$data['dtime'].'%'));

				if($ddates->count()){
					Mage::getSingleton('adminhtml/session')->addError(Mage::helper('ddate')->__('Unable to save item. The delivery date and AM/PM must be unique.'));
					$this->_redirect('*/*/');
					return;
				}
			}

			$model = Mage::getModel('ddate/ddate');		
			$model->setData($data)
				->setDdateId($this->getRequest()->getParam('ddate_id'));

			try {
				$model->save();
				Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('ddate')->__('Item was successfully saved'));
				Mage::getSingleton('adminhtml/session')->setFormData(false);

				if ($this->getRequest()->getParam('back')) {
					$this->_redirect('*/*/edit', array('ddate_id' => $model->getDdateId()));
					return;
				}
				$this->_redirect('*/*/');
				return;
			} catch (Exception $e) {
				Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
				Mage::getSingleton('adminhtml/session')->setFormData($data);
				$this->_redirect('*/*/edit', array('ddate_id' => $this->getRequest()->getParam('ddate_id')));
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

    public function massDeleteAction()
    {
        $ddateIds = $this->getRequest()->getParam('ddate_id');
        if (!is_array($ddateIds)) {
			Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select item(s)'));
        } else {
            try {
                foreach ($ddateIds as $ddateId) {
                    $ddate = Mage::getModel('ddate/ddate')->load($ddateId);
                    $ddate->delete();
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__('Total of %d record(s) were successfully deleted', count($ddateIds))
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }

        $this->_redirect('*/*/index');
    }
	
	public function massResetAction()
	{
        $ddateIds = $this->getRequest()->getParam('ddate_id');
        if (!is_array($ddateIds)) {
			Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select item(s)'));
        } else {
            try {
                foreach ($ddateIds as $ddateId) {
                    $ddate = Mage::getModel('ddate/ddate')->load($ddateId);
					$ddate->setOrdered(0);
					$ddate->save();
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__('Total of %d record(s) were successfully cleared', count($ddateIds))
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }

        $this->_redirect('*/*/index');
    }
	
    public function massStatusAction()
    {
        $ddateIds = $this->getRequest()->getParam('ddate_id');
        if(!is_array($ddateIds)) {
            Mage::getSingleton('adminhtml/session')->addError($this->__('Please select item(s)'));
        } else {
            try {
				$status = $this->getRequest()->getParam('status')==1?1:0;
                $i=0;foreach ($ddateIds as $ddateId) {
					if ($status==1 && Mage::getModel('ddate/ddate')->load($ddateId)->getOrdered()>0){
						$this->_getSession()->addError(Mage::getModel('ddate/ddate')->load($ddateId)->getDdate()." can not change to day off.");
					} else {
						$ddate = Mage::getSingleton('ddate/ddate')
							->load($ddateId)
							->setHoliday($status)
							->setIsMassupdate(true)
							->save();
						$i++;
					}
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
        $fileName   = 'ddate.csv';
        $content    = $this->getLayout()->createBlock('ddate/adminhtml_ddate_grid')
            ->getCsv();

        $this->_sendUploadResponse($fileName, $content);
    }

    public function exportXmlAction()
    {
        $fileName   = 'ddate.xml';
        $content    = $this->getLayout()->createBlock('ddate/adminhtml_ddate_grid')
            ->getXml();

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

	public function build_return_message($state=null,$message='')
	{	
		return '{"state":"'.$state.'","message":' .json_encode($message).'}';
	}

	public function isenableddayAction()
	{	
		$data=$this->getRequest()->getPost();
		$slotId = (isset($data['mwtime'])) ? $data['mwtime']: '';
		$date = (isset($data['mwdate'])) ? $data['mwdate'] : '';
		if (empty($date) || empty($slotId)) {
			echo $this->build_return_message('empty',Mage::helper('ddate')->__('Delivery Date or Time is empty!'));
			return;
		}

		if ($slotId=='deleted') {
			echo $this->build_return_message('deleted',Mage::helper('ddate')->__('This time slot had been deleted.Please choose an other time slot!'));
			return;
		}

		$enable=$this->isEnabled($slotId,$date);
		if ($enable !='ok') {
			echo $this->build_return_message('error',$enable);
		} else {
			echo $this->build_return_message('ok',Mage::helper('ddate')->__('Updated successfully!'));
		}
		
		return;
	}

	public function update_deliveryAction()
	{		
		$data=$this->getRequest()->getPost();
		$dtime = (isset($data['mwtime'])) ? $data['mwtime']: '';
        $dtime_osc = (isset($data['mwtime_osc'])) ? $data['mwtime_osc']: '';
		$comment = (isset($data['mwcomment'])) ? $data['mwcomment']: '';
        $ddate = (isset($data['mwdate'])) ? $data['mwdate'] : '';
		/**cornvert date into yyyy-mm-dd*/
		$ddate=Mage::helper('ddate')->convert_date_format($ddate);
		Mage::log($ddate, Zend_Log::DEBUG, 'debug.log');
		$data['orderid']= (string) $data['orderid'];
	 	if (!empty($data['orderid'])) {		           
            $ddates = Mage::getModel('ddate/ddate')->getCollection()
                    ->addFieldToFilter('ddate', array('like' => $ddate . '%'))
                    ->addFieldToFilter('dtime', $dtime);		
			if ($ddates->count() > 1) {
				 Mage::log('Table mw_ddate is having some record with same date+time value', Zend_Log::DEBUG, 'debug.log');
				 Mage::log($ddates->getFirstItem()->getData(), Zend_Log::DEBUG, 'debug.log');
				 echo $this->build_return_message('error',Mage::helper('ddate')->__('Table mw_ddate is having some records with same date+time value!This module should be disabled and contact to mage-world for supporting!'));
				 return;
			};
			if ($ddates->count() == 1) {
				$ddate1=$ddates->getFirstItem();
                $ddate1->setOrdered($ddate1->getOrdered() + 1);
                $ddate1->setIncrementId($data['orderid']);
                  
				$ddates_store=Mage::getModel('ddate/ddate_store')->getCollection()
                    ->addFieldToFilter('increment_id', $data['orderid']);
                    					
				if ($ddates_store->getFirstItem()->getDdateId() == $ddate1->getDdateId()) {
					echo $this->build_return_message('error',Mage::helper('ddate')->__('Posted data is same with old data!'));
					return;
				}

				if ($ddates_store->count()== 1){	
					$ddates_store=$ddates_store->getFirstItem();
					$old_ddate=Mage::getModel('ddate/ddate')->load($ddates_store->getDdateId());

					if($old_ddate->getOrdered()== 1) {
						$old_ddate->delete();
					} else {
						$old_ddate->setOrdered($old_ddate->getOrdered() - 1)->save();
						$ddates_store->delete();
					}
					$ddate1->setDdateComment($comment);
				};
                
                $ddate1->setData('oscdelivery_date',$ddate);
                $ddate1->setData('oscdelivery_time',$dtime_osc);
                $ddate1->setData('sales_order_id',$data['mwsalesoderid']);
                $ddate1->save();
            } else {
                $_ddate = Mage::getModel('ddate/ddate');
                $_ddate->setDdate($ddate);
                $_ddate->setDtime($dtime);
                $_ddate->setOrdered(1);
                $_ddate->setIncrementId($data['orderid']); 
				$ddates_store = Mage::getModel('ddate/ddate_store')
									->getCollection()
									->addFieldToFilter('increment_id', $data['orderid']);

				if ($ddates_store->count()== 1) { 
					$ddates_store=$ddates_store->getFirstItem();
					$old_ddate=Mage::getModel('ddate/ddate')->load($ddates_store->getDdateId());
					if ($old_ddate->getOrdered()== 1) {
						$old_ddate->delete();
					} else {
						$old_ddate->setOrdered($old_ddate->getOrdered() - 1)->save();
						$ddates_store->delete();
					}
					$_ddate->setDdateComment($comment);
				};

                $_ddate->setData('oscdelivery_date',$ddate);
                $_ddate->setData('oscdelivery_time',$dtime_osc);
                $_ddate->setData('sales_order_id',$data['mwsalesoderid']);					
                $_ddate->save();
			}
            
			echo $this->build_return_message('ok',Mage::helper('ddate')->__('Updated successfully!'));
		} else {
			echo $this->build_return_message('error',Mage::helper('ddate')->__('Update failed!'));
		}

		return;
	}

	 /**
     * check available date
     * @param int $slotId: dtime's id
     * @param date_type $date (example: 2011/11/2)
     * @return boolean 
     */
    public function isEnabled($slotId, $date)
    {
        $special_date = Mage::helper('ddate')->getSpecialDay();
        $slot = Mage::getModel('ddate/dtime')->load($slotId);
        
        if ($slot->getHoliday() == 1 && Mage::helper('ddate')->getDayoff()) {
        	$this->ajaxerror = Mage::helper('ddate')->__('This is Holiday or Dayoff');
            return $this->ajaxerror;
        }

		/* check ordered items */
		$ordered=Mage::helper('ddate')->ordered_counting($date,$slotId);	
		if($ordered && $ordered >= $slot->getMaximumBooking()) {
			$this->ajaxerror = Mage::helper('ddate')->__('This slot time has been full booking');	
			return $this->ajaxerror;
		}

        //check available slot based on day of week
        $method = 'get' . date('D', strtotime($date));
        if ($slot->{$method}() == "0") {
        	$this->ajaxerror = Mage::helper('ddate')->__('This time slot is not available in ').date('l', strtotime($date));
            return $this->ajaxerror;
        }

        //check available slot based on configuration of weekend (Satuday and Sunday)
        if (method_exists(Mage::helper('ddate'), $method)) {
            if (Mage::helper('ddate')->{$method}() == "0") {
                $this->ajaxerror = Mage::helper('ddate')->__('This time slot is not available in ').date('l', strtotime($date));
                return $this->ajaxerror;
            }
        }

        //check available slot based on configuration of special days
        if (($slot->getSpecialday() == "1") && isset($special_date[$date])) {
            $this->ajaxerror = Mage::helper('ddate')->__('This date is special day');
            return $this->ajaxerror;
        }

        //check available slot based on specified slot's special days
        $specifiedSpecial = $slot->getSpecialDays();
        if (isset($specifiedSpecial[$date])) {
            $this->ajaxerror = Mage::helper('ddate')->__('This time slot is not available in this day');
            return $this->ajaxerror;
        }

        //check available slot with delay hour
        $interval = $slot->getInterval();
        if (Mage::helper('ddate')->isAvailableDay($interval, $date) === FALSE) {
            $this->ajaxerror = Mage::helper('ddate')->__('This time slot is not available in this day');
            return $this->ajaxerror;
        }

        return 'ok';
    }

	public function update_commentAction()
	{
		$data=$this->getRequest()->getPost();
		if (!empty($data['orderid'])) {
            $ddates_store = Mage::getModel('ddate/ddate_store')->getCollection()
                    ->addFieldToFilter('increment_id', $data['orderid'])
                    ->getFirstItem();
			if ($ddates_store->getData('ddate_store_id')) {
				$ddates_store->setDdateComment($data['mwcomment'])->save();
				echo $this->build_return_message('ok',Mage::helper('ddate')->__('Updated successfully!'));
			} else {
                echo $this->build_return_message('error',Mage::helper('ddate')->__('Find no order with this id'));
			}
		} else {
			echo $this->build_return_message('error',Mage::helper('ddate')->__('Update failed!'));
		}
        
        //Check OSC Running -> update OSC
        if(Mage::helper('ddate')->isOSCRunning()) {            
            $sales_order_id = (int)$data['mwsalesoderid'];
            $osc = Mage::getModel('onestepcheckout/onestepcheckout')
            		->getCollection()
                    ->addFieldToFilter('sales_order_id', $sales_order_id)
                    ->getFirstItem();
            if($osc->getData('mw_onestepcheckout_date_id')) {
                $osc->setData('mw_customercomment_info',$data['mwcomment'])->save();
            }      
        }

		return;
	}

	public function getCurrentTime()
	{
        if (empty($this->_currentTime)) {
            $this->_currentTime = Mage::getSingleton('core/date')->timestamp();
        }

        return $this->_currentTime;
    }

	public function getNumberWeek()
	{
        $numberWeek = Mage::getStoreConfig("ddate/info/weeks") != '' ? Mage::getStoreConfig("ddate/info/weeks") : 4;

        return $numberWeek;
    }
}
