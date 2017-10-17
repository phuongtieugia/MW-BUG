<?php
    include_once 'lib/mw_rewardpoints/openinviter.php';
    class MW_RewardPoints_Block_Invitation_Mail extends Mage_Core_Block_Template
    {
        public function getInviter()
        {
            $inviter      = new OpenInviter();
            $oi_services  = $inviter->getPlugins();
            $email_box    = $this->getRequest()->getPost('email_box');
            $password_box = $this->getRequest()->getPost('password_box');
            $provider_box = $this->getRequest()->getPost('provider_box');

            $inviter->startPlugin($provider_box);
            $inviter->login($email_box, $password_box);

            return $inviter;

        }

        public function getOiServices()
        {
            $inviter     = new OpenInviter();
            $oi_services = $inviter->getPlugins();

            return $oi_services;

        }
    }