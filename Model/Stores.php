<?php

/**
 * Copyright © Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
//TODO: Catch duplicate new site codes and store codes
namespace MagentoEse\DataInstall\Model;

use Magento\Store\Api\Data\WebsiteInterfaceFactory;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Api\Data\GroupInterfaceFactory;
use Magento\Store\Api\Data\GroupInterface;
use Magento\Store\Api\GroupRepositoryInterface;
use Magento\Store\Api\GroupRepositoryInterfaceFactory;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Model\ResourceModel\Group as GroupResourceModel;
use Magento\Store\Model\ResourceModel\Website as WebsiteResourceModel;
use Magento\Catalog\Api\Data\CategoryInterfaceFactory;
use Magento\Catalog\Api\CategoryRepositoryInterface;

class Stores
{

    /** @var  WebsiteInterfaceFactory */
    protected $websiteInterfaceFactory;

    /** @var WebsiteResourceModel  */
    protected $websiteResourceModel;

    /** @var GroupResourceModel  */
    protected $groupResourceModel;

    /** @var GroupInterfaceFactory  */
    protected $groupInterfaceFactory;

    /** @var GroupRepositoryInterfaceFactory  */
    protected $groupRepository;

    /** @var CategoryInterfaceFactory  */
    protected $categoryInterfaceFactory;

    /** @var CategoryRepositoryInterface  */
    protected $categoryRepository;

    public function __construct(WebsiteInterfaceFactory $websiteInterfaceFactory,
                                WebsiteResourceModel $websiteResourceModel,
                                GroupResourceModel $groupResourceModel,
                                GroupInterfaceFactory $groupInterfaceFactory,
                                GroupRepositoryInterfaceFactory $groupRepository,
                                CategoryInterfaceFactory $categoryInterfaceFactory,
                                CategoryRepositoryInterface $categoryRepository)
    {
        $this->websiteInterfaceFactory = $websiteInterfaceFactory;
        $this->websiteResourceModel = $websiteResourceModel;
        $this->groupResourceModel = $groupResourceModel;
        $this->groupInterfaceFactory = $groupInterfaceFactory;
        $this->groupRepository = $groupRepository;
        $this->categoryInterfaceFactory = $categoryInterfaceFactory;
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * @param array $data
     * @throws \Exception
     */
    public function processStores(array $data){
        //site_code,site_name,site_order,store_code,store_name,store_root_category,is_default_store,view_code,view_name,is_default_view
        echo "--------------------\n";
        echo $data['testname']."\n";
        if(!empty($data['site_code'])){
            //fix site code if its not correct
            $data['site_code'] = $this->validateCode($data['site_code']);
            //echo "-updating site\n";
            $this->setSite($data);
            //if there is no store code, skip store and view
            if(!empty($data['store_code'])){
                //echo "-updating stores\n";
                //fix store code if its not correct
                $data['store_code'] = $this->validateCode($data['store_code']);
                $this->setStore($data);
                //if there is not view code and store code, skip view updates
                if(!empty($data['view_code'])&&!empty($data['store_code'])){
                    echo "-updating views\n";
                    //fix view code if its not correct
                    $data['view_code'] = $this->validateCode($data['view_code']);
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
            /*
             * TODO:Cleanup: make sure there is a default site
             * Make sure each site has a default Group (store)
             * Make sure each Group(store) has a default view (store)*/

        }else{
            echo "site_code column needs to be included with a value\n";
        }
    }

    //site requires name and code
    private function setSite($data){
        //no name,sort order, or default update - we can skip
        if(!empty($data['site_name'])||!empty($data['site_order'])||!empty($data['is_default_site'])) {
            echo $data['site_code']." eligable for add or update\n";
            //load site from the code.
            /** @var WebsiteInterface $website */
            $website = $this->getWebsite($data);
            //if the site exists - update
            if($website->getId()){
                echo "update site ".$data['site_code']."\n";
                if(!empty($data['site_name'])){
                    $website->setName($data['site_name']);
                }
                if(!empty($data['site_order'])){
                    $website->setSortOrder((is_int($data['site_order'])?:0));
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
                    $website->setSortOrder((is_int($data['site_order'])?:0));
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
    //Stores are referred to as groups in code
    private function setStore($data){
        //no name, root category, or isDefault we can skip
        if(!empty($data['store_name'])||!empty($data['store_root_category'])||!empty($data['is_default_store'])) {
            /** @var WebsiteInterface $website */
            $website = $this->getWebsite($data);
            echo $data['store_code']." eligable for add or update\n";
            //load store with the code.
            /** @var GroupInterface $store */
            $store = $this->getStore($data);
            //load or create root category if defined - default to 0
            $rootCategoryId = 2;
            if(!empty($data['store_root_category'])){
                $rootCategoryId = $this->getRootCategoryByName($data);
                if($data['store_root_category']=='rootcatbase'){
                    echo "using existing root cat\n";
                }else{
                   $rootCategoryId = $this->createRootCategory($data);
                }
            }

            //if the store exists - update
            if($store->getId()){
                //update name or isdefault
                if(!empty($data['store_name'])){
                    $store->setName($data['store_name']);
                    $this->groupResourceModel->save($store);
                }
                if(!empty($data['store_root_category'])){
                    $store->setRootCategoryId($rootCategoryId);
                    $this->groupResourceModel->save($store);
                }
                if(!empty($data['is_default_store'])){
                    $website->setDefaultGroupId($store->getId());
                    $this->websiteResourceModel->save($website);
                }

            }elseif(!empty($data['store_name'])){
                //create store, set default and root category
                echo "create store\n";
                if(!empty($data['store_name'])){
                    $store->setName($data['store_name']);
                    $store->setCode($data['store_code']);
                    $store->setRootCategoryId($rootCategoryId);
                    $store->setWebsiteId($website->getId());
                    $this->groupResourceModel->save($store);
                }
                if(!empty($data['is_default_store'])){
                    $website->setDefaultGroupId($store->getId());
                    $this->websiteResourceModel->save($website);
                }

            }else{
                //if the site doesnt exist and the name isn't provided, error out
                echo "store_name and store_root_category column need to be included with a value when creating a store\n";
            }
        }else{
            echo $data['store_code']." skipping store add/update\n";
        }

    }
    //view requires store, name, code
    //Views are referred to stores in code
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
     * @return WebsiteResourceModel
     */
    private function getWebsite($data)
    {
        return  $this->websiteInterfaceFactory->create()->load($data['site_code']);

    }

    private function getStore($data){
        /** @var GroupRepositoryInterface $groupRepository */
        $groupId = -1;
        $groupRepository = $this->groupRepository->create();
        $groups = $groupRepository->getList();
        foreach($groups as $group) {
            if ($group->getCode() == $data['store_code']) {
                $groupId = $group->getId();
                break;
            }
        }
        $store = $this->groupInterfaceFactory->create();
        if($groupId!=-1){
            $store->load($groupId);
        }
        return $store;
    }

    /**
     * @param $code
     * @return string|string[]|null
     */
    private function validateCode($code){
        //Code may only contain letters (a-z), numbers (0-9) or underscore (_), and the first character must be a letter.
        //remove all invalid characters
        $code = preg_replace("/[^A-Za-z0-9_]/", '', $code);
        //if the first character is not a letter, add an "m"
        if(!ctype_alpha($code[0])){
            $code = "m".$code;
        }
        return $code;
    }

    private function createRootCategory($data)
    {
        $data = [
            'parent_id' => 1,
            'name' => $data['store_root_category'],
            'is_active' => 1,
            'is_anchor' => 1,
            'include_in_menu' => 0,
            'position'=>10,
            'store_id'=>0
        ];
        $category = $this->categoryInterfaceFactory->create();
        $category->getDefaultAttributeSetId();
        $category->setData($data)
            ->setPath('1')
            ->setAttributeSetId($category->getDefaultAttributeSetId());
        //using repository save wont generate tree properly
        //$this->categoryRepository->save($category);
        $category->save();
        return $category->getId();
    }

    private function getRootCategoryByName($data){
        $categories = $this->categoryInterfaceFactory->create()
            ->getCollection()
            ->addAttributeToFilter('name',$data['store_root_category'])
            ->addAttributeToFilter('parent_id',1)
            ->addAttributeToSelect(['entity_id']);

        $id = $categories->getFirstItem()->getEntityId();
        return $categories->getFirstItem()->getEntityId();
    }
}
