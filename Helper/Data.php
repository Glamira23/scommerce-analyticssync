<?php
/**
 * Scommerce AnalyticsSync helper class for common functions and retrieving configuration values
 *
 * @category   Scommerce
 * @author     Scommerce Mage <core@scommerce-mage.com>
 */

namespace Scommerce\AnalyticsSync\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Scommerce\Core\Helper\Data as CoreHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\ResourceModel\Website\CollectionFactory;
use Magento\Eav\Model\ResourceModel\Entity\Attribute;
use Magento\Quote\Model\ResourceModel\Quote\Item\CollectionFactory as QuoteItem;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Inventory\Model\ResourceModel\Source\CollectionFactory as SourceCollection;
use Magento\Store\Model\StoreFactory;

/**
 * Class Data helper class
 */
class Data extends AbstractHelper
{
    const XML_PATH_ENABLED              = 'scommerce_analytics_sync/general/enabled';
    const XML_PATH_UA_SYNC_ENABLED      = 'scommerce_analytics_sync/general/enable_ua_sync';
    const XML_PATH_LICENSE_KEY          = 'scommerce_analytics_sync/general/license_key';
    const XML_PATH_APPLICATION_NAME     = 'scommerce_analytics_sync/general/application_name';
    const XML_PATH_SECURITY_KEY         = 'scommerce_analytics_sync/general/security_key';
    const XML_PATH_GOOGLE_VIEW_ID       = 'scommerce_analytics_sync/general/google_view_id';
    const XML_PATH_ORDER_STATUS_EXCLUDE = 'scommerce_analytics_sync/general/order_status_exclude';
    const XML_PATH_PAYMENT_METHODS_EXCLUDE      = 'scommerce_analytics_sync/general/payment_methods';
    const XML_PATH_ANALYTICS_ACCOUNT_ID = 'scommerce_analytics_sync/general/analytics_account_id';
    const XML_PATH_SEND_BASE_DATA       = 'scommerce_analytics_sync/general/send_base_data';
    const XML_PATH_SEND_PHONE_ORDER_TRANSACTION = 'scommerce_analytics_sync/general/send_phone_order_transaction';
    const XML_PATH_ADMIN_SOURCE         = 'scommerce_analytics_sync/general/admin_source';
    const XML_PATH_ADMIN_MEDIUM         = 'scommerce_analytics_sync/general/admin_medium';
    const XML_PATH_BRAND_DROPDOWN       = 'scommerce_analytics_sync/general/brand_dropdown';
    const XML_PATH_BRAND_TEXT           = 'scommerce_analytics_sync/general/brand_text';
    const XML_PATH_ORDERS_DAYS_SKIP     = 'scommerce_analytics_sync/general/orders_days_skip';
    const XML_PATH_DEBUGGING            = 'scommerce_analytics_sync/general/debugging';
    const XML_PATH_DEFAULT_LANDING_PAGE = 'scommerce_analytics_sync/general/default_landing_page';
    const XML_PATH_SKIP_HOURS           = 'scommerce_analytics_sync/general/skip_hours';
    const XML_PATH_TEST_MODE            = 'scommerce_analytics_sync/general/test_mode';

    const XML_PATH_ENABLE_GA4           = 'scommerce_analytics_sync/ga4/enable';
    const XML_PATH_MEASUREMENT_ID       = 'scommerce_analytics_sync/ga4/measurement_id';
    const XML_PATH_API_SECRET           = 'scommerce_analytics_sync/ga4/api_secret';
    const XML_PATH_PROPERTY_ID          = 'scommerce_analytics_sync/ga4/property_id';
    const XML_PATH_GA4_SKIP_DAYS        = 'scommerce_analytics_sync/ga4/skip_days';
    const XML_PATH_SEND_ON_INVOICE_CREATION = 'scommerce_analytics_sync/ga4/send_on_invoice_creation';


    const XML_PATH_LAST_CRON_RUN        = 'scommerce_analytics_sync/general/last_cron_run';

    /**
     * @var CoreHelper
     */
    protected $_coreHelper;

    protected $dateTime;

    /**
     * __construct
     *
     * @param Context $context
     * @param CoreHelper $coreHelper
     */
    public function __construct(
        Context $context,
        CoreHelper $coreHelper,
        DateTime $dateTime
    ) {
        $this->_coreHelper = $coreHelper;
        $this->dateTime = $dateTime;
        parent::__construct($context);
    }

    /**
     * Returns whether module is enabled or not
     *
     * @param int $storeId
     * @return boolean
     */
    public function isEnabled($storeId = null)
    {
        $enabled = $this->isSetFlag(self::XML_PATH_ENABLED, ScopeInterface::SCOPE_STORE, $storeId);
        return $this->isCliMode() ? $enabled : $enabled && $this->isLicenseValid();
    }

    /**
     * Returns license key administration configuration option
     *
     * @param int $storeId
     * @return string
     */
    public function getLicenseKey($storeId = null)
    {
        return $this->getValue(self::XML_PATH_LICENSE_KEY, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param null $storeId
     * @return mixed
     */
    public function getApplicationName($storeId = null)
    {
        return $this->getValue(self::XML_PATH_APPLICATION_NAME, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param null $storeId
     * @return mixed
     */
    public function getSecurityKey($storeId = null)
    {
        return $this->getValue(self::XML_PATH_SECURITY_KEY, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param null $storeId
     * @return mixed
     */
    public function getGoogleViewId($storeId = null)
    {
        $viewId = $this->getValue(self::XML_PATH_GOOGLE_VIEW_ID, ScopeInterface::SCOPE_STORE, $storeId);
        return trim($viewId);
    }

    /**
     * @param null $storeId
     * @return false|string[]
     */
    public function getOrderStatusExclude($storeId = null)
    {
        $rawValue = $this->getValue(self::XML_PATH_ORDER_STATUS_EXCLUDE, ScopeInterface::SCOPE_STORE, $storeId);
        return explode(',', $rawValue);
    }

    public function getExcludedPaymentMethods($storeId = null)
    {
        $rawValue = $this->getValue(self::XML_PATH_PAYMENT_METHODS_EXCLUDE, ScopeInterface::SCOPE_STORE, $storeId) ?? '';
        return explode(',', $rawValue);
    }

    /**
     * @param null $storeId
     * @return mixed
     */
    public function getAnalyticsAccountId($storeId = null)
    {
        $accountId = $this->getValue(self::XML_PATH_ANALYTICS_ACCOUNT_ID, ScopeInterface::SCOPE_STORE, $storeId);
        return trim($accountId);
    }

    /**
     * @param null $storeId
     * @return mixed
     */
    public function getSendBaseData($storeId = null)
    {
        return $this->getValue(self::XML_PATH_SEND_BASE_DATA, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param null $storeId
     * @return mixed
     */
    public function getSendPhoneOrderTransaction($storeId = null)
    {
        return $this->getValue(self::XML_PATH_SEND_PHONE_ORDER_TRANSACTION, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param null $storeId
     * @return mixed
     */
    public function getAdminSource($storeId = null)
    {
        return $this->getValue(self::XML_PATH_ADMIN_SOURCE, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param null $storeId
     * @return mixed
     */
    public function getAdminMedium($storeId = null)
    {
        return $this->getValue(self::XML_PATH_ADMIN_MEDIUM, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param null $storeId
     * @return mixed
     */
    public function getBrandDropdown($storeId = null)
    {
        return $this->getValue(self::XML_PATH_BRAND_DROPDOWN, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param null $storeId
     * @return mixed
     */
    public function getBrandText($storeId = null)
    {
        return $this->getValue(self::XML_PATH_BRAND_TEXT, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param null $storeId
     * @return mixed
     */
    public function getDefaultLandingPage($storeId = null)
    {
        $val = $this->getValue(self::XML_PATH_DEFAULT_LANDING_PAGE, ScopeInterface::SCOPE_STORE, $storeId);
        if (!$val) {
            $val = '/';
        }
        return $val;
    }

    /**
     * @param null $storeId
     * @return mixed
     */
    public function getSkipHours($storeId = null)
    {
        $value = $this->getValue(self::XML_PATH_SKIP_HOURS, ScopeInterface::SCOPE_STORE, $storeId);
        if (!$value) {
            $value = 1;
        }
        return $value;
    }

    /**
     * @param null $storeId
     * @return mixed
     */
    public function getOrdersDaysSkip($storeId = null)
    {
        return $this->useGa4($storeId) ? $this->getGa4SkipDays($storeId) : $this->getUAOrderDaysSkip($storeId);
    }

    /**
     * @param $storeId
     * @return int
     */
    public function getUAOrderDaysSkip($storeId = null)
    {
        $val = (int)$this->getValue(self::XML_PATH_ORDERS_DAYS_SKIP, ScopeInterface::SCOPE_STORE, $storeId);
        if (!$val) {
            $val = 0;
        }
        if ($val < 0) {
            $val = 0;
        }
        return $val;
    }

    /**
     * @param null $storeId
     * @return mixed
     */
    public function getGa4SkipDays($storeId = null)
    {
        $val = (int)$this->getValue(self::XML_PATH_GA4_SKIP_DAYS, ScopeInterface::SCOPE_STORE, $storeId);
        if (!$val) {
            $val = 2;
        }
        if ($val < 1) {
            $val = 1;
        }
        return $val;
    }

    /**
     * @param null $storeId
     * @return mixed
     */
    public function getDebugging($storeId = null)
    {
        return $this->getValue(self::XML_PATH_DEBUGGING, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param null $storeId
     * @return mixed
     */
    public function isTestMode($storeId = null)
    {
        return $this->getValue(self::XML_PATH_TEST_MODE, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param $storeId
     * @return bool
     */
    public function isUaSyncEnabled($storeId = null)
    {
        return $this->isSetFlag(self::XML_PATH_UA_SYNC_ENABLED, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param $storeId
     * @return mixed
     */
    public function useGa4($storeId = null)
    {
        return $this->getValue(self::XML_PATH_ENABLE_GA4, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param $storeId
     * @return mixed
     */
    public function getMeasurementId($storeId = null)
    {
        return $this->getValue(self::XML_PATH_MEASUREMENT_ID, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param $storeId
     * @return mixed
     */
    public function getApiSecret($storeId = null)
    {
        return $this->getValue(self::XML_PATH_API_SECRET, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param $storeId
     * @return mixed
     */
    public function getPropertyId($storeId = null)
    {
        return $this->getValue(self::XML_PATH_PROPERTY_ID, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @return mixed
     */
    public function getLastCronRun()
    {
        return $this->getValue(self::XML_PATH_LAST_CRON_RUN);
    }

    /**
     * @param $storeId
     * @return mixed
     */
    public function sendOnInvoiceCreation($storeId = null)
    {
        return $this->getValue(self::XML_PATH_SEND_ON_INVOICE_CREATION, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param $path
     * @param null $storeId
     * @return mixed
     */
    public function getValueByPath($path, $storeId = null)
    {
        return $this->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Check if running in cli mode
     *
     * @return bool
     */
    protected function isCliMode()
    {
        return php_sapi_name() === 'cli';
    }

    /**
     * Helper method for retrieve config value by path and scope
     *
     * @param string $path The path through the tree of configuration values, e.g., 'general/store_information/name'
     * @param string $scopeType The scope to use to determine config value, e.g., 'store' or 'default'
     * @param null|string $scopeCode
     * @return mixed
     */
    protected function getValue($path, $scopeType = ScopeInterface::SCOPE_STORE, $scopeCode = null)
    {
        return $this->scopeConfig->getValue($path, $scopeType, $scopeCode);
    }

    /**
     * Helper method for retrieve config flag by path and scope
     *
     * @param string $path The path through the tree of configuration values, e.g., 'general/store_information/name'
     * @param string $scopeType The scope to use to determine config value, e.g., 'store' or 'default'
     * @param null|string $scopeCode
     * @return bool
     */
    protected function isSetFlag($path, $scopeType = ScopeInterface::SCOPE_STORE, $scopeCode = null)
    {
        return $this->scopeConfig->isSetFlag($path, $scopeType, $scopeCode);
    }

    /**
     * Returns whether license key is valid or not
     *
     * @return bool
     */
    public function isLicenseValid()
    {
        $sku = strtolower(str_replace('\\Helper\\Data', '', str_replace('Scommerce\\', '', get_class($this))));
        return $this->_coreHelper->isLicenseValid($this->getLicenseKey(), $sku);
    }

    public function getTimestamp($date)
    {
        return $this->dateTime->gmtTimestamp($date);
    }
}
