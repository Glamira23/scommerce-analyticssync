<?php

namespace Scommerce\AnalyticsSync\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Scommerce\AnalyticsSync\Model\GetTrackingData;

class Tracking implements ArgumentInterface
{
    private $trackingData;

    public function __construct(
        GetTrackingData $trackingData
    ) {
        $this->trackingData = $trackingData;
    }

    public function getContainerId()
    {
        return $this->trackingData->getContainerId();
    }
}
