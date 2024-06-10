<?php
/**
 * Scommerce Analytics Sync Adminhtml Order View
 *
 * @category   Scommerce
 * @author     Scommerce Mage <core@scommerce-mage.com>
 */

namespace Scommerce\AnalyticsSync\Block\Adminhtml\Order\View;

use Magento\Framework\App\Request\Http;
use Magento\Framework\View\Element\Template;
use Magento\Sales\Api\OrderRepositoryInterface;
use Scommerce\AnalyticsSync\Lib\MobileDetect;

/**
 * Class TrackingBlock to show tracking information in order view
 */
class TrackingBlock extends Template
{
    /**
     * @var null|array
     */
    private $_trackingData = null;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * TrackingBlock constructor.
     * @param OrderRepositoryInterface $orderRepository
     * @param Template\Context $context
     * @param array $data
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        Template\Context $context,
        array $data = []
    ) {
        $this->orderRepository = $orderRepository;
        parent::__construct($context, $data);
    }

    /**
     * @return mixed
     */
    public function getOrderId()
    {
        return $this->getRequest()->getParam("order_id");
    }

    /**
     * @return \Magento\Sales\Api\Data\OrderInterface
     */
    public function getOrder()
    {
        $id = $this->getOrderId();
        return $this->orderRepository->get($id);
    }

    /**
     * @return array
     */
    public function getTrackingInfo()
    {
        if ($this->_trackingData === null) {
            $order = $this->getOrder();

            $tracking = $order->getData("sc_tracking_info");
            if ($tracking) {
                $result = json_decode($tracking, true);
                $result = $this->_addInfo($result);
                $this->_trackingData = $result;
            } else {
                $this->_trackingData = [];
            }
        }
        return $this->_trackingData;
    }

    /**
     * @param $code
     * @return string
     */
    public function getLabel($code)
    {
        switch ($code) {
            case "src":
                return "Source";
            case "mdm":
                return "Medium";
            case "cmp":
                return "Campaign";
            case "uip":
                return "User IP";
            case "uag":
                return "User Agent";
            case "gab":
                return "GA Blocked";
            case "gtmb":
                return "GTM Blocked";
            case "cnt":
                return "Content";
            case "trm":
                return "Term";
            case "typ":
                return "Type";
            case "vst":
                return "Visit";
            case "sc_lurl":
                return "Landing Url";
        }
        return $code;
    }

    /**
     * @param $trackinInfo
     * @param $code
     * @param null $renderType
     * @return string
     */
    public function renderItem($trackinInfo, $code, $renderType = null)
    {
        if (!$trackinInfo || !$code || !is_array($trackinInfo)) {
            return '';
        }
        if (isset($trackinInfo[$code])) {
            return
                '<tr><td class="label">' . __($this->getLabel($code)) . '</td>
            <td class="value">' . $this->renderValue($trackinInfo[$code], $renderType) . '</td></tr>';
        }
        return '';
    }

    /**
     * @param $value
     * @param $renderType
     * @return string
     */
    protected function renderValue($value, $renderType)
    {
        switch ($renderType) {
            case "yesno":
                if ($value) {
                    return "Yes";
                } else {
                    return "No";
                }
        }
        return $value;
    }

    /**
     * @param $result
     * @return mixed
     */
    private function _addInfo($result)
    {
        if (isset($result['uag'])) {
            $result["Browser"] = $this->getBrowser($result["uag"]);
            $result["Device Type"] = $this->_getDeviceType($result["uag"]);
            $result["OS"] = $this->getOS($result["uag"]);
        }
        return $result;
    }

    /**
     * @param $info
     * @return string
     */
    private function _getDeviceType($info)
    {
        $det = new MobileDetect(null, $info);
        if ($det->isMobile($info)) {
            return "Mobile";
        } elseif ($det->isTablet($info)) {
            return "Tablet";
        }
        return "Desktop";
    }

    /**
     * @param $info
     * @return mixed|string
     */
    private function getOS($info)
    {
        $os_platform    =   "Unknown OS Platform";

        $os_array       =   [
            '/windows nt 6.2/i'     =>  'Windows 8',
            '/windows nt 6.1/i'     =>  'Windows 7',
            '/windows nt 6.0/i'     =>  'Windows Vista',
            '/windows nt 5.2/i'     =>  'Windows Server 2003/XP x64',
            '/windows nt 5.1/i'     =>  'Windows XP',
            '/windows xp/i'         =>  'Windows XP',
            '/windows nt 5.0/i'     =>  'Windows 2000',
            '/windows me/i'         =>  'Windows ME',
            '/win98/i'              =>  'Windows 98',
            '/win95/i'              =>  'Windows 95',
            '/win16/i'              =>  'Windows 3.11',
            '/macintosh|mac os x/i' =>  'Mac OS X',
            '/mac_powerpc/i'        =>  'Mac OS 9',
            '/linux/i'              =>  'Linux',
            '/ubuntu/i'             =>  'Ubuntu',
            '/iphone/i'             =>  'iPhone',
            '/ipod/i'               =>  'iPod',
            '/ipad/i'               =>  'iPad',
            '/android/i'            =>  'Android',
            '/blackberry/i'         =>  'BlackBerry',
            '/webos/i'              =>  'Mobile'
        ];

        foreach ($os_array as $regex => $value) {
            if (preg_match($regex, $info)) {
                $os_platform    =   $value;
            }
        }

        return $os_platform;
    }

    /**
     * @param $info
     * @return string
     */
    private function getBrowser($info)
    {
        $browser        =   "Unknown Browser";
        $browser_array  =   [
            '/msie/i'       =>  'Internet Explorer',
            '/firefox/i'    =>  'Firefox',
            '/safari/i'     =>  'Safari',
            '/chrome/i'     =>  'Chrome',
            '/opera/i'      =>  'Opera',
            '/netscape/i'   =>  'Netscape',
            '/maxthon/i'    =>  'Maxthon',
            '/konqueror/i'  =>  'Konqueror',
            '/mobile/i'     =>  'Handheld Browser'
        ];

        foreach ($browser_array as $regex => $value) {
            if (preg_match($regex, $info)) {
                $browser    =   $value;
            }
        }
        return $browser;
    }

    /**
     * @return string
     */
    public function getCid()
    {
        $cidValue = isset($this->_trackingData['gaclid']) ? $this->_trackingData['gaclid'] : '-';
        return
            '<tr><td class="label">' . __("GA Client ID") . '</td>
            <td class="value">' . $cidValue . '</td></tr>';
    }
}
