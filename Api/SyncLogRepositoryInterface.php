<?php
/**
 * SyncLog Repository interface
 *
 * @category   Scommerce
 * @author     Scommerce Mage <core@scommerce-mage.com>
 */

namespace Scommerce\AnalyticsSync\Api;

use Scommerce\AnalyticsSync\Api\Data\SyncLogInterface;

/**
 * Interface SyncLogRepositoryInterface
 */
interface SyncLogRepositoryInterface
{
    /**
     * Returns log item by id
     *
     * @param int $logId
     * @return mixed
     */
    public function get($logId);

    /**
     * Save
     *
     * @param SyncLogInterface $syncLog
     * @return mixed
     */
    public function save($syncLog);

    /**
     * Save
     *
     * @param array $syncLogData
     * @return mixed
     */
    public function createFromArray($syncLogData);

    /**
     * @param $syncLog
     * @return mixed
     */
    public function delete($syncLog);

    /**
     * @param int $logId
     * @return mixed
     */
    public function deleteById($logId);
}
