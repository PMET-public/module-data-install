<?php
/**
 * Copyright 2022 Adobe, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace MagentoEse\DataInstall\Controller\Adminhtml\Aiimport;

use Exception;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Client\Curl;
use MagentoEse\DataInstall\Model\AI\ImportAIDataService;

class Generate extends \Magento\Backend\App\Action
{
    private ImportAIDataService $importAIDataService;

  /**
   * 
   * @param Context $context 
     * @param ImportAIDataService $importAIDataService
   */
    public function __construct(
        Context $context,
        ImportAIDataService $importAIDataService
    ) {
        parent::__construct($context);
        $this->importAIDataService = $importAIDataService;
    }
    /**
     * Execute
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        if ($this->getRequest()->getMethod() !== 'POST' ||
        !$this->_formKeyValidator->validate($this->getRequest())) {
            throw new LocalizedException(__('Invalid Request'));
        }
        $params = $this->getRequest()->getParams();
        try {
            $this->importAIDataService->execute($params);
        $this->messageManager->addSuccessMessage(__('Products generated'));
        } catch (CouldNotSaveException|LocalizedException $e) {
            $this->messageManager->addErrorMessage(__('Error while Products generation'));
        }
        return $this->_redirect('index');
        // $resultRedirect = $this->resultRedirectFactory->create();
        // $resultRedirect->setPath('*/*/index');
        // return $resultRedirect;
    }

}
