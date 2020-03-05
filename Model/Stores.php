<?php

/**
 * Copyright © Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MagentoEse\DataInstall\Model;

use Magento\Store\Api\Data\WebsiteInterfaceFactory;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Api\Data\GroupInterfaceFactory;
use Magento\Store\Api\StoreRepositoryInterfaceFactory;
use Magento\Store\Api\GroupRepositoryInterfaceFactory;
use Magento\Store\Model\ResourceModel\Group;
use Magento\Store\Model\ResourceModel\Website as WebsiteResourceModel;

class Stores
{

    /** @var  WebsiteInterfaceFactory */
    protected $websiteInterfaceFactory;

    /** @var WebsiteResourceModel  */
    protected $websiteResourceModel;

    public function __construct(WebsiteInterfaceFactory $websiteInterfaceFactory, WebsiteResourceModel $websiteResourceModel)
    {
        $this->websiteInterfaceFactory = $websiteInterfaceFactory;
        $this->websiteResourceModel = $websiteResourceModel;

    }

    /**
     * @param array $data
     * @throws \Exception
     */
    public function processStores(array $data){
        //site_code,site_name,site_order,store_code,store_name,store_root_category,is_default_store,view_code,view_name,is_default_view
        echo "--------------------\n";
        echo $data['testname']."\n";
        //TODO:Validate codes
        //TODO:Validate numeric (sort order)
        //TODO:Validate Y/N (is default)
        //TODO:Convert Y/N to true/false
        if(!empty($data['site_code'])){
            //echo "-updating site\n";
            $this->setSite($data);
            //if there is no store code, skip store and view
            if(!empty($data['store_code'])){
                //echo "-updating stores\n";
                $this->setStore($data);
                //if there is not view code and store code, skip view updates
                if(!empty($data['view_code'])&&!empty($data['store_code'])){
                    echo "-updating views\n";
                    $this->setView($data);
                    //if there is not view code, skip view update
                }else{
                    echo "skipping view updates\n";
                }
            }elseif(!empty($data['view_code'])&&empty($data['store_code'])) {
                    echo "store_code is required to update or create a view\n";
            }else{
                echo "skipping store updates\n";
            }

        }else{
            echo "site_code column needs to be included with a value\n";
        }
    }

    //site requires name and code
    private function setSite($data){
        //site_code, site_name, site_order, is_default_site
        //no name,sort order, or default update - we can skip
        if(!empty($data['site_name'])||!empty($data['site_order'])||!empty($data['is_default_site'])) {
            echo $data['site_code']." eligable for add or update\n";
            //load site from the code.
            $website = $this->getWebsite($data);
            //if the site exists - update
            if($website->getId()){
                echo "update site ".$data['site_code']."\n";
                if(!empty($data['site_name'])){
                    $website->setName($data['site_name']);
                }
                if(!empty($data['site_order'])){
                    $website->setSortOrder($data['site_order']);
                }
                if(!empty($data['is_default_site'])){
                    $website->setIsDefault($data['is_default_site']);
                }
                $this->websiteResourceModel->save($website);
            }elseif(!empty($data['site_name'])){
                //create site
                echo "create site\n";
                $website->setCode($data['site_code']);
                $website->setName($data['site_name']);
                if(!empty($data['site_order'])){
                    $website->setSortOrder($data['site_order']);
                }
                if(!empty($data['is_default_site'])){
                    $website->setIsDefault($data['is_default_site']);
                }
                $this->websiteResourceModel->save($website);
            }else{
                //if the site doesnt exist and the name isn't provided, error out
                echo "site_name column needs to be included with a value when creating a site\n";
            }
        }else{
            echo $data['site_code']." skipping site add/update\n";
        }
    }
    //store requires site, name, code, and root category
    private function setStore($data){
        //$siteCode,$storeCode,$storeName,$rootCategoryName,$isDefaultStore
        //no name, root category, or isDefault we can skip
        if(!empty($data['store_name'])||!empty($data['store_root_category'])||!empty($data['is_default_store'])) {
            echo $data['store_code']." eligable for add or update\n";
            //load store with the code.
            if($data['store_code']=='storebase'){
                $store = true;
            }else{
                $store = false;
            }
            //load or create root category if defined
            //TODO:This may be able to happen after creation
            if(!empty($data['store_root_category'])){
                if($data['store_root_category']=='rootcatbase'){
                    echo "using existing root cat\n";
                }else{
                    echo "creating new root cat\n";
                }
            }


            //if the store exists - update
            if($store){
                echo "update store ".$data['store_code']."\n";
                //update name or isdefault
            }elseif(!empty($data['site_name'])||!empty($data['store_root_category'])){
                //create store, set default and root category
                //TODO:if this is the only store will it be set to the default for the site?
                echo "create store\n";
            }else{
                //if the site doesnt exist and the name isn't provided, error out
                echo "store_name and store_root_category column need to be included with a value when creating a store\n";
            }
        }else{
            echo $data['store_code']." skipping store add/update\n";
        }

    }
    //view requires store, name, code
    private function setView($data){
        //$storeCode,$viewName,$viewCode,$viewStatus,$viewSortOrder,$isDefaultView
        //cannot do a wholesale skip as we may be assigning the view to a different store
        //load view with the code.
        if($data['view_code']=='viewbase'){
            $view = true;
        }else{
            $view = false;
        }
        //if the view exists - update
        if($view){
            echo "update view ".$data['view_code']."\n";
        }elseif(!empty($data['view_name'])){
            //create site
            //TODO:verify that view order and status is set by default
            echo "create view\n";
        }else{
            //if the site doesnt exist and the name isn't provided, error out
            echo "view_name column needs to be included with a value when creating a view\n";
        }
    }

    /**
     * @param $data
     * @return WebsiteInterface
     */
    private function getWebsite($data)
    {
        /** @var WebsiteInterface $website */
        $website = $this->websiteInterfaceFactory->create()->load($data['site_code']);
        return $website;
    }

    private function validateCode($code){
        //Code may only contain letters (a-z), numbers (0-9) or underscore (_), and the first character must be a letter.
    }
}
