<?php

    class MW_RewardPoints_Model_Facebook_Lang extends Varien_Object
    {
        static public function toOptionArray()
        {
            $result = array();
            $path   = 'http://facebook.com/translations/FacebookLocales.xml';
            if(file_exists($path))
            {
                $xml     = new Varien_Simplexml_Element($path, 0, true);
                $xmlData = $xml->children();
                $locales = $xmlData;

                foreach ($locales as $locale)
                {
                    $data  = $locale->asArray();
                    $codes = $data['codes'];
                    $code  = trim($codes['code']['standard']['representation']);
                    $label = $data['englishName'];

                    $result[] = array('value' => $code, 'label' => $label);
                }
            }
            else
            {
                $result[] = array('value' => 'en_US', 'label' => 'English (US)');
            }

            return $result;
        }
    }