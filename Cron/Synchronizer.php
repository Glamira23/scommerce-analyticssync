<?php
/**
 * Scommerce AnalyticsSync Synchonizer Cron class
 *
 * @category   Scommerce
 * @author     Scommerce Mage <core@scommerce-mage.com>
 */

namespace Scommerce\AnalyticsSync\Cron;

use Scommerce\AnalyticsSync\Helper\Data;
use Scommerce\AnalyticsSync\Model\AnalyticsSync;

/**
 * Class Synchronizer cron sync command
 */
class Synchronizer
{
    /**
     * @var AnalyticsSync
     */
    protected $synchronizer;

    /**
     * Synchronizer constructor.
     * @param Data $helper
     * @param AnalyticsSync $synchronizer
     */
    public function __construct(
        AnalyticsSync $synchronizer
    ) {
        $this->synchronizer = $synchronizer;
    }

    /**
     * Execute Synchronization
     */
    public function execute()
    {
        $this->synchronizer->execute();
    }
}
