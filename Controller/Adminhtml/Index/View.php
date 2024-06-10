<?php
/**
 * Analytics Sync SyncLog View
 *
 * @category   Scommerce
 * @author     Scommerce Ltd
 *
 */

namespace Scommerce\AnalyticsSync\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\View\LayoutFactory;
use Magento\Framework\Registry;
use Scommerce\AnalyticsSync\Api\SyncLogRepositoryInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\Page;

/**
 * Class View Log details controller
 */
class View extends Action
{
    /** @var PageFactory */
    public $resultPageFactory;

    /** @var LayoutFactory */
    public $layoutFactory;

    /** @var Registry */
    private $registry;

    /** @var SyncLogRepositoryInterface */
    private $syncLogRepository;

    /**
     * Edit constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param LayoutFactory $layoutFactory
     * @param Registry $registry
     * @param SyncLogRepositoryInterface $syncLogRepository
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        LayoutFactory $layoutFactory,
        Registry $registry,
        SyncLogRepositoryInterface $syncLogRepository
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->layoutFactory = $layoutFactory;
        $this->registry = $registry;
        $this->syncLogRepository = $syncLogRepository;

        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|ResultInterface|Page
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('log_id');
        $model = $this->syncLogRepository->get($id);

        if (!$model->getId()) {
            $this->_redirect("*/*/index");
        }
        $resultPage = $this->resultPageFactory->create();
        $resultPage->addBreadcrumb(__('Sync Log Details'), __('Sync Log Details'));
        $resultPage->getConfig()->getTitle()->prepend(__('Sync Log Details'));
        return $resultPage;
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
}
