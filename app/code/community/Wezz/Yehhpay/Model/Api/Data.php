<?php

use Zend_Locale as ZendLocale;

/**
 * Class for working with data for Api Yehhpay
 *
 * Class Wezz_Yehhpay_Model_Api_Date
 */
class Wezz_Yehhpay_Model_Api_Data
{
    /**
     * Method to create transaction in Yehhpay Api
     */
    public function prepareDataForTransaction()
    {
        /**
         * Get current created order id
         */
        $orderId = Mage::getSingleton('checkout/session')->getLastRealOrderId();

        /**
         * Get current order object by order_id
         */
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);

        /**
         * Prepare address and invoice data array for consumer struct of future transaction
         */
        $postcode = $this->checkPostcode();

        $houseNumber = '';
        $houseNumberAddition = '';
        $street = '';

        /**
         * If postcode extension active, take housenumber and housenumber addition from street's array
         */
        if ($postcode) {
            $shipping = $order->getShippingAddress()->getStreet();

            if (isset($shipping[0])) {
                $street = $shipping[0];
            }

            if (isset($shipping[1])) {
                $houseNumber = $shipping[1];
            }

            if (isset($shipping[2])) {
                $houseNumberAddition = $shipping[2];
            }

        } else {
            $street = implode(', ', $order->getShippingAddress()->getStreet());
        }

        $addressData = array(
            'postcode' => $order->getShippingAddress()->getPostcode(),
            'houseNumber' => $houseNumber,
            'houseNumberAddition' => $houseNumberAddition,
            'street' => $street,
            'city' => $order->getShippingAddress()->getCity(),
            'countryCode' => $order->getShippingAddress()->getCountryModel()->getIso3Code()
        );

        /**
         * Use Zend Locale to definite exact language from configuration locale
         * $languageCode is actual language code from local
         */
        $zendLocale = new ZendLocale();
        $languageCode = $zendLocale->getLanguage();

        /**
         * Prepare data array for consumer struct
         *
         * According to API documentation,
         * address struct and invoiceAddress struct
         * must be equal, so it will be use one prepared array $addressData
         * for both keys.
         */
        $transactionData['consumer'] = array(
                    'language' => $languageCode,
                    'firstName' => $order->getCustomerFirstname(),
                    'lastName' => $order->getCustomerLastname(),
                    'phoneNumber' => $order->getShippingAddress()->getTelephone(),
                    'emailAddress' => $order->getCustomerEmail(),
                    'address' => $addressData,
                    'invoiceAddress' => $addressData,
                    'dateOfBirth' => null,
                    'ipAddress' => Mage::helper('core/http')->getRemoteAddr(),
                    'trustScore' => null
        );

        $date = new DateTime();
        $date->modify('+1 day');

        /**
         * Prepare data array for order struct
         */
        $transactionData['order'] = array(
            'identifier' => $orderId,
            'invoiceDate' => $date->format('Y-m-d H:i:s'),
            'deliveryDate' => null,
            'redirectUrl' => Mage::getUrl('yehhpay/transaction/check', array('order' => $orderId, '_secure' => false)),
            'notificationUrl' =>  Mage::getUrl('yehhpay/transaction/notify', array('order' => $orderId, '_secure' => false)),
        );

        /**
         * Prepare data array for order items struct inside order struct
         */
        $orderItems = $order->getItemsCollection(array(), true);
        foreach ($orderItems as $item) {
            $price = $item->getPriceInclTax() ? $item->getPriceInclTax() : 0;
            $transactionData['order']['products'][] = array(
                'price' => $price,
                'quantity' => $item->getQtyOrdered(),
                'identifier' => $item->getProductId(),
                'description' => $item->getName()
            );
        }

        if ($order->getShippingInclTax() > 0) {
            $shippingVirtualItem = array(
                'price' => $order->getShippingInclTax(),
                'quantity' => '1',
                'identifier' => 'Shipping',
                'description' => 'Shipping Cost'
            );

            if (isset($transactionData['order']['products'])) {
                $transactionData['order']['products'][] = $shippingVirtualItem;
            }
        }

        return $transactionData;
    }

    /**
     * Method to save transactionId at db
     *
     * @param $transactionId
     */
    public function saveTransactionId($transactionId)
    {
        /**
         * Get current created order id
         */
        $orderId = Mage::getSingleton('checkout/session')->getLastRealOrderId();

        /**
         * Get current order object by order_id
         */
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);

        $payment = $order->getPayment();
        $payment->setYehhpayTransactionId($transactionId);
        $payment->save();
    }


    /**
     * Method to get transaction id by order id from
     * sales_flat_order_payment table
     *
     * @param int $orderId
     * @return int | boolean
     */
    public function getTransactionIdByOrderId($orderId)
    {
        /**
         * Make query to 'sales_flat_order_payment' to get transaction id from data collection (db_field = 'yehhpay_transaction_id')
         */
        $collection = Mage::getModel('sales/order')->getCollection()->addFieldToFilter('main_table.entity_id', $orderId);
        $collection->getSelect()->join(array('payment' => 'sales_flat_order_payment'), 'payment.parent_id = main_table.entity_id',
            array(
                'payment_method' => 'payment.method',
                'yehhpay_transaction_id' => 'payment.yehhpay_transaction_id'
            ));
        $collectionData = $collection->getFirstItem()->getData();
        $transactionId =  isset($collectionData['yehhpay_transaction_id']) ? $collectionData['yehhpay_transaction_id'] : false;

        if (!$transactionId) {
            return false;
        }

        return $transactionId;
    }

    /**
     * Method to get transaction by increment id
     *
     * @param $orderId
     * @return bool|int
     */
    public function getTransactionIdByIncrementId($orderId)
    {
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);

        if (!$order->getId()) {
            return false;
        }

        return $this->getTransactionIdByOrderId($order->getId());
    }

    /**
     * Method to set order status
     * according to yehhpay transaction status responses
     *
     * @param $success
     */
    public function setOrderStatus($success, $orderId = false)
    {
        if ($success) {
            $status = $this->getPaymentSuccessStatus();
        } else {
            $status = $this->getPaymentFailedStatus();
        }

        if (!$orderId) {
            $orderId = Mage::getSingleton('checkout/session')->getLastRealOrderId();
        }

        $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
        $order->setStatus($status);
        $order->save();
    }

    /**
     * Method to set order status
     * according to yehhpay transaction status responses
     * @param $resumeData
     * @param $orderId
     *
     * @return boolean
     */
    public function saveResumeDate($resumeData, $orderId)
    {
        if (!$orderId) {
            return false;
        }

        $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);

        if (!$order) {
            return false;
        }

        $payment = $order->getPayment();

        if ($payment) {
            $payment->setYehhpayTransactionDate($resumeData);
            $payment->save();
        }
    }

    /**
     * Method to create invoice
     *
     * @param $orderId
     */
    public function createInvoice($orderId)
    {
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);

        if ($order->getState() == $order::STATE_PROCESSING) {
            return;
        }

        $capture = Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE;
        $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();
        $invoice->setRequestedCaptureCase($capture);
        $invoice->register();

        $transaction = Mage::getModel('core/resource_transaction')
            ->addObject($invoice)
            ->addObject($invoice->getOrder());

        $transaction->save();
    }

    /**
     * Method to get payment success status
     *
     * @return mixed
     */
    private function getPaymentSuccessStatus()
    {
        return Mage::getStoreConfig(
            'yehhpay/yehhpay_advanced/payment_success_status',
            Mage::app()->getStore()
        );
    }

    /**
     * Method to get payment failure status
     */
    private function getPaymentFailedStatus()
    {
        return Mage::getStoreConfig(
            'yehhpay/yehhpay_advanced/payment_failed_status',
            Mage::app()->getStore()
        );
    }

    /**
     * Method to check postcode
     *
     * @return bool
     */
    private function checkPostcode()
    {
        $enabled = Mage::getStoreConfig(
            'postcodenl_api/config/enabled',
            Mage::app()->getStore());

        $use_housenumber = Mage::getStoreConfig(
            'postcodenl_api/advanced_config/use_street2_as_housenumber',
            Mage::app()->getStore());

        if ($enabled && $use_housenumber) {
            return true;
        } else {
            return false;
        }
    }

}