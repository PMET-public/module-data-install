<?php
namespace MagentoEse\DataInstall\Controller\Adminhtml\Import;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Validation\ValidationException;
use Magento\MediaStorage\Model\File\UploaderFactory;
use MagentoEse\DataInstall\Model\Queue\ScheduleBulk;
use Magento\Framework\App\Filesystem\DirectoryList;

class Save extends \Magento\Backend\App\Action
{
  
    const ZIPPED_DIR = 'datapacks/zipfiles';
    const UNZIPPED_DIR = 'datapacks/unzipped';
    /** @var UploaderFactory */
    protected $uploaderFactory;

    /** @var Filesystem\Directory\WriteInterface */
    protected $verticalDirectory;
  
    /** @var File */
    protected $file;

    /** @var ScheduleBulk */
    protected $scheduleBulk;

    public function __construct(
        Context $context,
        UploaderFactory $uploaderFactory,
        Filesystem $filesystem,
        ScheduleBulk $scheduleBulk,
        File $file
    ) {
        parent::__construct($context);
        $this->uploaderFactory = $uploaderFactory;
        $this->verticalDirectory = $filesystem->getDirectoryWrite(DirectoryList::TMP);
        $this->scheduleBulk = $scheduleBulk;
        $this->file = $file;
    }

    public function execute()
    {
        try {
            if ($this->getRequest()->getMethod() !== 'POST' ||
            !$this->_formKeyValidator->validate($this->getRequest())) {
                throw new LocalizedException(__('Invalid Request'));
            }
            $fileUploader = null;
            $params = $this->getRequest()->getParams();
            try {
                  $verticalId = 'vertical';
                if (isset($params['vertical']) && count($params['vertical'])) {
                    $verticalId = $params['vertical'][0];
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
                $fileInfo = $fileUploader->save($this->verticalDirectory->getAbsolutePath(self::ZIPPED_DIR));
            } catch (ValidationException $e) {
                throw new LocalizedException(__('File extension is not supported. Only extension allowed is .zip'));
            } catch (\Exception $e) {
                //if an except is thrown, no image has been uploaded
                throw new LocalizedException(__('Data Pack is required'));
            }
            
            $operationConditions = $this->setAdvancedConditions($params['advanced_conditions']);

            if ($this->unzipFile($fileInfo)) {
              ///schedule import
                $operation = [];
                $operation['fileSource']=$this->verticalDirectory->getAbsolutePath(self::UNZIPPED_DIR).'/'.basename($fileInfo['name'],'.zip');
                $operation['packFile']=$fileInfo['name'];
                $operation['load']=$operationConditions['load'];
                $operation['fileOrder']=$operationConditions['files'];
                $operation['reload']=$operationConditions['reload'];
                $operation['host']=$operationConditions['host'];
                $this->scheduleBulk->execute([$operation]);
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
            $this->messageManager->addErrorMessage(__('An error occurred, please try again later.'));
            return $this->_redirect('*/*/upload');
        }
    }

    protected function setAdvancedConditions($conditions){
        $settings = ["reload"=>0,"load"=>"", "files"=>"", "host"=>""];
       // $conditions='--files="b2b_approval_rules.csv" -r --host=subdomain --load=store';
        $commands = explode(" ",$conditions);
        foreach($commands as $command){
            $element = explode("=",trim($command));
            switch ($element[0]) {
                case "--files":
                    $settings["files"]=explode(",",trim($element[1],'"'));
                    break;
                case "-r":
                    $settings["reload"]=1;
                    break;
                case "--host":
                    $settings["host"]=$element[1];
                    break;
                case "--load":
                    $settings["load"]=$element[1];
                    break;    
            }
        }
        return $settings;
    }

    protected function unzipFile($fileInfo)
    {
        $zip = new \ZipArchive;
  
      // Zip File Name
        if ($zip->open($fileInfo["path"]."/".$fileInfo["file"]) === true) {
            // Unzip Path
            //directory is created if it doesnt exist
            $zip->extractTo($this->verticalDirectory->getAbsolutePath(self::UNZIPPED_DIR));
            $zip->close();
            //is there an unzipped directory the same name as the file? if not return false
            $this->file->deleteFile($fileInfo["path"]."/".$fileInfo["file"]);
            return true;
        } else {
            $this->file->deleteFile($fileInfo["path"]."/".$fileInfo["file"]);
            return false;
        }
    }
}
