<?php

$installer = $this;
$resource = Mage::getSingleton('core/resource');
$installer->startSetup();
$installer->run("
    ALTER TABLE `{$resource->getTableName('ddate/dtime')}` ADD `maximum_booking` int(1) NOT NULL DEFAULT 10 ;
    ");
$installer->endSetup();
