<?php

    $installer = $this;
    $resource  = Mage::getSingleton('core/resource');
    $installer->startSetup();
    $conn = $installer->getConnection();


    $installer->run("
	ALTER TABLE `{$resource->getTableName('rewardpoints/activerules')}` ADD `coupon_code` varchar(255) NULL DEFAULT '' AFTER `comment`;
");


    $installer->run("
	ALTER TABLE `{$resource->getTableName('rewardpoints/rewardpointshistory')}` ADD `history_order_id` int(11) NULL DEFAULT '0' AFTER `transaction_time`;
");


    $installer->run("
	ALTER TABLE {$resource->getTableName('sales/quote')} ADD `mw_rewardpoint_discount_show` decimal(12,4) NULL DEFAULT '0.0000' AFTER `mw_rewardpoint_discount`;
	");

    $sql_quote_address = "
		ALTER TABLE `{$resource->getTableName('sales/quote_address')}` 
		
		ADD `mw_rewardpoint` int(11) NULL DEFAULT '0',
		ADD `mw_rewardpoint_discount` decimal(12,4) NULL DEFAULT '0.0000',
		ADD `mw_rewardpoint_discount_show` decimal(12,4) NULL DEFAULT '0.0000'
		
		";

    $installer->run($sql_quote_address);


    $sql_order = "
		ALTER TABLE `{$resource->getTableName('sales/order')}` 
		
		ADD `mw_rewardpoint` int(11) NULL DEFAULT '0',
		ADD `mw_rewardpoint_discount` decimal(12,4) NULL DEFAULT '0.0000',
		ADD `mw_rewardpoint_discount_show` decimal(12,4) NULL DEFAULT '0.0000'
		
		";

    $installer->run($sql_order);

    $sql_invoice = "
		ALTER TABLE `{$resource->getTableName('sales/invoice')}` 
		
		ADD `mw_rewardpoint` int(11) NULL DEFAULT '0',
		ADD `mw_rewardpoint_discount` decimal(12,4) NULL DEFAULT '0.0000',
		ADD `mw_rewardpoint_discount_show` decimal(12,4) NULL DEFAULT '0.0000'
		
		";

    $installer->run($sql_invoice);

    $sql_creditmemo = "
		ALTER TABLE `{$resource->getTableName('sales/creditmemo')}` 
		
		ADD `mw_rewardpoint` int(11) NULL DEFAULT '0',
		ADD `mw_rewardpoint_discount` decimal(12,4) NULL DEFAULT '0.0000',
		ADD `mw_rewardpoint_discount_show` decimal(12,4) NULL DEFAULT '0.0000'
		
		";

    $installer->run($sql_creditmemo);


    $installer->endSetup();