<?php
/**
 * Copyright © Adobe  All rights reserved.
 */
namespace MagentoEse\DataInstall\Controller\Adminhtml\Import;

use Magento\Backend\App\Action;
use Magento\Framework\View\Result\PageFactory;
use Magento\Backend\Model\View\Result\Page;
use Magento\Backend\App\Action\Context;

class Upload extends Action
{

    /** @var PageFactory */
    protected $resultPageFactory;

    /**
     * Upload constructor
     *
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
     * Set menu
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|\Page
     */
    public function execute()
    {
      /** @var \Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('MagentoEse_DataInstall::import_vertical');
        $resultPage->getConfig()->getTitle()->prepend(__('Import Data Pack'));
        return $resultPage;
    }
}
