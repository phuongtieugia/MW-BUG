<?php

$installer = $this;
$resource = Mage::getSingleton('core/resource');
$installer->startSetup();
$installer->run("

ALTER TABLE `{$resource->getTableName('ddate/dtime')}` ADD `mon` smallint(1) NOT NULL DEFAULT 0 AFTER `status`;
ALTER TABLE `{$resource->getTableName('ddate/dtime')}` ADD `tue` smallint(1) NOT NULL DEFAULT 0 AFTER `mon`;
ALTER TABLE `{$resource->getTableName('ddate/dtime')}` ADD `wed` smallint(1) NOT NULL DEFAULT 0 AFTER `tue`;
ALTER TABLE `{$resource->getTableName('ddate/dtime')}` ADD `thu` smallint(1) NOT NULL DEFAULT 0 AFTER `wed`;
ALTER TABLE `{$resource->getTableName('ddate/dtime')}` ADD `fri` smallint(1) NOT NULL DEFAULT 0 AFTER `thu`;
ALTER TABLE `{$resource->getTableName('ddate/dtime')}` ADD `sat` smallint(1) NOT NULL DEFAULT 0 AFTER `fri`;
ALTER TABLE `{$resource->getTableName('ddate/dtime')}` ADD `sun` smallint(1) NOT NULL DEFAULT 0 AFTER `sat`;
ALTER TABLE `{$resource->getTableName('ddate/dtime')}` ADD `specialday` smallint(1) NOT NULL DEFAULT 0 AFTER `sun`;
");

$installer->endSetup();
