<?php

namespace Scommerce\AnalyticsSync\Model;

use Scommerce\AnalyticsSync\Helper\Data;

class AnalyticsSync
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var Synchronizer
     */
    protected $synchronizer;

    /**
     * @var SynchronizerGa4
     */
    protected $ga4Sync;

    /**
     * @param Data $helper
     * @param Synchronizer $synchronizer
     * @param SynchronizerGa4 $ga4Sync
     */
    public function __construct(
        Data $helper,
        Synchronizer $synchronizer,
        SynchronizerGa4 $ga4Sync
    ) {
        $this->helper = $helper;
        $this->synchronizer = $synchronizer;
        $this->ga4Sync = $ga4Sync;
    }

    /**
     * @return void
     */
    public function execute()
    {
        $this->synchronizer->execute();
        $this->ga4Sync->execute();
    }
}
