<?php
    $installer  = $this;
    $collection = Mage::getModel('rewardpoints/customer')->getCollection();
    $installer->startSetup();
    $sql = "ALTER TABLE {$collection->getTable('rewardpointshistory')} ADD `status_check_alert_expire` INT(1) NULL DEFAULT '0' AFTER `status_check`;";
    try{
        $installer->run($sql);
        $installer->endSetup();
    }catch(Exception $e){

    }
