<?php
/**
 * Scommerce AnalyticsSync Sync Log Data interface
 *
 * @category   Scommerce
 * @author     Scommerce Mage <core@scommerce-mage.com>
 */

namespace Scommerce\AnalyticsSync\Api\Data;

/**
 * Interface SyncLogInterface
 */
interface SyncLogInterface
{
    const TABLE_NAME = 'sc_gasynclog';

    const LOG_ID        = 'log_id';
    const INCREMENT_ID  = 'increment_id';
    const TRACKING_DATA = 'tracking_data';
    const STATUS        = 'status';
    const ERROR_MESSAGE = 'error_message';
    const CREATED_AT    = 'created_at';

    /**
     * @return mixed
     */
    public function getLogId();

    /**
     * @param $logId
     * @return mixed
     */
    public function setLogId($logId);

    /**
     * @return mixed
     */
    public function getIncrementId();

    /**
     * @param $incrementId
     * @return mixed
     */
    public function setIncrementId($incrementId);

    /**
     * @return mixed
     */
    public function getTrackingData();

    /**
     * @param $trackingData
     * @return mixed
     */
    public function setTrackingData($trackingData);

    /**
     * @return mixed
     */
    public function getStatus();

    /**
     * @param $status
     * @return mixed
     */
    public function setStatus($status);

    /**
     * @return mixed
     */
    public function getErrorMessage();

    /**
     * @param $errorMessage
     * @return mixed
     */
    public function setErrorMessage($errorMessage);

    /**
     * @return mixed
     */
    public function getCreatedAt();

    /**
     * @param $createdAt
     * @return mixed
     */
    public function setCreatedAt($createdAt);
}
