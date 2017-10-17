<?php

$installer = $this;
$resource = Mage::getSingleton('core/resource');
$installer->startSetup();
$installer->run("
    ALTER TABLE  `".$this->getTable('sales/order')."` ADD  `mw_deliverydate` date NULL DEFAULT NULL;
    ALTER TABLE  `".$this->getTable('sales/order')."` ADD  `mw_deliverytime` varchar(100) NOT NULL DEFAULT '';
    ALTER TABLE  `".$this->getTable('sales/order')."` ADD  `mw_customercomment_info` text NOT NULL DEFAULT '';
");
$installer->endSetup();
