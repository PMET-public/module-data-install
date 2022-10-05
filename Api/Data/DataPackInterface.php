<?php
/**
 * Copyright © Adobe  All rights reserved.
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
}
