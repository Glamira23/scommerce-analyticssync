<?php
/**
 * Scommerce AnalyticsSync Log Grid Column Action
 *
 * @category   Scommerce
 * @author     Scommerce Mage <core@scommerce-mage.com>
 */

namespace Scommerce\AnalyticsSync\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\UrlInterface;
use Scommerce\AnalyticsSync\Helper\Data;

class Action extends Column
{
    /** Url path for delete */
    const URL_PATH_VIEW = 'gasynclog/index/view';

    /** @var UrlInterface */
    protected $_urlBuilder;

    /** @var helper */
    protected $_helper;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     * @param Data  $helper
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        Data  $helper,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->_urlBuilder = $urlBuilder;
        $this->_helper = $helper;
    }

    /**
     * Prepare Data Source.
     *
     * @param array $dataSource
     *
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $name = $this->getData('name');
                if (isset($item['log_id'])) {
                    $item[$name]['view'] = [
                        'href' => $this->_urlBuilder->getUrl(
                            self::URL_PATH_VIEW,
                            [
                                'log_id' => $item['log_id']
                            ]
                        ),
                        'label' => __('View')
                    ];

                }
            }
        }

        return $dataSource;
    }
}
