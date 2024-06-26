<?php
/**
 * Copyright 2022 Adobe, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace MagentoEse\DataInstall\Model\DataTypes;

use Exception;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryInterfaceFactory;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\CmsUrlRewrite\Model\CmsPageUrlRewriteGenerator;
use Magento\Config\Model\ResourceModel\Config as ResourceConfig;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Area as AppArea;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\State;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Api\Data\GroupInterface;
use Magento\Store\Api\Data\GroupInterfaceFactory;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\Data\StoreInterfaceFactory;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Api\Data\WebsiteInterfaceFactory;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\Store\Api\GroupRepositoryInterface;
use Magento\Store\Api\GroupRepositoryInterfaceFactory;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Model\ResourceModel\Group as GroupResourceModel;
use Magento\Store\Model\ResourceModel\Store as StoreResourceModel;
use Magento\Store\Model\ResourceModel\Website as WebsiteResourceModel;
use Magento\Theme\Model\ResourceModel\Theme\Collection as ThemeCollection;
use Magento\Theme\Model\Theme\Registration as ThemeRegistration;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\UrlRewrite\Model\Exception\UrlAlreadyExistsException;
use Magento\UrlRewrite\Model\UrlPersistInterface;
use MagentoEse\DataInstall\Helper\Helper;
use Magento\Framework\Filesystem\DirectoryList;

class Stores
{
    /** @var array */
    protected $settings;

    /** @var Helper */
    protected $helper;

    /** @var  WebsiteInterfaceFactory */
    protected $websiteInterfaceFactory;

    /** @var WebsiteResourceModel  */
    protected $websiteResourceModel;

    /** @var WebsiteRepositoryInterface  */
    protected $websiteRepository;

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

    /** @var ComponentRegistrar */
    protected $componentRegistrar;
    
    /** @var ThemeRegistration */
    protected $themeRegistration;

    /** @var ThemeCollection */
    protected $themeCollection;

    /** @var ScopeConfigInterface */
    protected $scopeConfig;

    /** @var DirectoryList */
    protected $directoryList;

   /**
    * Stores constructor
    *
    * @param Helper $helper
    * @param WebsiteInterfaceFactory $websiteInterfaceFactory
    * @param WebsiteResourceModel $websiteResourceModel
    * @param WebsiteRepositoryInterface $websiteRepository
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
    * @param State $appState
    * @param ComponentRegistrar $componentRegistrar
    * @param ThemeRegistration $themeRegistration
    * @param ThemeCollection $themeCollection
    * @param ScopeConfigInterface $scopeConfig
    * @param DirectoryList $directoryList
    * @return void
    */
    
    public function __construct(
        Helper $helper,
        WebsiteInterfaceFactory $websiteInterfaceFactory,
        WebsiteResourceModel $websiteResourceModel,
        WebsiteRepositoryInterface $websiteRepository,
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
        State $appState,
        ComponentRegistrar $componentRegistrar,
        ThemeRegistration $themeRegistration,
        ThemeCollection $themeCollection,
        ScopeConfigInterface $scopeConfig,
        DirectoryList $directoryList
    ) {
        $this->helper = $helper;
        $this->websiteInterfaceFactory = $websiteInterfaceFactory;
        $this->websiteResourceModel = $websiteResourceModel;
        $this->websiteRepository = $websiteRepository;
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
        $this->componentRegistrar = $componentRegistrar;
        $this->themeRegistration = $themeRegistration;
        $this->themeCollection = $themeCollection;
        $this->scopeConfig = $scopeConfig;
        $this->directoryList = $directoryList;
    }

    /**
     * Install
     *
     * @param array $data
     * @param array $settings
     * @param mixed $cliHost
     * @return bool
     * @throws AlreadyExistsException
     * @throws LocalizedException
     * @throws UrlAlreadyExistsException
     */
    public function install(array $data, array $settings, $cliHost)
    {
        $this->settings = $settings;
        if (!empty($settings['is_override'])) {
            $data = $this->overrideSettings($data, $settings);
        }

        if (!empty($data['site_code'])) {
            //fix site code if its not correct
            $data['site_code'] = $this->validateCode($data['site_code']);
            $data['site_code'] = $this->replaceBaseWebsiteCode($data['site_code']);
            $this->helper->logMessage("-updating site", "info");

            //if website needs to be set as default, adjust data
            if (isset($settings['job_settings']['isDefaultWebsite'])) {
                if ($settings['job_settings']['isDefaultWebsite']) {
                    $data['is_default_site'] = $settings['job_settings']['isDefaultWebsite'];
                    $data['host'] = '';
                } else {
                    $data['is_default_site'] = 0;
                }
            }

            $website = $this->setSite($data);

            //check to make sure there is another site set as default. If not, set base as default
            // $siteId = $website->getID();
            // $defaultSite = $this->websiteRepository->getDefault()->getId();
            // $this->helper->logMessage($data['site_code'] . " set as default site", "info");
            // if ($siteId==$defaultSite) {
            //     $baseSite = $this->websiteRepository->getById(1);
            //     $baseSite->setData('is_default', '1');
            //     $this->websiteResourceModel->save($baseSite);
            //     $this->helper->logMessage("base reset as default site", "info");
            //     $data['is_default_site']= '1';
            // }

            //if there is a host value, set base urls
            if ($cliHost) {
                $data['host']=$cliHost;
            }
            if (!empty($data['host'])) {
                switch ($data['host']) {
                    case 'subdirectory':
                        $this->setBaseUrls($this->getBaseUrlHost()."/".$data['site_code'], $website->getId());
                        $this->setMediaUrls($this->getBaseUrlHost()."/", $website->getId());
                        break;
                    case 'subdomain':
                        $this->setBaseUrls($data['site_code'].".".$this->getBaseUrlHost(), $website->getId());
                        break;
                    default:
                        $this->setBaseUrls($data['host'], $website->getId());
                }
            }
            //if there is no store code, skip store and view
            if (!empty($data['store_code'])) {
                $this->helper->logMessage("-updating stores", "info");
                //fix store code if its not correct
                $data['store_code'] = $this->validateCode($data['store_code']);
                $store = $this->setStore($data, $website);
                //if there is not view code and store code, skip view updates
                if (!empty($data['store_view_code']) && !empty($data['store_code'])) {
                    $this->helper->logMessage("-updating views", "info");
                    //fix view code if its not correct
                    $data['store_view_code'] = $this->validateCode($data['store_view_code']);
                    $this->setView($data, $store);
                //if there is not view code, skip view update
                } else {
                    $this->helper->logMessage("skipping view updates", "info");
                }
            } elseif (!empty($data['store_view_code']) && empty($data['store_code'])) {
                $this->helper->logMessage("store_code is required to update or create a view", "error");
            } else {
                $this->helper->logMessage("skipping store updates", "info");
            }
        } else {
            $this->helper->logMessage("site_code column needs to be included with a value", "error");
        }
        return true;
    }

    //site requires name and code

    /**
     * Set Website
     *
     * @param array $data
     * @return WebsiteInterface|null
     * @throws AlreadyExistsException
     */
    private function setSite(array $data)
    {
        //load site from the code.
        /** @var WebsiteInterface $website */
        $website = $this->getWebsite($data);

        if (isset($data['is_default_site'])) {
            $website->setData('is_default', $data['is_default_site']);
            if ($data['is_default_site']==0) {
                //check to make sure there is another site set as default. If not, set base as default
                $siteId = $website->getID();
                $defaultSite = $this->websiteRepository->getDefault()->getId();
                $this->helper->logMessage($data['site_code'] . " set as default site", "info");
                if ($siteId==$defaultSite) {
                    $baseSite = $this->websiteRepository->getById(1);
                    $baseSite->setData('is_default', '1');
                    $this->websiteResourceModel->save($baseSite);
                    $this->helper->logMessage("base reset as default site", "info");
                    $data['is_default_site']= '1';
                }
            }
        }
        $website = $this->getWebsite($data);
        //no name,sort order  update - we can skip
        if (!empty($data['site_name']) || !empty($data['site_order'])) {
            $this->helper->logMessage($data['site_code'] . " eligible for add or update", "info");

            //if the site exists - update
            if ($website->getId()) {
                $this->helper->logMessage("update site " . $data['site_code'] . "\n");
                if (!empty($data['site_name'])) {
                    $website->setName($data['site_name']);
                }

                if (!empty($data['site_order'])) {
                    $website->setSortOrder($data['site_order']);
                }
                $website->setData('is_default', $data['is_default_site']);

                $this->websiteResourceModel->save($website);
                return $website;
            } elseif (!empty($data['site_name'])) {
                //create site
                $this->helper->logMessage("create site " . $data['site_code'], "info");
                $website->setCode($data['site_code']);
                $website->setName($data['site_name']);
                if (!empty($data['site_order'])) {
                    $website->setSortOrder($data['site_order']);
                }
                $website->setData('is_default', $data['is_default_site']);
                $this->websiteResourceModel->save($website);
                return $website;
            } else {
                //if the site doesnt exist and the name isn't provided, error out
                $this->helper->logMessage(
                    "site_name column needs to be included with a value when creating a site",
                    "error"
                );
                return null;
            }
        } else {
            $this->helper->logMessage($data['site_code'] . " skipping site add/update", "info");
            return $website;
        }
    }
    //store requires site, name, code, and root category
    //Stores are referred to as groups in code
    /**
     * Set Store (Group)
     *
     * @param array $data
     * @param Website $website
     * @return GroupInterface|null
     * @throws AlreadyExistsException
     */
    private function setStore(array $data, $website)
    {
        $store = $this->getStore($data);
        //no name, root category, or isDefault we can skip
        if (!empty($data['store_name']) || !empty($data['store_root_category']) || !empty($data['is_default_store'])) {
            /** @var WebsiteInterface $website */
            //$website = $this->getWebsite($data);
            $this->helper->logMessage($data['store_code'] . " eligible for add or update", "info");
            //load store with the code.
            /** @var GroupInterface $store */
            //$store = $this->getStore($data);
            //load or create root category if defined - default to 2
            $rootCategoryId = $this->settings['root_category_id'];
            if (!empty($data['store_root_category'])) {
                $rootCategoryId = $this->getRootCategoryByName($data);
                if (!$rootCategoryId) {
                    $rootCategoryId = $this->createRootCategory($data);
                    $this->helper->logMessage($data['store_root_category'] . " root category created", "info");
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
                $this->helper->logMessage($data['store_code'] . " store updated", "info");
                return $store;
            } elseif (!empty($data['store_name'])) {
                //create store, set default and root category
                $this->helper->logMessage("create store", "info");
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

                $this->helper->logMessage($data['store_code'] . " store created", "info");
                return $store;
            } else {
                //if the store doesnt exist and the name isn't provided, error out
                $this->helper->logMessage(
                    "store_name and store_root_category column need to be included
                with a value when creating a store",
                    "error"
                );
                return null;
            }
        } else {
            $this->helper->logMessage($data['store_code'] . " skipping store add/update", "info");
            return $store;
        }
    }
    //view requires store, name, code
    //Views are referred to as stores in code
    /**
     * Set View (Store)
     *
     * @param array $data
     * @param Store $store
     * @throws AlreadyExistsException
     * @throws LocalizedException
     * @throws UrlAlreadyExistsException
     */
    private function setView(array $data, $store)
    {
        //if there is no store or view code we can skip
        if (!empty($data['store_code']) || !empty($data['store_view_code'])) {
            
            /** @var WebsiteInterface $website */
            $website = $this->getWebsite($data);

            //verify default website is set
            // $siteId = $website->getID();
            // $defaultSite = $this->websiteRepository->getDefault()->getId();
            // $this->helper->logMessage($data['site_code'] . " set as default site", "info");
            // if ($siteId==$defaultSite) {
            //     $baseSite = $this->websiteRepository->getById(1);
            //     $baseSite->setData('is_default', '1');
            //     $this->websiteResourceModel->save($baseSite);
            //     $this->helper->logMessage("base reset as default site", "info");
            // }

            $this->helper->logMessage($data['store_view_code'] . " view eligible for add or update", "info");
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
                        $view->setIsActive($data['view_is_active']=='Y' ? 1 : 0);
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

                //add theme to view if provided
                $this->setTheme($data, $view->getId());
                $this->helper->logMessage($data['store_view_code'] . " view updated", "info");
            } elseif (!empty($data['view_name'])) {
                //create view, set default, status and order
                $this->helper->logMessage("create view", "info");
                if (!empty($data['view_name'])) {
                    $view->setName($data['view_name']);
                    $view->setCode($data['store_view_code']);
                    if (empty($data['view_is_active'])) {
                        $data['view_is_active']='Y';
                    }
                    $view->setIsActive($data['view_is_active']=='Y' ? 1 : 0);
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

                //add theme to view if provided
                $this->setTheme($data, $view->getId());
                $this->helper->logMessage($data['store_view_code'] . " view created", "info");
            } else {
                //if the view doesnt exist and the view isn't provided, error out
                $this->helper->logMessage(
                    "view_name needs to be included with a value when creating a view",
                    "error"
                );
            }
        } else {
            $this->helper->logMessage($data['store_view_code'] . " skipping view add/update", "info");
        }
    }

    /**
     * Generate Urls for store pages
     *
     * @param int $storeId
     * @return array
     * @throws LocalizedException
     */
    private function generateCmsPagesUrls(int $storeId): array
    {
        $rewrites = [];
        $urls = [];
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $cmsPagesCollection = $this->pageRepository->getList($searchCriteria)->getItems();
        foreach ($cmsPagesCollection as $page) {
            //generate urls for pages shared across stores
            if ($page->getStoreId()[0]==0) {
                $page->setStoreId($storeId);
                $rewrites[] = $this->cmsPageUrlRewriteGenerator->generate($page);
            }
        }

        $urls = array_merge($urls, ... $rewrites);

        return $urls;
    }

    /**
     * Get Website
     *
     * @param array $data
     * @return mixed
     */
    private function getWebsite(array $data)
    {
        return  $this->websiteInterfaceFactory->create()->load($data['site_code']);
    }

    /**
     * Get code of default Website
     *
     * @return string
     */
    public function getDefaultWebsiteCode()
    {
        $defaultWebsite = $this->websiteRepository->getDefault();
        return $defaultWebsite->getCode();
    }

    /**
     * Get all defined website codes
     *
     * @return array
     */
    public function getAllWebsiteCodes()
    {
        $siteList=[];
        $sites = $this->websiteRepository->getList();
        foreach ($sites as $site) {
            ///remove admin because its not a valid view for these purposes
            if ($site->getCode()!='admin') {
                $siteList[]=$site->getCode();
            }
        }

        return $siteList;
    }

    /**
     * Get the default website code if not base
     *
     * @param string $websiteCode
     * @return string
     * In the situations where the default website code may not be 'base'
     * get the value of the default website code
     */
    public function replaceBaseWebsiteCode($websiteCode)
    {
        if ($websiteCode=='base') {
            $websiteCode = $this->websiteRepository->getDefault()->getCode();
        }
        return $websiteCode;
    }

    /**
     * Get Website id
     *
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
     * Get Store (Group)
     *
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
     * Get Store id
     *
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
     * Get View id
     *
     * @param array $data
     * @return StoreInterface
     */
    public function getView(array $data)
    {
        $viewList = $this->storeRepository->getList();
        foreach ($viewList as $view) {
            if ($view->getCode()==$data['store_view_code']) {
                return $view;
            }
        }
        //if store is not found, create
        $view = $this->storeInterfaceFactory->create();
        return $view;
    }

    /**
     * Get all view codes
     *
     * @return array
     */
    public function getAllViewCodes()
    {
        $viewList=[];
        $views = $this->storeRepository->getList();
        foreach ($views as $view) {
            ///remove admin because its not a valid view for these purposes
            if ($view->getCode()!='admin') {
                $viewList[]=$view->getCode();
            }
        }

        return $viewList;
    }

    /**
     * Get view codes from stores not associated with given view
     *
     * @param string $currentView
     * @return array
     */
    public function getViewCodesFromOtherStores($currentView)
    {
        $currentStoreId = $this->getView(['store_view_code'=>$currentView])->getStoreGroupId();
        $viewList=[];
        $views = $this->storeRepository->getList();
        foreach ($views as $view) {
            //still need to restrict admin stores
            if ($view->getStoreGroupId()!=$currentStoreId && $view->getStoreGroupId()!=0) {
                $viewList[]=$view->getCode();
            }
        }

        return $viewList;
    }

    /**
     * Get View id by code
     *
     * @param string $viewCode
     * @return int
     */
    public function getViewId(string $viewCode)
    {
        $data = ['store_view_code'=>$viewCode];
        $view = $this->getView($data);
        return  $view->getId();
    }

     /**
      * Get view ids from code list
      *
      * @param string $viewCodes
      * @return string
      */
    public function getViewIdsFromCodeList(string $viewCodes)
    {
        $returnList=[];
        $allCodes = explode(",", $viewCodes);
        foreach ($allCodes as $code) {
            $returnList[]=$this->getViewId(trim($code));
        }
        return implode(",", $returnList);
    }

     /**
      * Get View name
      *
      * @param string $viewCode
      * @return int
      */
    public function getViewName(string $viewCode)
    {
        $data = ['store_view_code'=>$viewCode];
        $view = $this->getView($data);
        return  $view->getName();
    }

    /**
     * Validate code format
     *
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
            $code = "m" . $code;
        }

        return $code;
    }

    /**
     * Create catalog root category
     *
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
     * Get root cateogry by name
     *
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
     * Set base url for website
     *
     * @param string $host
     * @param int $websiteId
     */
    private function setBaseUrls(string $host, int $websiteId): void
    {
        $this->configuration->saveConfig('web/unsecure/base_url', 'http://' . $host . '/', 'websites', $websiteId);
        $this->configuration->saveConfig('web/secure/base_url', 'https://' . $host . '/', 'websites', $websiteId);
    }

    /**
     * Set media urls for website
     *
     * @param string $host
     * @param int $websiteId
     */
    private function setMediaUrls(string $host, int $websiteId): void
    {
        $this->configuration->saveConfig(
            'web/unsecure/base_static_url',
            'http://' . $host . 'static/',
            'websites',
            $websiteId
        );
        $this->configuration->saveConfig(
            'web/secure/base_static_url',
            'https://' . $host . 'static/',
            'websites',
            $websiteId
        );
        $this->configuration->saveConfig(
            'web/unsecure/base_media_url',
            'http://' . $host . 'media/',
            'websites',
            $websiteId
        );
        $this->configuration->saveConfig(
            'web/secure/base_media_url',
            'https://' . $host . 'media/',
            'websites',
            $websiteId
        );
    }

    /**
     * Get host of base url
     *
     * @return string
     */
    private function getBaseUrlHost(): string
    {
        $baseUrl = $this->scopeConfig->getValue('web/unsecure/base_url', 'default', 0);
        //parse_url is used frequently in core
        // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
        return parse_url($baseUrl, PHP_URL_HOST);
    }

    /**
     * Set theme for view
     *
     * @param array $data
     * @param int $storeViewId
     */
    private function setTheme(array $data, int $storeViewId)
    {
        if (!empty($data['theme'])) {
            //make sure theme is registered
            $this->registerTheme($data['theme']);
            $themeId = $this->themeCollection->getThemeByFullPath('frontend/' . $data['theme'])->getThemeId();
            // if theme doesnt exist, try the fallback
            if (!$themeId) {
                $this->helper->logMessage("Theme ".$data['theme']. " not found", "warning");
                if (!empty($data['theme_fallback'])) {
                    //make sure theme is registered
                    $this->registerTheme($data['theme_fallback']);
                    $themeId = $this->themeCollection->getThemeByFullPath('frontend/' . $data['theme_fallback'])
                    ->getThemeId();
                    if (!$themeId) {
                        $this->helper->logMessage("Fallback theme ".$data['theme_fallback']. " not found", "warning");
                    }
                }
            }
            if (!$themeId) {
                $this->helper->logMessage("Theme not set", "warning");
            }
            //set theme
            $this->configuration->saveConfig("design/theme/theme_id", $themeId, "stores", $storeViewId);
            $this->helper->logMessage("Theme assigned", "info");
        }
    }

    /**
     * Register added theme
     *
     * @param mixed $theme
     * @return void
     * @throws LocalizedException
     */
    private function registerTheme($theme)
    {
        try {
            $this->componentRegistrar->register(
                ComponentRegistrar::THEME,
                'frontend/'.$theme,
                $this->directoryList->getRoot().'/app/design/frontend/'.$theme
            );
        } catch (Exception $e) {
            //ignore as it will throw an exception if the theme is already registered
            $r=1;
        }
        $this->themeRegistration->register();
    }

    /**
     * Override settings
     *
     * @param array $data
     * @param array $settings
     * @return array
     */
    private function overrideSettings(array $data, array $settings) : array
    {
        if (!empty($settings['site_code'])) {
            $data['site_code'] = $settings['site_code'];
        }
        if (!empty($settings['store_code'])) {
            $data['store_code'] = $settings['store_code'];
        }
        if (!empty($settings['store_view_code'])) {
            $data['store_view_code'] = $settings['store_view_code'];
        }
        if (!empty($settings['site_name'])) {
            $data['site_name'] = $settings['site_name'];
        }
        if (!empty($settings['store_name'])) {
            $data['store_name'] = $settings['store_name'];
        }
        if (!empty($settings['store_view_name'])) {
            $data['view_name'] = $settings['store_view_name'];
        }
        return $data;
    }
}
