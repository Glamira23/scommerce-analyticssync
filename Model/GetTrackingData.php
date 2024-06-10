<?php
/**
 * Scommerce AnalyticsSync observer class for checkout_cart_product_add_after event
 *
 * @category   Scommerce
 * @author     Scommerce Mage <core@scommerce-mage.com>
 */

namespace Scommerce\AnalyticsSync\Model;

use Magento\Framework\Stdlib\CookieManagerInterface;
use Scommerce\AnalyticsSync\Helper\Data;

/**
 * Class GetTrackingData returns collected tracking data
 */
class GetTrackingData
{
    /**
     * @var CookieManagerInterface
     */
    private $cookieManager;

    private $helper;

    /**
     * GetTrackingData constructor.
     * @param CookieManagerInterface $cookieManager
     */
    public function __construct(
        CookieManagerInterface $cookieManager,
        Data $helper
    ) {
        $this->cookieManager = $cookieManager;
        $this->helper = $helper;
    }

    /**
     * @return array|null
     */
    public function execute()
    {
        $resultData = [];

        $utm = $this->cookieManager->getCookie('sbjs_current');
        $udata = $this->cookieManager->getCookie('sbjs_udata');
        $gagtmblock = $this->cookieManager->getCookie('gagtmblock');
        $clientId = $this->cookieManager->getCookie('scgacid');
        $landingUrl = $this->cookieManager->getCookie('sc_lurl');
        $containerCookie = $this->getContainerCookie();

        $result = [];
        if ($utm) {
            $result = explode("|||", $utm);
        }
        if ($udata) {
            $data = explode("|||", $udata);
            $result = array_merge($result, $data);
        }
        if ($gagtmblock) {
            $data = explode("|||", $gagtmblock);
            $result = array_merge($result, $data);
        }
        if ($clientId) {
            $result[] = 'gaclid=' . $clientId;
        }
        if (count($result)) {
            foreach ($result as $item) {
                $itemData = explode("=", $item);
                $resultData[$itemData[0]] = $itemData[1];
            }
        }
        if ($landingUrl) {
            $resultData['sc_lurl'] = $landingUrl;
        }
        if ($containerCookie !== null) {
            $session = $containerCookie['session'];
            $timestamp = $containerCookie['timestamp'];
            if ($session) {
                $resultData['gsSessionId'] = $session;
            }
            if ($timestamp) {
                $resultData['gsTimestamp'] = $timestamp;
            }
        }
        if (count($resultData)) {
            return $resultData;
        }
        return null;
    }

    /**
     * @return array|null
     */
    public function getContainerCookie()
    {
        $cookie = $this->cookieManager->getCookie('scgacookie');
        if ($cookie) {
            return self::extractValues($cookie);
        }
        return null;
    }

    /**
     * @return string|null
     */
    public function getContainerId()
    {
        $id = $this->helper->getMeasurementId();
        if (!$id) {
            return null;
        }
        $parts = explode('-', $id);
        if (!isset($parts[1])) {
            return null;
        }
        return $parts[1];
    }

    /**
     * @param $cookie
     * @return array
     */
    public static function extractValues($cookie)
    {
        $session = null;
        $timestamp = null;
        preg_match("/^GS1\.1\.(\d+)\.\d+\.\d+\.(\d+)/", $cookie, $matches);
        if (isset($matches[1])) {
            $session = $matches[1];
        }
        if (isset($matches[2])) {
            $timestamp = $matches[2] . '000000';
        }
        return [
            'session' => $session,
            'timestamp' => $timestamp,
        ];
    }
}
