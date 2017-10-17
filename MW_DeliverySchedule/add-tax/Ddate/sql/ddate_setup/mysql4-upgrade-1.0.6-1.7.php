<?php

$installer = $this;
$resource = Mage::getSingleton('core/resource');
$installer->startSetup();
$installer->run("
   ALTER TABLE `{$resource->getTableName('ddate/dtime')}` ADD `dtimesort` smallint(5) unsigned NOT NULL DEFAULT 0;
    ");
$installer->endSetup();
