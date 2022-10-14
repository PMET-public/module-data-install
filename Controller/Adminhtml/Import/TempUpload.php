<?php
/**
 * Copyright 2022 Adobe, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace MagentoEse\DataInstall\Controller\Adminhtml\Import;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\File\UploaderFactory;
use Magento\Framework\App\Filesystem\DirectoryList;

class TempUpload extends \Magento\Backend\App\Action
{
    /** @var string  */
    public const UPLOAD_DIR='datapacks/upload';

    /** @var UploaderFactory */
    protected $uploaderFactory;

    /** @var Filesystem\Directory\WriteInterface  */
    protected $tmpDirectory;

    /**
     * TempUpload constructor
     *
     * @param Context $context
     * @param UploaderFactory $uploaderFactory
     * @param Filesystem $filesystem
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function __construct(
        Context $context,
        UploaderFactory $uploaderFactory,
        Filesystem $filesystem
    ) {
        parent::__construct($context);
        $this->uploaderFactory = $uploaderFactory;
        $this->tmpDirectory = $filesystem->getDirectoryWrite(DirectoryList::TMP);
    }

    /**
     * Execute File upload
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $jsonResult = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        try {
            $fileUploader = $this->uploaderFactory->create(['fileId' => 'vertical']);
            $fileUploader->setAllowedExtensions(['zip']);
            $fileUploader->setAllowRenameFiles(true);
            $fileUploader->setAllowCreateFolders(true);
            $fileUploader->setFilesDispersion(false);
            $result = $fileUploader->save($this->tmpDirectory->getAbsolutePath(self::UPLOAD_DIR));
            return $jsonResult->setData($result);
        } catch (LocalizedException $e) {
            return $jsonResult->setData(['errorcode' => 0, 'error' => $e->getMessage()]);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            error_log($e->getTraceAsString());
            return $jsonResult->
            setData(['errorcode' => 0, 'error' => __('An error occurred, please try again later.')]);
        }
    }
}
