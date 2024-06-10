<?php
/**
 * Scommerce AnalyticsSync last cron run
 *
 * @category   Scommerce
 * @author     Scommerce Mage <core@scommerce-mage.com>
 */

namespace Scommerce\AnalyticsSync\Model;

use Scommerce\AnalyticsSync\Helper\Data;
use Magento\Framework\App\Config\Storage\WriterInterface;

/**
 * Class LastCron
 */
class LastSyncRun
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * LastCron constructor.
     * @param Data $helper
     * @param WriterInterface $configWriter
     */
    public function __construct(
        Data $helper,
        WriterInterface $configWriter
    ) {
        $this->helper = $helper;
        $this->configWriter = $configWriter;
    }

    /**
     * @return mixed
     */
    public function getLastCronRun()
    {
        return $this->helper->getLastCronRun();
    }

    /**
     * @param $date
     */
    public function saveLastCronRun($date)
    {
        $this->configWriter->save(Data::XML_PATH_LAST_CRON_RUN, $date);
    }
}
