<?php

/**
 * Model for payment method Yehhpay
 *
 * Class Wezz_Yehhpay_Model_Payment
 */
class Wezz_Yehhpay_Model_Payment extends Mage_Payment_Model_Method_Abstract
{
    protected $_code = 'yehhpay';
    protected $_title = 'Yehhpay';

    protected $_isInitializeNeeded      = true;
    protected $_canCapture              = true;
    protected $_canUseInternal          = false;
    protected $_canUseForMultishipping  = false;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;

    /**
     * Method to check yehhpay payment method rendering on checkout form
     *
     * according to configuration of yehhpay
     *
     * @param null $quote
     * @return bool
     */
    public function isAvailable($quote = null)
    {
        $active = Mage::getStoreConfig(
            'yehhpay/yehhpay_group/active',
            Mage::app()->getStore()
        );

        if (!$active) {
            return false;
        }

        /**
         * Check for min and max order total
         */
        $baseGrandTotal = $quote->getBaseGrandTotal();
        $minOrderTotal = Mage::getStoreConfig(
            'yehhpay/yehhpay_group/min_order_total',
            Mage::app()->getStore()
        );

        $maxOrderTotal = Mage::getStoreConfig(
            'yehhpay/yehhpay_group/max_order_total',
            Mage::app()->getStore()
        );

        if ($minOrderTotal !== '' && $minOrderTotal !== null && $baseGrandTotal < $minOrderTotal) {
            return false;
        }

        if ($maxOrderTotal !== '' && $maxOrderTotal !== null && $baseGrandTotal > $maxOrderTotal) {
            return false;
        }

        /**
         * Check for specified countries
         *
         * Take specified countries from in yehhpay config.
         * Check if quote shipping country not exist in specified countries array,
         * return false
         */
        $specificCountry = Mage::getStoreConfig(
            'yehhpay/yehhpay_advanced/specificcountry',
            Mage::app()->getStore()
        );

        if ($specificCountry) {
            $specificCountryArray = explode(',', $specificCountry);

            $shippingCountry = $quote->getShippingAddress()->getCountry();

            if (!in_array($shippingCountry, $specificCountryArray)) {
                return false;
            }
        }

        return parent::isAvailable($quote);
    }

    /**
     * Method to validate payment method checkout
     *
     * @return Mage_Payment_Model_Abstract
     */
    public function validate()
    {
        $checkAddress = Mage::getStoreConfig(
            'yehhpay/yehhpay_advanced/check_address',
            Mage::app()->getStore()
        );

        /**
         * If check for same addresses is enabled
         * check if shipping address same as billing
         */
        if ($checkAddress == 1) {
            $currentQuote = Mage::getSingleton('checkout/session')->getQuote();
            $isSame = $currentQuote->getShippingAddress()->getSameAsBilling();
            if (!$isSame) {
                $errorMsg = $this->_getHelper()->__('Yehhpay requires that your billing and sipping address are the same.');
                Mage::throwException($errorMsg);
            }
        }

        return parent::validate();
    }

    /**
     * Method to get title
     *
     * @return mixed
     */
    public function getTitle()
    {
        return Mage::getStoreConfig(
            'yehhpay/yehhpay_advanced/title',
            Mage::app()->getStore()
        );
    }

    /**
     * Method to redirect for yehhpay transaction create controller,
     * if payment method yehhpay is choosed at checkout form
     *
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('yehhpay/transaction/create', array('_secure' => false));
    }

    /**
     * Method to return old quote if yehhpay transaction was created with errors
     */
    public function returnOldQuote()
    {
        $orderId = Mage::getSingleton('checkout/session')->getLastRealOrderId();

        /**
         * Get current order object by order_id
         */
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);

        /**
         * Get old quote
         */
        $oldQuote = Mage::getModel('sales/quote')->loadByIdWithoutStore($order->getQuoteId());

        $cart = Mage::getModel('checkout/cart');
        $cart->init();

        /**
         * Replace quote
         */
        Mage::getSingleton('checkout/session')->replaceQuote($oldQuote);
        $session = Mage::getSingleton('checkout/session');
        $currentQuoteId = $session->getQuoteId();
        $currentQuote = Mage::getModel('sales/quote')->load($currentQuoteId);
        $currentQuote->setIsActive(1)
            ->collectTotals()
            ->save();
    }

}