<?php


/**
 * Class to render yehhpay transaction information at admin order view info form
 *
 * Class Wezz_Yehhpay_Block_Adminhtml_Sales_Order_View_Info_Block
 */
class Wezz_Yehhpay_Block_Adminhtml_Sales_Order_View_Info_Block extends Mage_Core_Block_Template {

    /**
     * getTransaction to render in template
     *
     * @return mixed
     */
    public function getTransaction() {

        $data = Mage::getModel('wezz_yehhpay/api_transaction')->getTransaction();

        if (!$data) {
            return false;
        }

        /**
         * Take calendar dates
         * Transaction can be suspended min to 1 day
         * and max to 7 day
         */
        $calendarDates = Mage::helper('wezz_yehhpay/data')->getCalendarDates();
        $data['start'] = isset($calendarDates['start']) ? $calendarDates['start'] : '';
        $data['end'] = isset($calendarDates['end']) ? $calendarDates['end'] : '';

        if (isset($data['state']['canBeSuspended']) && $data['state']['canBeSuspended']) {
            $data['urlSuspend'] = Mage::helper("adminhtml")->getUrl(
                "wezz_yehhpay/adminhtml_transaction/suspend",
                array(
                    'id' => $data['transactionId'],
                    'orderId' => $data['orderId']
                )
            );
        }

        if (isset($data['state']['isSuspended']) && $data['state']['isSuspended']) {
            $data['urlResume'] = Mage::helper("adminhtml")->getUrl(
                "wezz_yehhpay/adminhtml_transaction/resume",
                array(
                    'id' => $data['transactionId'],
                    'orderId' => $data['orderId']
                )
            );
        }

        return $data;
    }
}