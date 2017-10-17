<?php

$installer = $this;
$resource = Mage::getSingleton('core/resource');
$installer->startSetup();
$installer->run("
   ALTER TABLE `{$resource->getTableName('ddate/ddate')}` add  `dtimetext` varchar(50) NOT NULL DEFAULT '';
    ");
$installer->endSetup();
