<?php
/**
 * User: Anh TO
 * Date: 5/6/14
 * Time: 6:15 PM
 */

class MW_Onestepcheckout_Model_Adminhtml_Validation_Config_Enabled extends Mage_Core_Model_Config_Data{
    protected $baseDir;
    public function save()
    {
        $this->baseDir = Mage::getBaseDir();
        $path_to_skin = Mage::getSingleton('core/design_package')->getPackageName()."/".Mage::getSingleton('core/design_package')->getTheme('frontend');
        $dirjs = Mage::getBaseDir().'/media/mw_onestepcheckout/js/';
        $dircss = Mage::getBaseDir().'/media/mw_onestepcheckout/css/';
                       
        //Delete all
        MW_Onestepcheckout_Model_Observer::unlinkRecursive($dirjs,true);
        MW_Onestepcheckout_Model_Observer::unlinkRecursive($dircss,true);
        MW_Onestepcheckout_Model_Observer::unlinkRecursive( Mage::getBaseDir().'/media/mw_onestepcheckout/',true);

        return parent::save();
    }
}