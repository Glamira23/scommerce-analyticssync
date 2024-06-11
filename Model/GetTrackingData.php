<?php
/**
 * Scommerce AnalyticsSync observer class for checkout_cart_product_add_after event
 *
 * @category   Scommerce
 * @author     Scommerce Mage <core@scommerce-mage.com>
 */

namespace Scommerce\AnalyticsSync\Model;

use Magento\Framework\Stdlib\CookieManagerInterface;

/**
 * Class GetTrackingData returns collected tracking data
 */
class GetTrackingData
{
    /**
     * @var CookieManagerInterface
     */
    private $cookieManager;

    /**
     * GetTrackingData constructor.
     * @param CookieManagerInterface $cookieManager
     */
    public function __construct(
        CookieManagerInterface $cookieManager
    ) {
        $this->cookieManager = $cookieManager;
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
        if (count($resultData)) {
            return $resultData;
        }
        return null;
    }
}
