<?php
    /**
     * User: Anh TO
     * Date: 6/7/14
     * Time: 10:28 AM
     */

    class MW_RewardPoints_Block_Adminhtml_Sellproducts_Edit_Tab_Headjs extends Mage_Adminhtml_Block_Widget_Grid
    {
        protected function _construct()
        {
            parent::_construct();
            $this->setTemplate('mw_rewardpoints/sellproducts/edit/tab/headjs.phtml');
        }
        /**
         * Return URL for refresh input element 'path' in form
         *
         * @param array $args
         * @return string
         */
    }