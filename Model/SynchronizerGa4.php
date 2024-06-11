<?php
/**
 * Scommerce AnalyticsSync GA4 Synchronizer class
 *
 * @category   Scommerce
 * @author     Scommerce Mage <core@scommerce-mage.com>
 */

namespace Scommerce\AnalyticsSync\Model;

use Google\Analytics\Data\V1beta\BetaAnalyticsDataClient;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Dimension;
use Google\Analytics\Data\V1beta\Filter;
use Google\Analytics\Data\V1beta\FilterExpression;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;

/**
 * Specific synchronizer class for GA4
 */
class SynchronizerGa4 extends SyncBase
{
    const GA4_URL = 'https://www.google-analytics.com/mp/collect?';
    const GA4_DEBUG_URL = 'https://www.google-analytics.com/debug/mp/collect?';

    const LOG_MESSAGE_PREFIX = 'GA4: ';
    const TRANSACTION_FIELD = 'sc_transaction_sent_ga4';

    /**
     * @return void
     */
    protected function syncGoogleOrders()
    {
        try {
            $skipCount = $this->helper->getGa4SkipDays($this->_storeId);
            $transactions = $this->getTransactionsFromGoogle($skipCount);

            //marking all the transactions to true which are found in google analytics
            if (!empty($transactions)) {
                $this->updateProcessedTransactions($transactions);
            }

            $dates = $this->getSyncDates();
            $orders = $this->getOrders($dates['dateFrom'], $dates['dateTo']);
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
                        $this->log($e->getMessage());
                        continue;
                    }
                } else {
                    $this->log("Order processing skipped == " . $increment_id);
                }
                $this->log("Sync done for order == " . $increment_id . " at " . date("Y-m-j h:i:s"));
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    /**
     * @param $skipCount
     * @return array
     */
    protected function getTransactionsFromGoogle($skipCount)
    {
        $range = $this->getGoogleRange($skipCount);
        $dateTo = $range['dateTo'];
        $dateFrom = $range['dateFrom'];
        $result = $this->getGoogleTransactions($dateFrom, $dateTo);
        return $this->parseResult($result);
    }

    /**
     * @param $skipCount
     * @param $extended
     * @return array
     */
    protected function getGoogleRange($skipCount, $extended = false)
    {
        if ($extended) {
            $dateTo = date("Y-m-j", strtotime("-" . 0 . " day"));
            $dateFrom = date("Y-m-j", strtotime("-" . ($skipCount + 2) . " day"));
        } else {
            $dateTo = date("Y-m-j", strtotime("-" . $skipCount . " day"));
            $dateFrom = date("Y-m-j", strtotime("-" . ($skipCount + 1) . " day"));
        }
        return [
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo
        ];
    }

    /**
     * @param $dateFrom
     * @param $dateTo
     * @return \Google\Analytics\Data\V1beta\RunReportResponse
     * @throws \Google\ApiCore\ApiException
     * @throws \Google\ApiCore\ValidationException
     */
    protected function getGoogleTransactions($dateFrom, $dateTo)
    {
        $filePath = $this->getSecurityJsonFilePath();
        $client = new BetaAnalyticsDataClient(['credentials' => $filePath]);
        $response = $client->runReport([
            'property' => 'properties/' . $this->helper->getPropertyId($this->_storeId),
            'dateRanges' => [new DateRange([
                'start_date' => $dateFrom,
                'end_date' => $dateTo
            ])],
            'dimensions' => [
                new Dimension(['name' => 'transactionId'])
            ]
        ]);
        return $response;
    }

    /**
     * @param $transactionId
     * @return bool
     * @throws \Google\ApiCore\ApiException
     * @throws \Google\ApiCore\ValidationException
     */
    protected function checkTransactionInGoogle($transactionId)
    {
        $range = $this->getGoogleRange($this->_skipCount, true);
        $filePath = $this->getSecurityJsonFilePath();
        $client = new BetaAnalyticsDataClient(['credentials' => $filePath]);
        $result = $client->runReport([
            'property' => 'properties/' . $this->helper->getPropertyId($this->_storeId),
            'dimensions' => [
                new Dimension(['name' => 'transactionId'])
            ],
            'dateRanges' => [new DateRange([
                'start_date' => $range['dateFrom'],
                'end_date' => $range['dateTo']
            ])],
            'dimensionFilter' => new FilterExpression([
                'filter' => new Filter([
                    'field_name' => 'transactionId',
                    'string_filter' => new Filter\StringFilter([
                        'match_type' => Filter\StringFilter\MatchType::EXACT,
                        'value' => "$transactionId"
                    ])
                ])
            ])
        ]);
        $ids = $this->parseResult($result);
        if (count($ids)) {
            return true;
        }
        return false;
    }

    /**
     * @param $serviceResult
     * @return array
     */
    private function parseResult($serviceResult)
    {
        $this->log('service result');
        $result = [];
        foreach ($serviceResult->getRows() as $row) {
            $val = $row->getDimensionValues(0)[0]->getValue();
            if ($val == '(not set)') {
                continue;
            }
            $this->log($val);
            $result[] = $val;
        }
        return $result;
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

        $this->gaParseTSCookie($order, $storeId);
        if (!$this->_cid) {
            $this->_cid = $this->genUuid();
        }
        $cid = $this->_cid;

        $data = [
            'client_id' => $cid,
            'events' => []
        ];
        $data['events'][] = [
            'name' => 'purchase',
            'params' => []
        ];

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
            $replace = ['http://', 'https://', 'www.'];
            $this->_domainHost = rtrim(str_replace($replace, '', $this->_domainHost), '/');
        }
        $data['events'][0]['params'] = [
            'currency' => $orderCurrency,
            'transaction_id' => $orderId,
            'value' => (float)$orderGrandTotal,
            'shipping' => (float)$orderShippingTotal,
            'tax' => (float)$orderTax
        ];
        if ($order->getCouponCode()) {
            $data['events'][0]['params']['coupon'] = $order->getCouponCode();
        }
        if ($order->getAffiliation()) {
            $data['events'][0]['params']['affiliation'] = $order->getAffiliation();
        }

        if ($invoice === null) {
            $data['events'][0]['params']['items'] = $this->addProductData($order, $orderCurrency, true, $storeId);
        } else {
            $data['events'][0]['params']['items'] = $this->addProductData($invoice, $orderCurrency);
        }

        $result = $this->sendDataToGoogle($data);
        if ($result) {
            $this->updateProcessedTransactions([$orderId]);
        }
    }

    /**
     * @param $cart
     * @param $orderCurrency
     * @param $order
     * @param $storeId
     * @return array
     */
    protected function addProductData($cart, $orderCurrency, $order = false, $storeId = null)
    {
        $intCtr = 1;
        $result = [];
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
            $result[] = [
                'item_id'   => $item->getSku(), // Item code / SKU.
                'item_name' => $this->escaper->escapeJsQuote($item->getName(), '"'), // Item name. Required.
                'price'     => (float) ($this->helper->getSendBaseData($storeId) == true ? $item->getBasePrice() : $item->getPrice()), // Item price.
                'quantity'  => (float) ($order == true ? $item->getQtyOrdered() : $item->getQty()), // Item quantity.
                'item_category' => $this->escaper->escapeJsQuote($category), // Item category.
                'item_brand'    => $this->escaper->escapeJsQuote($brand), // Item brand.
                'currency'  => $orderCurrency,
                'index'     => $intCtr // Item Position.
            ];
            $intCtr++;
        }
        return $result;
    }

    /**
     * @param $data
     * @return bool|void
     */
    protected function sendDataToGoogle($data)
    {
        if ($data) {
            $log = [
                'increment_id' => $data['events'][0]['params']['transaction_id'],
                'tracking_data' => json_encode($data),
                'store_id' => $this->_storeId,
            ];
            $measurementId = $this->helper->getMeasurementId($this->_storeId);
            $apiSecret = $this->helper->getApiSecret($this->_storeId);

            if (!$this->helper->isTestMode()) {
                try {
                    $url = self::GA4_URL;
                    $url .= "measurement_id=$measurementId&api_secret=$apiSecret";
                    $content = json_encode($data);

                    $this->curl->addHeader('Content-Type', 'application/json');
                    $this->curl->addHeader('Content-Length', strlen($content));
                    $this->curl->post($url, $content);
                    $result = $this->curl->getBody();

                    $this->log($content);
                    $this->log('result=' . json_encode($result));

                    $log['status'] = 'success';
                } catch (\Exception $e) {
                    $this->log('GA API Curl URL == ' . $url);
                    $this->log('GA API Curl Error == ' . $e->getMessage());
                    $log['status'] = 'error';
                    $log['error_message'] = $e->getMessage();
                    $result = false;
                    $this->logger->error($e->getMessage());
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
     * @return bool
     */
    protected function isEnabledForStore(): bool
    {
        $isEnabled = $this->helper->isEnabled($this->_storeId) && $this->helper->useGa4($this->_storeId);
        if (!$isEnabled) {
            $this->log('Synchronization for store ID ' . $this->_storeId . ' disabled');
            return false;
        }
        $hasProps = $this->helper->getMeasurementId($this->_storeId)
            && $this->helper->getPropertyId($this->_storeId)
            && $this->helper->getApiSecret($this->_storeId);
        if (!$hasProps) {
            $this->log('Synchronization for store ID ' . $this->_storeId
                . ' skipped: Measurement Id, Property ID or Api Secret not set');
            return false;
        }
        return true;
    }

    /**
     * @return int
     */
    protected function getOrdersDaysSkip(): int
    {
        return $this->helper->getGa4SkipDays($this->_storeId);
    }

    /**
     * @return string
     */
    protected function getLogMessagePrefix(): string
    {
        return self::LOG_MESSAGE_PREFIX;
    }

    /**
     * @return string
     */
    protected function getTransactionField()
    {
        return self::TRANSACTION_FIELD;
    }
}
