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
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Sales\Model\Order;
use Magento\Quote\Model\Quote;
use Scommerce\AnalyticsSync\Helper\Data;
use Scommerce\AnalyticsSync\Model\GetTrackingData;

class SaveOrderBeforeSalesModelQuoteObserver implements ObserverInterface
{
    const SC_TRACKING_INFO = 'sc_tracking_info';

    /**
     * @var string[]
     */
    private $attributes = [
        self::SC_TRACKING_INFO
    ];

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var GetTrackingData
     */
    protected $trackingData;

    /**
     * @var Json
     */
    protected $json;

    /**
     * @param Data $helper
     * @param GetTrackingData $trackingData
     * @param Json $json
     */
    public function __construct(
        Data $helper,
        GetTrackingData $trackingData,
        Json $json
    ) {
        $this->helper = $helper;
        $this->trackingData = $trackingData;
        $this->json = $json;
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
                $data = $quote->getData($attribute);
                if ($attribute == self::SC_TRACKING_INFO) {
                    if (is_null($data)) {
                        continue;
                    }
                    $data = $this->addNewTimestamp($data);
                }
                $order->setData($attribute, $data);
            }
        }

        return $this;
    }

    protected function addNewTimestamp($data)
    {
        if ($data == null) {
            return null;
        }
        $trackingData = $this->json->unserialize($data);

        $newData = $this->trackingData->getContainerCookie();
        if ($newData !== null && $newData['timestamp']) {
            $trackingData['gsTimestamp'] = $newData['timestamp'];
        }

        return $this->json->serialize($trackingData);
    }
}
