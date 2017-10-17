<?php

$installer = $this;
$resource = Mage::getSingleton('core/resource');
$installer->startSetup();
$installer->run("
    ALTER TABLE `{$resource->getTableName('ddate/dtime')}` ADD `interval` varchar(20) NOT NULL DEFAULT '5h-6h';
    ");
$installer->endSetup();
