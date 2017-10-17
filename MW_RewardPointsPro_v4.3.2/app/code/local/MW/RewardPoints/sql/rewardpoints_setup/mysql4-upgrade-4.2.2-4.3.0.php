<?php
    $installer = $this;
    $resource  = Mage::getSingleton('core/resource');
    $installer->startSetup();
    $conn = $installer->getConnection();

    if(!$conn->showTableStatus($resource->getTableName('rewardpoints/productsellpoint')))
    {
        $installer->run("
		CREATE TABLE {$resource->getTableName('rewardpoints/productsellpoint')} (
		  `id` int(11) unsigned NOT NULL auto_increment,
		  `product_id` int(11) NOT NULL default 0,
		  `option_id` int(11) NOT NULL default 0,
		  `option_type_id` int(11) NOT NULL default 0,
		  `sell_point` decimal(11, 4) NOT NULL default '0.0000',
		  `earn_point` decimal(11, 4) NOT NULL default '0.0000',
		  `type_id` varchar(50) NOT NULL default 'custom_option',
		  PRIMARY KEY (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
        );

    }

    $installer->endSetup();