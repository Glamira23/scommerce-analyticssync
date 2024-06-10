<?php
/**
 * Scommerce Analytics Sync Back Button Class
 *
 * @category   Scommerce
 * @author     Scommerce Mage <core@scommerce-mage.com>
 */

namespace Scommerce\AnalyticsSync\Block\Adminhtml\SyncLog;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Magento\Backend\Block\Widget\Context;

/**
 * Class SaveButton
 */
class BackButton implements ButtonProviderInterface
{
    protected $context;

    /**
     * __construct
     *
     * @param Context $context
     */
    public function __construct(
        Context $context
    ) {
        $this->context = $context;
    }

    /**
     * Retrieve button-specified settings
     *
     * @return array
     */
    public function getButtonData()
    {
        return [
            'label' => __('Back'),
            'on_click' => sprintf('location.href = "%s";', $this->getBackUrl()),
            'class' => 'back',
            'sort_order' => 10
        ];
    }

    /**
     * @return string
     */
    private function getBackUrl()
    {
        return $this->getUrl('*/index/index/');
    }

    /**
     * @param string $route
     * @param array $param
     * @return string
     */
    protected function getUrl($route = '', $param = [])
    {
        return $this->context->getUrlBuilder()->getUrl($route, $param);
    }
}
