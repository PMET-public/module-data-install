<?php
namespace MagentoEse\DataInstall\Controller\Adminhtml\Import;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

//https://inchoo.net/magento-2/file-upload-in-magento-2-store-configuration/
//https://www.mageplaza.com/devdocs/four-steps-to-create-a-custom-form-in-magento-2.html
//*****https://magecomp.com/blog/add-custom-file-upload-control-magento-2/ 


use Magento\Backend\App\Action;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\MediaStorage\Model\File\UploaderFactory;
use MagentoEse\DataInstall\Model\Queue\ScheduleBulk;
 
class Index extends Action
{
    protected $fileSystem;
 
    protected $uploaderFactory;
 
    protected $allowedExtensions = ['csv']; // to allow file upload types 
 
    protected $fileId = 'file'; // name of the input file box  
    
    /** @var \ZipArchive */
    protected $zip;

    /** @var ScheduleBulk */
    protected $scheduleBulk;

    public function __construct(
        Action\Context $context,
        Filesystem $fileSystem,
        UploaderFactory $uploaderFactory,
        ScheduleBulk $scheduleBulk
    ) {
        $this->fileSystem = $fileSystem;
        $this->uploaderFactory = $uploaderFactory;
        $this->scheduleBulk = $scheduleBulk;
        parent::__construct($context);
    }
 
    public function execute()
    {
        $destinationPath = $this->getDestinationPath();
        echo $destinationPath;
       // $this->zip->unpack($destinationPath.'Archive.tar.gz', $destinationPath);
       //**SHOULD THIS BE DONE WITH DI */
        $zip = new \ZipArchive;
  
// Zip File Name
if ($zip->open($destinationPath.'BaseB2cWebsite.zip') === TRUE) {
  
    // Unzip Path
    //directory is created if it doesnt exist
    $zip->extractTo($destinationPath.'tmp-archive');
    $zip->close();
    echo 'Unzipped Process Successful!';
} else {
    echo 'Unzipped Process failed';
}
        $operation = [];
        $operation['fileSource']='var/tmp/tmp-archive/BaseB2cWebsite';
        $operation['load']=[];
        $operation['fileOrder']='';
        $operation['reload']='1';
        $this->scheduleBulk->execute([$operation]);

 
        // try {
        //     $uploader = $this->uploaderFactory->create(['fileId' => $this->fileId])
        //         ->setAllowCreateFolders(true)
        //         ->setAllowedExtensions($this->allowedExtensions)
        //         ->addValidateCallback('validate', $this, 'validateFile');
        //     if (!$uploader->save($destinationPath)) {
        //         throw new LocalizedException(
        //             __('File cannot be saved to path: $1', $destinationPath)
        //         );
        //     }
 

        // process the uploaded file
        //unzip
        //validate
            //is there a data directory
            //what are the subdirectories?
            //can we return the default setting if its there
        //create job
        //*** Job processing....clean up file */
        // } catch (\Exception $e) {
        //     $this->messageManager->addError(
        //         __($e->getMessage())
        //     );
        // }
    }
    
    public function validateFile($filePath)
    {
        // @todo
        // your custom validation code here
    }
 
    public function getDestinationPath()
    {
        return $this->fileSystem
            ->getDirectoryWrite(DirectoryList::TMP)
            ->getAbsolutePath('/');
    }
}