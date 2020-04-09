<?php


namespace MagentoEse\DataInstall\Model;

use Magento\Config\Model\ResourceModel\Config as ResourceConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;


class Configuration
{

    /** Default install site/store/view values  **/
    protected $defaultWebsiteCode = 'base';

    protected $defaultStoreCode = 'main_website_store';

    protected $defaultViewCode = 'default';

    protected $defaultRootCategory = 'Default Category';

    protected $defaultRootCategoryId = 2;

    /** @var ResourceConfig  */
    protected $resourceConfig;

    /** @var Stores  */
    protected $stores;

    //$scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeId = 0
    public function __construct(ResourceConfig $resourceConfig, Stores $stores)
    {
        $this->resourceConfig = $resourceConfig;
        $this->stores = $stores;
    }

    public function install(array $row){
        if(!empty($row['path']) && !empty($row['value'])){
            $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
            $scopeId = 0;
            if (!empty($row['scope'])) {
                $scope = $row['scope'];
            }
            if (!empty($row['scope_code'])) {
                //TODO: look up scope id from scope_code for store and site
                //TODO: handle encrypt flag
                $scopeId = $row['scope_code'];
            }
            $this->saveConfig($row['path'],$row['value'],$scope, $scopeId);
        }

        return true;
    }

    public function installJson($json){
        $config = json_decode($json)->custom_demo->configuration;
        foreach($config as $key=>$item){
            array_walk_recursive($item, array($this,'getValuePath'),$key);
           // print_r($setting);
        }
        //print_r($config);
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
                    if(!empty($item->scope_code)){
                        if($item->scope_code=='websites'){
                            $scopeCode = $item->scope_code;
                            $scopeId = $this->stores->getWebsiteId($item->scope_code);
                        }elseif($item->scope_code=='stores'){
                            $scopeCode = $item->scope_code;
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



    private function saveConfig(string $path, string $value, string $scope, int $scopeId){
        $this->resourceConfig->saveConfig($path, $value, $scope, $scopeId);
    }


    /**
     * @return string
     */
    public function getDefaultRootCategory(): string
    {
        return $this->defaultRootCategory;
    }

    /**
     * @return string
     */
    public function getDefaultStoreCode(): string
    {
        return $this->defaultStoreCode;
    }

    /**
     * @return string
     */
    public function getDefaultWebsiteCode(): string
    {
        return $this->defaultWebsiteCode;
    }

    /**
     * @return int
     */
    public function getDefaultRootCategoryId(): int
    {
        return $this->defaultRootCategoryId;
    }

    /**
     * @return string
     */
    public function getDefaultViewCode(): string
    {
        return $this->defaultViewCode;
    }
}
