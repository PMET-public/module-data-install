<?php
/**
 * Copyright © Adobe  All rights reserved.
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
        Curl $curl
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
    }
    /**
     * Execute
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $dataPack = $this->dataPack->create();
        try {
            if ($this->getRequest()->getMethod() !== 'POST' ||
            !$this->_formKeyValidator->validate($this->getRequest())) {
                throw new LocalizedException(__('Invalid Request'));
            }
            $fileUploader = null;
            $params = $this->getRequest()->getParams();
            //params['vertical'] for upload params['remote_source'] for upload
            if ($params['remote_source']!='') {
                $dataPack->setAuthToken("");
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
            }
            $dataPack->unZipDataPack();
            $this->setAdvancedConditions($dataPack, $params['advanced_conditions']);
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
    * @param string $conditions
    * @return DataPack
    */
    protected function setAdvancedConditions($dataPack, $conditions)
    {
        $commands = explode(" ", $conditions);
        foreach ($commands as $command) {
            $element = explode("=", trim($command));
            switch ($element[0]) {
                case "--files":
                    $dataPack->setFiles(explode(",", trim($element[1], '"')));
                    break;
                case "-r":
                    $dataPack->setReload(1);
                    break;
                case "--host":
                    $dataPack->setHost($element[1]);
                    break;
                case "--load":
                    $dataPack->setLoad($element[1]);
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
    protected function unzipFiled($fileInfo)
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
     * Return authentication token
     *
     * Defaults to github token for now, but can be expanded to support additional authentication methods
     *
     * @param array $params
     * @return mixed
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    protected function getAuthenticationd($params)
    {
        if (!empty($params['github_access_token'])) {
            return $params['github_access_token'];
        } else {
            return $this->scopeConfig->getValue(
                'magentoese/datainstall/github_access_token',
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT
            );
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
            LocalizedException(__('Data pack could not be retrieved. Check the url and necessary authenticatication'));
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
