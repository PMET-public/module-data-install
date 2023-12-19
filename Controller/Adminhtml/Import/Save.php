<?php
/**
 * Copyright 2022 Adobe, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace MagentoEse\DataInstall\Controller\Adminhtml\Import;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Validation\ValidationException;
use Magento\MediaStorage\Model\File\UploaderFactory;
use MagentoEse\DataInstall\Model\Queue\ScheduleBulk;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\HTTP\Client\Curl;
use MagentoEse\DataInstall\Api\Data\DataPackInterfaceFactory;
use MagentoEse\DataInstall\Api\Data\DataPackInterface;
use MagentoEse\DataInstall\Api\Data\InstallerJobInterfaceFactory;
use MagentoEse\DataInstall\Api\Data\InstallerJobInterface;

class Save extends \Magento\Backend\App\Action
{

    public const ZIPPED_DIR = 'datapacks/zipfiles';
    public const UNZIPPED_DIR = 'datapacks/unzipped';
    public const UPLOAD_DIR = 'datapacks/upload';

    /** @var DataPackInterfaceFactory */
    protected $dataPack;
    
    /** @var UploaderFactory */
    protected $uploaderFactory;

    /** @var Filesystem\Directory\WriteInterface */
    protected $verticalDirectory;

    /** @var File */
    protected $file;

    /** @var ScheduleBulk */
    protected $scheduleBulk;

    /** @var ScopeConfigInterface */
    protected $scopeConfig;

    /** @var Curl */
    protected $curl;

    /** @var InstallerJobInterfaceFactory */
    protected $installerJobInterface;

    /** @var DirectoryList */
    protected $directoryList;

    /**
     * Save constructor.
     *
     * @param Context $context
     * @param DataPackInterfaceFactory $dataPack
     * @param InstallerJobInterfaceFactory $installerJobInterface
     * @param UploaderFactory $uploaderFactory
     * @param Filesystem $filesystem
     * @param ScheduleBulk $scheduleBulk
     * @param File $file
     * @param ScopeConfigInterface $scopeConfig
     * @param Curl $curl
     * @param DirectoryList $directoryList
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function __construct(
        Context $context,
        DataPackInterfaceFactory $dataPack,
        InstallerJobInterfaceFactory $installerJobInterface,
        UploaderFactory $uploaderFactory,
        Filesystem $filesystem,
        ScheduleBulk $scheduleBulk,
        File $file,
        ScopeConfigInterface $scopeConfig,
        Curl $curl,
        DirectoryList $directoryList
    ) {
        parent::__construct($context);
        $this->dataPack = $dataPack;
        $this->installerJobInterface = $installerJobInterface;
        $this->uploaderFactory = $uploaderFactory;
        $this->verticalDirectory = $filesystem->getDirectoryWrite(DirectoryList::TMP);
        $this->scheduleBulk = $scheduleBulk;
        $this->file = $file;
        $this->scopeConfig = $scopeConfig;
        $this->curl = $curl;
        $this->directoryList = $directoryList;
    }
    /**
     * Execute
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $dataPack = $this->dataPack->create();
        //set to delete source files
        $dataPack->setDeleteSourceFiles(true);
        try {
            if ($this->getRequest()->getMethod() !== 'POST' ||
            !$this->_formKeyValidator->validate($this->getRequest())) {
                throw new LocalizedException(__('Invalid Request'));
            }
            $fileUploader = null;
            $params = $this->getRequest()->getParams();
            //set auth token to empty to retrieve config setting if entered.
            $dataPack->setAuthToken('');
            $dataPack = $this->setAdvancedConditions($dataPack, $params);
            //params['vertical'] for upload params['remote_source'] for upload
            if ($params['remote_source']!='') {
                $dataPack->setIsRemote(true);
                $dataPack->setDataPackLocation($dataPack->getRemoteDataPack(
                    $params['remote_source'],
                    $dataPack->getAuthToken()
                ));
            } else {
                try {
                    $verticalId = 'vertical';
                    //file goes into tmp/datapacks/upload
                    if (isset($params['vertical']) && count($params['vertical'])) {
                        $verticalId = $params['vertical'][0];
                        //phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
                        if (!file_exists($verticalId['tmp_name'])) {
                            $verticalId['tmp_name'] = $verticalId['path'] . '/' . $verticalId['file'];
                        }
                    }
                    $fileUploader = $this->uploaderFactory->create(['fileId' => $verticalId]);
                    $fileUploader->setAllowedExtensions(['zip']);
                    $fileUploader->setAllowRenameFiles(true);
                    $fileUploader->setAllowCreateFolders(true);
                    $fileUploader->validateFile();
                //upload file
                    $dataPack->setDataPackLocation($fileUploader->save($this->verticalDirectory->
                    getAbsolutePath(DataPackInterface::ZIPPED_DIR)));
                } catch (ValidationException $e) {
                    throw new LocalizedException(__('File extension is not supported. Only extension allowed is .zip'));
                } catch (\Exception $e) {
                    //if an except is thrown, no image has been uploaded
                    throw new LocalizedException(__('Data Pack is required'));
                }
                //upload media if exists
                try {
                    $verticalId = 'images';
                    //file goes into tmp/datapacks/upload
                    if (isset($params['images']) && count($params['images'])) {
                        $verticalId = $params['images'][0];
                        //phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
                        if (!file_exists($verticalId['tmp_name'])) {
                            $verticalId['tmp_name'] = $verticalId['path'] . '/' . $verticalId['file'];
                            $fileUploader = $this->uploaderFactory->create(['fileId' => $verticalId]);
                            $fileUploader->setAllowedExtensions(['zip']);
                            $fileUploader->setAllowRenameFiles(true);
                            $fileUploader->setAllowCreateFolders(true);
                            $fileUploader->validateFile();
                        //upload file
                            $dataPack->setImagePackLocation($fileUploader->save($this->verticalDirectory->
                            getAbsolutePath(DataPackInterface::ZIPPED_DIR)));
                        }
                    }
                } catch (ValidationException $e) {
                    throw new LocalizedException(__('File extension is not supported. Only extension allowed is .zip'));
                }
            }
            $dataPack->unZipDataPack();
            if ($dataPack->getImagePackLocation()) {
                $dataPack->unZipImagePack();
                $dataPack->mergeDataPacks($dataPack->getImagePackLocation(), $dataPack->getDataPackLocation().'/media');
                //delete image pack directory
                if ($this->file->isExists($dataPack->getImagePackLocation())) {
                    $this->file->deleteDirectory($dataPack->getImagePackLocation());
                }
            }
            //TODO: warning if media does not exist in data
            $mediaDir = $this->verticalDirectory->isExist($dataPack->getDataPackLocation().'/media');

            if (!$mediaDir) {
                $this->messageManager->addWarningMessage(__('Media directory does not exist in data pack, and valid 
                images file is not uploaded. 
                This is not an error if no images are expected, but it could effect import if it is expected.'));
            } else {
                $mediaDir =  $this->file->readDirectory($dataPack->getDataPackLocation().'/media');
                if (count($mediaDir)==0) {
                    $this->messageManager->addWarningMessage(__('Images have not been included. 
                    This is not an error if no media is expected, but it could effect import if it is expected.'));
                }
            }

            if ($dataPack->getDataPackLocation()) {
              ///schedule import
                $installerJob = $this->installerJobInterface->create();
                $installerJob->scheduleImport($dataPack);
            } else {
                $this->messageManager->addErrorMessage(__('Data Pack could not be unzipped. Please check file format'));
                return $this->_redirect('*/*/upload');
            }

            $this->messageManager->addSuccessMessage(__('Data Pack uploaded successfully and Scheduled for Import'));

            return $this->_redirect('*/*/upload');
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $this->_redirect('*/*/upload');
        } catch (\Exception $e) {
            error_log($e->getMessage());
            error_log($e->getTraceAsString());
            $this->messageManager->addErrorMessage(__('An error occurred, '.$e->getMessage()));
            return $this->_redirect('*/*/upload');
        }
    }

   /**
    * Set the advanced conditions of the job
    *
    * @param DataPack $dataPack
    * @param array $params
    * @return DataPack
    */
    protected function setAdvancedConditions($dataPack, $params)
    {
        foreach ($params as $param => $value) {
            switch ($param) {
                case "files":
                    if ($value !="") {
                        $dataPack->setFiles(explode(",", trim($value, '"')));
                    }
                    break;
                case "reload":
                    $dataPack->setReload($value);
                    break;
                case "host":
                    $dataPack->setHost($value);
                    break;
                case "load":
                    $dataPack->setLoad($value);
                    break;
                case "authtoken":
                    $dataPack->setAuthToken($value);
                    break;
                case "remote_source":
                    $dataPack->setIsRemote($value);
                    break;
                case "make_default_website":
                    $dataPack->setIsDefaultWebsite($value);
                    break;
                case "site_code":
                    $dataPack->setSiteCode($value);
                    break;
                case "site_name":
                    $dataPack->setSiteName($value);
                    break;
                case "store_code":
                    $dataPack->setStoreCode($value);
                    break;
                case "store_name":
                    $dataPack->setStoreName($value);
                    break;
                case "store_view_code":
                    $dataPack->setStoreViewCode($value);
                    break;
                case "store_view_name":
                    $dataPack->setStoreViewName($value);
                    break;
                case "is_override":
                    $dataPack->setIsOverride($value);
                    break;
                case "restrict_products_from_views":
                    $dataPack->setRestrictProductsFromViews($value);
                    break;
            }
        }
        return $dataPack;
    }

    /**
     * Unzip data pack file
     *
     * @param string $fileInfo
     * @return mixed
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    protected function unzipFile($fileInfo)
    {
        $zip = new \ZipArchive;

      // Zip File Name
        if ($zip->open($fileInfo["path"]."/".$fileInfo["file"]) === true) {
            //get name of directory in the zip file
            $fileIndex = $zip->statIndex(0)['name'];
            $directoryName = str_replace("/", "", $zip->statIndex(0)['name']);
            //directory is created if it doesnt exist
            $zip->extractTo($this->verticalDirectory->getAbsolutePath(self::UNZIPPED_DIR));
            
            $zip->close();
            $this->file->deleteFile($fileInfo["path"]."/".$fileInfo["file"]);
            return $directoryName;
        } else {
            $this->file->deleteFile($fileInfo["path"]."/".$fileInfo["file"]);
            return false;
        }
    }
   
        /**
         * Get a remote data pack
         *
         * @param string $url
         * @param string $githubToken
         * @return array
         */
    protected function getRemoteFile($url, $githubToken)
    {
        $filename = uniqid();
        $this->curl->setOption(CURLOPT_URL, $url);
        $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
        $this->curl->setOption(CURLOPT_SSL_VERIFYPEER, true);
        $this->curl->setOption(CURLOPT_HTTPHEADER, ["Authorization: token ".$githubToken]);
        $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
        $this->curl->setOption(CURLOPT_FOLLOWLOCATION, true);
        $this->curl->get($url);
        $result=$this->curl->getBody();
        if ($result=='Not Found') {
            throw new
            LocalizedException(__('Data pack could not be retrieved. Check the url, 
            php settings for file size, and necessary authenticatication'));
        }
        $this->file->filePutContents($this->verticalDirectory->
            getAbsolutePath(self::ZIPPED_DIR).'/'.$filename.'.zip', $result);
        $fileInfo = [
            'name' => $filename.'.zip',
            'full_path' => $filename.'.zip',
            'type' => 'application/zip',
            'path' => $this->verticalDirectory->getAbsolutePath(self::ZIPPED_DIR),
            'file' => $filename.'.zip'
        ];
        return $fileInfo;
    }
}
