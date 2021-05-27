<?php
/**
 * Copyright © Adobe  All rights reserved.
 */

namespace MagentoEse\DataInstall\Model\DataTypes;

use Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\SharedCatalog\Api\CategoryManagementInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollection;
use Magento\SharedCatalog\Model\SharedCatalogAssignment;
use Magento\SharedCatalog\Model\CatalogPermissionManagement;
use Magento\SharedCatalog\Model\ResourceModel\Permission\CategoryPermissions\ScheduleBulk;
use Magento\Framework\App\Area as AppArea;
use Magento\Framework\App\State;

class SharedCatalogCategories
{

    //TODO: Set Default Catalog
    /** @var SharedCatalogRepositoryInterface */
    protected $sharedCatalogRepository;

    /** @var CategoryManagementInterface */
    protected $categoryManagementInterface;

    /** @var SearchCriteriaBuilder */
    protected $searchCriteriaBuilder;

    /** @var CategoryCollection */
    protected $categoryCollection;

    /** @var Stores */
    protected $stores;

    /** @var CatalogPermissionManagement */
    protected $catalogPermissionManagement;

    /** @var SharedCatalogAssignment */
    protected $sharedCatalogAssignment;

     /** @var ScheduleBulk */
     protected $scheduleBulk;

     /** @var State */
    protected $appState;

    /**
     * SharedCatalogCategories constructor.
     * @param SharedCatalogRepositoryInterface $sharedCatalogRepositoryInterface
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param CategoryManagementInterface $categoryManagementInterface
     * @param CategoryCollection $categoryCollection
     * @param Stores $stores
     * @param SharedCatalogAssignment $sharedCatalogAssignment
     * @param CatalogPermissionManagement $catalogPermissionManagement
     * @param ScheduleBulk $scheduleBulk
     */
     public function __construct(
        SharedCatalogRepositoryInterface $sharedCatalogRepositoryInterface,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        CategoryManagementInterface $categoryManagementInterface,
        CategoryCollection $categoryCollection,
        Stores $stores,
        SharedCatalogAssignment $sharedCatalogAssignment,
        CatalogPermissionManagement $catalogPermissionManagement,
        ScheduleBulk $scheduleBulk,
        State $appState
    ) {
        $this->sharedCatalogRepository = $sharedCatalogRepositoryInterface;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->categoryManagementInterface = $categoryManagementInterface;
        $this->categoryCollection = $categoryCollection;
        $this->stores = $stores;
        $this->sharedCatalogAssignment = $sharedCatalogAssignment;
        $this->catalogPermissionManagement = $catalogPermissionManagement;
        $this->scheduleBulk = $scheduleBulk;
        $this->appState = $appState;
    }

    /**
     * @param array $rows
     * @param array $header
     * @param string $modulePath
     * @param array $settings
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function install(array $rows, array $header, string $modulePath, array $settings)
    {
        foreach ($rows as $row) {
            $categoryRowArray[] = array_combine($header, $row);
        }
        //create separate array for each shared catalog
        foreach ($categoryRowArray as $categoryRow) {
            $categoryArray[$categoryRow['shared_catalog']][]=$categoryRow['category'];
        }
        //if the default catalog is not defined, then add all categories and products to it
        $allCategoryIds = $this->getCategoryIds($this->getAllCategories($settings));
        if (!array_key_exists('Default (General)', $categoryArray)) {
            $categoryArray['Default (General)'] = $allCategoryIds;
        }
        foreach ($categoryArray as $catalogName => $categoryArray) {
            $groupIds = [];
            //get id for shared catalog
            /** @var SharedCatalogInterface $catalog */
            $catalog = $this->getSharedCatalogByName($catalogName);

            if ($catalog) {
                $catalogId = $catalog->getId();
                //remove current categories
                ///this returns category ids, it should be categories.
                // get an instance of CategoryCollection
                $catIds = $this->categoryManagementInterface->getCategories($catalogId);
                if(count($catIds) > 0){
                    $categories = $this->categoryCollection->create();
                    
                    // add a filter to get the IDs you need
                    $categories->addFieldToFilter('entity_id', $this->categoryManagementInterface->getCategories($catalogId));
                    //$r=$this->categoryManagementInterface->getCategories($catalogId);
                    $catlist=[];
                    foreach($categories as $cat){
                        $catlist[] = $cat;
                    }
                    $this->appState->emulateAreaCode(
                        AppArea::AREA_ADMINHTML,
                        [$this->categoryManagementInterface, 'unassignCategories'],
                        [$catalogId, $catlist]
                    );
                    //$this->categoryManagementInterface->unassignCategories($catalogId, $catlist);
                }
                

                //get ids of added categories by path
                $newCategories = $this->getCategoriesByPath($categoryArray, $settings);
                //add new categories
                $this->categoryManagementInterface->assignCategories($catalogId, $newCategories);
                //add products in categories
                $catgoryIds = $this->getCategoryIds($newCategories);
                $this->sharedCatalogAssignment->assignProductsForCategories($catalogId, $catgoryIds);

                //set catalog permissions
                $groupIds[] = $catalog->getCustomerGroupId();
                $catalogType = $catalog->getType();
                if ($catalogType == SharedCatalogInterface::TYPE_PUBLIC) {
                    $groupIds[]=0;
                }
                $this->catalogPermissionManagement->setDenyPermissions(array_diff($allCategoryIds, $catgoryIds), $groupIds);
                $this->scheduleBulk->execute($allCategoryIds, $groupIds);
            }
        }
    }

    /**
     * @param $categoryArray
     * @return array
     */
    private function getCategoryIds($categoryArray)
    {
        $categoryIds = [];
        foreach ($categoryArray as $category) {
            $categoryIds[]=$category->getId();
        }
        return $categoryIds;
    }

    /**
     * @param $sharedCatalogName
     * @return SharedCatalogInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getSharedCatalogByName($sharedCatalogName)
    {
        $catalogSearch = $this->searchCriteriaBuilder
        ->addFilter(SharedCatalogInterface::NAME, $sharedCatalogName, 'eq')->create()->setPageSize(1)->setCurrentPage(1);
        $catalogList = $this->sharedCatalogRepository->getList($catalogSearch);
        return current($catalogList->getItems());
    }

    /**
     * @param $categories
     * @param $settings
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getCategoriesByPath($categories, $settings)
    {
        $categoryIds = [];
        $allCategories = $this->getAllCategories($settings);
        foreach ($categories as $category) {
            $index = mb_strtolower($category);
            if (isset($allCategories[$index])) {
                $categoryId = $allCategories[$index]; // here is your category id
                $categoryIds[]=$categoryId;
            }
        }
        return $categoryIds;
    }

    /**
     * @param $settings
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getAllCategories($settings)
    {

        $allCategories = [];
        $collection = $this->categoryCollection->create();
        $collection->addAttributeToSelect('name')
            ->addAttributeToSelect('url_key')
            ->addAttributeToSelect('url_path');
        $collection->setStoreId($this->stores->getStoreId($settings['store_code']));
        /* @var $collection \Magento\Catalog\Model\ResourceModel\Category\Collection */
        foreach ($collection as $category) {
            $allCategories[$category->getId()] = $category;
            $structure = explode('/', $category->getPath());
            $pathSize = count($structure);
            $allCategories[$category->getId()] = $category;
            if ($pathSize > 1) {
                $path = [];
                for ($i = 1; $i < $pathSize; $i++) {
                    $name = $collection->getItemById((int)$structure[$i])->getName();
                    $path[] = str_replace('/', '\\' . '/', $name);
                    ;
                }
                $index = mb_strtolower(implode('/', $path));
                $allCategories[$index] = $category;
            }
        }

        return $allCategories;
    }
}
