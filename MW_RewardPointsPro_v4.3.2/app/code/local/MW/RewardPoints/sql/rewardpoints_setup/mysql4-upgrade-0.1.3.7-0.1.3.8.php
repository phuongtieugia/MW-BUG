<?php
//showTableStatus
    $setup = new Mage_Eav_Model_Entity_Setup('core_setup');
    $setup->addAttribute('catalog_product', 'mw_reward_point_sell_product', array(
        'label'    => 'Sell Products in Points',
        'type'     => 'int',
        'input'    => 'text',
        'visible'  => true,
        'required' => false,
        'position' => 10,
    ));

    $installer = $this;
    $resource  = Mage::getSingleton('core/resource');
    $installer->startSetup();
    $conn = $installer->getConnection();

    if(!$conn->tableColumnExists($resource->getTableName('sales/quote'), 'earn_rewardpoint'))
    {
        $installer->run("
	ALTER TABLE {$resource->getTableName('sales/quote')} ADD `earn_rewardpoint` int(11) NULL DEFAULT '0' AFTER `rewardpoint`;
	");
    }
    if(!$conn->tableColumnExists($resource->getTableName('sales/quote'), 'earn_rewardpoint_cart'))
    {
        $installer->run("
	ALTER TABLE {$resource->getTableName('sales/quote')} ADD `earn_rewardpoint_cart` int(11) NULL DEFAULT '0' AFTER `earn_rewardpoint`;
	");
    }
    if(!$conn->tableColumnExists($resource->getTableName('sales/quote'), 'spend_rewardpoint_cart'))
    {
        $installer->run("
	ALTER TABLE {$resource->getTableName('sales/quote')} ADD `spend_rewardpoint_cart` int(11) NULL DEFAULT '0' AFTER `earn_rewardpoint_cart`;
	");
    }

    $sql_change = "
		ALTER TABLE `{$resource->getTableName('sales/quote')}` 
		ADD `mw_rewardpoint_sell_product` decimal(12,4) NULL DEFAULT '0.0000',
		ADD `mw_rewardpoint_detail` text NULL DEFAULT '',
		ADD `mw_rewardpoint_rule_message` text NULL DEFAULT '',
		CHANGE `rewardpoint_discount` `mw_rewardpoint_discount` decimal(12,4) NULL DEFAULT '0.0000',
		CHANGE `rewardpoint` `mw_rewardpoint` int(11) NULL DEFAULT '0'
		";

    $installer->run($sql_change);

    $sql_history = "
		ALTER TABLE `{$resource->getTableName('rewardpoints/rewardpointshistory')}` 
		ADD `check_time` int(2)  NULL DEFAULT '1' AFTER `transaction_time`,
		CHANGE `transaction_detail` `transaction_detail` text NULL DEFAULT ''
		";
    $installer->run($sql_history);


    $sql_order = "
		ALTER TABLE `{$resource->getTableName('rewardpoints/rewardpointsorder')}` 
		ADD `rewardpoint_sell_product` int(11)  NULL DEFAULT '0' AFTER `reward_point`,
		ADD `earn_rewardpoint` int(11)  NULL DEFAULT '0' AFTER `rewardpoint_sell_product`
		";

    $installer->run($sql_order);

//if(!$conn->isTableExists($resource->getTableName('rewardpoints/catalogrules'))){
    if(!$conn->showTableStatus($resource->getTableName('rewardpoints/catalogrules')))
    {
        $installer->run("
		CREATE TABLE {$resource->getTableName('rewardpoints/catalogrules')} (
		  `rule_id` int(11) unsigned NOT NULL auto_increment,
		  `name` varchar(255) NOT NULL default '',
		  `description` text NOT NULL default '',
		  `conditions_serialized` mediumtext NOT NULL default '',
		  `store_view` varchar(255) NOT NULL default '0',
		  `customer_group_ids` varchar(255) NOT NULL default '',
		  `start_date` varchar(255) NOT NULL default '',
		  `end_date` varchar(255) NOT NULL default '',
		  `simple_action` int(2) NOT NULL default '0',
		  `reward_step` int(11) NOT NULL default '0',
		  `reward_point` int(11) NOT NULL default '0',
		  `rule_position` int(11) NOT NULL default '0',
		  `stop_rules_processing` int(2) NOT NULL default '0',
		  `status` INT(2) NOT NULL default '0',
		  PRIMARY KEY (`rule_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
        );

    }

//if(!$conn->isTableExists($resource->getTableName('rewardpoints/activerules'))){
    if(!$conn->showTableStatus($resource->getTableName('rewardpoints/activerules')))
    {
        $installer->run("
		CREATE TABLE {$resource->getTableName('rewardpoints/activerules')} (
		  `rule_id` int(11) unsigned NOT NULL auto_increment,
		  `rule_name` varchar(255) NOT NULL default '',
		  `type_of_transaction` int(2) NOT NULL default '0',
		  `store_view` varchar(255) NOT NULL default '0',
		  `customer_group_ids` varchar(255) NOT NULL default '',
		  `date_event` varchar(255) NOT NULL default '',
		  `comment` varchar(255) NOT NULL default '',
		  `reward_point` varchar(255) NOT NULL default '0',
		  `status` INT(2) NOT NULL default '0',
		  PRIMARY KEY (`rule_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
        );

    }
    else
    {
        $sql_activerules = "ALTER TABLE `{$resource->getTableName('rewardpoints/activerules')}`
		 ADD `date_event` varchar(255) NOT NULL default '' AFTER `customer_group_ids`,
		 ADD `comment` varchar(255) NULL default '' AFTER `date_event`
		 ;";

        $installer->run($sql_activerules);
    }

//if(!$conn->isTableExists($resource->getTableName('rewardpoints/spendcartrules'))){
    if(!$conn->showTableStatus($resource->getTableName('rewardpoints/spendcartrules')))
    {
        $installer->run("
		CREATE TABLE {$resource->getTableName('rewardpoints/spendcartrules')} (
		  `rule_id` int(11) unsigned NOT NULL auto_increment,
		  `name` varchar(255) NOT NULL default '',
		  `description` text NOT NULL default '',
		  `conditions_serialized` mediumtext NOT NULL default '',
		  `actions_serialized` mediumtext NOT NULL default '',
		  `store_view` varchar(255) NOT NULL default '0',
		  `customer_group_ids` varchar(255) NOT NULL default '',
		  `start_date` varchar(255) NOT NULL default '',
		  `end_date` varchar(255) NOT NULL default '',
		  `simple_action` int(2) NOT NULL default '0',
		  `reward_step` int(11) NOT NULL default '0',
		  `reward_point` int(11) NOT NULL default '0',
		  `rule_position` int(11) NOT NULL default '0',
		  `stop_rules_processing` int(2) NOT NULL default '0',
		  `status` INT(2) NOT NULL default '0',
		  PRIMARY KEY (`rule_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
        );
    }
//if(!$conn->isTableExists($resource->getTableName('rewardpoints/productpoint'))){
    if(!$conn->showTableStatus($resource->getTableName('rewardpoints/productpoint')))
    {
        $installer->run("
		CREATE TABLE {$resource->getTableName('rewardpoints/productpoint')} (
		  `id` int(11) unsigned NOT NULL auto_increment,
		  `rule_id` int(11) NOT NULL default '0',
		  `product_id` int(11) NOT NULL default '0',
		  `reward_point` int(11) NOT NULL default '0',
		  PRIMARY KEY (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
        );
    }
//if(!$conn->isTableExists($resource->getTableName('rewardpoints/cartrules'))){
    if(!$conn->showTableStatus($resource->getTableName('rewardpoints/cartrules')))
    {
        $installer->run("
		CREATE TABLE {$resource->getTableName('rewardpoints/cartrules')} (
		  `rule_id` int(11) unsigned NOT NULL auto_increment,
		  `name` varchar(255) NOT NULL default '',
		  `description` text NOT NULL default '',
		  `promotion_message` text NULL default '',
		  `promotion_image` varchar(255) NULL default '',
		  `conditions_serialized` mediumtext NOT NULL default '',
		  `actions_serialized` mediumtext NOT NULL default '',
		  `store_view` varchar(255) NOT NULL default '0',
		  `customer_group_ids` varchar(255) NOT NULL default '',
		  `start_date` varchar(255) NOT NULL default '',
		  `end_date` varchar(255) NOT NULL default '',
		  `simple_action` int(2) NOT NULL default '0',
		  `reward_step` int(11) NOT NULL default '0',
		  `reward_point` int(11) NOT NULL default '0',
		  `rule_position` int(11) NOT NULL default '0',
		  `stop_rules_processing` int(2) NOT NULL default '0',
		  `status` INT(2) NOT NULL default '0',
		  PRIMARY KEY (`rule_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
        );
    }
    else
    {
        $sql_cart = "ALTER TABLE `{$resource->getTableName('rewardpoints/cartrules')}`
		 ADD `promotion_message` text NULL default '' AFTER `description`,
		 ADD `promotion_image` varchar(255) NULL default '' AFTER `promotion_message`
		 ;";

        $installer->run($sql_cart);
    }

    $installer->endSetup();