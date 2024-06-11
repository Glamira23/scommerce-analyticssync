<?php
/**
 * Base Class for synchronisation
 *
 * @category   Scommerce
 * @author     Scommerce Mage <core@scommerce-mage.com>
 */


namespace Scommerce\AnalyticsSync\Model;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Sales\Model\ResourceModel\Order as Resource;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Framework\Escaper;
use Scommerce\AnalyticsSync\Api\SyncLogRepositoryInterface;
use Scommerce\AnalyticsSync\Helper\Data;
use Scommerce\AnalyticsSync\Logger\Logger;

abstract class SyncBase
{
    const TRANSACTION_FIELD = 'sc_transaction_sent';

    protected $_cid;
    protected $_cn;
    protected $_cs;
    protected $_cm;
    protected $_cc;
    protected $_ck;
    protected $_gclid;
    protected $_domainHost;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var DirectoryList
     */
    protected $dir;

    /**
     * @var Resource
     */
    protected $resource;

    /**
     * @var OrderCollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var CategoryRepositoryInterface
     */
    protected $categoryRepository;

    /**
     * @var Escaper
     */
    protected $escaper;

    /**
     * @var SyncLogRepositoryInterface
     */
    protected $syncLogRepository;

    /**
     * @var Curl
     */
    protected $curl;

    protected $_storeId;

    protected $_skipCount;

    /**
     * @param Data $helper
     * @param StoreManagerInterface $storeManager
     * @param Logger $logger
     * @param DirectoryList $dir
     * @param Resource $resource
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param ProductRepositoryInterface $productRepository
     * @param CategoryRepositoryInterface $categoryRepository
     * @param Escaper $escaper
     * @param SyncLogRepositoryInterface $syncLogRepository
     */
    public function __construct(
        Data                        $helper,
        StoreManagerInterface       $storeManager,
        Logger                      $logger,
        DirectoryList               $dir,
        Resource                    $resource,
        OrderCollectionFactory      $orderCollectionFactory,
        ProductRepositoryInterface  $productRepository,
        CategoryRepositoryInterface $categoryRepository,
        Escaper                     $escaper,
        SyncLogRepositoryInterface  $syncLogRepository,
        Curl                        $curl
    )
    {
        $this->helper = $helper;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->dir = $dir;
        $this->resource = $resource;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
        $this->escaper = $escaper;
        $this->syncLogRepository = $syncLogRepository;
        $this->curl = $curl;
    }

    /**
     * @return void
     */
    public function execute()
    {
        $this->log('Synchronization Started');
        $stores = $this->storeManager->getStores();
        foreach ($stores as $store) {
            $this->_storeId = $store->getId();
            if (!$this->isEnabledForStore()) {
                continue;
            }
            $this->_skipCount = $this->getOrdersDaysSkip();
            $this->log('Start sync for store ID = ' . $this->_storeId);
            $this->syncGoogleOrders();
            $this->log('Sync complete for store ID = ' . $this->_storeId);
        }
        $this->log('Synchronization Complete');
    }

    /**
     * @return bool
     */
    protected abstract function isEnabledForStore(): bool;

    /**
     * @return int
     */
    protected abstract function getOrdersDaysSkip(): int;

    /**
     * @return array
     */
    protected function getSyncDates()
    {
        $skipCount = $this->_skipCount;
        if ($skipCount > 0) {
            $dateFrom = date("Y-m-j 00:00:01", strtotime("-" . $skipCount . " day"));
            $dateTo = date("Y-m-j 23:59:59", strtotime("-" . $skipCount . " day"));
        } else {
            $hours = $this->helper->getSkipHours($this->_storeId);
            $dateTo = strtotime("-" . $hours . " hour");
            $dateFrom = strtotime("-25 hour");
            $dateTo = date("Y-m-j H:i:0", $dateTo);
            $dateFrom = date("Y-m-j H:i:0", $dateFrom);
        }
        return [
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo
        ];
    }

    /**
     * @param $transactionIds
     * @return void
     */
    protected function updateProcessedTransactions($transactionIds)
    {
        if ($this->helper->isTestMode($this->_storeId)) {
            return;
        }
        $connection = $this->resource->getConnection();
        $connection->update(
            $this->resource->getTable('sales_order'),
            [$this->getTransactionField() => 1],
            ['increment_id IN (?)' => $transactionIds]
        );
        $this->log('Transactions marked as processed == ' . implode(', ', $transactionIds));
    }

    /**
     * @param $start
     * @param $end
     * @return DataObject[]
     */
    protected function getOrders($start, $end)
    {
        $collection = $this->orderCollectionFactory->create();
        $collection->addFieldToFilter(
            'status',
            ['nin' => $this->helper->getOrderStatusExclude($this->_storeId)]
        )->addFieldToFilter(
            'store_id',
            ['eq' => $this->_storeId]
        )->addFieldToFilter(
            'created_at',
            ['gteq' => $start]
        )->addFieldToFilter(
            'created_at',
            ['lteq' => $end]
        )->addFieldToFilter(
            $this->getTransactionField(),
            [['neq' => 1], ['null' => true]]
        );
        $this->log('Orders select query == ' . (string)$collection->getSelect());
        return $collection->getItems();
    }

    /**
     * @param $order
     * @param $storeId
     * @return bool
     */
    protected function shouldProcessOrder($order, $storeId)
    {
        if (!$this->helper->isEnabled($storeId)) {
            return false;
        }
        return !($this->isAdminOrder($order) && !$this->helper->getSendPhoneOrderTransaction($storeId));
    }

    /**
     * @param $order
     * @return bool
     */
    protected function isAdminOrder($order)
    {
        return empty($order->getRemoteIp());
    }

    /**
     * @return string
     * @throws FileSystemException
     */
    protected function getSecurityJsonFilePath()
    {
        return $this->dir->getPath('var') . '/' . $this->helper->getSecurityKey($this->_storeId);
    }

    /**
     * @param $order
     * @param $storeId
     * @return void
     */
    protected function gaParseTSCookie($order, $storeId = null)
    {
        $tracking = $order->getData("sc_tracking_info");
        if (isset($tracking)) {
            $result = json_decode($tracking, true);
            if (!empty($result)) {
                if (isset($result['gaclid']) && strtolower($result['gaclid']) !== "") {
                    $this->_cid = $result['gaclid'];
                }
                if (isset($result['src']) && strtolower($result['src']) !== "(none)") {
                    $this->_cs = $result['src'];
                }
                if (isset($result['mdm']) && strtolower($result['mdm']) !== "(none)") {
                    $this->_cm = $result['mdm'];
                }
                if (isset($result['cmp']) && strtolower($result['cmp']) !== "(none)") {
                    $this->_cn = urldecode($result['cmp']);
                }
                if (isset($result['trm']) && strtolower($result['trm']) !== "(none)") {
                    $this->_ck = $result['trm'];
                }
                if (isset($result['cnt']) && strtolower($result['cnt']) !== "(none)") {
                    $this->_cc = $result['cnt'];
                }
            }
        }
        if ($this->isAdminOrder($order) && $this->helper->getSendPhoneOrderTransaction($storeId)) {
            $this->_cs = $this->helper->getAdminSource($storeId);
            $this->_cm = $this->helper->getAdminMedium($storeId);
        }
    }

    /**
     * @return string
     * @throws \Exception
     */
    protected function genUuid()
    {
        // Generates a UUID. A UUID is required for the measurement protocol.
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            // 16 bits for "time_mid"
            random_int(0, 0xffff),
            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            random_int(0, 0x0fff) | 0x4000,
            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            random_int(0, 0x3fff) | 0x8000,
            // 48 bits for "node"
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff)
        );
    }

    /**
     * @param $quoteItem
     * @return mixed
     */
    public function getQuoteCategoryName($quoteItem, $product)
    {
        if ($_catName = $quoteItem->getCategory()) {
            return $_catName;
        }
        $_product = $quoteItem->getProduct();
        if (!$_product) {
            $_product = $product;
        }

        return $this->getProductCategoryName($_product);
    }

    /**
     * @param $_product
     * @return mixed
     */
    public function getProductCategoryName($_product)
    {
        $_cats = $_product->getCategoryIds();
        $_categoryId = array_pop($_cats);
        try {
            $_cat = $this->categoryRepository->get($_categoryId);
            return $_cat->getName();
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * @param $quoteItem
     * @param $product
     * @return mixed
     */
    public function getQuoteBrand($quoteItem, $product, $storeId)
    {
        $_product = $quoteItem->getProduct();
        if (!$_product) {
            $_product = $product;
        }

        return $this->getBrand($_product, $storeId);
    }

    /**
     * returns brand value using product or text
     * @param $product
     * @return int
     */
    public function getBrand($product, $storeId)
    {
        if ($attribute = $this->helper->getBrandDropdown($storeId)) {
            $data = $product->getAttributeText($attribute);
            if (is_array($data)) {
                $data = end($data);
            }
            if (strlen($data) == 0) {
                $data = $product->getData($attribute);
            }
            return $data;
        }
        return $this->helper->getBrandText($storeId);
    }

    /**
     * @return string
     */
    protected abstract function getLogMessagePrefix(): string;

    /**
     * @param $message
     * @param false $error
     */
    protected function log($message, $error = false)
    {
        if ($this->helper->getDebugging($this->_storeId)) {
            $message = $this->getLogMessagePrefix() . $message;
            if ($error) {
                $this->logger->error($message);
            } else {
                $this->logger->info($message);
            }
        }
    }

    /**
     * @return string
     */
    protected function getTransactionField()
    {
        return self::TRANSACTION_FIELD;
    }
}
