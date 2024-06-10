<?php
/**
 * Scommerce AnalyticsSync Repository class
 *
 * @category   Scommerce
 * @author     Scommerce Mage <core@scommerce-mage.com>
 */

namespace Scommerce\AnalyticsSync\Model;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Scommerce\AnalyticsSync\Api\SyncLogRepositoryInterface;
use Scommerce\AnalyticsSync\Model\ResourceModel\SyncLog as SyncLogResource;
use Scommerce\AnalyticsSync\Model\SyncLogFactory as SyncLogFactory;

/**
 * Class SyncLogRepository repository for Log records
 */
class SyncLogRepository implements SyncLogRepositoryInterface
{
    /**
     * @var SyncLogResource
     */
    private $resource;

    /**
     * @var \Scommerce\AnalyticsSync\Model\SyncLogFactory
     */
    private $logFactory;

    /**
     * SyncLogRepository constructor.
     * @param SyncLogResource $resource
     * @param \Scommerce\AnalyticsSync\Model\SyncLogFactory $logFactory
     */
    public function __construct(
        SyncLogResource $resource,
        SyncLogFactory $logFactory
    ) {
        $this->resource = $resource;
        $this->logFactory = $logFactory;
    }

    /**
     * @param int $logId
     * @return mixed
     */
    public function get($logId)
    {
        $model = $this->logFactory->create();
        $this->resource->load($model, $logId);
        return $model;
    }

    /**
     * @param \Scommerce\AnalyticsSync\Api\Data\SyncLogInterface $syncLog
     * @return mixed|\Scommerce\AnalyticsSync\Api\Data\SyncLogInterface
     * @throws \Exception
     */
    public function save($syncLog)
    {
        try {
            $syncLog->setCreatedAt(time());
            $this->resource->save($syncLog);
            return $syncLog;
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__($e->getMessage()));
        }
    }

    /**
     * @param array $syncLogData
     * @return mixed|\Scommerce\AnalyticsSync\Api\Data\SyncLogInterface
     * @throws \Exception
     */
    public function createFromArray($syncLogData)
    {
        $model = $this->logFactory->create();
        $syncLogData['created_at'] = time();
        $model->setData($syncLogData);
        return $this->save($model);
    }

    /**
     * @param $syncLog
     * @return mixed|SyncLogResource
     * @throws \Exception
     */
    public function delete($syncLog)
    {
        return $this->resource->delete($syncLog);
    }

    /**
     * @param int $logId
     * @return mixed|SyncLogResource
     * @throws \Exception
     */
    public function deleteById($logId)
    {
        $log = $this->get($logId);
        return $this->delete($log);
    }
}
