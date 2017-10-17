<?php

$installer = $this;
$resource = Mage::getSingleton('core/resource');
$installer->startSetup();
$installer->run("
    ALTER TABLE `{$resource->getTableName('ddate/dtime')}` ADD `dtime_tax` int(11) NOT NULL DEFAULT 0 ;
    ");
$installer->endSetup();
