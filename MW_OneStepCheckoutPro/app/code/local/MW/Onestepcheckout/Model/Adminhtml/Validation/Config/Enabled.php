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

        foreach (Mage::app()->getWebsites() as $website) {
            foreach ($website->getGroups() as $group) {
                $stores = $group->getStores();
                foreach ($stores as $store) {
                    $dirjs = Mage::getBaseDir().DS.'media'.DS.'mw_onestepcheckout'.DS.'js'.DS;
                    $dircss = Mage::getBaseDir().DS.'media'.DS.'mw_onestepcheckout'.DS.'css'.DS.'';
                    //Delete all
                    Mage::getModel("onestepcheckout/observer")->unlinkRecursive($dirjs, true);
                    Mage::getModel("onestepcheckout/observer")->unlinkRecursive($dircss, true);
                    Mage::getModel("onestepcheckout/observer")->unlinkRecursive( Mage::getBaseDir().''.DS.'media'.DS.'mw_onestepcheckout'.DS.'',true);

                }
            }
        }
        return parent::save();
    }
}