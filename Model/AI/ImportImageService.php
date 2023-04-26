<?php

namespace MagentoEse\DataInstall\Model\AI;

	use Magento\Framework\App\Filesystem\DirectoryList;
	use Magento\Framework\Filesystem\Io\File;
	
	class ImportImageService
    {
        protected $directoryList;
        protected $file;

        public function __construct(DirectoryList $directoryList,File $file)
        {
            $this->directoryList = $directoryList;
            $this->file = $file;
        }

        public function execute($product, $imageUrl, $visible = false, $imageType = [])
        {
            //$tmpDir = $this->getMediaDirTmpDir();
            $tmpDir = '/var/www/html/var/import/images/';
            $this->file->checkAndCreateFolder($tmpDir);
            //probably need to determine image type to add the proper extension
            //$newFileName = $tmpDir . '/' .$product . '.png';
            $newFileName = $tmpDir .$product . '.png';
            $result = $this->file->read($imageUrl, $newFileName);
            if ($result) {
                return $product . '.png';
            } else{
                return $result;
            }
            
        }

        protected function getMediaDirTmpDir()
        {
            return $this->directoryList->getPath(DirectoryList::MEDIA) . DIRECTORY_SEPARATOR . 'tmp';
        }
    }
