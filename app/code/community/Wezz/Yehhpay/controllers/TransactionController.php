<?php

/**
 * Class Wezz_Yehhpay_TransactionController
 */
class Wezz_Yehhpay_TransactionController extends Mage_Core_Controller_Front_Action
{
    /**
     * Method to create transaction
     */
    public function createAction()
    {
        /**
         * Provide transaction
         */
        $result = Mage::getModel('wezz_yehhpay/api_transaction')->transactionCreate();

        /**
         * If transaction has success status,
         * redirect to external yehh pay part
         * else - set payment failed status and payment review state comment
         * and redirect to order page success
         */
         if (isset($result['isSuccess']) && $result['isSuccess'] && isset($result['url']) && $result['url']) {
             $this->getResponse()->setRedirect($result['url']);
         } else {

             $message = Mage::helper('wezz_yehhpay/data')->__("Yehhpay transaction is not completed and approved. Please try to re-order.");

             $session = Mage::getSingleton('checkout/session');
             $session->addError($message);

             Mage::getModel('wezz_yehhpay/payment')->returnOldQuote();
             $this->_redirect('checkout/cart');
         }
    }

    /**
     * Callback method from yehhpay api
     */
    public function checkAction()
    {
        $orderId = $this->getRequest()->get('order');

        if ($orderId) {
            /***
             * Check transaction status and update order
             */
            $result = Mage::getModel('wezz_yehhpay/api_transaction')->notify($orderId);

            if ($result) {
                /**
                 * Redirect to checkout success page
                 */
                $this->_redirect('checkout/onepage/success');
            } else {

                $message = Mage::helper('wezz_yehhpay/data')->__("Yehhpay transaction is not completed and approved. Please try to re-order.");

                $session = Mage::getSingleton('checkout/session');
                $session->addError($message);

                Mage::getModel('wezz_yehhpay/payment')->returnOldQuote();
                $this->_redirect('checkout/cart');
            }
        }
    }

    /**
     * Callback method from yehhpay api - change notify
     */
    public function notifyAction()
    {
        if (!isset($_POST['transactionId']))
        {
            throw new \Exception('Missing transaction id in notification callback.');
        }

        $transactionId = (int) $_POST['transactionId'];

        if ($transactionId) {
            /***
             * Check transaction status and update order
             */
            Mage::getModel('wezz_yehhpay/api_transaction')->hook($transactionId);
        }
    }
}