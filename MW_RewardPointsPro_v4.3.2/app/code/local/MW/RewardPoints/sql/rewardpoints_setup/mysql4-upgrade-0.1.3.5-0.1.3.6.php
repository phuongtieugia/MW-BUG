<?php

    $installer = $this;
    $resource  = Mage::getSingleton('core/resource');

    $sql = "
		ALTER TABLE `{$resource->getTableName('sales/quote')}` 
		ADD `rewardpoint_discount` decimal(12,4) NULL DEFAULT '0.0000',
		ADD `rewardpoint` INT UNSIGNED NOT NULL;
		";
    $installer->run($sql);
    $installer->endSetup();