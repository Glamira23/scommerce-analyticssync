<?php

namespace Scommerce\AnalyticsSync\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Scommerce\AnalyticsSync\Helper\Data;
use Scommerce\AnalyticsSync\Model\SynchronizerGa4;

class SendInvoiceDataToGA4 implements ObserverInterface
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var SynchronizerGa4
     */
    protected $synchronizerGa4;

    public function __construct(
        Data $helper,
        SynchronizerGa4 $synchronizerGa4
    )
    {
        $this->helper = $helper;
        $this->synchronizerGa4 = $synchronizerGa4;
    }

    public function execute(EventObserver $observer)
    {
        $invoice = $observer->getEvent()->getInvoice();
        $order = $invoice->getOrder();
        $storeId = $order->getStoreId();
        $excludedPaymentMethods = $this->helper->getExcludedPaymentMethods();
        $paymentMethod = $order->getPayment()->getMethod();
        $excludedOrderStatuses = $this->helper->getOrderStatusExclude();
        $orderStatus = $order->getStatus();
        if (
            $this->helper->isEnabled($storeId)
            && $this->helper->sendOnInvoiceCreation($storeId)
            && !in_array($paymentMethod, $excludedPaymentMethods)
            && !in_array($orderStatus, $excludedOrderStatuses)
        ) {
            $this->synchronizerGa4->buildAndSendData($order, $storeId, $invoice);
        }
    }
}
