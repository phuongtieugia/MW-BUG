<?php
/**
 * User: Anh TO
 * Date: 8/11/14
 * Time: 5:16 PM
 */

class MW_Onestepcheckout_Block_Rewrite_Html_Head extends Mage_Page_Block_Html_Head
{
    public function addJsCustom($name, $params = "")
    {
        $this->addItem('skin_css', $name, $params, 'IE');
        return $this;
    }
}