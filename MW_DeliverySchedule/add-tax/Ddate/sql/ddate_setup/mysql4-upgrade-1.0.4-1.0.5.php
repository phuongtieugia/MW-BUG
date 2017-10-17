<?php

$installer = $this;
$resource = Mage::getSingleton('core/resource');
$installer->startSetup();
$installer->run("
    ALTER TABLE `{$resource->getTableName('ddate/dtime')}` ADD `special_day` varchar(20);
    ");
$installer->endSetup();
