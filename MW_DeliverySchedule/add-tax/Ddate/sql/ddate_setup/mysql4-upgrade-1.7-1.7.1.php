<?php

$installer = $this;
$resource = Mage::getSingleton('core/resource');
$installer->startSetup();
$installer->run("
   ALTER TABLE `{$resource->getTableName('ddate/ddate')}` modify  `ddate` date NULL DEFAULT NULL;
    ");
$installer->endSetup();
