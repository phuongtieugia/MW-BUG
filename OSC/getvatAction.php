<?php 
public function getvatAction()
	{
		$countrycode = $this->getRequest()->getParam('countrycode');
		$vat         = $this->getRequest()->getParam('vatnumber');
		if(empty($vat)) {
			return '';
		}
		return '<?xml version="1.0" encoding="UTF-8"?>
			<response>
			  <country-code>' . $countrycode . '</country-code>
			  <vat-number>' . $vat . '</vat-number>
			  <valid>true</valid>
			  <name>---</name>
			  <address>---</address>				  
			</response>';
		$result = @file_get_contents('http://vatid.eu/check/' . $countrycode . '/' . $vat);
		if($result) {
			return $result;
		} else {
			$urlcheckvat = 'http://isvat.appspot.com/' . $countrycode . '/' . $vat;
			$result      = @file_get_contents($urlcheckvat);
			if((string)$result == "true" || (string)$result == "false") {
				return '<?xml version="1.0" encoding="UTF-8"?>
			<response>
			  <country-code>' . $countrycode . '</country-code>
			  <vat-number>' . $vat . '</vat-number>
			  <valid>' . $result . '</valid>
			  <name>--ds-</name>
			  <address>---</address>				  
			</response>';

			} else {
				return '<?xml version="1.0" encoding="UTF-8"?>
			<response>
			  <country-code>' . $countrycode . '</country-code>
			  <vat-number>' . $vat . '</vat-number>
			  <valid>false</valid>
			  <name>---</name>
			  <address>---</address>				  
			</response>';
			}
		}
	}