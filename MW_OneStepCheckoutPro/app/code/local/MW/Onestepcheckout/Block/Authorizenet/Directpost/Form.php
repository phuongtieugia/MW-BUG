<?php
/**
 * User: Anh TO
 * Date: 4/17/14
 * Time: 11:37 PM
 */

class MW_Onestepcheckout_Block_Authorizenet_Directpost_Form extends Mage_Authorizenet_Block_Directpost_Form{
    /**
     * Internal constructor
     * Set info template for payment step
     *
     */
    protected function _construct()
    {
        parent::_construct();
    }

    /**
     * Render block HTML
     * If method is not directpost - nothing to return
     *
     * @return string
     */
    protected function _toHtml()
    {
        return parent::_toHtml();
    }

    /**
     * Set method info
     *
     * @return Mage_Authorizenet_Block_Directpost_Form
     */
    public function setMethodInfo()
    {

    }

    /**
     * Get type of request
     *
     * @return bool
     */
    public function isAjaxRequest()
    {
        return $this->getAction()
            ->getRequest()
            ->getParam('isAjax');
    }
}