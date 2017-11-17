<?php

/**
 * Class for working with Yehhpay API
 *
 * Class Wezz_Yehhpay_Model_Api_Transaction
 */
class Wezz_Yehhpay_Model_Api_Transaction extends Mage_Core_Model_Abstract
{
    private $transaction;
    private $client;

    public function __construct()
    {
        $this->_init();
    }

    /**
     * Method to init
     */
    protected function _init()
    {
        $this->client = Mage::getSingleton('wezz_yehhpay/api_client');
        $this->setTransaction();
    }

    /**
     * Method to create transaction in Yehhpay Api
     * @param array
     */
    public function transactionCreate()
    {
        /**
         * Call transaction create method
         */
        $data = $this->getDataModel()->prepareDataForTransaction();

        $result = $this->client->callApi('Transaction.create', $data, true);

        /**
         * Set transactionId
         */
        if (isset($result['transactionId'])) {
            $this->getDataModel()->saveTransactionId($result['transactionId']);
        }

        return $result;
    }

    /**
     * Method to view transaction status
     *
     * @param $transactionId
     * @return mixed
     */
    public function transactionView($transactionId)
    {
        if (!$transactionId) {
            return false;
        }

        $result = $this->client->callApi('Transaction.view', $transactionId);

        return $result;
    }

    /**
     * Method for transaction suspend
     *
     * @param $transactionId
     * @param $suspendDate
     * @param $orderId
     * @return mixed
     */
    public function transactionSuspend($transactionId, $suspendDate, $orderId) {

        $suspendDate = Mage::helper('core')->formatDate($suspendDate, 'short', true);
        $data = array($transactionId, $suspendDate);

        $result = $this->client->callApi('Transaction.suspend', $data);

        if (isset($result['resumeDate'])) {
            $this->getDataModel()->saveResumeDate($result['resumeDate'], $orderId);
        }

        return $result;
    }

    /**
     * Method for transaction suspend
     *
     * @param $transactionId
     * @param $orderId
     * @return mixed
     */
    public function transactionResume($transactionId, $orderId) {

        $result = $this->client->callApi('Transaction.resume', $transactionId);

        $this->getDataModel()->saveResumeDate(null, $orderId);

        return $result;
    }

    /**
     * Method for transaction cancel
     *
     * @param $transactionId
     * @return mixed
     */
    public function transactionCancel($transactionId) {

        $result = $this->client->callApi('Transaction.cancel', $transactionId);
        return $result;
    }

    /**
     * Method for create refund
     *
     * @param $transactionId
     * @param $amount
     * @return mixed
     */
    public function refundCreate($transactionId, $amount)
    {
        $data = array($transactionId, $amount);
        $result = $this->client->callApi('Refund.create', $data);
        return $result;
    }


    /**
     * Method to check suspended status of transaction
     *
     * and if transaction is suspended,
     * then call transaction resume method
     *
     * @param $orderId
     * @param $realOrderId
     * @return bool
     */
    public function checkTransactionIsSuspendedAndResume($orderId, $realOrderId)
    {
        /**
         * Get transaction id by orderId
         */
        $transactionId = $this->getDataModel()->getTransactionIdByOrderId($orderId);

        if (!$transactionId) {
            return false;
        }

        /**
         * Make API request for transaction data
         */
        $result = $this->transactionView($transactionId);

        if (!isset($result['transactionId'])) {
            return false;
        }

        /**
         * Make API request to resume transaction
         */
        if (isset($result['state']['isSuspended']) && $result['state']['isSuspended']) {
            $this->transactionResume($transactionId, $realOrderId);
        } else {
            return false;
        }
    }

    /**
     * Method to check enabling status to refund transaction
     *
     * and if transaction is able to be refunded,
     * then call refund create method
     *
     * Transaction is able to refund only if consumer pay for transaction
     *
     * @param $orderId
     * @return bool
     */
    public function checkTransactionIsRefundAbleAndRefund($orderId)
    {
        /**
         * Get transaction id by orderId
         */
        $transactionId = $this->getDataModel()->getTransactionIdByOrderId($orderId);

        if (!$transactionId) {
            return false;
        }

        /**
         * Make API request for transaction data
         */
        $result = $this->transactionView($transactionId);

        if (!isset($result['transactionId'])) {
            return false;
        }

        /**
         * Make API request to refund transaction
         */
        if ($result['state']['canCreateRefund']) {
            if (isset($result['order']['amount']) && $result['order']['amount'] > 0) {
                $this->refundCreate($transactionId, $result['order']['amount']);
            }
        }

        /**
         * Make API request to cancel transaction
         */
        if ($result['state']['canBeCanceled']) {
            $this->transactionCancel($transactionId);
        }
    }

    /**
     * Callback notify listener from API
     *
     * @param $orderId
     * @return boolean
     */
    public function notify($orderId)
    {
        /**
         * Check current transaction status
         */
        $transactionResult = $this->checkCurrentTransaction($orderId);

        /**
         * Update status
         */
        $this->getDataModel()->setOrderStatus($transactionResult, $orderId);

        if ($transactionResult) {
            $this->getDataModel()->createInvoice($orderId);
        }

        return $transactionResult;
    }

    /**
     * Method to get current transaction
     *
     * @return int | boolean
     */
    public function getTransaction()
    {
        return $this->transaction;
    }

    /**
     * Method to set current transaction
     *
     */
    public function setTransaction() {

        /**
         * Check for current order object
         */
        if (Mage::registry('current_order')) {
            $this->order = Mage::registry('current_order');
        }
        elseif (Mage::registry('order')) {
            $this->order = Mage::registry('order');
        }
        else {
            $this->order = new Varien_Object();
        }

        if (!$this->order->getId()) {
            return false;
        }

        /**
         * Get transaction id by order id
         */
        $transactionId = $this->getDataModel()->getTransactionIdByOrderId($this->order->getId());

        if (!$transactionId) {
            return false;
        }

        /**
         * Make API request for transaction data
         */
        $result = $this->transactionView($transactionId);

        if (!isset($result['transactionId'])) {
            return false;
        }

        /**
         * Prepare data array of current transaction
         */
        $this->transaction = $result;

        $this->transaction['orderId'] = $this->order->getRealOrderId();
        $this->transaction['resumeDate'] = $this->order->getPayment()->getYehhpayTransactionDate();

        if (isset($result['state'])) {
            if (isset($result['state']['hasBeenApprovedByConsumer']) && $result['state']['hasBeenApprovedByConsumer']) {
                $this->transaction['consumerStatus'] = Mage::helper('wezz_yehhpay/data')->__("Approved by consumer and Yehhpay");
            } else {
                $this->transaction['consumerStatus'] = Mage::helper('wezz_yehhpay/data')->__("Not approved by consumer or Yehhpay");
            }

            if (isset($result['state']['isCanceled']) && $result['state']['isCanceled']) {
                $this->transaction['stateStatus'] =  Mage::helper('wezz_yehhpay/data')->__("Canceled");
            } else if (isset($result['state']['isExpired']) && $result['state']['isExpired'] == true) {
                $this->transaction['stateStatus'] = Mage::helper('wezz_yehhpay/data')->__("Expired");
            } else if (isset($result['state']['isSuspended']) && $result['state']['isSuspended'] == true) {
                $this->transaction['stateStatus'] = Mage::helper('wezz_yehhpay/data')->__("Suspended");
            }
        }
    }

    /**
     * Method to check current transaction
     *
     * @param $orderId
     * @return bool
     */
    private function checkCurrentTransaction($orderId)
    {
        $transactionId = $this->getDataModel()->getTransactionIdByIncrementId($orderId);

        if (!$transactionId) {
            return false;
        }

        /**
         * Make API request for transaction data
         */
        $result = $this->transactionView($transactionId);

        if (!isset($result['transactionId'])) {
            return false;
        }

        if (isset($result['state']['isOpen'])
            && isset($result['state']['hasBeenApprovedByConsumer'])
            && $result['state']['isOpen']
            && $result['state']['hasBeenApprovedByConsumer']) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Method to get WezzYehhpayApiData Model Object
     *
     * @return false|Mage_Core_Model_Abstract
     */
    protected function getDataModel()
    {
        return Mage::getModel('wezz_yehhpay/api_data');
    }
}