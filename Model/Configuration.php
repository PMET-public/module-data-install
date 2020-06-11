<?php


namespace MagentoEse\DataInstall\Model;

use Magento\Config\Model\ResourceModel\Config as ResourceConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Theme\Model\ResourceModel\Theme\Collection as ThemeCollection;
use Magento\Theme\Model\Theme\Registration as ThemeRegistration;
use Magento\Framework\Encryption\EncryptorInterface;

class Configuration
{

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
    public function __construct(ResourceConfig $resourceConfig, Stores $stores,
                                ScopeConfigInterface $scopeConfig,
                                ThemeCollection $themeCollection,
                                ThemeRegistration $themeRegistration,
                                EncryptorInterface $encryptor)
    {
        $this->resourceConfig = $resourceConfig;
        $this->stores = $stores;
        $this->scopeConfig = $scopeConfig;
        $this->themeCollection = $themeCollection;
        $this->themeRegistration = $themeRegistration;
        $this->encryptor = $encryptor;
    }

    public function install(array $row){
        //TODO: handle encrypt flag for value
        if(!empty($row['path']) && !empty($row['value'])){
            $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
            $scopeId = 0;
            if (!empty($row['scope'])) {
                $scope = $row['scope'];
            }
            if (!empty($row['scope_code'])) {
                if($scope=='website'||$scope=='websites'){
                    $scope = 'websites';
                    $scopeId = $this->stores->getWebsiteId($row['scope_code']);
                }elseif($scope=='store'||$scope=='stores'){
                    $scope = 'stores';
                    $scopeId = $this->stores->getStoreId($row['scope_code']);
                }
            }
            $this->saveConfig($row['path'],$row['value'],$scope, $scopeId);
        }

        return true;
    }

    public function installJson($json){
        //TODO: Validate json
        try{
            $config = json_decode($json)->configuration;
        }catch(\Exception $e){
            echo "The JSON in your configuration file is invalid.\n";
            return true;
        }
        foreach($config as $key=>$item){
            array_walk_recursive($item, array($this,'getValuePath'),$key);
           // print_r($setting);
        }
        //TODO:set theme - this will be incorporated into the config structure
        //$this->setTheme('MagentoEse/venia',$this->stores->getStoreId($this->stores->getDefaultStoreCode()));
        return true;
    }
    public function getValuePath($item, $key,$path)
    {
        $scopeCode = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
        $scopeId = 0;

        if($key != 'store_view' && $key != 'website'){
            $path = $path."/". $key;
            if (is_object($item)) {
                if(!empty($item->website)){
                    //TODO: handle encrypt flag
                    foreach($item->website as $scopeCode=>$value ){
                        $scopeId = $this->stores->getWebsiteId($scopeCode);
                        $this->saveConfig($path, $value,'websites',$scopeId);
                    }

                }
                elseif(!empty($item->store_view)){
                    //TODO: handle encrypt flag
                    foreach($item->store_view as $scopeCode=>$value ){
                        $scopeId = $this->stores->getViewId($scopeCode);
                        $this->saveConfig($path, $value,'stores',$scopeId);
                    }

                }
                else{
                    array_walk_recursive($item, array($this, 'getValuePath'),$path);
                }
            }else{
                $this->saveConfig($path, $item, $scopeCode,$scopeId);
            }
        }

    }

    /**
     * @param string $path
     * @param string $value
     * @param string $scope
     * @param $scopeId
     */
    public function saveConfig(string $path, string $value, string $scope, $scopeId){
        if($scopeId){
            $this->resourceConfig->saveConfig($path, $this->setEncryption($value), $scope, $scopeId);
        }else{
            echo "Error setting configuration. Check your scope codes as a ".$scope. " code does not exist\n";
        }

    }

    /**
     * @param string $path
     * @param string $scope
     * @param string $scopeCode
     * @return mixed
     */
    public function getConfig(string $path, string $scope, string $scopeCode){
        return $this->scopeConfig->getValue($path,$scope,$scopeCode);
    }

    /**
     * @param $themePath
     * @param $storeCode
     */
    private function setTheme($themePath, $storeCode){
        //make sure theme is registered
        $this->themeRegistration->register();
        $themeId = $this->themeCollection->getThemeByFullPath('frontend/'.$themePath)->getThemeId();
        //set theme
        $this->saveConfig("design/theme/theme_id", $themeId, "stores", $storeCode);
    }

    /**
     * @param $value
     * @return string
     */
    private function setEncryption($value){
        if(preg_match('/encrypt\((.*)\)/', $value)){
            return $this->encryptor->encrypt(preg_replace('/encrypt\((.*)\)/', '$1', $value));;
        }else{
            return $value;
        }
    }

}
