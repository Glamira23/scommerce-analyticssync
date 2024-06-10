<?php
/**
 * Scommerce SyncLog Collection
 *
 * @category   Scommerce
 * @author     Scommerce Mage <core@scommerce-mage.com>
 */

namespace Scommerce\AnalyticsSync\Model\ResourceModel\SyncLog;

use Scommerce\AnalyticsSync\Api\Data\SyncLogInterface;
use Scommerce\AnalyticsSync\Model\SyncLog;
use Scommerce\AnalyticsSync\Model\ResourceModel\SyncLog as SyncLogResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Class Collection for log records
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = SyncLogInterface::LOG_ID;

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(SyncLog::class, SyncLogResource::class);
    }
}
