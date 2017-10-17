<?php

class MW_Ddate_Helper_Data extends Mage_Core_Helper_Abstract
{
    const WM_OSC_ENABLE = 1;
    const MYNAME = "MW_Ddate";

    // Define layout file name
    const LAYOUT_FILE = 'ddate.xml';
    // Define template folder name
    const TEMPLATE_FOLDER = 'ddate';

    public function getDtime($storeid=null)
    {
		if(empty($storeid)) $storeid=Mage::app()->getStore()->getId();
		$dtimes = Mage::getModel('ddate/dtime')->getCollection();
        $dtimes->getSelect()
            ->join(array(
                'mwdtime_store' => Mage::getSingleton("core/resource")->getTableName('mwdtime_store')),
                'mwdtime_store.dtime_id = main_table.dtime_id ',
                array('main_table.dtime_id')
            )
            ->where('mwdtime_store.store_id in (?)', array('0', $storeid))
			->where('main_table.status = 1');

        return $dtimes;
    }

    public function getSpecialDay()
    {
		$regis=Mage::registry('mw_ddate_specialday');//fix error:Mage registry key "mw_ddate_specialday" already exists
        if(empty($regis)) {
            $list = (Mage::getStoreConfig('ddate/info/special_days')) ? Mage::getStoreConfig('ddate/info/special_days') : "";
            if ($list) {
                $list = trim($list);
                $list = explode(';', $list);
				if (is_array($list)) {
					foreach ($list as $key => $date) {
						if ($date) {
							$date = $this->validateDate($date);
							$list[$date] = 1;
						}
						unset($list[$key]);
					}
				} else {
					$date = $this->validateDate($list);
					$list[$date] = 1;
				}
                return $list;
            } else {
                $list = array();
            }

            Mage::unregister('mw_ddate_specialday');
            Mage::register('mw_ddate_specialday', $list);
        }

        return Mage::registry('mw_ddate_specialday');
    }

    public function getSaturday()
    {
        return (Mage::getStoreConfig('ddate/info/deliver_saturdays')) ? Mage::getStoreConfig('ddate/info/deliver_saturdays') : "0";
    }

    public function getSunday()
    {
        return (Mage::getStoreConfig('ddate/info/deliver_sundays')) ? Mage::getStoreConfig('ddate/info/deliver_sundays') : "0";
    }

    public function getSpecialDayByList()
    {
        return (Mage::getStoreConfig('ddate/info/special_days')) ? Mage::getStoreConfig('ddate/info/special_days') : "";
    }

    public function getDayoff()
    {
        return (Mage::getStoreConfig('ddate/info/dayoff')) ? Mage::getStoreConfig('ddate/info/dayoff') : "0";
    }

    public function getMaxBooking()
    {
        return (Mage::getStoreConfig("ddate/info/maximum_bookings")) ? Mage::getStoreConfig("ddate/info/maximum_bookings") : 10000000;
    }

	public function disableConfig()
	{
		Mage::getModel('core/config')->saveConfig("advanced/modules_disable_output/".self::MYNAME,1);
		Mage::getConfig()->reinit();
	}

    public function validateDate($date)
    {
        preg_match("/^[0-9]{1,2}-[0-9]{1,2}$/", $date, $result, PREG_OFFSET_CAPTURE);
        if (count($result)) {
            $currentTime = time();
            $date = date('Y',$currentTime).'-'.$date;
        }

        return $date;
    }

	public function find_delivery_info($id)
    {
		$adress_ddate=array();
		$ddate=Mage::getSingleton('customer/session')->getDdateinfo();
		if(!empty($ddate)) {
    		$adress_ddate['ddate']=$ddate['datemultiaddress'.$id];
    		$adress_ddate['dtime']=$ddate['dtimemultiaddress'.$id];
    		$adress_ddate['mwcomment']=$ddate['ddate_commentmultiaddress'.$id];

    		return $adress_ddate;
        } else{
			Mage::log('find_delivery_info function $ddate is empty');
			return false;
		}
	}

	public function ordered_counting($date = null,$id = null)
    {
        if (empty($date) || empty($id)) {
            return false;
        }

		$ordered = 0;
		$collection= Mage::getSingleton('ddate/ddate')->getCollection()
                ->addFieldToFilter('ddate', array('eq' => $date))
                ->addFieldToFilter('dtime', array('eq' => $id));
		foreach ($collection as $cl) {
			$ordered = $ordered + $cl->getOrdered();
		}
		if ($ordered > 0) {
            return $ordered;
        }

        return false;
    }

	public function get_config_format()
    {
    	$c_f=Mage::getStoreConfig("ddate/info/formatdate");
    	if(empty($c_f)) {
            return 'Ymd';
        } else {
            return $c_f;
        }
	}

	/*
	* Return php date format string base on configuration
	*/
	public function php_date_format($sign="-")
    {
		$c_f = $this->get_config_format();
		if ($c_f == "mdY") return 'm' . $sign . 'd' . $sign.'Y';
		if ($c_f == "dmY") return 'd' . $sign . 'm' . $sign.'Y';
		if ($c_f == "Ymd") return 'Y' . $sign . 'm' . $sign.'d';

		return "Y-m-d";
	}

	/*
	*
	*return php date/month format string base on configuration
	*/
	public function month_date_format($sign="/")
    {
		$c_f = $this->get_config_format();
		if ($c_f == "mdY") return 'm' . $sign . 'j';
		if ($c_f == "dmY") return 'j' . $sign . 'm';
		if ($c_f == "Ymd") return 'm' . $sign . 'j';

		return "m/j";
	}

	/*
	*
	*return php date format string base on configuration
	*/
	public function php_date_format_M($sign="-")
    {
		$c_f = $this->get_config_format();
		if ($c_f == "mdY") return 'MM' . $sign . 'd' . $sign . 'Y';
		if ($c_f == "dmY") return 'd' . $sign . 'MM' . $sign . 'Y';
		if ($c_f == "Ymd") return 'Y' . $sign . 'MM' . $sign . 'd';

		return "Y-MM-d";
	}

	public function calendar_date_format($sign="-")
    {
		$c_f = $this->get_config_format();
		if ($c_f == "mdY") return '%m' . $sign . '%d' . $sign . '%Y';
		if ($c_f == "dmY") return '%d' . $sign . '%m' . $sign . '%Y';
		if ($c_f == "Ymd") return '%Y' . $sign . '%m' . $sign . '%d';

		return "Y-m-d";
	}
	/**
	* Convert date from Ddate'config into yyyy-mm-dd
	* $date string
	* return date format "yyy-mm-dd"
	*/
	public function convert_date_format($date=null,$sign="-")
    {
		if (empty($date)) {
            return '';
        }

		$c_f = $this->get_config_format();
		$date_ar = explode($sign,$date);
		if ($c_f == "mdY") return $date_ar[2] . $sign . $date_ar[0] . $sign . $date_ar[1];
		if ($c_f == "dmY") return $date_ar[2] . $sign . $date_ar[1] . $sign . $date_ar[0];
		if ($c_f == "Ymd") return $date_ar[0] . $sign . $date_ar[1] . $sign . $date_ar[2];

		return '';
	}
	/**
	* Convert date from yyyy-mm-dd into Ddate config date format
	* $date string
	* return date format in Ddate config
	*/
	public function convert_date_format_config($date=null,$sign="-")
    {
		if(empty($date)) {
            return '';
        }

		$c_f = $this->get_config_format();
		$date_ar = explode($sign,$date);
		if ($c_f == "mdY") return $date_ar[1] . $sign . $date_ar[2] . $sign . $date_ar[0];
		if ($c_f == "dmY") return $date_ar[2] . $sign . $date_ar[1] . $sign . $date_ar[0];
		if ($c_f == "Ymd") return $date_ar[0] . $sign . $date_ar[1] . $sign . $date_ar[2];

		return '';
	}

	/**
	* $date string
	* return date format "yyy-mm-dd"
	*/
	public function format_ddate($date=null)
    {
		if (empty($date)) {
            return '';
        }

		return date($this->php_date_format(), strtotime($date));
	}

    /**
     * Check OSC
     */
    public function isOSCRunning()
    {
        if (Mage::helper('core')->isModuleEnabled('MW_Onestepcheckout') &&
            Mage::helper('core')->isModuleOutputEnabled('MW_Onestepcheckout'))
        {
            if (Mage::getStoreConfig('onestepcheckout/config/enabled') == MW_Ddate_Helper_Data::WM_OSC_ENABLE) {
                return true;
            }
        }

        return false;
    }

    /**
     * Function check missing layout file and template folder of 
     * this module in current theme via front-end
     * @return void
     */
    public function checkMissingLayoutCurrentThemeViaFrontend()
    {
        $currentPackageName = Mage::getSingleton('core/design_package')->getPackageName();
        $currentTemplateName = Mage::getSingleton('core/design_package')->getTheme('frontend');
        $message = $this->__('Advance Delivery Schedule: Missing layout file or template folder of this module. You can submit a ticket at <a href="http://www.mage-world.com/contacts/" target="_blank">here</a> for us about this.');

        // Get directory path to current theme
        $dirPath = Mage::getBaseDir('design') . DS . 'frontend' . DS;
        $dirPath .= $currentPackageName . DS . $currentTemplateName;

        // Check layout file is exists
        $layoutPath = $dirPath . DS . 'layout' . DS . self::LAYOUT_FILE;
        if(!file_exists($layoutPath)) {
            $flagLayout = true;
            if($currentTemplateName != 'default') {
                $defaultPath = Mage::getBaseDir('design') . DS . 'frontend' . DS . $currentPackageName . DS . 'default';
                $defaultPath .= DS . 'layout' . DS . self::LAYOUT_FILE;
                if(!file_exists($defaultPath)) {
                    $flagLayout = false;
                }
            } else {
                $flagLayout = false;
            }

            if($flagLayout == false) {
                $basePath = Mage::getBaseDir('design') . DS . 'frontend' . DS . 'base' . DS . 'default';
                $basePath .= DS . 'layout' . DS . self::LAYOUT_FILE;
                if(!file_exists($basePath)) {
                    Mage::getSingleton('core/session')->addError($message);
                    return;
                }
            }
        }

        // Check template folder is exists
        $templatePath = $dirPath . DS . 'template' . DS . self::TEMPLATE_FOLDER;
        if(!file_exists($templatePath) || !is_dir($templatePath)) {
            $flagTemplate = true;
            if($currentTemplateName != 'default') {
                $defaultPath = Mage::getBaseDir('design') . DS . 'frontend' . DS . $currentPackageName . DS . 'default';
                $defaultPath .= DS . 'template' . DS . self::TEMPLATE_FOLDER;
                if(!file_exists($defaultPath) || !is_dir($defaultPath)) {
                    $flagTemplate = false;
                }
            } else {
                $flagTemplate = false;
            }

            if($flagTemplate == false) {
                $basePath = Mage::getBaseDir('design') . DS . 'frontend' . DS . 'base' . DS . 'default';
                $basePath .= DS . 'template' . DS . self::TEMPLATE_FOLDER;
                if(!file_exists($basePath) || !is_dir($basePath)) {
                    Mage::getSingleton('core/session')->addError($message);
                    return;
                }
            }
        }

        // Check skin folder is exists
        $skinPath = Mage::getBaseDir('skin') . DS . 'frontend' . DS;
        $skinPath .= $currentPackageName . DS . $currentTemplateName . DS . self::TEMPLATE_FOLDER;
        if(!file_exists($skinPath) || !is_dir($skinPath)) {
            $flagSkin = true;
            if($currentTemplateName != 'default') {
                $defaultPath = Mage::getBaseDir('skin') . DS . 'frontend' . DS . $currentPackageName . DS . 'default';
                $defaultPath .= DS . self::TEMPLATE_FOLDER;
                if(!file_exists($defaultPath) || !is_dir($defaultPath)) {
                    $flagSkin = false;
                }
            } else {
                $flagSkin = false;
            }

            if($flagSkin == false) {
                $basePath = Mage::getBaseDir('skin') . DS . 'frontend' . DS . 'base' . DS . 'default';
                $basePath .= DS . self::TEMPLATE_FOLDER;
                if(!file_exists($basePath) || !is_dir($basePath)) {
                    Mage::getSingleton('core/session')->addError($message);
                    return;
                }
            }
        }
    }

    /**
     * Function check missing layout file and template folder of 
     * this module in configured themes via back-end
     * @return void
     */
    public function checkMissingLayoutThemesViaBackend()
    {
        $stores = Mage::app()->getStores();
        $message = $this->__('Advance Delivery Schedule: Missing layout file or template folder of this module. You can submit a ticket at <a href="http://www.mage-world.com/contacts/" target="_blank">here</a> for us about this.');

        // Check with each store view
        foreach($stores as $store) {
            $packageName = Mage::getStoreConfig('design/package/name', $store->getStoreId());
            $templateName = Mage::getStoreConfig('design/theme/template', $store->getStoreId());

            if($templateName == '') {
                // If Template field is empty, get Default field
                $templateName = Mage::getStoreConfig('design/theme/default', $store->getStoreId());
                if($templateName == '') {
                    // If Default field is empty, set value is default
                    $templateName = 'default';
                }
            }

            // Get directory path to current theme
            $dirPath = Mage::getBaseDir('design') . DS . 'frontend' . DS;
            $dirPath .= $packageName . DS . $templateName;

            // Check layout file is exists
            $layoutPath = $dirPath . DS . 'layout' . DS . self::LAYOUT_FILE;
            if(!file_exists($layoutPath)) {
                $flagLayout = true;
                if($templateName != 'default') {
                    $defaultPath = Mage::getBaseDir('design') . DS . 'frontend' . DS . $packageName . DS . 'default';
                    $defaultPath .= DS . 'layout' . DS . self::LAYOUT_FILE;
                    if(!file_exists($defaultPath)) {
                        $flagLayout = false;
                    }
                } else {
                    $flagLayout = false;
                }

                if($flagLayout == false) {
                    $basePath = Mage::getBaseDir('design') . DS . 'frontend' . DS . 'base' . DS . 'default';
                    $basePath .= DS . 'layout' . DS . self::LAYOUT_FILE;
                    if(!file_exists($basePath)) {
                        Mage::getSingleton('adminhtml/session')->addError($message);
                        return;
                    }
                }
            }

            // Check template folder is exists
            $templatePath = $dirPath . DS . 'template' . DS . self::TEMPLATE_FOLDER;
            if(!file_exists($templatePath) || !is_dir($templatePath)) {
                $flagTemplate = true;
                if($templateName != 'default') {
                    $defaultPath = Mage::getBaseDir('design') . DS . 'frontend' . DS . $packageName . DS . 'default';
                    $defaultPath .= DS . 'template' . DS . self::TEMPLATE_FOLDER;
                    if(!file_exists($defaultPath) || !is_dir($defaultPath)) {
                        $flagTemplate = false;
                    }
                } else {
                    $flagTemplate = false;
                }

                if($flagTemplate == false) {
                    $basePath = Mage::getBaseDir('design') . DS . 'frontend' . DS . 'base' . DS . 'default';
                    $basePath .= DS . 'template' . DS . self::TEMPLATE_FOLDER;
                    if(!file_exists($basePath) || !is_dir($basePath)) {
                        Mage::getSingleton('adminhtml/session')->addError($message);
                        return;
                    }
                }
            }

            // Check skin folder is exists
            $skinPath = Mage::getBaseDir('skin') . DS . 'frontend' . DS;
            $skinPath .= $packageName . DS . $templateName . DS . self::TEMPLATE_FOLDER;
            if(!file_exists($skinPath) || !is_dir($skinPath)) {
                $flagSkin = true;
                if($templateName != 'default') {
                    $defaultPath = Mage::getBaseDir('skin') . DS . 'frontend' . DS . $packageName . DS . 'default';
                    $defaultPath .= DS . self::TEMPLATE_FOLDER;
                    if(!file_exists($defaultPath) || !is_dir($defaultPath)) {
                        $flagSkin = false;
                    }
                } else {
                    $flagSkin = false;
                }

                if($flagSkin == false) {
                    $basePath = Mage::getBaseDir('skin') . DS . 'frontend' . DS . 'base' . DS . 'default';
                    $basePath .= DS . self::TEMPLATE_FOLDER;
                    if(!file_exists($basePath) || !is_dir($basePath)) {
                        Mage::getSingleton('adminhtml/session')->addError($message);
                        return;
                    }
                }
            }

            // Release variables
            unset($packageName);
            unset($templateName);
            unset($dirPath);
            unset($layoutPath);
            unset($templatePath);
            unset($skinPath);
            unset($flagLayout);
            unset($flagTemplate);
            unset($flagSkin);
            unset($defaultPath);
            unset($basePath);
        }
    }

    public function haveAnySlotAvailable(){
        $currentTime = Mage::getSingleton('core/date')->timestamp();

        $weeks = Mage::getStoreConfig("ddate/info/weeks") != '' ? Mage::getStoreConfig("ddate/info/weeks") : 4;

        $slots = Mage::getResourceModel('ddate/ddate')->getDtime();

        $block = new MW_Ddate_Block_Onepage_Ddate();

        $haveSlotAvailable = false;
        for ($w = 0; $w < $weeks; $w++) {
            foreach ($slots as $slot) {
                for ($i = 7 * $w; $i < 7 * $w + 7; $i++) {
                    $strDate = date('Y-m-d', strtotime('+' . $i . ' day', $currentTime));
                    $haveSlotAvailable = $block->isEnabled($slot->getId(), $strDate);
                    if($haveSlotAvailable){
                        break;
                    }
                }
            }
        }

        return $haveSlotAvailable;
    }

    public function isAvailableDay($interval = NULL, $day = NULL)
    {

        if(($interval != NULL) && ($day != NULL) && ($day != '')) {
                    
            $delay = (Mage::getStoreConfig('ddate/info/delay') ? (int)Mage::getStoreConfig('ddate/info/delay') : 0);
            preg_match("/-(\d+):/", $interval, $hours, PREG_OFFSET_CAPTURE);
            preg_match("/:(\d+)$/", $interval, $minutes, PREG_OFFSET_CAPTURE);

            $additionMin = "";
            if (isset($minutes[0])) {
                $additionMin = " " . $minutes[1][0] . " minutes";
            }

            // $stringtime = '+ 19 hours 00 minutes';
            $stringtime = '+ '.$hours[1][0].' hours'.$additionMin;
            $hightBoundTime = strtotime($stringtime, strtotime($day));
            $delayTime = strtotime('+'.$delay.' hours', Mage::getSingleton('core/date')->timestamp());

            if ($hightBoundTime < $delayTime) {
                return FALSE;
            }else{
                return TRUE;
            }

        }
        return TRUE;
    }
    
    public function checkVersion($str)
    {
        $a       = explode('.', $str);
        $modules = array_keys((array) Mage::getConfig()->getNode('modules')->children());
        if (in_array('Enterprise_Banner', $modules)) {
            if ($a[1] >= '12') {
                return "enterprise12";
            }
        } elseif (in_array('Enterprise_Enterprise', $modules)) {
            if ($a[1] <= '10') {
                return "enterprise10";
            }
        } else {
            if ($a[1] == '7' || $a[1] == '8') {
                return "mg1.7";
            }
            if ($a[1] == '6') {
                return "mg1.6";
            }
            if ($a[1] == '5') {
                return "mg1.5";
            }
            if ($a[1] == '4') {
                return "mg1.4";
            }

            return "mg{$a[0]}.{$a[1]}";
        }
    }
    public function getdtimetext(){
        $rs = null;
        $dtimes = Mage::getModel('ddate/dtime')->getCollection();
        $dtimes->getSelect('dtime_id,dtime');
        foreach ($dtimes as $dtime) {
            $rs[$dtime['dtime_id']] = $dtime['dtime'];
        }
        return $rs;
    }
	public function getPostDeliveryConfig($postcode){
		$config_deliverys = Mage::getStoreConfig('ddate/info/config_delivery');
		if ($config_deliverys) {
			$config_deliverys = unserialize($config_deliverys);
			if (is_array($config_deliverys)) {
				foreach($config_deliverys as $config_delivery) {
					$dpostfrom = $config_delivery['dpostfrom'];
					$dpostto = $config_delivery['dpostto'];
					if($postcode >= $dpostfrom && $postcode <= $dpostto){
						return $config_delivery['ddays'];
					}
				}
			} else {}
		}
		return null;
	}
	// special date
	public function getPostSpecialDeliveryConfig($postcode){
		$postcodespecials = Mage::getStoreConfig('ddate/info/config_delivery_postcodespecial');
		if ($postcodespecials) {
			$postcodespecials = unserialize($postcodespecials);
			if (is_array($postcodespecials)) {
				foreach($postcodespecials as $postcodespecial) {
					$_postcodespecial = $postcodespecial['postcodespecial'];
					if($postcode == $_postcodespecial){
						return $postcodespecial['slotid'];
					}
				}
			} else {}
		}
		return null;
	}
	
}
