<?php
/**
 * Scommerce AnalyticsSync observer class for sales_model_service_quote_submit_before event
 *
 * @category   Scommerce
 * @author     Scommerce Mage <core@scommerce-mage.com>
 */

namespace Scommerce\AnalyticsSync\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Sales\Model\Order;
use Magento\Quote\Model\Quote;
use Scommerce\AnalyticsSync\Helper\Data;

class SaveOrderBeforeSalesModelQuoteObserver implements ObserverInterface
{
    /**
     * @var string[]
     */
    private $attributes = [
        'sc_tracking_info'
    ];

    /**
     * @var Data
     */
    protected $helper;

    /**
     * SaveOrderBeforeSalesModelQuoteObserver constructor.
     * @param Data $helper
     */
    public function __construct(
        Data $helper
    ) {
        $this->helper = $helper;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        /* @var Order $order */
        $order = $observer->getEvent()->getData('order');
        /* @var Quote $quote */
        $quote = $observer->getEvent()->getData('quote');
        if (!$this->helper->isEnabled($quote->getStoreId())) {
            return $this;
        }

        foreach ($this->attributes as $attribute) {
            if ($quote->hasData($attribute)) {
                $order->setData($attribute, $quote->getData($attribute));
            }
        }

        return $this;
    }
}
