<?php
/**
 * Copyright © Magento. All rights reserved.
 */

namespace MagentoEse\DataInstall\Model\DataTypes;

use Magento\Config\Model\ResourceModel\Config as ResourceConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Theme\Model\ResourceModel\Theme\Collection as ThemeCollection;
use Magento\Theme\Model\Theme\Registration as ThemeRegistration;
use MagentoEse\DataInstall\Helper\Helper;

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

    /**
     * Configuration constructor.
     * @param ResourceConfig $resourceConfig
     * @param Stores $stores
     * @param ScopeConfigInterface $scopeConfig
     * @param ThemeCollection $themeCollection
     * @param ThemeRegistration $themeRegistration
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        Helper $helper,
        ResourceConfig $resourceConfig,
        Stores $stores,
        ScopeConfigInterface $scopeConfig,
        ThemeCollection $themeCollection,
        ThemeRegistration $themeRegistration,
        EncryptorInterface $encryptor
    ) {
        $this->helper = $helper;
        $this->resourceConfig = $resourceConfig;
        $this->stores = $stores;
        $this->scopeConfig = $scopeConfig;
        $this->themeCollection = $themeCollection;
        $this->themeRegistration = $themeRegistration;
        $this->encryptor = $encryptor;
    }

    /**
     * @param array $row
     * @param array $settings
     * @return bool
     */
    public function install(array $row, array $settings)
    {
        //TODO: handle encrypt flag for value
        if (!empty($row['path'])) {
            $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
            $scopeId = "0";
            if (!empty($row['scope'])) {
                $scope = $row['scope'];
            }

            if (!empty($row['scope_code'])) {
                if ($scope=='website' || $scope=='websites') {
                    $scope = 'websites';
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
            $this->helper->printMessage("The JSON in your configuration file is invalid","error");
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
     * @param $item
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
     * @param string $path
     * @param string $value
     * @param string $scope
     * @param $scopeId
     */
    public function saveConfig(string $path, string $value, string $scope, $scopeId)
    {
        if ($scopeId!==null) {
            $this->resourceConfig->saveConfig($path, $this->setEncryption($value), $scope, $scopeId);
        } else {
            $this->helper->printMessage(
                "Error setting configuration " . $path . ". Check your scope codes as the " .
                $scope . " code you used does not exist","error"
            );
        }
    }

    /**
     * @param string $path
     * @param string $scope
     * @param string $scopeCode
     * @return mixed
     */
    public function getConfig(string $path, string $scope, string $scopeCode)
    {
        return $this->scopeConfig->getValue($path, $scope, $scopeCode);
    }

    /**
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
