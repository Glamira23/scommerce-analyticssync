<?php
/**
 * Scommerce AnalyticsSync observer class for checkout_cart_product_add_after event
 *
 * @category   Scommerce
 * @author     Scommerce Mage <core@scommerce-mage.com>
 */

namespace Scommerce\AnalyticsSync\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Scommerce\AnalyticsSync\Helper\Data;
use Scommerce\AnalyticsSync\Model\GetTrackingData;

/**
 * Class CheckoutCartProductAddAfterObserver add tracking information
 */
class CheckoutCartProductAddAfterObserver implements ObserverInterface
{
    /** @var Data */
    protected $helper;

    /** @var GetTrackingData */
    protected $trackingData;

    /**
     * CheckoutCartProductAddAfterObserver constructor.
     * @param Data $helper
     * @param GetTrackingData $trackingData
     */
    public function __construct(
        Data $helper,
        GetTrackingData $trackingData
    ) {
        $this->helper = $helper;
        $this->trackingData = $trackingData;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $quote = $observer->getEvent()->getQuoteItem()->getQuote();
        if (!$this->helper->isEnabled($quote->getStoreId())) {
            return;
        }
        try {
            $data = $this->trackingData->execute();
            $quote->setScTrackingInfo(json_encode($data));
        } catch (\Exception $e) {
            //
            return;
        }
    }
}
