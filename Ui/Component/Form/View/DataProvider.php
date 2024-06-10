<?php
/**
 * Scommerce AnalyticsSync Form Data Provider
 *
 * @category   Scommerce
 * @author     Scommerce Mage <core@scommerce-mage.com>
 */

namespace Scommerce\AnalyticsSync\Ui\Component\Form\View;

use Magento\Ui\DataProvider\AbstractDataProvider;
use Scommerce\AnalyticsSync\Model\ResourceModel\SyncLog\CollectionFactory;

class DataProvider extends AbstractDataProvider
{
    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $syncLogCollectionFactory
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $syncLogCollectionFactory,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $syncLogCollectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        if (isset($this->_loadedData)) {
            return $this->_loadedData;
        }
        $items = $this->collection->getItems();
        foreach ($items as $log) {
            $this->_loadedData[$log->getId()] = $log->getData();
        }
        return $this->_loadedData;
    }
}
