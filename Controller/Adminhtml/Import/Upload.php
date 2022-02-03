<?php
namespace MagentoEse\DataInstall\Controller\Adminhtml\Import;

class Upload extends \Magento\Backend\App\Action
{

    /**
     *
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;
  
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }
  
    public function execute()
    {
      /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('MagentoEse_DataInstall::import_vertical');
        $resultPage->getConfig()->getTitle()->prepend(__('Import Data Pack'));
        return $resultPage;
    }
}
