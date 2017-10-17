<?php

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

    if(!$conn->tableColumnExists($resource->getTableName('rewardpoints/rewardpointshistory'), 'history_order_id'))
    {
        $installer->run("
		ALTER TABLE `{$resource->getTableName('rewardpoints/rewardpointshistory')}` ADD `history_order_id` int(11) NULL DEFAULT '0' AFTER `transaction_time`;
	");
    }

    if(!$conn->showTableStatus($resource->getTableName('rewardpoints/activerules')))
    {
        $installer->run("
		CREATE TABLE {$resource->getTableName('rewardpoints/activerules')} (
		  `rule_id` int(11) unsigned NOT NULL auto_increment,
		  `rule_name` varchar(255) NOT NULL default '',
		  `type_of_transaction` int(2) NOT NULL default '0',
		  `store_view` varchar(255) NOT NULL default '0',
		  `customer_group_ids` varchar(255) NOT NULL default '',
		  `default_expired` int(2) default '1',
		  `expired_day` int(11) default '0',
		  `date_event` varchar(255) NOT NULL default '',
		  `comment` varchar(255) NOT NULL default '',
		  `coupon_code` varchar(255) NULL DEFAULT '',
		  `reward_point` varchar(255) NOT NULL default '0',
		  `status` INT(2) NOT NULL default '0',
		  PRIMARY KEY (`rule_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
        );

    }

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


    $installer->endSetup();