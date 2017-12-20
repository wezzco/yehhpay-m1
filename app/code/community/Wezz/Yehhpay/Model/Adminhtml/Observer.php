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
     * Get refund transaction
     *
     * @param Varien_Event_Observer $observer
     */
    public function refundTransaction(Varien_Event_Observer $observer)
    {
        $creditMemo = $observer->getCreditmemo();

        if ($creditMemo) {
            Mage::getModel('wezz_yehhpay/api_transaction')->checkTransactionIsRefundAbleAndRefund(
                $creditMemo->getOrderId(),
                (string) $creditMemo->getGrandTotal()
            );
        }

        return $creditMemo;
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

        return $order;
    }

    /**
     * Suspend transaction if order status is holded.
     *
     * @param Varien_Event_Observer $observer
     */
    public function setSuspendTransaction(Varien_Event_Observer $observer)
    {
        $orderId = $observer->getControllerAction()->getRequest()->getParam('order_id');

        if ($orderId) {
            $order = Mage::getModel('sales/order')->load($orderId);

            if ($order && $order->getPayment()->getMethod() == 'yehhpay') {
                $transactionId = $order->getPayment()->getYehhpayTransactionId();
                $orderState = $order->getState();
                if ($orderState == Mage_Sales_Model_Order::STATE_HOLDED && $transactionId) {
                    $currentDate = new DateTime();
                    $currentDate->modify('+8 days');
                    $futureDate = $currentDate->format('Y-m-d');

                    Mage::getModel('wezz_yehhpay/api_transaction')->transactionSuspend($transactionId, $futureDate, $order->getRealOrderId());
                }
            }
        }
    }

    /**
     * Resume transaction if order is unheld.
     *
     * @param Varien_Event_Observer $observer
     */
    public function resumeSuspendTransaction(Varien_Event_Observer $observer)
    {
        $orderId = $observer->getControllerAction()->getRequest()->getParam('order_id');

        if ($orderId) {
            $order = Mage::getModel('sales/order')->load($orderId);

            if ($order && $order->getPayment()->getMethod() == 'yehhpay') {
                $orderState = $order->getState();
                $realOrderId = $order->getRealOrderId();

                if ($orderState == Mage_Sales_Model_Order::STATE_PROCESSING && $realOrderId) {
                    Mage::getModel('wezz_yehhpay/api_transaction')->checkTransactionIsSuspendedAndResume($orderId, $realOrderId);
                }
            }
        }
    }
}