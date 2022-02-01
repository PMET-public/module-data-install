<?php
/**
 * Copyright © Adobe, Inc. All rights reserved.
 */
namespace MagentoEse\DataInstall\Controller\Adminhtml\Export;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\File\Csv;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Cms\Model\ResourceModel\Page\CollectionFactory;

class Page extends Action implements HttpPostActionInterface
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Magento_Cms::page';

    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var FileFactory
     */
    protected $fileFactory;

    /**
     * @var Csv
     */
    protected $csvProcessor;

    /**
     * @var DirectoryList
     */
    protected $directoryList;

    /**
     * Index constructor.
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param FileFactory $fileFactory
     * @param Csv $csvProcessor
     * @param DirectoryList $directoryList
     */
    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        FileFactory $fileFactory,
        Csv $csvProcessor,
        DirectoryList $directoryList
    ) {
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->fileFactory = $fileFactory;
        $this->csvProcessor = $csvProcessor;
        $this->directoryList = $directoryList;
        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|ResultInterface
     * @throws FileSystemException
     * @throws LocalizedException
     */
    public function execute()
    {
        $fileName = 'pages.csv';
        $filePath = $this->directoryList->getPath(DirectoryList::VAR_DIR)
            . "/" . $fileName;

        $collection = $this->filter->getCollection($this->collectionFactory->create());

        $result = $this->generateData($collection);

        $this->csvProcessor
            ->setDelimiter(',')
            ->setEnclosure('"')
            ->saveData(
                $filePath,
                $result
            );

        return $this->fileFactory->create(
            $fileName,
            [
                'type' => "filename",
                'value' => $fileName,
                'rm' => true,
            ],
            DirectoryList::VAR_DIR,
            'application/octet-stream'
        );
    }

    /**
     * @param AbstractDb $collection
     * @return array
     */
    private function generateData(AbstractDb $collection): array
    {
        $result = [];
        //$customerData = $customer->getData();
        $result[] = [
            'identifier',
            'title',
            'content',
            'page_layout',
            'meta_title',
            'meta_keywords',
            'meta_description',
            'content_heading'
        ];

        foreach ($collection as $block) {
            $result[] = [
                $block->getIdentifier(),
                $block->getTitle(),
                $block->getContent(),
                $block->getPageLayout(),
                $block->getMetaTitle(),
                $block->getMetaKeywords(),
                $block->getMetaDescription(),
                $block->getContentHeading()
            ];
        }

        return $result;
    }
}
