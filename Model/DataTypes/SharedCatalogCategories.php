<?php
namespace MagentoEse\DataInstall\Model\DataTypes;

use Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\SharedCatalog\Api\CategoryManagementInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollection;
use Magento\SharedCatalog\Model\SharedCatalogAssignment;
use Magento\SharedCatalog\Model\CatalogPermissionManagement;
use Magento\SharedCatalog\Model\ResourceModel\Permission\CategoryPermissions\ScheduleBulk;

class SharedCatalogCategories{

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

    public function __construct(SharedCatalogRepositoryInterface $sharedCatalogRepositoryInterface,
    SearchCriteriaBuilder $searchCriteriaBuilder, CategoryManagementInterface $categoryManagementInterface,
    CategoryCollection $categoryCollection, Stores $stores,SharedCatalogAssignment $sharedCatalogAssignment,
    CatalogPermissionManagement $catalogPermissionManagement, ScheduleBulk $scheduleBulk)
    {
        $this->sharedCatalogRepository = $sharedCatalogRepositoryInterface;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->categoryManagementInterface = $categoryManagementInterface;
        $this->categoryCollection = $categoryCollection;
        $this->stores = $stores;
        $this->sharedCatalogAssignment = $sharedCatalogAssignment;
        $this->catalogPermissionManagement = $catalogPermissionManagement;
        $this->scheduleBulk = $scheduleBulk;
    }

    public function install(array $rows, array $header, string $modulePath, array $settings){
        foreach ($rows as $row) {
            $categoryRowArray[] = array_combine($header, $row);
        }
        //create separate array for each shared catalog
        foreach($categoryRowArray as $categoryRow){
            $categoryArray[$categoryRow['shared_catalog']][]=$categoryRow['category'];
        }
        //if the default catalog is not defined, then add all categories and products to it
        $allCategoryIds = $this->getCategoryIds($this->getAllCategories($settings));
        if(!array_key_exists('Default (General)',$categoryArray)){
            $categoryArray['Default (General)'] = $allCategoryIds;
        }
        foreach($categoryArray as $catalogName=>$categoryArray){
            $groupIds = [];
            //get id for shared catalog
            /** @var SharedCatalogInterface $catalog */
            $catalog = $this->getSharedCatalogByName($catalogName);

            if($catalog){
                $catalogId = $catalog->getId();
                //remove current categories
                $this->categoryManagementInterface->unassignCategories($catalogId,$this->categoryManagementInterface->getCategories($catalogId));

                //get ids of added categories by path
                $newCategories = $this->getCategoriesByPath($categoryArray,$settings);
                //add new categories
                $this->categoryManagementInterface->assignCategories($catalogId,$newCategories);
                //add products in categories
                $catgoryIds = $this->getCategoryIds($newCategories);
                $this->sharedCatalogAssignment->assignProductsForCategories($catalogId,$catgoryIds);
                
                //set catalog permissions
                $groupIds[] = $catalog->getCustomerGroupId();
                $catalogType = $catalog->getType();
                if($catalogType == SharedCatalogInterface::TYPE_PUBLIC){
                    $groupIds[]=0;
                }
                $this->catalogPermissionManagement->setDenyPermissions(array_diff($allCategoryIds,$catgoryIds),$groupIds);
                $this->scheduleBulk->execute($allCategoryIds,$groupIds);

            }
        
        }
        
        
        //$r=$l;
    }

    private function getCategoryIds($categoryArray){
        $categoryIds = [];
        foreach($categoryArray as $category){
            $categoryIds[]=$category->getId();
        }
        return $categoryIds;
    }

    private function getSharedCatalogByName($sharedCatalogName){
        $catalogSearch = $this->searchCriteriaBuilder
        ->addFilter(SharedCatalogInterface::NAME, $sharedCatalogName, 'eq')->create()->setPageSize(1)->setCurrentPage(1);
        $catalogList = $this->sharedCatalogRepository->getList($catalogSearch);
        return current($catalogList->getItems());
    }

    private function getCategoriesByPath($categories,$settings){
        $categoryIds = [];
        $allCategories = $this->getAllCategories($settings);
        foreach($categories as $category){
            $index = mb_strtolower($category);
            if (isset($allCategories[$index])) {
               $categoryId = $allCategories[$index]; // here is your category id
               $categoryIds[]=$categoryId;
            }
        }
        return $categoryIds;
    }

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
                    $path[] = str_replace('/', '\\' . '/', $name);;
                }
                $index = mb_strtolower(implode('/', $path));
                $allCategories[$index] = $category;
            }
        }

        return $allCategories;
    }
}
