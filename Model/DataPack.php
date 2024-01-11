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
    protected $imagePackLocation;

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

    /** @var string  */
    protected $isDefaultWebsite;

    /** @var boolean */
    protected $isOverride;

    /** @var string  */
    protected $siteCode;

    /** @var string  */
    protected $siteName;

    /** @var string  */
    protected $storeCode;

    /** @var string  */
    protected $storeName;

    /** @var string  */
    protected $storeViewCode;

    /** @var string  */
    protected $storeViewName;

    /** @var string */
    protected $additionalParameters;

    /** @var string  */
    protected $jobId;

    /** @var boolean  */
    protected $restrictProductsFromViews;

    /** @var Filesystem\Directory\WriteInterface */
    protected $verticalDirectory;

    /** @var File */
    protected $file;

    /** @var ScopeConfigInterface */
    protected $scopeConfig;

    /** @var boolean */
    protected $deleteSourceFiles;

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
        $this->isDefaultWebsite=0;
        $this->jobId='';
        $this->file = $file;
        $this->scopeConfig = $scopeConfigInterface;
        $this->curl = $curl;
        $this->verticalDirectory = $filesystem->getDirectoryWrite(DirectoryList::TMP);
        $this->files = [];
        $this->deleteSourceFiles = false;
        $this->isOverride = false;
        $this->restrictProductsFromViews = false;
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
      * Get path of image pack
      *
      * @return mixed
      */
    public function getImagePackLocation()
    {
        return $this->imagePackLocation;
    }

    /**
     * Get path of image pack
     *
     * @param mixed $location
     */
    public function setImagePackLocation($location)
    {
        $this->imagePackLocation = $location;
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
        foreach ($files as $file) {
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
     * Set to delete source files after import
     *
     * @param bool $deleteSourceFiles
     * @return void
     */
    public function setDeleteSourceFiles($deleteSourceFiles)
    {
        $this->deleteSourceFiles = $deleteSourceFiles;
    }

    /**
     * Delete source files after import
     *
     * @return bool
     */
    public function deleteSourceFiles()
    {
        return $this->deleteSourceFiles;
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
            LocalizedException(__('Data pack could not be retrieved. Check the url, 
            php settings for file size, and necessary authenticatication'));
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

    /**
     * Unzip image pack file
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function unZipImagePack()
    {
        $zip = new \ZipArchive;
        $fileInfo = $this->getImagePackLocation();
        if ($zip->open($fileInfo["path"]."/".$fileInfo["file"]) === true) {
            //directory is created if it doesnt exist
            $zip->extractTo($this->verticalDirectory->getAbsolutePath(self::UNZIPPED_DIR));
            //get name of directory in the zip file and determina absolute path
            $this->setImagePackLocation($this->verticalDirectory->getAbsolutePath(self::UNZIPPED_DIR).'/media');
            $zip->close();
            $this->file->deleteFile($fileInfo["path"]."/".$fileInfo["file"]);
        } else {
            $this->file->deleteFile($fileInfo["path"]."/".$fileInfo["file"]);
            $this->setImagePackLocation(false);
            //return false;
        }
    }
    /**
     * Combine data packs
     *
     * @param string $source
     * @param string $destination
     * @return void
     * @throws FileSystemException
     */
    public function mergeDataPacks($source, $destination)
    {
        $this->cprp($source, $destination);
    }
    
    /**
     * Does the data back have files in a media directory
     *
     * @return boolean
     */
    public function isMediaIncluded()
    {
        $files = $this->getFiles();
        foreach ($files as $file) {
            if (strpos($file, 'media') !== false) {
                return true;
            }
        }
        return false;
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

     /**
      * Set Wbsite to default
      *
      * @param string $makeDefault
      * @return void
      */
    public function setIsDefaultWebsite($makeDefault)
    {
        $this->isDefaultWebsite = $makeDefault;
    }

    /**
     * Get data directory
     *
     * @return string
     */
    public function getIsDefaultWebsite()
    {
        return $this->isDefaultWebsite ;
    }

    /**
     * Recursive Copy
     *
     * @param string $source
     * @param string $destination
     * @return void
     */
    private function cprp($source, $destination)
    {
        if ($this->file->isDirectory($source)) {
            // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
            $dir=opendir($source);
            while ($file=readdir($dir)) {
                if ($file!="." && $file!="..") {
                    if ($this->file->isDirectory($source."/".$file)) {
                        if (!$this->file->isDirectory($destination."/".$file)) {
                            $this->file->createDirectory($destination."/".$file);
                        }
                        $this->cprp($source."/".$file, $destination."/".$file);
                    } else {
                        $this->file->copy($source."/".$file, $destination."/".$file);
                    }
                }
            }
            closedir($dir);
        } else {
            $this->file->copy($source, $destination);
        }
    }

    /**
     * Get Override flag
     *
     * @return boolean
     */
    public function getIsOverride()
    {
        return $this->isOverride;
    }

    /**
     * Set Override flag
     *
     * @param boolean $isOverride
     * @return void
     */
    public function setIsOverride($isOverride)
    {
        $this->isOverride = $isOverride;
    }
    
    /**
     * Get Site Code
     *
     * @return string
     */
    public function getSiteCode()
    {
        return $this->siteCode;
    }

    /**
     * Set Site Code
     *
     * @param string $siteCode
     * @return void
     */
    public function setSiteCode($siteCode)
    {
        $this->siteCode = $siteCode;
    }

    /**
     * Set Site Name
     *
     * @param string $siteName
     * @return void
     */
    public function setSiteName($siteName)
    {
        $this->siteName = $siteName;
    }

    /**
     * Get Site Name
     *
     * @return string
     */
    public function getSiteName()
    {
        return $this->siteName;
    }

    /**
     * Set Store Code
     *
     * @param string $storeCode
     * @return void
     */
    public function setStoreCode($storeCode)
    {
        $this->storeCode = $storeCode;
    }

    /**
     * Get Store Code
     *
     * @return string
     */
    public function getStoreCode()
    {
        return $this->storeCode;
    }

    /**
     * Set Store Name
     *
     * @param string $storeName
     * @return void
     */
    public function setStoreName($storeName)
    {
        $this->storeName = $storeName;
    }

    /**
     * Get Store Name
     *
     * @return string
     */
    public function getStoreName()
    {
        return $this->storeName;
    }

    /**
     * Set Store View Code
     *
     * @param string $storeViewCode
     * @return void
     */
    public function setStoreViewCode($storeViewCode)
    {
        $this->storeViewCode = $storeViewCode;
    }

    /**
     * Get Store View Code
     *
     * @return string
     */
    public function getStoreViewCode()
    {
        return $this->storeViewCode;
    }

    /**
     * Set Store View Name
     *
     * @param string $storeViewName
     * @return void
     */
    public function setStoreViewName($storeViewName)
    {
        $this->storeViewName = $storeViewName;
    }

    /**
     * Get Store View Name
     *
     * @return string
     */
    public function getStoreViewName()
    {
        return $this->storeViewName;
    }

    /**
     * Set Restrict Products From Store Views
     *
     * @param boolean $restrictProductsFromViews
     * @return void
     */
    public function setRestrictProductsFromViews($restrictProductsFromViews)
    {
        $this->restrictProductsFromViews = $restrictProductsFromViews;
    }

    /**
     * Get Restrict Products From Store Views
     *
     * @return boolean
     */
    public function restrictProductsFromViews()
    {
        return $this->restrictProductsFromViews;
    }

    /**
     * Set Additional Parameters
     *
     * @param string $additionalParameters
     * @return void
     */
    public function setAdditionalParameters($additionalParameters)
    {
        $this->additionalParameters = $additionalParameters;
    }

    /**
     * Get Additional Parameters
     *
     * @return string
     */

    public function getAdditionalParameters()
    {
        return $this->additionalParameters;
    }

    /**
     * Convert Data Pack to Array
     *
     * @return array
     */
    public function convertDataPackToArray()
    {
        $dataPackArray = [];
        $dataPackArray['location'] = $this->getDataPackLocation();
        $dataPackArray['imagePackLocation'] = $this->getImagePackLocation();
        $dataPackArray['load'] = $this->getLoad();
        $dataPackArray['files'] = $this->getFiles();
        $dataPackArray['host'] = $this->getHost();
        $dataPackArray['reload'] = $this->getReload();
        $dataPackArray['isRemote'] = $this->getIsRemote();
        $dataPackArray['authToken'] = $this->getAuthToken();
        $dataPackArray['isDefaultWebsite'] = $this->getIsDefaultWebsite();
        $dataPackArray['isOverride'] = $this->getIsOverride();
        $dataPackArray['siteCode'] = $this->getSiteCode();
        $dataPackArray['siteName'] = $this->getSiteName();
        $dataPackArray['storeCode'] = $this->getStoreCode();
        $dataPackArray['storeName'] = $this->getStoreName();
        $dataPackArray['storeViewCode'] = $this->getStoreViewCode();
        $dataPackArray['storeViewName'] = $this->getStoreViewName();
        $dataPackArray['restrictProductsFromViews'] = $this->restrictProductsFromViews();
        $dataPackArray['additionalParameters'] = $this->getAdditionalParameters();
        $dataPackArray['jobId'] = $this->getJobId();
        return $dataPackArray;
    }
}
