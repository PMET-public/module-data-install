<?php


namespace MagentoEse\DataInstall\Model;

use Magento\Framework\Setup\SampleData\Context as SampleDataContext;
use Magento\Framework\Setup\SampleData\FixtureManager;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\Filesystem\Directory\ReadInterface;

class CopyMedia
{

    /** @var SampleDataContext */
    protected $sampleDataContext;

    /** @var FixtureManager  */
    protected $fixtureManager;

    /** @var Filesystem */
    protected $fileSystem;

    /** @var ReadInterface  */
    protected $directoryRead;

    /** @var WriteInterface  */
    protected $directoryWrite;

    /** @var DirectoryList */
    protected $directoryList;

    protected $directoryMappings=[['from'=>'wysiwyg','to'=>'pub/media/wysiwyg'],['from'=>'logo','to'=>'pub/media/logo/stores'],
        ['from'=>'favicon','to'=>'pub/media/favicon/stores'],//['from'=>'theme','to'=>'app/design/frontend'],
        ['from'=>'template_manager','to'=>'pub/media/.template-manager'],['from'=>'downloadable_products','to'=>'/pub/media/import'],
        ['from'=>'.template-manager','to'=>'pub/media/.template-manager']];


    public function __construct(SampleDataContext $sampleDataContext,Filesystem $fileSystem,DirectoryList $directoryList)
    {
        $this->fixtureManager = $sampleDataContext->getFixtureManager();
        $this->fileSystem = $fileSystem;
        $this->directoryWrite = $fileSystem->getDirectoryWrite(DirectoryList::ROOT);
        $this->directoryRead = $fileSystem->getDirectoryRead(DirectoryList::ROOT);
        $this->directoryList = $directoryList;
    }

    public function moveFiles($moduleName){
        foreach($this->directoryMappings as $nextDirectory){
            $fromName = $this->fixtureManager->getFixture($moduleName . "::" . "media/" . $nextDirectory['from']);
            $toName = $this->directoryList->getRoot()."/".$nextDirectory['to'];
            $this->copyFilesFromTo($fromName,$toName);
        }
    }


    protected function copyFilesFromTo($fromPath, $toPath)
    {
        if($this->directoryRead->isDirectory($fromPath)){
            $files = $this->directoryRead->readRecursively($fromPath);
            foreach ($files as $file) {
                $file = $this->directoryList->getRoot()."/".$file;
                $newFileName = str_replace($fromPath, $toPath, $file);
                if ($this->directoryRead->isFile($file)) {
                    $this->directoryWrite->copyFile($file, $newFileName);
                    //$this->directoryWrite->changePermissions($newFileName, 0660);
                } elseif($this->directoryRead->isDirectory($newFileName)) {
                    //$this->directoryWrite->changePermissions($newFileName, 0755);
                }
            }
        }
    }
}
