<?php

    $installer = $this;
    $resource  = Mage::getSingleton('core/resource');
    $installer->startSetup();


    $sql = "
		ALTER TABLE `{$resource->getTableName('rewardpoints/customer')}` 
		ADD `subscribed_balance_update` int(2)  DEFAULT '1' AFTER `mw_friend_id`,
		ADD `subscribed_point_expiration` int(2)  DEFAULT '1' AFTER `subscribed_balance_update`
		";
    $installer->run($sql);


    $installer->endSetup();