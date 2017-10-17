<?php

class MW_Mcore_IndexController extends Mage_Core_Controller_Front_Action {

    public function activemanualAction()
    {
        $module = "mw_ajaxcart"; 
        $newmodule = $module;
        $keygen = "a4f2e57f629ee6fd11ba8d03417f8d89";
        $type_site = "live_site";
        $domain = Mage::getBaseUrl('link',Mage::getStoreConfig('web/secure/use_in_adminhtml')); 
        
        if(empty($type_site))
            $type_site = "live_site";
        
        $extend_name = Mage::helper('mcore')->getModuleEdition($module);
        if(!empty($extend_name))
         $newmodule = $module.strtolower($extend_name);
        
        $arr_info_api = array('module' =>$newmodule, 'domain'=>$domain,'type_site'=>$type_site,'module_system'=>$module);
                    
        if(Mage::helper('mcore')->activeOnLocal($domain,$type_site))
            {    
                echo "Can not activate on local host.";
                return; 
            }
            else if(Mage::helper('mcore')->activeOnDevelopSite($domain,$type_site))
            {
                echo "Can not activate the extension on the development site.";
                 return;
            }
            else  
            {                   
                if($module!="" && $keygen !="")
                {
                    Mage::helper('mcore')->getCommentActive($arr_info_api,$keygen);
                }
                else 
                {       
                    echo "Activate failed. Please enter a valid activation key.";
                }
            }
    }

}
