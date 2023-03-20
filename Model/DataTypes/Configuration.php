<?php
/**
 * Copyright 2022 Adobe, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace MagentoEse\DataInstall\Model\DataTypes;

use Magento\Config\Model\ResourceModel\Config as ResourceConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Theme\Model\ResourceModel\Theme\Collection as ThemeCollection;
use Magento\Theme\Model\Theme\Registration as ThemeRegistration;
use MagentoEse\DataInstall\Helper\Helper;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\Filesystem\Directory\ReadInterface;
use Magento\Framework\Exception\FileSystemException;

class Configuration
{

    /** @var Helper */
    protected $helper;

   /** @var ResourceConfig  */
    protected $resourceConfig;

    /** @var Stores  */
    protected $stores;

    /** @var ScopeConfigInterface  */
    protected $scopeConfig;

    /** @var ThemeCollection */
    protected $themeCollection;

    /** @var ThemeRegistration */
    protected $themeRegistration;

    /** @var EncryptorInterface  */
    protected $encryptor;

    /** @var DirectoryList  */
    protected $directoryList;

    /** @var FileSystem  */
    protected $fileSystem;
    
    /** @var WriteInterface  */
    protected $directoryWrite;

    /** @var ReadInterface  */
    protected $directoryRead;

    /**
     * Configuration constructor
     *
     * @param Helper $helper
     * @param ResourceConfig $resourceConfig
     * @param Stores $stores
     * @param ScopeConfigInterface $scopeConfig
     * @param ThemeCollection $themeCollection
     * @param ThemeRegistration $themeRegistration
     * @param EncryptorInterface $encryptor
     * @param DirectoryList $directoryList
     * @param Filesystem $fileSystem

     *
     */
    public function __construct(
        Helper $helper,
        ResourceConfig $resourceConfig,
        Stores $stores,
        ScopeConfigInterface $scopeConfig,
        ThemeCollection $themeCollection,
        ThemeRegistration $themeRegistration,
        EncryptorInterface $encryptor,
        DirectoryList $directoryList,
        Filesystem $fileSystem
    ) {
        $this->helper = $helper;
        $this->resourceConfig = $resourceConfig;
        $this->stores = $stores;
        $this->scopeConfig = $scopeConfig;
        $this->themeCollection = $themeCollection;
        $this->themeRegistration = $themeRegistration;
        $this->encryptor = $encryptor;
        $this->directoryList = $directoryList;
        $this->fileSystem = $fileSystem;
        $this->directoryWrite = $fileSystem->getDirectoryWrite(DirectoryList::ROOT);
        $this->directoryRead = $fileSystem->getDirectoryRead(DirectoryList::ROOT);
    }

    /**
     * Install
     *
     * @param array $row
     * @param array $settings
     * @return bool
     */
    public function install(array $row, array $settings)
    {
        if (!empty($row['path'])) {
            $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
            $scopeId = "0";
            if (!empty($row['scope'])) {
                $scope = $row['scope'];
            }

            if (!empty($row['scope_code'])) {
                if ($scope=='website' || $scope=='websites') {
                    $scope = 'websites';
                    $row['scope_code'] = $this->stores->replaceBaseWebsiteCode($row['scope_code']);
                    $scopeId = $this->stores->getWebsiteId($row['scope_code']);
                } elseif ($scope=='store' || $scope=='stores') {
                    $scope = 'stores';
                    $scopeId = $this->stores->getViewId($row['scope_code']);
                }
            }

            $this->saveConfig($row['path'], $row['value'], $scope, $scopeId);
        }

        return true;
    }

    /**
     * Install Json
     *
     * @param string $json
     * @param array $settings
     * @return bool
     */
    public function installJson(string $json, array $settings)
    {
        //TODO: Validate json
        try {
            $config = json_decode($json)->configuration;
        } catch (\Exception $e) {
            $this->helper->logMessage("The JSON in your configuration file is invalid", "error");
            return true;
        }

        foreach ($config as $key => $item) {
            array_walk_recursive($item, [$this,'getValuePath'], $key);
        }

        //TODO:set theme - this will be incorporated into the config structure
        //$this->setTheme('MagentoEse/venia',$this->stores->getStoreId($this->stores->getDefaultStoreCode()));
        return true;
    }

    /**
     * Get confibg by value and path
     *
     * @param object $item
     * @param string $key
     * @param string $path
     */
    public function getValuePath($item, string $key, string $path)
    {
        $scopeCode = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
        $scopeId = 0;

        if ($key != 'store_view' && $key != 'website') {
            $path = $path . "/" . $key;
            if (is_object($item)) {
                if (!empty($item->website)) {
                    //TODO: handle encrypt flag
                    foreach ($item->website as $scopeCode => $value) {
                        $scopeId = $this->stores->getWebsiteId($scopeCode);
                        $this->saveConfig($path, $value, 'websites', $scopeId);
                    }
                } elseif (!empty($item->store_view)) {
                    //TODO: handle encrypt flag
                    foreach ($item->store_view as $scopeCode => $value) {
                        $scopeId = $this->stores->getViewId($scopeCode);
                        $this->saveConfig($path, $value, 'stores', $scopeId);
                    }
                } else {
                    array_walk_recursive($item, [$this, 'getValuePath'], $path);
                }
            } else {
                $this->saveConfig($path, $item, $scopeCode, $scopeId);
            }
        }
    }

    /**
     * Save config
     *
     * @param string $path
     * @param string $value
     * @param string $scope
     * @param int $scopeId
     */
    public function saveConfig(string $path, string $value, string $scope, $scopeId)
    {
        if ($scopeId!==null) {
            $adjustedValue = $this->moveLogo($path, $this->setEncryption($value), $scope, $scopeId);
            $this->resourceConfig->saveConfig($path, $adjustedValue, $scope, $scopeId);
        } else {
            $this->helper->logMessage(
                "Error setting configuration " . $path . ". Check your scope codes as the " .
                $scope . " code you used does not exist",
                "error"
            );
        }
    }

    /**
     * Move logo and other images to store/scope subdirectory
     *
     * @param string $path
     * @param string $value
     * @param string $scope
     * @param int $scopeId
     * @return string
     */
    private function moveLogo($path, $value, $scope, $scopeId)
    {
        //split value
        $valueArray = explode('/', $value);
        switch ($path) {
            case "design/header/logo_src":
                $imgDir = 'logo';
                break;
            case "design/head/shortcut_icon":
                $imgDir = 'favicon';
                break;
            default:
                return $value;
        }
        //copy image
        $fromName = $this->directoryList->getRoot().'/pub/media/'.$imgDir.'/'.$value;
        $toName = $this->directoryList->getRoot().'/pub/media/'. $imgDir.'/'.$scope.'/'.$scopeId.'/'.end($valueArray);

        if ($this->directoryRead->isFile($fromName)) {
            try {
                $this->directoryWrite->copyFile($fromName, $toName);
            } catch (FileSystemException $exception) {
                $this->helper->logMessage(
                    "Unable to copy file ".$fromName. " --- ".$exception->getMessage(),
                    "warning"
                );
            }
        }

        return $scope.'/'.$scopeId.'/'.end($valueArray);
    }

    /**
     * Get config
     *
     * @param string $path
     * @param string $scopeType
     * @param string $scopeCode
     * @return mixed
     */
    public function getConfig(string $path, string $scopeType, string $scopeCode)
    {
        return $this->scopeConfig->getValue($path, $scopeType, $scopeCode);
    }

    /**
     * Set theme
     *
     * @param string $themePath
     * @param string $storeCode
     */
    private function setTheme(string $themePath, string $storeCode)
    {
        //make sure theme is registered
        $this->themeRegistration->register();
        $themeId = $this->themeCollection->getThemeByFullPath('frontend/' . $themePath)->getThemeId();
        //set theme
        $this->saveConfig("design/theme/theme_id", $themeId, "stores", $storeCode);
    }

    /**
     * Encrypt value
     *
     * @param string $value
     * @return string
     */
    private function setEncryption(string $value)
    {
        if (preg_match('/encrypt\((.*)\)/', $value)) {
            return $this->encryptor->encrypt(preg_replace('/encrypt\((.*)\)/', '$1', $value));
        } else {
            return $value;
        }
    }
}
