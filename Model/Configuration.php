<?php


namespace MagentoEse\DataInstall\Model;

use Magento\Config\Model\ResourceModel\Config as ResourceConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Theme\Model\ResourceModel\Theme\Collection as ThemeCollection;
use Magento\Theme\Model\Theme\Registration as ThemeRegistration;

class Configuration
{

   /** @var ResourceConfig  */
    protected $resourceConfig;

    /** @var Stores  */
    protected $stores;

    /** @var ScopeConfigInterface  */
    protected $scopeConfig;

    /** @var ThemeCollection */
    private $themeCollection;

    /** @var ThemeRegistration */
    private $themeRegistration;

    public function __construct(ResourceConfig $resourceConfig, Stores $stores,
                                ScopeConfigInterface $scopeConfig,
                                ThemeCollection $themeCollection, ThemeRegistration $themeRegistration)
    {
        $this->resourceConfig = $resourceConfig;
        $this->stores = $stores;
        $this->scopeConfig = $scopeConfig;
        $this->themeCollection = $themeCollection;
        $this->themeRegistration = $themeRegistration;
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
                //TODO: look up scope id from scope_code for store and site
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
        $config = json_decode($json)->custom_demo->configuration;
        foreach($config as $key=>$item){
            array_walk_recursive($item, array($this,'getValuePath'),$key);
           // print_r($setting);
        }
        //print_r($config);
        //set theme - this will be incorporated into the config structure
        //$this->setTheme('MagentoEse/venia',$this->stores->getStoreId($this->stores->getDefaultStoreCode()));
        return true;
    }
    public function getValuePath($item, $key,$path)
    {
        $scopeCode = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
        $scopeId = 0;

        if($key != 'scope' && $key != 'scope_code'){
            $path = $path."/". $key;
            if (is_object($item)) {
                if(!empty($item->value)){
                    //TODO: handle encrypt flag
                    if(!empty($item->scope)){
                        if($item->scope=='websites'||$item->scope=='website'){
                            $scopeCode = $item->scope;
                            $scopeId = $this->stores->getWebsiteId($item->scope_code);
                        }elseif($item->scope=='stores'||$item->scope=='store'){
                            $scopeCode = $item->scope;
                            $scopeId = $this->stores->getViewId($item->scope_code);
                        }
                    }
                    $this->saveConfig($path,  $item->value,  $scopeCode,$scopeId);
                }else{
                    array_walk_recursive($item, array($this, 'getValuePath'),$path);
                }
            }else{
                //echo $path.'----'.$item. "\n";
                $this->saveConfig($path,  $item,  $scopeCode,$scopeId);
            }
        }

    }

    public function saveConfig(string $path, string $value, string $scope, int $scopeId){
        $this->resourceConfig->saveConfig($path, $value, $scope, $scopeId);
    }

    public function getConfig(string $path, string $scope, string $scopeCode){
        return $this->scopeConfig->getValue($path,$scope,$scopeCode);
    }

    private function setTheme($themePath, $storeCode){
        //make sure theme is registered
        $this->themeRegistration->register();
        $themeId = $this->themeCollection->getThemeByFullPath('frontend/'.$themePath)->getThemeId();
        //set theme for Venia store
        $this->saveConfig("design/theme/theme_id", $themeId, "stores", $storeCode);
}

}
