<?php

$installer = $this;
$resource = Mage::getSingleton('core/resource');
$installer->startSetup();
$installer->run("
    ALTER TABLE `{$resource->getTableName('ddate/ddate_store')}` ADD `ddate_tax` int(11) NOT NULL DEFAULT 0 ;
    ");
$installer->endSetup();
