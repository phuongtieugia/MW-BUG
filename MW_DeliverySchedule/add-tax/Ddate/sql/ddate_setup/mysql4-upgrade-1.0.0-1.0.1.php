<?php

$installer = $this;
$resource = Mage::getSingleton('core/resource');
$installer->startSetup();
$installer->run("

ALTER TABLE `{$resource->getTableName('ddate/ddate')}` ADD `dtime` VARCHAR(50) NOT NULL AFTER `ddate`;

DROP TABLE IF EXISTS {$resource->getTableName('ddate/dtime')};

CREATE TABLE `{$resource->getTableName('ddate/dtime')}`(	
	`dtime_id` mediumint(8) unsigned NOT NULL auto_increment,	
	`dtime` varchar(20) NOT NULL DEFAULT '',	
	`status` smallint(1) NOT NULL DEFAULT 0,	
	PRIMARY KEY (`dtime_id`) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS {$resource->getTableName('ddate/dtime_store')};

CREATE TABLE `{$resource->getTableName('ddate/dtime_store')}`(	
	`dtime_id` mediumint(8) NOT NULL DEFAULT 1,	
	`store_id` mediumint(8) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS {$resource->getTableName('ddate/ddate_store')}; 
CREATE TABLE `{$resource->getTableName('ddate/ddate_store')}`(
	`ddate_store_id` mediumint(8) unsigned NOT NULL auto_increment,
	`increment_id` varchar(50) NOT NULL DEFAULT '',
	`ddate_id` mediumint(8) NOT NULL DEFAULT 1,
	`ddate_comment` text NULL default '',
	PRIMARY KEY (`ddate_store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

");

$installer->endSetup();

$orderTypeId = Mage::getModel('eav/entity_type')->loadByCode('order')->getEntityTypeId();
$quoteTypeId = Mage::getModel('eav/entity_type')->loadByCode('quote')->getEntityTypeId();

$setup = new Mage_Eav_Model_Entity_Setup('core_setup');

if($orderTypeId){
	$setup->removeAttribute($orderTypeId, 'dtime');
	$setup->removeAttribute($orderTypeId, 'ampm');
	$setup->removeAttribute($orderTypeId, 'ddate_comment');
	$setup->removeAttribute($orderTypeId, 'ddate');
	$setup->addAttribute($orderTypeId, 'ddate', array('label' => 'Delivery Date','type' => 'datetime','input' => 'date','required'=>1));
	$setup->addAttribute($orderTypeId, 'ampm', array('label' => 'AM/PM','type' => 'datetime','input' => 'date','required'=>1));
	$setup->addAttribute($orderTypeId, 'ddate_comment', array('label' => 'Comment','type' => 'text','input' => 'text','required'=>1));
	$setup->addAttribute($orderTypeId, 'dtime', array('label' => 'Delivery Time','type' => 'text','input' => 'text','required'=>1));
}

if($quoteTypeId){
	$setup->removeAttribute($quoteTypeId, 'dtime');
	$setup->removeAttribute($quoteTypeId, 'ampm');
	$setup->removeAttribute($quoteTypeId, 'ddate_comment');
	$setup->removeAttribute($quoteTypeId, 'ddate');
		
	$setup->addAttribute($quoteTypeId, 'ddate', array('label' => 'Delivery Date','type' => 'datetime','input' => 'date','required'=>1));
	$setup->addAttribute($quoteTypeId, 'ampm', array('label' => 'AM/PM','type' => 'datetime','input' => 'date','required'=>1));
	$setup->addAttribute($quoteTypeId, 'ddate_comment', array('label' => 'Comment','type' => 'text','input' => 'text','required'=>1));
	$setup->addAttribute($quoteTypeId, 'dtime', array('label' => 'Delivery Time','type' => 'text','input' => 'text','required'=>1));
}
