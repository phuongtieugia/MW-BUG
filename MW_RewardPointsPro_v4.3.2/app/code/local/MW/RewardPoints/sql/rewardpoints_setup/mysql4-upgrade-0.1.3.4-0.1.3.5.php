<?php
    $setup = new Mage_Eav_Model_Entity_Setup('core_setup');
    $setup->removeAttribute('catalog_product', 'reward_point_product');
    $setup->removeAttribute('catalog_product', 'reward_point_will_get');
    $setup->addAttribute('catalog_product', 'reward_point_product', array(
        'label'    => 'Reward Points Earned',
        'type'     => 'int',
        'input'    => 'text',
        'visible'  => true,
        'required' => false,
        'position' => 10,
    ));


    $installer  = $this;
    $collection = Mage::getModel('rewardpoints/customer')->getCollection();
    $installer->startSetup();
    $sql = "
ALTER TABLE {$collection->getTable('customer')} ADD `last_checkout` DATETIME NOT NULL AFTER `mw_friend_id`;
UPDATE {$collection->getTable('customer')} set `last_checkout`='" . Mage::getSingleton('core/date')->gmtDate() . "';
";

    $installer->run($sql);
    $installer->endSetup();