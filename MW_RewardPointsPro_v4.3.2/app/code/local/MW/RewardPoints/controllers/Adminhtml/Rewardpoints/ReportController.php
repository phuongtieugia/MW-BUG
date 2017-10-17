<?php
    class MW_Rewardpoints_Adminhtml_Rewardpoints_ReportController extends Mage_Adminhtml_Controller_Action
    {
        protected function _isAllowed()
        {
            return Mage::getSingleton('admin/session')->isAllowed('promo/rewardpoints/report');
        }
        protected function _initAction()
        {
            $this->loadLayout()
                ->_setActiveMenu('promo/rewardpoints')
                ->_addBreadcrumb(Mage::helper('adminhtml')->__('Items Manager'), Mage::helper('adminhtml')->__('Item Manager'));

            return $this;
        }

        public function overviewAction()
        {
            $this->_title($this->__('Reports'))
                ->_title($this->__('Result'))
                ->_title($this->__('rewardpoints'));

            $this->_initAction()
                ->_setActiveMenu('promo/rewardpoints')
                ->_addBreadcrumb(Mage::helper('rewardpoints')->__('Report rewardpoints'), Mage::helper('rewardpoints')->__('Rewardpoints Overview'))
                ->_addContent($this->getLayout()->createBlock('rewardpoints/adminhtml_report_overview'))
                ->renderLayout();
        }

        public function rewardedAction()
        {

            $this->_title($this->__('Reports'))
                ->_title($this->__('Result'))
                ->_title($this->__('rewardpoints'));

            $this->_initAction()
                ->_setActiveMenu('promo/rewardpoints')
                ->_addBreadcrumb(Mage::helper('rewardpoints')->__('Report rewardpoints'), Mage::helper('rewardpoints')->__('Rewarded Points'))
                ->_addContent($this->getLayout()->createBlock('rewardpoints/adminhtml_report_rewarded'))
                ->renderLayout();
        }

        public function redeemedAction()
        {

            $this->_title($this->__('Reports'))
                ->_title($this->__('Result'))
                ->_title($this->__('rewardpoints'));

            $this->_initAction()
                ->_setActiveMenu('promo/rewardpoints')
                ->_addBreadcrumb(Mage::helper('rewardpoints')->__('Report rewardpoints'), Mage::helper('rewardpoints')->__('Redeemed Points'))
                ->_addContent($this->getLayout()->createBlock('rewardpoints/adminhtml_report_redeemed'))
                ->renderLayout();
        }

        public function exportRewardedCsvAction()
        {
            $fileName = 'Rewardpoint_Rewarded.csv';
            $content  = $this->getLayout()->createBlock('rewardpoints/adminhtml_report_rewarded_grid')
                ->getCsv();

            $this->_prepareDownloadResponse($fileName, $content);
        }

        public function exportRewardedExcelAction()
        {
            $fileName = 'Rewardpoint_Rewarded.xml';
            $content  = $this->getLayout()->createBlock('rewardpoints/adminhtml_report_rewarded_grid')
                ->getExcel($fileName);

            $this->_prepareDownloadResponse($fileName, $content);
        }

        public function exportRedeemedCsvAction()
        {
            $fileName = 'Rewardpoint_Redeemed.csv';
            $content  = $this->getLayout()->createBlock('rewardpoints/adminhtml_report_redeemed_grid')
                ->getCsv();

            $this->_prepareDownloadResponse($fileName, $content);
        }

        public function exportRedeemedExcelAction()
        {
            $fileName = 'Rewardpoint_Redeemed.xml';
            $content  = $this->getLayout()->createBlock('rewardpoints/adminhtml_report_redeemed_grid')
                ->getExcel($fileName);

            $this->_prepareDownloadResponse($fileName, $content);
        }

        public function exportOverviewCsvAction()
        {
            $fileName = 'Rewardpoint_overview.csv';
            $content  = $this->getLayout()->createBlock('rewardpoints/adminhtml_report_overview_grid')
                ->getCsv();

            $this->_prepareDownloadResponse($fileName, $content);
        }

        public function exportOverviewExcelAction()
        {
            $fileName = 'Rewardpoint_overview.xml';
            $content  = $this->getLayout()->createBlock('rewardpoints/adminhtml_report_overview_grid')
                ->getExcel($fileName);

            $this->_prepareDownloadResponse($fileName, $content);
        }

        public function dashboardAction()
        {
            if($this->getRequest()->getPost('ajax') == 'true'){
                $data = $this->getRequest()->getPost();

                switch($this->getRequest()->getPost('type'))
                {
                    case 'dashboard':
                        print Mage::getModel('rewardpoints/report')->prepareCollection($data);
                        break;
                    case 'circle':
                        print Mage::getModel('rewardpoints/report')->preapareCollectionPieChart($data);
                        break;
                }

                exit;
            }
            $this->_title($this->__('Reports'))
                ->_title($this->__('Result'))
                ->_title($this->__('rewardpoints'));
            $this->_initAction()
                ->_setActiveMenu('promo/rewardpoints')
                ->_addBreadcrumb(Mage::helper('rewardpoints')->__('Report rewardpoints'), Mage::helper('rewardpoints')->__('Rewardpoints Dashboard'))
                ->_addContent($this->getLayout()->createBlock('rewardpoints/adminhtml_report_dashboard'))
                ->renderLayout();
        }
    }