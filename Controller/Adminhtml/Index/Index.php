<?php
/**
 * Scommerce Analytics Sync Grid Controller Index
 *
 * @category   Scommerce
 * @author     Scommerce Mage <core@scommerce-mage.com>
 */

namespace Scommerce\AnalyticsSync\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * Check the permission to run it
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Scommerce_AnalyticsSync::grid');
    }

    /**
     * CacheWarmer List action
     *
     * @return string
     */
    public function execute()
    {
        /** @var PageFactory $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu(
            'Scommerce_AnalyticsSync::sync_log'
        )->addBreadcrumb(
            __('GA Sync Log'),
            __('GA Sync Log')
        );
        $resultPage->getConfig()->getTitle()->prepend(__('Sync Log'));
        return $resultPage;
    }
}
