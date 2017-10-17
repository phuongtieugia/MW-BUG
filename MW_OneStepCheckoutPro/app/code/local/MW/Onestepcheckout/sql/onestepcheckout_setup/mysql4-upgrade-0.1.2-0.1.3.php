<?php
$installer = $this;

$installer->startSetup();
$resource =Mage::getSingleton('core/resource');

$installer->run("

DROP TABLE IF EXISTS {$resource->getTableName('onestepcheckout/onestepcheckout')};
CREATE TABLE {$resource->getTableName('onestepcheckout/onestepcheckout')} (
  `mw_onestepcheckout_date_id` int(11) unsigned NOT NULL auto_increment,
  `sales_order_id` int(11) unsigned NOT NULL,
  `mw_customercomment_info` varchar(255) default '',
  `mw_deliverydate_date` varchar(15) default '',
  `mw_deliverydate_time` varchar(50) default '',
  `status` smallint(6) default '0',
  `created_time` datetime NULL,
  `update_time` datetime NULL,
  PRIMARY KEY (`mw_onestepcheckout_date_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

    ");

$installer->endSetup();