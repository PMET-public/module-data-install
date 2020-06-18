<?php
/**
 * Copyright © Magento. All rights reserved.
 */
namespace MagentoEse\DataInstall\Model;

use Magento\Framework\App\Area as AppArea;
use Magento\Framework\App\State;
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
use Magento\Config\Model\ResourceModel\Config as ResourceConfig;
use Magento\UrlRewrite\Model\Exception\UrlAlreadyExistsException;
use Magento\UrlRewrite\Model\UrlPersistInterface;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\CmsUrlRewrite\Model\CmsPageUrlRewriteGenerator;
use Magento\Framework\Api\SearchCriteriaBuilder;

class Stores
{
    protected $settings;

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

    /** @var ResourceConfig  */
    protected $configuration;

    /** @var UrlPersistInterface  */
    protected $urlPersist;

    /** @var SearchCriteriaBuilder  */
    protected $searchCriteriaBuilder;

    /** @var PageRepositoryInterface  */
    protected $pageRepository;

    /** @var CmsPageUrlRewriteGenerator  */
    protected $cmsPageUrlRewriteGenerator;

    /** @var State  */
    protected $appState;

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
     * @param ResourceConfig $configuration
     * @param UrlPersistInterface $urlPersist
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param PageRepositoryInterface $pageRepository
     * @param CmsPageUrlRewriteGenerator $cmsPageUrlRewriteGenerator
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
        ResourceConfig $configuration,
        UrlPersistInterface $urlPersist,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        PageRepositoryInterface $pageRepository,
        CmsPageUrlRewriteGenerator $cmsPageUrlRewriteGenerator,
        State $appState
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
        $this->urlPersist = $urlPersist;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->pageRepository = $pageRepository;
        $this->cmsPageUrlRewriteGenerator = $cmsPageUrlRewriteGenerator;
        $this->appState = $appState;
    }

    /**
     * @param array $data
     * @param array $settings
     * @return bool
     * @throws AlreadyExistsException
     */
    public function install(array $data, array $settings)
    {
        $this->settings = $settings;
        print_r("--------------------\n");
        if (!empty($data['site_code'])) {
            //fix site code if its not correct
            $data['site_code'] = $this->validateCode($data['site_code']);
            print_r("-updating site\n");
            $website = $this->setSite($data);
            //if there is a host value, set base urls
            if (!empty($data['host'])) {
                $this->setBaseUrls($data['host'], $website->getId());
            }
            //if there is no store code, skip store and view
            if (!empty($data['store_code'])) {
                print_r("-updating stores\n)");
                //fix store code if its not correct
                $data['store_code'] = $this->validateCode($data['store_code']);
                $store = $this->setStore($data, $website);
                //if there is not view code and store code, skip view updates
                if (!empty($data['view_code']) && !empty($data['store_code'])) {
                    print_r("-updating views\n");
                    //fix view code if its not correct
                    $data['view_code'] = $this->validateCode($data['view_code']);
                    $this->setView($data, $store);
                    //if there is not view code, skip view update
                } else {
                    print_r("skipping view updates\n");
                }
            } elseif (!empty($data['view_code']) && empty($data['store_code'])) {
                    print_r("store_code is required to update or create a view\n");
            } else {
                print_r("skipping store updates\n");
            }

        } else {
            print_r("site_code column needs to be included with a value\n");
        }
        return true;
    }

    //site requires name and code

    /**
     * @param array $data
     * @return WebsiteInterface|null
     * @throws AlreadyExistsException
     */
    private function setSite(array $data)
    {
        //load site from the code.
        /** @var WebsiteInterface $website */
        $website = $this->getWebsite($data);
        //no name,sort order, or default update - we can skip
        if (!empty($data['site_name']) || !empty($data['site_order']) || !empty($data['is_default_site'])) {
            print_r($data['site_code']." eligible for add or update\n");

            //if the site exists - update
            if ($website->getId()) {
                print_r("update site ".$data['site_code']."\n");
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
                print_r("create site ".$data['site_code']."\n");
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
                print_r("site_name column needs to be included with a value when creating a site\n");
                return null;
            }
        } else {
            print_r($data['site_code']." skipping site add/update\n");
            return $website;
        }
    }
    //store requires site, name, code, and root category
    //Stores are referred to as groups in code
    /**
     * @param array $data
     * @param WebsiteInterface $website
     * @return GroupInterface|null
     * @throws AlreadyExistsException
     */
    private function setStore(array $data, WebsiteInterface $website)
    {
        /** @var GroupInterface $store */
        $store = $this->getStore($data);
        //no name, root category, or isDefault we can skip
        if (!empty($data['store_name']) || !empty($data['store_root_category']) || !empty($data['is_default_store'])) {
            /** @var WebsiteInterface $website */
            //$website = $this->getWebsite($data);
            print_r($data['store_code']." eligible for add or update\n");
            //load store with the code.
            /** @var GroupInterface $store */
            //$store = $this->getStore($data);
            //load or create root category if defined - default to 2
            $rootCategoryId = $this->settings['root_category_id'];
            if (!empty($data['store_root_category'])) {
                $rootCategoryId = $this->getRootCategoryByName($data);
                //print_r( "requested root cat=".$data['store_root_category']."Id=".$rootCategoryId."\n");
                if (!$rootCategoryId) {
                    $rootCategoryId = $this->createRootCategory($data);
                    print_r($data['store_root_category']." root category created\n");
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
                print_r($data['store_code']." store updated\n");
                return $store;
            } elseif (!empty($data['store_name'])) {
                //create store, set default and root category
                print_r("create store\n");
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
                print_r($data['store_code']." store created\n");
                return $store;
            } else {
                //if the store doesnt exist and the name isn't provided, error out
                print_r("store_name and store_root_category column need to be included
                with a value when creating a store\n");
                return null;
            }
        } else {
            print_r($data['store_code']." skipping store add/update\n");
            return $store;
        }
    }
    //view requires store, name, code
    //Views are referred to as stores in code
    /**
     * @param array $data
     * @param StoreInterface $store
     * @throws AlreadyExistsException
     * @throws UrlAlreadyExistsException
     */
    private function setView(array $data, StoreInterface $store)
    {
        //if there is no store or view code we can skip
        if (!empty($data['store_code']) || !empty($data['view_code'])) {

            /** @var WebsiteInterface $website */
            $website = $this->getWebsite($data);
            print_r($data['view_code']." view eligible for add or update\n");
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
                    $this->appState->emulateAreaCode(
                        AppArea::AREA_ADMINHTML,
                        [$this->storeResourceModel, 'save'],
                        [$view]
                    );
                    $store->setDefaultStoreId($view->getId());
                    $this->appState->emulateAreaCode(
                        AppArea::AREA_ADMINHTML,
                        [$this->groupResourceModel, 'save'],
                        [$store]
                    );

                }
                print_r($data['view_code']." view updated\n");
            } elseif (!empty($data['view_name'])) {
                //create view, set default, status and order
                print_r("create view\n");
                if (!empty($data['view_name'])) {
                    $view->setName($data['view_name']);
                    $view->setCode($data['view_code']);
                    $view->setIsActive($data['view_is_active']=='Y'? 1:0);
                    $view->setStoreGroupId($store->getId());
                    $view->setWebsiteId($website->getId());
                    if (!empty($data['view_order'])) {
                        $view->setSortOrder($data['view_order']);
                    }

                    $this->appState->emulateAreaCode(
                        AppArea::AREA_ADMINHTML,
                        [$this->storeResourceModel, 'save'],
                        [$view]
                    );

                    //set cms page url rewrites for new view
                    $this->urlPersist->replace(
                        $this->generateCmsPagesUrls((int)$view->getId())
                    );
                }
                if (!empty($data['is_default_view']) && $data['is_default_view']=='Y') {
                    //default needs to be active
                    $view->setIsActive(1);
                    $this->appState->emulateAreaCode(
                        AppArea::AREA_ADMINHTML,
                        [$this->storeResourceModel, 'save'],
                        [$view]
                    );
                    $store->setDefaultStoreId($view->getId());
                    $this->appState->emulateAreaCode(
                        AppArea::AREA_ADMINHTML,
                        [$this->groupResourceModel, 'save'],
                        [$store]
                    );

                }
                print_r($data['view_code']." view created\n");
            } else {
                //if the view doesnt exist and the view isn't provided, error out
                print_r("view_name needs to be included with a value when creating a view\n");
            }
        } else {
            print_r($data['view_code']." skipping view add/update\n");
        }
    }

    /**
     * Generate url rewrites for cms pages to store view
     *
     * @param int $storeId
     * @return array
     */
    private function generateCmsPagesUrls(int $storeId): array
    {
        $rewrites = [];
        $urls = [];
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $cmsPagesCollection = $this->pageRepository->getList($searchCriteria)->getItems();
        foreach ($cmsPagesCollection as $page) {
            $page->setStoreId($storeId);
            $rewrites[] = $this->cmsPageUrlRewriteGenerator->generate($page);
        }
        $urls = array_merge($urls, ...$rewrites);

        return $urls;
    }

    /**
     * @param array $data
     * @return mixed
     */
    private function getWebsite(array $data)
    {
        return  $this->websiteInterfaceFactory->create()->load($data['site_code']);
    }

    /**
     * @param string $websiteCode
     * @return mixed
     */
    public function getWebsiteId(string $websiteCode)
    {
        $data = ['site_code'=>$websiteCode];
        $website = $this->getWebsite($data);
        return  $website->getId();
    }

    /**
     * @param array $data
     * @return GroupInterface
     */
    private function getStore(array $data)
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
     * @param string $storeCode
     * @return int
     */
    public function getStoreId(string $storeCode)
    {
        $data = ['store_code'=>$storeCode];
        $store = $this->getStore($data);
        return  $store->getId();
    }

    /**
     * @param array $data
     * @return StoreInterface
     */
    private function getView(array $data)
    {
        try {
            $view = $this->storeRepository->get($data['view_code']);
        } catch (NoSuchEntityException $e) {
            $view = $this->storeInterfaceFactory->create();
        }
        return $view;
    }

    /**
     * @param string $viewCode
     * @return int
     */
    public function getViewId(string $viewCode)
    {
        $data = ['view_code'=>$viewCode];
        $view = $this->getView($data);
        return  $view->getId();
    }

    /**
     * @param string $code
     * @return string
     */
    private function validateCode(string $code)
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
     * @param array $data
     * @return int|null
     */
    private function createRootCategory(array $data)
    {
        $catData = [
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
        $category->setData($catData)
            ->setPath('1')
            ->setAttributeSetId($category->getDefaultAttributeSetId());
        //using repository save wont generate tree properly
        //$this->categoryRepository->save($category);
        $category->save();
        return $category->getId();
    }

    /**
     * @param array $data
     * @return mixed
     */
    private function getRootCategoryByName(array $data)
    {
        $categories = $this->categoryInterfaceFactory->create()
            ->getCollection()
            ->addAttributeToFilter('name', $data['store_root_category'])
            ->addAttributeToFilter('parent_id', 1)
            ->addAttributeToSelect(['entity_id']);
        $id = $categories->getFirstItem()->getEntityId();
        return $categories->getFirstItem()->getEntityId();
    }

    /**
     * @param string $host
     * @param int $websiteId
     */
    private function setBaseUrls(string $host, int $websiteId): void
    {
        $this->configuration->saveConfig('web/unsecure/base_url', 'http://' . $host . '/', 'websites', $websiteId);
        $this->configuration->saveConfig('web/secure/base_url', 'https://' . $host . '/', 'websites', $websiteId);
    }
}
