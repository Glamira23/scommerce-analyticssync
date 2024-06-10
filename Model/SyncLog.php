<?php
/**
 * SyncLog model
 *
 * @category   Scommerce
 * @author     Scommerce Mage <core@scommerce-mage.com>
 */

namespace Scommerce\AnalyticsSync\Model;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;
use Scommerce\AnalyticsSync\Api\Data\SyncLogInterface;
use Scommerce\AnalyticsSync\Model\ResourceModel\SyncLog as SyncLogResource;

class SyncLog extends AbstractModel implements IdentityInterface, SyncLogInterface
{
    const CACHE_TAG = 'ga_sync_log'; // Cache tag

    protected function _construct()
    {
        $this->_init(SyncLogResource::class);
        $this->setIdFieldName(self::LOG_ID);
    }

    /**
     * @return string[]
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    /**
     * @inherit
     */
    public function getLogId()
    {
        return $this->getData(self::LOG_ID);
    }

    /**
     * @inherit
     */
    public function setLogId($logId)
    {
        return $this->setData(self::LOG_ID, $logId);
    }

    /**
     * @inherit
     */
    public function getIncrementId()
    {
        return $this->getData(self::INCREMENT_ID);
    }

    /**
     * @inherit
     */
    public function setIncrementId($incrementId)
    {
        return $this->setData(self::INCREMENT_ID, $incrementId);
    }

    /**
     * @inherit
     */
    public function getTrackingData()
    {
        return $this->getData(self::TRACKING_DATA);
    }

    /**
     * @inherit
     */
    public function setTrackingData($trackingData)
    {
        return $this->setData(self::TRACKING_DATA, $trackingData);
    }

    /**
     * @inherit
     */
    public function getStatus()
    {
        return $this->getData(self::STATUS);
    }

    /**
     * @inherit
     */
    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * @inherit
     */
    public function getErrorMessage()
    {
        return $this->getData(self::ERROR_MESSAGE);
    }

    /**
     * @inherit
     */
    public function setErrorMessage($errorMessage)
    {
        return $this->setData(self::ERROR_MESSAGE, $errorMessage);
    }

    /**
     * @inherit
     */
    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * @inherit
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }
}
