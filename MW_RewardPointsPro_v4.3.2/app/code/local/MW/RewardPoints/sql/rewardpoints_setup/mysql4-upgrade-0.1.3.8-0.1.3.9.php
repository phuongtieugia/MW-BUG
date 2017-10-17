<?php

    $installer = $this;
    $resource  = Mage::getSingleton('core/resource');
    $installer->startSetup();


    $sql_history = "
		ALTER TABLE `{$resource->getTableName('rewardpoints/rewardpointshistory')}` 
		ADD `expired_day` int(11)  DEFAULT '0' AFTER `transaction_time`,
		ADD `expired_time` datetime  DEFAULT NULL AFTER `expired_day`,
		ADD `point_remaining` int(11)  NULL DEFAULT '0' AFTER `expired_time`
		";
    $installer->run($sql_history);


    $sql_activerules = "ALTER TABLE `{$resource->getTableName('rewardpoints/activerules')}`
		 ADD `default_expired` int(2) default '1' AFTER `customer_group_ids`,
		 ADD `expired_day` int(11) default '0' AFTER `default_expired`
		 ;";

    $installer->run($sql_activerules);


    $installer->endSetup();