<?php

class MW_Ddate_Model_Dtime extends Mage_Core_Model_Abstract
{
    private $startTimeOfFirstSlot = null;

    public function _construct()
    {
        parent::_construct();
        $this->_init('ddate/dtime');
    }

    public function getStartTimeOfFirstSlot()
    {
        if (is_null($this->startTimeOfFirstSlot)) {
            $startTime = 24;
            $dtimes = $this->getCollection()->addFieldToFilter('status', array('eq' => 1));
            foreach ($dtimes as $dtime) {
                preg_match_all('/(\d+):/', $dtime->getInterval(), $matchesarray, PREG_SET_ORDER);
                if (isset($matchesarray[0][1]) && $matchesarray[0][1] < $startTime) {
                    $startTime = $matchesarray[0][1];
                }
            }
            $this->startTimeOfFirstSlot = $startTime;
        }

        return $this->startTimeOfFirstSlot;
    }

    /**
     * retrieve Format Time hh:mm am(pm)
     * @return dtime_id
     */
    public function getDtimeIdFromTime($time)
    {
        $hours_array = explode(':',$time);
        $format_array = explode(' ',$time);

        $minute_array = explode(':',$format_array[0]);
        $minute = (int)$minute_array[1];

        $fomat = $format_array[1]; //am (pm)

        if ($fomat == 'am' || $fomat == 'AM') {
            $hours = (int)$hours_array[0];
        } else {
            $hours = (int)$hours_array[0] + 12;
        }
         
        $collections = Mage::getModel('ddate/dtime')->getCollection();
        $i = 0;
        foreach ($collections as $collection) {
            if ($i == 0) {
                $default = $collection['dtime_id'];
            }
            $i++;
            
            $time_arange = $collection['interval'];
            $time_array = explode('-',$time_arange);
            
            $times_begin = explode(':',$time_array[0]);
            $times_end = explode(':',$time_array[1]);
            
            $hour_begin = (int)$times_begin[0];
            $minute_begin = (int)$times_begin[1];
            
            $hour_end = (int)$times_end[0];
            $minute_end = (int)$times_end[1];
            
            if ($hours >= $hour_begin && $hours <= $hour_end) {
                if ($hours == $hour_begin && $minute >= $minute_begin) {
                    return $collection['dtime_id'];
                } else if ($hours == $hour_end && $minute <= $minute_end) {
                    return $collection['dtime_id'];
                } else if ($hours > $hour_begin && $hours < $hour_end) {
                    return $collection['dtime_id'];
                }
            }
        }
        
        return $default;
    }
}
