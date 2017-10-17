<?php

$installer = $this;
$installer->startSetup();

$installer->run("
     ALTER TABLE {$this->getTable('ddate/ddate_store')} ADD COLUMN (`sales_order_id` int(11) unsigned NOT NULL DEFAULT 0                                                             );
"); 

$installer->endSetup();
