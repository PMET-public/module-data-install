<?php
/**
 * Copyright 2022 Adobe, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace MagentoEse\DataInstall\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

interface DataPackInterface extends ExtensibleDataInterface
{
    public const ZIPPED_DIR = 'datapacks/zipfiles';
    public const UNZIPPED_DIR = 'datapacks/unzipped';
    public const UPLOAD_DIR = 'datapacks/upload';
    
    /**
     * Get path of data pack files or module name
     *
     * @return string
     */
    public function getDataPackLocation();

    /**
     * Get path of data pack files or module name
     *
     * @param mixed $location
     */
    public function setDataPackLocation($location);

    /**
     * Get path of image files pack
     *
     * @return string
     */
    public function getImagePackLocation();

    /**
     * Get path of image files pack
     *
     * @param mixed $location
     */
    public function setImagePackLocation($location);

    /**
     * Set data directory to load
     *
     * @param string $dataDirectory
     */
    public function setLoad($dataDirectory);

    /**
     * Get data directory
     *
     * @return string
     */
    public function getLoad();

    /**
     * Set list of files to load
     *
     * @param array $files
     */
    public function setFiles($files);

    /**
     * Get list of files to load
     *
     * @return array
     */
    public function getFiles();

    /**
     * Set host to define in base url
     *
     * @param string $host
     */
    public function setHost($host);

    /**
     * Get host to defined for base url
     *
     * @return string
     */
    public function getHost();
    
    /**
     * Get reload flag
     *
     * @return int
     */
    public function getReload();
    
    /**
     * Set reload flag
     *
     * @param  int $reload
     * @return int
     */
    public function setReload($reload);

     /**
      * Get is remote flag
      *
      * @return boolean
      */
    public function getIsRemote();
    
    /**
     * Set is remote flag
     *
     * @param  boolean $isRemote
     * @return boolean
     */
    public function setIsRemote($isRemote);

    /**
     * Get Auth Token
     *
     * @return string
     */
    public function getAuthToken();

    /**
     * Set name/path of data module
     *
     * @param string $token
     * @return void
     */
    public function setAuthToken($token);

     /**
      * Get job id if pack has been imported as job
      *
      * @return string
      */
    public function getJobId();

    /**
     * Set job id if pack has been imported as job
     *
     * @param string $jobId
     * @return void
     */
    public function setJobId($jobId);

    /**
     * Get a remote data pack
     *
     * @param string $url
     * @param string $token
     * @return array
     */
    public function getRemoteDataPack($url, $token);

    /**
     * Unzip a data pack file
     *
     * @return void
     */
    public function unZipDataPack();

    /**
     * Unzip a image pack file
     *
     * @return void
     */
    public function unZipImagePack();

    /**
     * Merge Data Packs
     *
     * Used to copy data from one data pack to another in the case of separate image and data uploads
     *
     * @param string $source
     * @param string $destination
     * @return void
     */
    public function mergeDataPacks($source, $destination);

    /**
     * Is media included in data pack
     *
     * @return boolean
     */
    public function isMediaIncluded();

    /**
     * Get Make Default Website
     *
     * @return string
     */
    public function getIsDefaultWebsite();

    /**
     * Set Website as default
     *
     * @param string $makeDefault
     * @return void
     */
    public function setIsDefaultWebsite($makeDefault);

    /**
     * Get Override flag
     *
     * @return boolean
     */
    public function getIsOverride();

    /**
     * Set Override flag
     *
     * @param boolean $isOverride
     * @return void
     */
    public function setIsOverride($isOverride);

    /**
     * Set Site Code
     *
     * @param string $siteCode
     * @return void
     */
    public function setSiteCode($siteCode);

    /**
     * Get Site Code
     *
     * @return string
     */
    public function getSiteCode();

    /**
     * Set Site Name
     *
     * @param string $siteName
     * @return void
     */
    public function setSiteName($siteName);

    /**
     * Get Site Name
     *
     * @return string
     */
    public function getSiteName();

    /**
     * Set Store Code
     *
     * @param string $storeCode
     * @return void
     */
    public function setStoreCode($storeCode);

    /**
     * Get Store Code
     *
     * @return string
     */
    public function getStoreCode();

    /**
     * Set Store Name
     *
     * @param string $storeName
     * @return void
     */
    public function setStoreName($storeName);
    
    /**
     * Get Store Name
     *
     * @return string
     */
    public function getStoreName();

    /**
     * Set Store View Code
     *
     * @param string $storeViewCode
     * @return void
     */
    public function setStoreViewCode($storeViewCode);

    /**
     * Get Store View Code
     *
     * @return string
     */
    public function getStoreViewCode();

    /**
     * Set Store View Name
     *
     * @param string $storeViewName
     * @return void
     */
    public function setStoreViewName($storeViewName);
    
    /**
     * Get Store View Name
     *
     * @return string
     */
    
    public function getStoreViewName();
     /**
      * Return true if source files are to be deleted after import
      *
      * @return boolean
      */
    public function deleteSourceFiles();

    /**
     * Set true if source files are to be deleted after import
     *
     * @param boolean $deleteSourceFiles
     * @return void
     */
    public function setDeleteSourceFiles($deleteSourceFiles);

    
    /**
     * Should the products be restricted from other store views
     * 
     * @return boolean 
     */
    public function restrictProductsFromViews();


    /**
     * Set true if products should be restricted from other store views
     *
     * @param boolean $restrictProductsFromViews
     * @return void
     */
    public function setRestrictProductsFromViews($restrictProductsFromViews);
}
