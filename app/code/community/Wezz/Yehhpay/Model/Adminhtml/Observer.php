<?php

/**
 * Observer class for render yehhpay blocks in admin order view
 *
 * Class Wezz_Yehhpay_Model_Adminhtml_Observer
 */
class Wezz_Yehhpay_Model_Adminhtml_Observer
{
    /**
     * Method to render yehhpay info block,
     * if yehhpay transaction is exist
     *
     * @param Varien_Event_Observer $observer
     */
    public function getYehhpayTransactionInfo(Varien_Event_Observer $observer) {

        $block = $observer->getBlock();

        if (($block->getNameInLayout() == 'order_info') && ($child = $block->getChild('yehhpay.order.info.transaction.block'))) {
            $transaction = Mage::getModel('wezz_yehhpay/api_transaction')->getTransaction();
            if ($transaction) {
                $transport = $observer->getTransport();
                if ($transport) {
                    $html = $transport->getHtml();
                    $html .= $child->toHtml();
                    $transport->setHtml($html);
                }
            }

        }
    }

    /**
     * Listener for order change events
     *
     * @param Varien_Event_Observer $observer
     */
    public function changeOrder(Varien_Event_Observer $observer) {

        $order = $observer->getOrder();

        /**
         * If current state of order is complete,
         * than resume suspended yehhpay transaction
         */
        if ($order->getState() == $order::STATE_COMPLETE) {
            Mage::getModel('wezz_yehhpay/api_transaction')->checkTransactionIsSuspendedAndResume($order->getId(),
                $order->getRealOrderId());
        }
        /**
         * If current state of order is closed and order has credit memos,
         * so we create refund for Yehhpay according to credit memos
         * and cancel transaction
         */
        else if ($order->getState() == $order::STATE_CLOSED && $order->hasCreditmemos()) {
            Mage::getModel('wezz_yehhpay/api_transaction')->checkTransactionIsRefundAbleAndRefund($order->getId());
        }
    }
}