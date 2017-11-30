<?php

/**
 * Class Wezz_Yehhpay_TransactionController
 */
class Wezz_Yehhpay_Adminhtml_TransactionController extends Mage_Core_Controller_Front_Action
{
    /**
     * Method to suspend yehhpay transaction
     */
    public function suspendAction(){

        $transactionId = $this->getRequest()->get('id');
        $orderId = $this->getRequest()->get('orderId');
        $suspendDate = $this->getRequest()->getPost('suspendDate');

        if (!$transactionId || !$orderId || !$suspendDate) {
            $this->_redirectReferer();
        }

        Mage::getModel('wezz_yehhpay/api_transaction')->transactionSuspend($transactionId, $suspendDate, $orderId);

        $this->_redirectReferer();
    }

    /**
     * Method to resume yehhpay transaction
     *
     */
    public function resumeAction(){

        $transactionId = $this->getRequest()->get('id');
        $orderId = $this->getRequest()->get('orderId');

        if (!$transactionId || !$orderId) {
            $this->_redirectReferer();
        }

        Mage::getModel('wezz_yehhpay/api_transaction')->transactionResume($transactionId, $orderId);
        $this->_redirectReferer();
    }
}