<?php

    $installer = $this;
    /* @var $installer Mage_Core_Model_Resource_Setup */

    $configValuesMap = array(
        'rewardpoints/email_to_sender/template'    => 'rewardpoints_email_notifications_sender_template',
        'rewardpoints/email_to_recipient/template' => 'rewardpoints_email_notifications_recipient_template',
    );

    foreach ($configValuesMap as $configPath => $configValue)
    {
        $installer->setConfigData($configPath, $configValue);
    }
