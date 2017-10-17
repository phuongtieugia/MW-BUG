<?php
    $installer  = $this;
    $collection = Mage::getModel('rewardpoints/customer')->getCollection();
    $installer->startSetup();
    $sql = "ALTER TABLE {$collection->getTable('rewardpointshistory')} ADD `status_check` INT(1) NULL DEFAULT '0' AFTER `status`;";
    try{
        $installer->run($sql);
        $installer->endSetup();
    }catch(Exception $e){

    }
