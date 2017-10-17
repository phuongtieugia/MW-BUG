<?php

    $installer  = $this;
    $resource   = Mage::getSingleton('core/resource');
    $collection = Mage::getModel('rewardpoints/rewardpointsorder')->getCollection();
    $installer->startSetup();

    $sql_change = "ALTER TABLE {$resource->getTableName('sales/quote')} CHANGE `rewardpoint_discount` `rewardpoint_discount` decimal(12,4) NULL DEFAULT '0.0000';";

    $installer->run($sql_change);

    $sql_add = "";
    $sql_add .= "ALTER TABLE {$resource->getTableName('sales/quote')} ADD `earn_rewardpoint` int(11) NULL DEFAULT '0' AFTER `rewardpoint` ;";
    $sql_add .= "ALTER TABLE {$resource->getTableName('sales/quote')} ADD `earn_rewardpoint_cart` int(11) NULL DEFAULT '0' AFTER `earn_rewardpoint` ;";
    $sql_add .= "ALTER TABLE {$resource->getTableName('sales/quote')} ADD `spend_rewardpoint_cart` int(11) NULL DEFAULT '0' AFTER `earn_rewardpoint_cart` ;";

    $installer->run($sql_add);

    $installer->run("

DROP TABLE IF EXISTS {$collection->getTable('catalogrules')};
CREATE TABLE {$collection->getTable('catalogrules')} (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS {$collection->getTable('cartrules')};
CREATE TABLE {$collection->getTable('cartrules')} (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS {$collection->getTable('activerules')};
CREATE TABLE {$collection->getTable('activerules')} (
  `rule_id` int(11) unsigned NOT NULL auto_increment,
  `rule_name` varchar(255) NOT NULL default '',
  `type_of_transaction` int(2) NOT NULL default '0',
  `store_view` varchar(255) NOT NULL default '0',
  `customer_group_ids` varchar(255) NOT NULL default '',
  `reward_point` varchar(255) NOT NULL default '0',
  `status` INT(2) NOT NULL default '0',
  PRIMARY KEY (`rule_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS {$collection->getTable('spendcartrules')};
CREATE TABLE {$collection->getTable('spendcartrules')} (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS {$collection->getTable('productpoint')};
CREATE TABLE {$collection->getTable('productpoint')} (
  `id` int(11) unsigned NOT NULL auto_increment,
  `rule_id` int(11) NOT NULL default '0',
  `product_id` int(11) NOT NULL default '0',
  `reward_point` int(11) NOT NULL default '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



");

    $installer->endSetup();