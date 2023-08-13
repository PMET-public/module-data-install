<?php
/**
 * Copyright 2022 Adobe, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace MagentoEse\DataInstall\Controller\Adminhtml\Export;

use Magento\Backend\App\Action;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Quote\Model\ResourceModel\Quote\CollectionFactory;
use MagentoEse\DataInstall\Model\NegotiableQuote\ExportQuotes;

class Quotes extends Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Magento_NegotiableQuote::view_quotes';

    /**
     * @var Filter
     */
    protected Filter $filter;

    /**
     * @var CollectionFactory
     */
    protected CollectionFactory $collectionFactory;

    /**
     * @var FileFactory
     */
    protected FileFactory $fileFactory;

    /**
     * @var DirectoryList
     */
    protected DirectoryList $directoryList;

    protected string $fileName = 'b2b_negotiated_quotes.json';

    private JoinProcessorInterface $extensionAttributesJoinProcessor;
    private SerializerInterface $jsonSerializer;
    private Filesystem $filesystem;
    private ExportQuotes $exportQuotes;

    /**
     * Page constructor
     *
     * @param ExportQuotes $exportQuotes
     * @param SerializerInterface $jsonSerializer
     * @param JoinProcessorInterface $extensionAttributesJoinProcessor
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param FileFactory $fileFactory
     * @param Filesystem $filesystem
     * @param DirectoryList $directoryList
     */
    public function __construct(
        ExportQuotes $exportQuotes,
        SerializerInterface        $jsonSerializer,
        JoinProcessorInterface     $extensionAttributesJoinProcessor,
        Context                    $context,
        Filter                     $filter,
        CollectionFactory          $collectionFactory,
        FileFactory                $fileFactory,
        Filesystem                 $filesystem,
        DirectoryList              $directoryList
    ) {
        parent::__construct($context);
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->fileFactory = $fileFactory;
        $this->directoryList = $directoryList;
        $this->extensionAttributesJoinProcessor = $extensionAttributesJoinProcessor;
        $this->jsonSerializer = $jsonSerializer;
        $this->filesystem = $filesystem;
        $this->exportQuotes = $exportQuotes;
    }

    /**
     * Execute
     *
     * @return ResponseInterface|ResultInterface
     * @throws FileSystemException
     * @throws LocalizedException
     * @throws \Exception
     */
    public function execute(): ResultInterface|ResponseInterface
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());

        $this->extensionAttributesJoinProcessor->process($collection);
        $collection->addFieldToFilter(
            'extension_attribute_negotiable_quote.is_regular_quote',
            ['eq' => 1]
        );

        $data["data"]["negotiableQuotesExport"] = $this->exportQuotes->generateData($collection);
        //$result = $this->jsonSerializer->serialize($data);
        $result = json_encode($data, JSON_PRETTY_PRINT);
        $this->createFile($result);

        return $this->fileFactory->create(
            $this->fileName,
            [
                'type' => "filename",
                'value' => $this->fileName,
                'rm' => true,
            ],
            DirectoryList::VAR_DIR
        );
    }

    public function createFile($generatedContent = ''): void
    {
        try {
            $file = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_IMPORT_EXPORT);
            $file->writeFile($this->fileName, $generatedContent);
        } catch (FileSystemException $e) {
            die($e->getMessage());
        }
    }
}
