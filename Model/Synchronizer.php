<?php
/**
 * Scommerce AnalyticsSync Synchronizer class
 *
 * @category   Scommerce
 * @author     Scommerce Mage <core@scommerce-mage.com>
 */

namespace Scommerce\AnalyticsSync\Model;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Scommerce\AnalyticsSync\Api\SyncLogRepositoryInterface;
use Scommerce\AnalyticsSync\Helper\Data;
use Scommerce\AnalyticsSync\Logger\Logger;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\UrlInterface;
use Magento\Framework\Escaper;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order as Resource;

/**
 * Class Synchronizer specific for UA
 */
class Synchronizer extends SyncBase
{
    const TRANSACTION_FIELD = 'sc_transaction_sent';

    const GOOGLE_API_URL = 'https://ssl.google-analytics.com/collect';

    const LOG_MESSAGE_PREFIX = 'UA: ';

    /** @var \Google_Client */
    protected $googleClient;

    /** @var \Google_Service_Analytics */
    protected $serviceAnalytics;

    /** @var LastSyncRun */
    protected $lastSyncRun;

    /** @var */
    protected $lastSyncRunDate;

    /** @var SyncLogRepositoryInterface */
    protected $syncLogRepository;

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
     * @param \Google_Client $googleClient
     * @param LastSyncRun $lastSyncRun
     * @param Curl $curl
     */
    public function __construct(
        Data $helper,
        StoreManagerInterface $storeManager,
        Logger $logger,
        DirectoryList $dir,
        Resource $resource,
        OrderCollectionFactory $orderCollectionFactory,
        ProductRepositoryInterface $productRepository,
        CategoryRepositoryInterface $categoryRepository,
        Escaper $escaper,
        SyncLogRepositoryInterface $syncLogRepository,
        \Google_Client $googleClient,
        LastSyncRun $lastSyncRun,
        Curl $curl
    ) {
        parent::__construct(
            $helper,
            $storeManager,
            $logger,
            $dir,
            $resource,
            $orderCollectionFactory,
            $productRepository,
            $categoryRepository,
            $escaper,
            $syncLogRepository,
            $curl
        );
        $this->lastSyncRun = $lastSyncRun;

        $this->googleClient = $googleClient;
        $this->serviceAnalytics = new \Google_Service_Analytics($googleClient);
    }

    /**
     * Call google API to sync data between GA and Magento
     */
    public function syncGoogleOrders()
    {
        try {
            $skipCount = $this->helper->getUAOrderDaysSkip($this->_storeId);
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
            $transactions = $this->getTransactionsFromGoogle($skipCount);

            //marking all the transactions to true which are found in google analytics
            if (!empty($transactions)) {
                $this->updateProcessedTransactions($transactions);
            }

            $orders = $this->getOrders($dateFrom, $dateTo);
            foreach ($orders as $order) {
                $storeId = $order->getStoreId();
                $increment_id = $order->getIncrementId();
                if ($this->checkTransactionInGoogle($increment_id)) {
                    $this->updateProcessedTransactions([$increment_id]);
                    continue;
                }

                if ($this->shouldProcessOrder($order, $storeId)) {
                    $this->log("Sync started for order == " . $increment_id . " at " . date("Y-m-j h:i:s"));
                    try {
                        $this->buildAndSendData($order, $storeId);
                    } catch (\Exception $e) {
                        $this->log("Building and sending data failed for order == " . $increment_id);
                        continue;
                    }
                } else {
                    $this->log("Order processing skipped == " . $increment_id);
                }
                $this->log("Sync done for order == " . $increment_id . " at " . date("Y-m-j h:i:s"));
            }
        } catch (\Exception $e) {
            $this->log($e->getMessage(), true);
        }
    }

    /**
     * @param $order
     * @param $storeId
     * @return bool
     * @throws \Exception
     */

    /**
     * Returns IDs of transactions found in GA
     *
     * @param $skipCount
     * @return array
     * @throws \Google_Exception|\Magento\Framework\Exception\FileSystemException
     */
    protected function getTransactionsFromGoogle($skipCount)
    {
        $this->log('Get transactions from GA started');

        // Create and configure a new client object.
        $filePath = $this->getSecurityJsonFilePath();
        $profile = $this->helper->getGoogleViewId($this->_storeId);

        $client = new \Google_Client();
        $client->setApplicationName($this->helper->getApplicationName($this->_storeId));
        $client->setAuthConfig($filePath);
        $client->addScope(\Google_Service_Analytics::ANALYTICS_READONLY);

        $analytics = new \Google_Service_Analytics($client);
        $results = $this->getTransactions($analytics, $profile, $skipCount);

        $result = $this->getTransactionIds($results);

        $this->log('Transactions from GA: ' . implode(', ', $result));
        return $result;
    }

    protected function checkTransactionInGoogle($id)
    {
        $this->log('Double check transaction ID: ' . $id);
        // Create and configure a new client object.
        $filePath = $this->getSecurityJsonFilePath();
        $profile = $this->helper->getGoogleViewId($this->_storeId);

        $client = new \Google_Client();
        $client->setApplicationName($this->helper->getApplicationName($this->_storeId));
        $client->setAuthConfig($filePath);
        $client->addScope(\Google_Service_Analytics::ANALYTICS_READONLY);

        $analytics = new \Google_Service_Analytics($client);
        $results = $analytics->data_ga->get(
            'ga:' . $profile,
            '60daysAgo',
            'today',
            'ga:totalValue',
            ['dimensions' => 'ga:transactionId', 'sort' => '-ga:transactionId', 'filters' => 'ga:transactionId==' . $id]
        );

        $result = $this->getTransactionIds($results);

        if (count($result) > 0) {
            $this->log('Transaction exists ID: ' . implode(', ', $result));
            return true;
        }
        $this->log('Transaction missing ID: ' . implode(', ', $result));
        return false;
    }

    /**
     * Calls the Core Reporting API and queries for the number of transactions
     * for yesterday
     *
     * @param \Google_Service_Analytics $analytics
     * @param $profileId
     * @param $skipCount
     * @return \Google_Service_Analytics_GaData
     */
    protected function getTransactions(&$analytics, $profileId, $skipCount)
    {
        if ($skipCount > 0) {
            return $analytics->data_ga->get(
                'ga:' . $profileId,
                $skipCount . 'daysAgo',
                'yesterday',
                'ga:totalValue',
                ['dimensions' => 'ga:transactionId', 'sort' => '-ga:transactionId']
            );
        }
        return $analytics->data_ga->get(
            'ga:' . $profileId,
            '0daysAgo',
            'today',
            'ga:totalValue',
            ['dimensions' => 'ga:transactionId', 'sort' => '-ga:transactionId']
        );
    }

    /**
     * Get the transaction ids based on the results from Google Analytics
     * for yesterday
     *
     * @return array
     */
    protected function getTransactionIds(&$results)
    {
        $rows = $results->getRows();
        $transactions = [];
        if (!empty($rows[0])) {
            if (count($rows) > 0) {
                $rowsCount = count($rows);
                for ($i = 0; $i < $rowsCount; $i++) {
                    array_push($transactions, $rows[$i][0]);
                }
            }
        }
        return $transactions;
    }

    /**
     * @param $order
     * @param $storeId
     * @param null $invoice
     * @throws NoSuchEntityException
     */
    protected function buildAndSendData($order, $storeId, $invoice = null)
    {
        $orderId = $order->getIncrementId();
        $this->log('Building data for order == ' . $orderId);

        $this->_cid = '';
        $this->_cs = '';
        $this->_cm = '';
        $this->_cn = '';
        $this->_ck = '';
        $this->_cc = '';

        $this->gaParseTSCookie($order, $storeId);
        if (!$this->_cid) {
            $this->_cid = $this->genUuid();
        }
        $cid = $this->_cid;

        if ($this->helper->getSendBaseData($storeId)) {
            $orderCurrency = $order->getBaseCurrencyCode();
            $orderGrandTotal = $order->getBaseGrandTotal();
            $orderShippingTotal = $order->getBaseShippingAmount();
            $orderTax = $order->getBaseTaxAmount();
        } else {
            $orderCurrency = $order->getOrderCurrencyCode();
            $orderGrandTotal = $order->getGrandTotal();
            $orderShippingTotal = $order->getShippingAmount();
            $orderTax = $order->getTaxAmount();
        }

        $this->_domainHost = $this->storeManager->getStore($storeId)->getBaseUrl(UrlInterface::URL_TYPE_LINK);
        if (isset($this->_domainHost)) {
            $replace = ['http://','https://','www.'];
            $this->_domainHost = rtrim(str_replace($replace, '', $this->_domainHost), '/');
        }

        $landingUrl = $this->helper->getDefaultLandingPage($storeId);
        $tracking = $order->getData("sc_tracking_info");
        if ($tracking) {
            $result = json_decode($tracking, true);
            if (isset($result["sc_lurl"]) && $result["sc_lurl"]) {
                $landingUrl = $result["sc_lurl"];
            }
        }

        /* Sending Transactional Data to GA */
        $data = $this->addTransactionalPageView(
            $this->helper->getAnalyticsAccountId($storeId),
            $cid,
            $landingUrl,
            'Order Confirmation',
            $orderId,
            $orderCurrency,
            $order->getAffiliation(),
            $orderGrandTotal,
            $orderShippingTotal,
            $orderTax,
            $order->getCouponCode(),
            'purchase'
        );

        //adding traffic source data
        $data = $this->addTrafficSourceData($data);

        if ($invoice === null) {
            $data = $this->addProductData($order, $data, true, $storeId);
        } else {
            $data = $this->addProductData($invoice, $data);
        }

        $result = $this->sendDataToGoogle($data);
        if ($result) {
            $this->updateProcessedTransactions([$orderId]);
            $this->log('Data sent for order == ' . json_encode($data));
        }
    }

    /**
     * @param $data
     */
    protected function sendDataToGoogle($data)
    {
        if ($data) {
            $log = [
                'increment_id' => $data['ti'],
                'tracking_data' => json_encode($data),
                'store_id' => $this->_storeId,
            ];

            if (!$this->helper->isTestMode()) {
                try {
                    // This is the URL to which we'll be sending the post request.
                    $url = self::GOOGLE_API_URL;
                    // The body of the post must include exactly 1 URI encoded payload and must be no longer than 8192
                    // bytes. See http_build_query.
                    $content = http_build_query($data);
                    // The payload must be UTF-8 encoded.
                    $content = utf8_encode($content);

                    $this->curl->addHeader('Content-Type', 'application/x-www-form-urlencoded');
                    $this->curl->post($url, $content);
                    $result = $this->curl->getBody();

                    $log['status'] = 'success';
                } catch (\Exception $e) {
                    $this->log('GA API Curl URL == ' . $url);
                    $this->log('GA API Curl Error == ' . $e->getMessage());
                    $log['status'] = 'error';
                    $log['error_message'] = $e->getMessage();
                    $result = false;
                }
            } else {
                $result = true;
                $log['status'] = 'test mode';
            }
            $this->syncLogRepository->createFromArray($log);
            if ($result === false) {
                return false;
            } else {
                return true;
            }
        }
    }

    /**
     * Adding product data of the current cart to send to GA as part of measurement protocol call
     *
     * @param $cart
     * @param $data
     * @param bool $order
     * @param null $storeId
     * @return array
     * @throws NoSuchEntityException
     */
    protected function addProductData($cart, $data, $order = false, $storeId = null)
    {
        $intCtr = 1;
        foreach ($cart->getAllItems() as $item) {
            if ($item->getBasePrice() <= 0) {
                continue;
            }
            try {
                $_product = $this->productRepository->getById($item->getProductId());
                $category = $this->getQuoteCategoryName($item, $_product);
                $brand = $this->getQuoteBrand($item, $_product, $storeId);
            } catch (\Exception $e) {
                $category = '';
                $brand = '';
            }
            $product = [
                'pr' . $intCtr . 'nm'   => $this->escaper->escapeJsQuote($item->getName(), '"'), // Item name. Required.
                'pr' . $intCtr . 'pr'   => (float) ($this->helper->getSendBaseData($storeId) == true ?
                    $item->getBasePrice() : $item->getPrice()), // Item price.
                'pr' . $intCtr . 'qt'   => (float) ($order == true ? $item->getQtyOrdered() : $item->getQty()), // Item quantity.
                'pr' . $intCtr . 'ca'   => $this->escaper->escapeJsQuote($category), // Item category.
                'pr' . $intCtr . 'br'   => $this->escaper->escapeJsQuote($brand), // Item brand.
                'pr' . $intCtr . 'id'   => $item->getSku(), // Item code / SKU.
                'pr' . $intCtr . 'ps'   => $intCtr // Item Position.
            ];
            $data = array_merge($data, $product);
            $intCtr++;
        }
        return $data;
    }

    /**
     * Adding transactional page view data to send to GA as part of measurement protocol call
     *
     * @param $accountId
     * @param $cid
     * @param $dp
     * @param $dt
     * @param $orderId
     * @param $orderCurrency
     * @param $orderAffiliation
     * @param $orderGrandTotal
     * @param $orderShippingTotal
     * @param $orderTax
     * @param $orderCouponCode
     * @param $pa
     * @return array
     */
    protected function addTransactionalPageView(
        $accountId,
        $cid,
        $dp,
        $dt,
        $orderId,
        $orderCurrency,
        $orderAffiliation,
        $orderGrandTotal,
        $orderShippingTotal,
        $orderTax,
        $orderCouponCode,
        $pa
    ) {
        $domainHost = $this->_domainHost;

        $data = [
            'v'     => 1, // The version of the measurement protocol
            'tid'   => $accountId, // Google Analytics account ID (UA-98765432-1)
            'cid'   => $cid, // The UUID
            't'     => 'pageview', // Hit Type
            'dh'    => $domainHost, // Domain Hostname
            'dp'    => $dp, // Page
            'dt'    => $dt,// Page Title
            'ti'    => $orderId,       // transaction ID. Required.
            'cu'    => $orderCurrency,  // Transaction currency code.
            'ta'    => $orderAffiliation,  // Transaction affiliation.
            'tr'    => (float)$orderGrandTotal,        // Transaction revenue.
            'ts'    => (float)$orderShippingTotal,        // Transaction shipping.
            'tt'    => (float)$orderTax,       // Transaction tax.
            'tcc'   => $orderCouponCode, // Transaction coupon code
            'pa'    => $pa, // Product Action
        ];
        return $data;
    }

    /**
     * Adding traffic source data to send to GA as part of measurement protocol call
     *
     * @return array
     */
    protected function addTrafficSourceData($data)
    {
        $tsdata = [
            'cn'    => $this->_cn, //Campaign Name
            'cs'    => $this->_cs, //Campaign Source
            'cm'    => $this->_cm, //Campaign Medium
            'ck'    => $this->_ck, //Campaign Keyword
            'cc'    => $this->_cc, //Content
            'gclid' => $this->_gclid //gclid
        ];
        $data = array_merge($data, $tsdata);
        return $data;
    }

    /**
     * Sending event data to GA for each step
     *
     * @param $accountId
     * @param $cid
     * @param $el
     * @return void
     */
    protected function sendEvent($accountId, $cid, $el)
    {
        if (strlen($el)) {
            $data = [
                'v'     => 1, // The version of the measurement protocol
                'tid'   => $accountId, // Google Analytics account ID (UA-98765432-1)
                'cid'   => $cid, // The UUID
                't'     => 'event', // Hit Type
                'ec'    => 'UX', // Event Category
                'ea'    => 'click', // Event Action
                'el'    => $el, // Event Label
                'ni'    => 1, // Non-Interaction Hit
            ];
            $this->sendDataToGoogle($data);
        }
    }

    /**
     * @return bool
     */
    protected function isEnabledForStore(): bool
    {
        $isEnabled = $this->helper->isEnabled($this->_storeId) && $this->helper->isUaSyncEnabled($this->_storeId);
        if (!$isEnabled) {
            $this->log('Synchronization for store ID ' . $this->_storeId . ' disabled');
            return false;
        }
        $hasProps = $this->helper->getGoogleViewId($this->_storeId)
            && $this->helper->getAnalyticsAccountId($this->_storeId);
        if (!$hasProps) {
            $this->log('Synchronization for store ID ' . $this->_storeId
                . ' skipped: Google View Id or Analytics Account ID not set');
            return false;
        }
        return true;
    }

    /**
     * @return int
     */
    protected function getOrdersDaysSkip(): int
    {
        return $this->helper->getUAOrderDaysSkip($this->_storeId);
    }

    /**
     * @return string
     */
    protected function getLogMessagePrefix(): string
    {
        return self::LOG_MESSAGE_PREFIX;
    }
}
