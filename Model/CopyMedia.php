<?php

/**
 * Copyright © Adobe, Inc. All rights reserved.
 */

namespace MagentoEse\DataInstall\Model;

use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Setup\SampleData\Context as SampleDataContext;
use Magento\Framework\Setup\SampleData\FixtureManager;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\Filesystem\Directory\ReadInterface;
use Magento\Framework\File\Mime;
use MagentoEse\DataInstall\Helper\Helper;

class CopyMedia
{
    /** @var string[][]  */
    protected $directoryMappings=[['from'=>'wysiwyg','to'=>'pub/media/wysiwyg','type'=>'image'],
        ['from'=>'logo','to'=>'pub/media/logo/stores','type'=>'image'],
        ['from'=>'email','to'=>'pub/media/email/logo/stores','type'=>'image'],
        ['from'=>'favicon','to'=>'pub/media/favicon/stores','type'=>'image'],
        ['from'=>'theme','to'=>'app/design/frontend','type'=>'theme'],
        ['from'=>'template_manager','to'=>'pub/media/.template-manager','type'=>'image'],
        ['from'=>'downloadable_products','to'=>'pub/media/import','type'=>'download'],
        ['from'=>'.template-manager','to'=>'pub/media/.template-manager','type'=>'image']];

    /** @var string[]  */
    protected $allowedImageFiles = [ 'jpg' => 'image/jpeg','png' => 'image/png', 'jpeg' => 'image/jpeg',
        'gif'  => 'image/gif','jpe' => 'image/jpeg', 'bmp'  => 'image/bmp', 'svg' => 'image/svg+xml',
        'svgz' => 'image/svg+xml','md'=>'application/octet-stream|text/plain',
        'ico'=>'image/vnd.microsoft.icon|image/x-icon|image/png'];

    /** @var string[]  */
    protected $allowedDownloadableFiles = ['pdf'  => 'application/pdf', 'mp3'  => 'audio/mpeg',
        'qt'   => 'video/quicktime', 'mov'  => 'video/quicktime','txt'  => 'text/plain',
        'csv'  => 'text/plain',  'psd'  => 'image/vnd.adobe.photoshop', 'ai'   => 'application/postscript',
        'eps'  => 'application/postscript'];

    /** @var string[]  */
    protected $allowedThemeFiles = ['xml'=>'application/xml','less'=>'text/plain','phtml'=>'text/html|text/x-php',
        'css'=>'text/html','md'=>'application/octet-stream|text/plain','json'=>'application/json','csv'=>'text/plain',
        'php'=>'text/html|text/x-php','eot'=>'application/vnd.ms-fontobject','svg'=>'image/svg+xml',
        'woff'=>'application/octet-stream','woff2'=>'application/octet-stream','ttf'=>'application/font-sfnt',
        'txt'=>'text/plain'];

    /** @var Helper */
    protected $helper;

    /** @var SampleDataContext */
    protected $sampleDataContext;

    /** @var FixtureManager  */
    protected $fixtureManager;

    /** @var Filesystem */
    protected $fileSystem;

    /** @var WriteInterface  */
    protected $directoryWrite;

    /** @var ReadInterface  */
    protected $directoryRead;

    /** @var DirectoryList */
    protected $directoryList;

    /** @var Mime */
    protected $fileMime;

    /**
     * CopyMedia constructor.
     * @param Helper $helper
     * @param SampleDataContext $sampleDataContext
     * @param Filesystem $fileSystem
     * @param DirectoryList $directoryList
     * @param Mime $fileMime
     * @throws FileSystemException
     */
    public function __construct(
        Helper $helper,
        SampleDataContext $sampleDataContext,
        Filesystem $fileSystem,
        DirectoryList $directoryList,
        Mime $fileMime
    ) {
        $this->helper = $helper;
        $this->fixtureManager = $sampleDataContext->getFixtureManager();
        $this->fileSystem = $fileSystem;
        $this->directoryWrite = $fileSystem->getDirectoryWrite(DirectoryList::ROOT);
        $this->directoryRead = $fileSystem->getDirectoryRead(DirectoryList::ROOT);
        $this->directoryList = $directoryList;
        $this->fileMime = $fileMime;
    }

    /**
     * @param $filePath
     */
    public function moveFiles($filePath)
    {
        foreach ($this->directoryMappings as $nextDirectory) {
            $fromName = $filePath . "media/" . $nextDirectory['from'];
            $toName = $this->directoryList->getRoot()."/".$nextDirectory['to'];
            $this->copyFilesFromTo($fromName, $toName, $nextDirectory['type']);
        }
    }

    /**
     * @param $fromPath
     * @param $toPath
     * @param $fileType
     */
    protected function copyFilesFromTo($fromPath, $toPath, $fileType)
    {
        if ($this->directoryRead->isDirectory($fromPath)) {
            $files = $this->directoryRead->readRecursively($fromPath);
            foreach ($files as $file) {
                $file = $this->directoryList->getRoot()."/".$file;
                //validate file against type and extension
                if ($this->validateFile($file, $fileType)) {
                    $newFileName = str_replace($fromPath, $toPath, $file);
                    if ($this->directoryRead->isFile($file)) {
                        try {
                            $this->directoryWrite->copyFile($file, $newFileName);
                        } catch (FileSystemException $exception) {
                            $this->helper->logMessage(
                                "Unable to copy file ".$file. " --- ".$exception->getMessage(),
                                "warning"
                            );
                        }
                    }
                } else {
                    $this->helper->logMessage($file." is an invalid type and was not copied", "warning");
                }
            }
        }
    }

    /**
     * @param $file
     * @param $fileType
     * @return bool
     * @throws FileSystemException
     */
    private function validateFile($file, $fileType)
    {
        $validFiles = [];
        if ($this->fileMime->getMimeType($file)=='directory') {
            return true;
        }
        switch ($fileType) {
            case "image":
                $validFiles = $this->allowedImageFiles;
                break;

            case "theme":
                $validFiles = array_merge($this->allowedImageFiles, $this->allowedThemeFiles);
                break;

            case "download":
                $validFiles = array_merge($this->allowedImageFiles, $this->allowedDownloadableFiles);
                break;
        }
        foreach ($validFiles as $extension => $type) {
            $fileExtension=$this->getFileExtension($file);
            $fileType=$this->fileMime->getMimeType($file);
            $pos = strpos($type, $this->fileMime->getMimeType($file));
            if ($extension == $this->getFileExtension($file)) {
                // phpcs:ignore Magento2.PHP.ReturnValueCheck.ImproperValueTesting
                if (is_integer(strpos($type, $this->fileMime->getMimeType($file)))) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @param string $file
     * @return string
     */
    private function getFileExtension(string $file): string
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction.DiscouragedWithAlternative
        return strtolower(pathinfo($file, 4));
    }
}
