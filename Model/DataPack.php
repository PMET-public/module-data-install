<?php
/**
 * Copyright 2022 Adobe, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace MagentoEse\DataInstall\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use MagentoEse\DataInstall\Api\Data\DataPackInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;

class DataPack implements DataPackInterface
{
    /** @var string  */
    protected $location;

    /** @var string  */
    protected $load;

    /** @var string  */
    protected $files;

    /** @var string  */
    protected $host;

    /** @var string  */
    protected $reload;

    /** @var string  */
    protected $isRemote;

    /** @var string  */
    protected $authToken;

    /** @var Filesystem\Directory\WriteInterface */
    protected $verticalDirectory;

    /** @var File */
    protected $file;

    /** @var ScopeConfigInterface */
    protected $scopeConfig;

    /** @var Curl */
    protected $curl;
    
   /**
    *
    * @param File $file
    * @param ScopeConfigInterface $scopeConfigInterface
    * @param Curl $curl
    * @param Filesystem $filesystem
    * @return void
    * @throws FileSystemException
    */
    public function __construct(
        File $file,
        ScopeConfigInterface $scopeConfigInterface,
        Curl $curl,
        Filesystem $filesystem
    ) {
        $this->reload=0;
        $this->jobId='';
        $this->file = $file;
        $this->scopeConfig = $scopeConfigInterface;
        $this->curl = $curl;
        $this->verticalDirectory = $filesystem->getDirectoryWrite(DirectoryList::TMP);
        $this->files = [];
    }
    
    /**
     * Get path of data pack files or module name
     *
     * @return mixed
     */
    public function getDataPackLocation()
    {
        return $this->location;
    }

    /**
     * Get path of data pack files or module name
     *
     * @param mixed $location
     */
    public function setDataPackLocation($location)
    {
        $this->location = $location;
    }

    /**
     * Set data directory to load
     *
     * @param string $dataDirectory
     * @return void
     */
    public function setLoad($dataDirectory)
    {
        $this->load = $dataDirectory;
    }

    /**
     * Get data directory
     *
     * @return string
     */
    public function getLoad()
    {
        return $this->load;
    }

    /**
     * Set list of files to load
     *
     * @param array $files
     * @return void
     */
    public function setFiles($files)
    {
        //clean up possible spaces introduced in file list
        $cleanFiles = [];
        foreach($files as $file){
            $cleanFiles[] = trim($file);
        }
        $this->files = $cleanFiles;
    }

    /**
     * Get list of files to load
     *
     * @return array
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * Set host to define in base url
     *
     * @param string $host
     * @return void
     */
    public function setHost($host)
    {
        $this->host = $host;
    }

    /**
     * Get host to defined for base url
     *
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

     /**
      * Get reload flag
      *
      * @return int
      */
    public function getReload()
    {
        return $this->reload;
    }
    
    /**
     * Set reload flad
     *
     * @param  int $reload
     * @return void
     */
    public function setReload($reload)
    {
        $this->reload = $reload;
    }

    /**
     * Get remote flag
     *
     * @return bool
     */
    public function getIsRemote()
    {
        return $this->isRemote;
    }

    /**
     * Set flag if location is remote
     *
     * @param bool $isRemote
     * @return bool
     */
    public function setIsRemote($isRemote)
    {
        $this->isRemote = $isRemote;
    }

    /**
     * Get authorization token
     *
     * @return string
     */
    public function getAuthToken()
    {
        return $this->authToken;
    }

    /**
     * Set authorization token
     *
     * @param string $token
     * @return void
     */
    public function setAuthToken($token)
    {
        $this->authToken = $this->getAuthentication($token);
    }

    /**
     * Get job id if pack has been imported as job
     *
     * @return string
     */
    public function getJobId()
    {
        return $this->jobId;
    }

    /**
     * Set job id if pack has been imported as job
     *
     * @param string $jobId
     * @return void
     */
    public function setJobId($jobId)
    {
        $this->jobId = $jobId;
    }

     /**
      * Get a rempote data pack
      *
      * @param string $url
      * @param string $token
      * @return array
      * @throws LocalizedException
      */
    public function getRemoteDataPack($url, $token)
    {
        $filename = uniqid();
        $this->curl->setOption(CURLOPT_URL, $url);
        $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
        $this->curl->setOption(CURLOPT_SSL_VERIFYPEER, true);
        $this->curl->setOption(CURLOPT_HTTPHEADER, ["Authorization: token ".$token]);
        $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
        $this->curl->setOption(CURLOPT_FOLLOWLOCATION, true);
        $this->curl->get($url);
        $result=$this->curl->getBody();
        if ($result=='Not Found') {
            throw new
            LocalizedException(__('Data pack could not be retrieved. Check the url and necessary authenticatication'));
        }

        $f = $this->verticalDirectory->getAbsolutePath(self::ZIPPED_DIR);
        $this->file->createDirectory($this->verticalDirectory->getAbsolutePath(self::ZIPPED_DIR));
        $this->file->filePutContents($this->verticalDirectory->
            getAbsolutePath(DataPackInterface::ZIPPED_DIR).'/'.$filename.'.zip', $result);

        $fileInfo = [
            'name' => $filename.'.zip',
            'full_path' => $filename.'.zip',
            'type' => 'application/zip',
            'path' => $this->verticalDirectory->getAbsolutePath(DataPackInterface::ZIPPED_DIR),
            'file' => $filename.'.zip'
        ];
        return $fileInfo;
    }

     /**
      * Unzip data pack file
      *
      * @return mixed
      * @throws \Magento\Framework\Exception\FileSystemException
      */
    public function unZipDataPack()
    {
        $zip = new \ZipArchive;
        $fileInfo = $this->getDataPackLocation();
        //skip if datapack is not a zip file
        if(!empty($fileinfo["path"]) && !empty($fileinfo["file"])){
            if ($zip->open($fileInfo["path"]."/".$fileInfo["file"]) === true) {
                //directory is created if it doesnt exist
                $zip->extractTo($this->verticalDirectory->getAbsolutePath(self::UNZIPPED_DIR));
                //get name of directory in the zip file and determina absolute path
                $this->setDataPackLocation($this->verticalDirectory->getAbsolutePath(self::UNZIPPED_DIR).'/'.
                str_replace("/", "", $zip->statIndex(0)['name']));
                $zip->close();
                $this->file->deleteFile($fileInfo["path"]."/".$fileInfo["file"]);
            } else {
                $this->file->deleteFile($fileInfo["path"]."/".$fileInfo["file"]);
                $this->setDataPackLocation(false);
                //return false;
            }
        } 
    }
    
    /**
     * Return authentication token. Defaults to github token for now, but can be expanded to support additional methods
     *
     * @param string $token
     * @return mixed
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    protected function getAuthentication($token)
    {
        if ($token != "") {
            return $token;
        } else {
            return $this->scopeConfig->getValue(
                'magentoese/datainstall/github_access_token',
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT
            );
        }
    }
}
