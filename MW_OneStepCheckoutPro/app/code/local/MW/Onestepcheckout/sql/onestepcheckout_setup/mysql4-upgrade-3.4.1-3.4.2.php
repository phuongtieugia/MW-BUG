<?php

$installer = $this;

$installer->startSetup();

$installer->run("
    ALTER TABLE {$this->getTable('sales/order')}
    ADD COLUMN `giftwrap_discount` decimal(12,4) default NULL,
    ADD COLUMN `base_giftwrap_discount` decimal(12,4) default NULL;
");
$installer->run("
    ALTER TABLE  `".$this->getTable('sales/quote_address')."` ADD  `giftwrap_discount` DECIMAL( 12, 4 ) default NULL;
    ALTER TABLE  `".$this->getTable('sales/quote_address')."` ADD  `base_giftwrap_discount` DECIMAL( 12, 4 ) default NULL;
");
$installer->run("
    ALTER TABLE  `".$this->getTable('sales/invoice')."` ADD  `giftwrap_discount` DECIMAL( 12, 4 ) default NULL;
    ALTER TABLE  `".$this->getTable('sales/invoice')."` ADD  `base_giftwrap_discount` DECIMAL( 12, 4 ) default NULL;
");
$installer->run("
    ALTER TABLE  `".$this->getTable('sales/creditmemo')."` ADD  `giftwrap_discount` DECIMAL( 12, 4 ) default NULL;
    ALTER TABLE  `".$this->getTable('sales/creditmemo')."` ADD  `base_giftwrap_discount` DECIMAL( 12, 4 ) default NULL;
");

$installer->endSetup();