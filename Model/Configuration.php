<?php


namespace MagentoEse\DataInstall\Model;

use Magento\Config\Model\ResourceModel\Config as ResourceConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;


class Configuration
{

   /** @var ResourceConfig  */
    protected $resourceConfig;

    /** @var Stores  */
    protected $stores;

    /** @var ScopeConfigInterface  */
    protected $scopeConfig;

    //$scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeId = 0
    public function __construct(ResourceConfig $resourceConfig, Stores $stores,
                                ScopeConfigInterface $scopeConfig)
    {
        $this->resourceConfig = $resourceConfig;
        $this->stores = $stores;
        $this->scopeConfig = $scopeConfig;
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
        //TODO: Validate json
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

}
