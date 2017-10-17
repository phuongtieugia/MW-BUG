<?php
/**
 * User: Anh TO
 * Date: 4/18/14
 * Time: 4:30 PM
 */

class MW_Onestepcheckout_Block_Authorizenet_Directpost_Iframe extends Mage_Authorizenet_Block_Directpost_Iframe{
    /**
     * Request params
     * @var array
     */
    protected $_params = array();
    /**
     * Internal constructor
     * Set template for iframe
     *
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('mw_onestepcheckout/authorizenet/directpost/iframe.phtml');
    }
    /**
     * Set output params
     *
     * @param array $params
     * @return Mage_Authorizenet_Block_Directpost_Iframe
     */
    public function setParams($params)
    {
        $this->_params = $params;
        return $this;
    }

    /**
     * Get params
     *
     * @return array
     */
    public function getParams()
    {
        return $this->_params;
    }
}