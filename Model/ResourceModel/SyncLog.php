<?php
/**
 * SyncLog resource model
 *
 * @category   Scommerce
 * @author     Scommerce Mage <core@scommerce-mage.com>
 */

namespace Scommerce\AnalyticsSync\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Scommerce\AnalyticsSync\Api\Data\SyncLogInterface;

/**
 * Class SyncLog resource model
 */
class SyncLog extends AbstractDb
{
    protected function _construct()
    {
        $this->_init(SyncLogInterface::TABLE_NAME, SyncLogInterface::LOG_ID);
    }
}
