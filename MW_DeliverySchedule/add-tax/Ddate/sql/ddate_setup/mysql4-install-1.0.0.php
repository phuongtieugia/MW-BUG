<?php

$installer = $this;
$resource = Mage::getSingleton('core/resource');
$installer->startSetup();
$installer->run("

DROP TABLE IF EXISTS {$resource->getTableName('ddate/ddate')};

CREATE TABLE `{$resource->getTableName('ddate/ddate')}` (  
	`ddate_id` mediumint(8) unsigned NOT NULL auto_increment,  
	`ddate` varchar(20) NOT NULL,  
	`ampm` tinyint(1) NOT NULL DEFAULT '1',  
	`holiday` tinyint(1) DEFAULT '0',  
	`ordered` tinyint(3) NOT NULL default '0',  
	PRIMARY KEY  (`ddate_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");
$installer->endSetup();
