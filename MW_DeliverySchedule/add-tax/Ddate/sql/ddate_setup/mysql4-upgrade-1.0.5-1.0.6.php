<?php

$installer = $this;
$resource = Mage::getSingleton('core/resource');
$installer->startSetup();
$installer->run("
    ALTER TABLE `{$resource->getTableName('ddate/ddate_store')}` ADD `order_status` varchar(50);
    ");
$installer->endSetup();
