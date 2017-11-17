<?php

/**
 * Class Wezz_Yehhpay_Helper_Data
 */
class Wezz_Yehhpay_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Method to get calendar dates for calendar dates
     *
     * Using in admin order form yehhpay info block
     * for calendar to suspend transaction
     *
     * @return string
     */
    public function getCalendarDates()
    {
        $zendData = new Zend_Date();

        $currentDate = $zendData->getDate();

        $data['start'] = $currentDate->addDay('1')->getIso();
        $data['end'] = $currentDate->addDay('7')->getIso();

        return $data;
    }
}