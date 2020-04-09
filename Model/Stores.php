<?php

/**
 * Copyright © Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MagentoEse\DataInstall\Model;

use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterfaceFactory;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\Data\WebsiteInterfaceFactory;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Api\Data\GroupInterfaceFactory;
use Magento\Store\Api\Data\GroupInterface;
use Magento\Store\Api\GroupRepositoryInterface;
use Magento\Store\Api\GroupRepositoryInterfaceFactory;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Model\ResourceModel\Store as StoreResourceModel;
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

    /** @var StoreResourceModel  */
    protected $storeResourceModel;

    /** @var StoreRepositoryInterface  */
    protected $storeRepository;

    /** @var StoreInterfaceFactory  */
    protected $storeInterfaceFactory;

    /** @var Configuration  */
    protected $configuration;

    /**
     * Stores constructor.
     * @param WebsiteInterfaceFactory $websiteInterfaceFactory
     * @param WebsiteResourceModel $websiteResourceModel
     * @param GroupResourceModel $groupResourceModel
     * @param GroupInterfaceFactory $groupInterfaceFactory
     * @param GroupRepositoryInterface $groupRepository
     * @param CategoryInterfaceFactory $categoryInterfaceFactory
     * @param CategoryRepositoryInterface $categoryRepository
     * @param StoreResourceModel $storeResourceModel
     * @param StoreRepositoryInterface $storeRepository
     * @param StoreInterfaceFactory $storeInterfaceFactory
     */

    public function __construct(
        WebsiteInterfaceFactory $websiteInterfaceFactory,
        WebsiteResourceModel $websiteResourceModel,
        GroupResourceModel $groupResourceModel,
        GroupInterfaceFactory $groupInterfaceFactory,
        GroupRepositoryInterface $groupRepository,
        CategoryInterfaceFactory $categoryInterfaceFactory,
        CategoryRepositoryInterface $categoryRepository,
        StoreResourceModel $storeResourceModel,
        StoreRepositoryInterface $storeRepository,
        StoreInterfaceFactory $storeInterfaceFactory,
        Configuration $configuration
    ) {
        $this->websiteInterfaceFactory = $websiteInterfaceFactory;
        $this->websiteResourceModel = $websiteResourceModel;
        $this->groupResourceModel = $groupResourceModel;
        $this->groupInterfaceFactory = $groupInterfaceFactory;
        $this->groupRepository = $groupRepository;
        $this->categoryInterfaceFactory = $categoryInterfaceFactory;
        $this->categoryRepository = $categoryRepository;
        $this->storeResourceModel = $storeResourceModel;
        $this->storeRepository = $storeRepository;
        $this->storeInterfaceFactory = $storeInterfaceFactory;
        $this->configuration = $configuration;
    }

    /**
     * @param array $data
     * @throws \Exception
     */
    public function install(array $data)
    {
        //site_code,site_name,site_order,store_code,store_name,store_root_category,is_default_store,view_code,view_name,is_default_view
        echo "--------------------\n";
        //echo $data['testname']."\n";
        if (!empty($data['site_code'])) {
            //fix site code if its not correct
            $data['site_code'] = $this->validateCode($data['site_code']);
            echo "-updating site\n";
            $website = $this->setSite($data);
            //if there is no store code, skip store and view
            if (!empty($data['store_code'])) {
                echo "-updating stores\n";
                //fix store code if its not correct
                $data['store_code'] = $this->validateCode($data['store_code']);
                $store = $this->setStore($data, $website);
                //if there is not view code and store code, skip view updates
                if (!empty($data['view_code']) && !empty($data['store_code'])) {
                    echo "-updating views\n";
                    //fix view code if its not correct
                    $data['view_code'] = $this->validateCode($data['view_code']);
                    $this->setView($data, $store);
                    //if there is not view code, skip view update
                } else {
                    echo "skipping view updates\n";
                }
            } elseif (!empty($data['view_code']) && empty($data['store_code'])) {
                    echo "store_code is required to update or create a view\n";
            } else {
                echo "skipping store updates\n";
            }

        } else {
            echo "site_code column needs to be included with a value\n";
        }
        return true;
    }

    //site requires name and code

    /**
     * @param $data
     * @return WebsiteInterface|null
     * @throws AlreadyExistsException
     */
    private function setSite($data)
    {
        //load site from the code.
        /** @var WebsiteInterface $website */
        $website = $this->getWebsite($data);
        //no name,sort order, or default update - we can skip
        if (!empty($data['site_name']) || !empty($data['site_order']) || !empty($data['is_default_site'])) {
            echo $data['site_code']." eligible for add or update\n";

            //if the site exists - update
            if ($website->getId()) {
                echo "update site ".$data['site_code']."\n";
                if (!empty($data['site_name'])) {
                    $website->setName($data['site_name']);
                }
                if (!empty($data['site_order'])) {
                    $website->setSortOrder($data['site_order']);
                }
                if (!empty($data['is_default_site'])) {
                    $website->setIsDefault($data['is_default_site']);
                }
                $this->websiteResourceModel->save($website);
                return $website;
            } elseif (!empty($data['site_name'])) {
                //create site
                echo "create site ".$data['site_code']."\n";
                $website->setCode($data['site_code']);
                $website->setName($data['site_name']);
                if (!empty($data['site_order'])) {
                    $website->setSortOrder($data['site_order']);
                }
                if (!empty($data['is_default_site'])) {
                    $website->setIsDefault($data['is_default_site']);
                }
                $this->websiteResourceModel->save($website);
                return $website;
            } else {
                //if the site doesnt exist and the name isn't provided, error out
                echo "site_name column needs to be included with a value when creating a site\n";
                return null;
            }
        } else {
            echo $data['site_code']." skipping site add/update\n";
            return $website;
        }
    }
    //store requires site, name, code, and root category
    //Stores are referred to as groups in code
    /**
     * @param $data
     * @param $website
     * @return GroupInterface|null
     * @throws AlreadyExistsException
     */
    private function setStore($data, $website)
    {
        /** @var GroupInterface $store */
        $store = $this->getStore($data);
        //no name, root category, or isDefault we can skip
        if (!empty($data['store_name']) || !empty($data['store_root_category']) || !empty($data['is_default_store'])) {
            /** @var WebsiteInterface $website */
            //$website = $this->getWebsite($data);
            echo $data['store_code']." eligible for add or update\n";
            //load store with the code.
            /** @var GroupInterface $store */
            //$store = $this->getStore($data);
            //load or create root category if defined - default to 2
            $rootCategoryId = $this->configuration->getDefaultRootCategoryId();
            if (!empty($data['store_root_category'])) {
                $rootCategoryId = $this->getRootCategoryByName($data);
                //echo "requested root cat=".$data['store_root_category']."Id=".$rootCategoryId."\n";
                if (!$rootCategoryId) {
                    $rootCategoryId = $this->createRootCategory($data);
                    echo $data['store_root_category']." root category created\n";
                }
            }

            //if the store exists - update
            if ($store->getId()) {
                //update name or isdefault
                if (!empty($data['store_name'])) {
                    $store->setName($data['store_name']);
                }
                if (!empty($data['store_root_category'])) {
                    $store->setRootCategoryId($rootCategoryId);
                }
                if (!empty($data['is_default_store']) && $data['is_default_store']=='Y') {
                    $website->setDefaultGroupId($store->getId());
                    $this->websiteResourceModel->save($website);
                }

                $this->groupResourceModel->save($store);
                echo $data['store_code']." store updated\n";
                return $store;
            } elseif (!empty($data['store_name'])) {
                //create store, set default and root category
                echo "create store\n";
                if (!empty($data['store_name'])) {
                    $store->setName($data['store_name']);
                    $store->setCode($data['store_code']);
                    $store->setRootCategoryId($rootCategoryId);
                    $store->setWebsiteId($website->getId());
                    $this->groupResourceModel->save($store);
                }
                if (!empty($data['is_default_store']) && $data['is_default_store']=='Y') {
                    $website->setDefaultGroupId($store->getId());
                    $this->websiteResourceModel->save($website);
                }
                echo $data['store_code']." store created\n";
                return $store;
            } else {
                //if the store doesnt exist and the name isn't provided, error out
                echo "store_name and store_root_category column need to be included
                with a value when creating a store\n";
                return null;
            }
        } else {
            echo $data['store_code']." skipping store add/update\n";
            return $store;
        }
    }
    //view requires store, name, code
    //Views are referred to as stores in code
    /**
     * @param $data
     * @param $store
     * @throws AlreadyExistsException
     */
    private function setView($data, $store)
    {
        //if there is no store or view code we can skip
        if (!empty($data['store_code']) || !empty($data['view_code'])) {

            /** @var WebsiteInterface $website */
            $website = $this->getWebsite($data);
            echo $data['view_code']." view eligible for add or update\n";
            //load View with the code.
            /** @var StoreInterface $store */
            $view = $this->getView($data);

            //if the view exists - update
            if ($view->getId()) {
                //update name, status, order or isdefault
                if (!empty($data['view_name'])) {
                    $view->setName($data['view_name']);
                }
                if (!empty($data['view_order'])) {
                    $view->setSortOrder($data['view_order']);
                }
                if (!empty($data['view_is_active'])) {
                    //dont deactivate if it is the default
                    if ($store->getDefaultStoreId()!=$store->getId()) {
                        $view->setIsActive($data['view_is_active']=='Y'? 1:0);
                    }
                }

                $this->storeResourceModel->save($view);

                if (!empty($data['is_default_view']) && $data['is_default_view']=='Y') {
                    //default needs to be active
                    $view->setIsActive(1);
                    $this->storeResourceModel->save($view);
                    $store->setDefaultStoreId($view->getId());
                    $this->groupResourceModel->save($store);
                }
                echo $data['view_code']." view updated\n";
            } elseif (!empty($data['view_name'])) {
                //create view, set default, status and order
                echo "create view\n";
                if (!empty($data['view_name'])) {
                    $view->setName($data['view_name']);
                    $view->setCode($data['view_code']);
                    $view->setIsActive($data['view_is_active']=='Y'? 1:0);
                    $view->setStoreGroupId($store->getId());
                    $view->setWebsiteId($website->getId());
                    if (!empty($data['view_order'])) {
                        $view->setSortOrder($data['view_order']);
                    }
                    $this->storeResourceModel->save($view);
                }
                if (!empty($data['is_default_view']) && $data['is_default_view']=='Y') {
                    //default needs to be active
                    $view->setIsActive(1);
                    $this->storeResourceModel->save($view);
                    $store->setDefaultStoreId($view->getId());
                    $this->groupResourceModel->save($store);
                }
                echo $data['view_code']." view created\n";
            } else {
                //if the view doesnt exist and the view isn't provided, error out
                echo "view_name needs to be included with a value when creating a view\n";
            }
        } else {
            echo $data['view_code']." skipping view add/update\n";
        }
    }

    /**
     * @param $data
     * @return WebsiteInterface
     */
    private function getWebsite($data)
    {
        return  $this->websiteInterfaceFactory->create()->load($data['site_code']);
    }

    /**
     * @param $websiteCode
     * @return int
     */
    public function getWebsiteId($websiteCode){
        $data = ['site_code'=>$websiteCode];
        $website = $this->getWebsite($data);
        return  $website->getId();
    }

    /**
     * @param $data
     * @return GroupInterface
     */
    private function getStore($data)
    {
        /** @var GroupRepositoryInterface $groupRepository */
        $groupId = -1;
        //$groupRepository = $this->groupRepository->create();
        $groups = $this->groupRepository->getList();
        foreach ($groups as $group) {
            if ($group->getCode() == $data['store_code']) {
                $groupId = $group->getId();
                break;
            }
        }
        $store = $this->groupInterfaceFactory->create();
        if ($groupId!=-1) {
            $store->load($groupId);
        }
        return $store;
    }

    /**
     * @param $storeCode
     * @return int
     */
    public function getStoreId($storeCode){
        $data = ['store_code'=>$storeCode];
        $store = $this->getStore($data);
        return  $store->getId();
    }

    /**
     * @param $data
     * @return StoreInterface
     */
    private function getView($data)
    {
        try {
            $view = $this->storeRepository->get($data['view_code']);
        } catch (NoSuchEntityException $e) {
            $view = $this->storeInterfaceFactory->create();
        }
        return $view;
    }

    /**
     * @param $viewCode
     * @return int
     */
    public function getViewId($viewCode){
        $data = ['view_code'=>$viewCode];
        $view = $this->getView($data);
        return  $view->getId();
    }

    /**
     * @param $code
     * @return string|string[]|null
     */
    private function validateCode($code)
    {
        /*Code may only contain letters (a-z), numbers (0-9) or underscore (_), and
        the first character must be a letter.*/
        //remove all invalid characters
        $code = preg_replace("/[^A-Za-z0-9_]/", '', $code);
        //if the first character is not a letter, add an "m"
        if (!ctype_alpha($code[0])) {
            $code = "m".$code;
        }
        return $code;
    }

    /**
     * @param $data
     * @return int|null
     */
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

    /**
     * @param $data
     * @return mixed
     */
    private function getRootCategoryByName($data)
    {
        $categories = $this->categoryInterfaceFactory->create()
            ->getCollection()
            ->addAttributeToFilter('name', $data['store_root_category'])
            ->addAttributeToFilter('parent_id', 1)
            ->addAttributeToSelect(['entity_id']);
        $id = $categories->getFirstItem()->getEntityId();
        return $categories->getFirstItem()->getEntityId();
    }
}
