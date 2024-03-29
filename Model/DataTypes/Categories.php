<?php
/**
 * Copyright 2022 Adobe, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace MagentoEse\DataInstall\Model\DataTypes;

use Magento\Catalog\Api\Data\CategoryInterfaceFactory;
use Magento\Catalog\Api\CategoryListInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Category\TreeFactory;
use Magento\Cms\Api\Data\BlockInterfaceFactory;
use Magento\Framework\Data\Tree\Node;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\StoreRepositoryInterface;
use MagentoEse\DataInstall\Helper\Helper;
use MagentoEse\DataInstall\Model\Converter;
use Magento\Theme\Model\ResourceModel\Theme\Collection as ThemeCollection;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Categories
{
    /** @var CategoryInterfaceFactory  */
    protected $categoryFactory;

    /** @var CategoryListInterface  */
     protected $categoryList;

    /** @var SearchCriteriaBuilder  */
    protected $searchCriteria;

    /** @var StoreRepositoryInterface  */
    protected $storeRepository;

    /** @var TreeFactory  */
    protected $resourceCategoryTreeFactory;

    /** @var Node */
    protected $categoryTree;

    /** @var BlockInterfaceFactory  */
    protected $blockFactory;

    /** @var Configuration  */
    protected $configuration;

    /** @var Converter  */
    protected $converter;

    /** @var Helper  */
    protected $helper;

    /** @var Stores  */
    protected $stores;

    /** @var ThemeCollection */
    protected $themeCollection;

    /** @var ScopeConfigInterface  */
    protected $scopeConfig;

   /**
    *
    * @param CategoryInterfaceFactory $categoryFactory
    * @param CategoryListInterface $categoryList
    * @param SearchCriteriaBuilder $searchCriteria
    * @param TreeFactory $resourceCategoryTreeFactory
    * @param StoreRepositoryInterface $storeRepository
    * @param BlockInterfaceFactory $blockFactory
    * @param Configuration $configuration
    * @param Converter $converter
    * @param Helper $helper
    * @param Stores $stores
    * @param ThemeCollection $themeCollection
    * @param ScopeConfigInterface $scopeConfig
    * @return void
    */
    public function __construct(
        CategoryInterfaceFactory $categoryFactory,
        CategoryListInterface $categoryList,
        SearchCriteriaBuilder $searchCriteria,
        TreeFactory $resourceCategoryTreeFactory,
        StoreRepositoryInterface $storeRepository,
        BlockInterfaceFactory $blockFactory,
        Configuration $configuration,
        Converter $converter,
        Helper $helper,
        Stores $stores,
        ThemeCollection $themeCollection,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->categoryFactory = $categoryFactory;
        $this->categoryList = $categoryList;
        $this->searchCriteria = $searchCriteria;
        $this->resourceCategoryTreeFactory = $resourceCategoryTreeFactory;
        $this->storeRepository = $storeRepository;
        $this->blockFactory = $blockFactory;
        $this->configuration = $configuration;
        $this->converter = $converter;
        $this->helper = $helper;
        $this->stores = $stores;
        $this->themeCollection = $themeCollection;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Install
     *
     * @param array $row
     * @param array $settings
     * @return bool
     */
    public function install(array $row, array $settings)
    {
        if (empty($row['name'])) {
            $this->helper->logMessage("A value for name is required in your categories file", "warning");
            return true;
        }
        if (!isset($row['is_active'])) {
            //for backwards compatibility
            if (!empty($row['active'])) {
                $row['is_active'] = $row['active'];
            } else {
                $row['is_active'] = 1;
            }
        }
        // if ($row['is_active']!=0) {
        //     $row['is_active'] = 1;
        // }

        if (empty($row['is_anchor']) && $row['is_anchor']!=0) {
            $row['is_anchor'] = 1;
        }
        if (empty($row['include_in_menu']) && $row['include_in_menu']!=0) {
            $row['include_in_menu'] = 1;
        }

        if (empty($row['store_view_code'])) {
            $row['store_view_code'] = $settings['store_view_code'];
        }

        //store view code override
        if (!empty($settings['is_override'])) {
            if (!empty($settings['store_view_code'])) {
                $row['store_view_code'] = $settings['store_view_code'];
            }
        }
        
        $storeViewId = $this->storeRepository->get($row['store_view_code'])->getId();

        $category = $this->getCategoryByPath($row['path'] . '/' . $row['name'], $row['store_view_code']);
        if (!$category) {
            $parentCategory = $this->getCategoryByPath($row['path'], $row['store_view_code']);
            if ($parentCategory) {
                $data = [
                    'parent_id' => $parentCategory->getId(),
                    'name' => str_replace('\/', '/', $row['name']),
                    'is_active' => $row['is_active'],
                    'is_anchor' => $row['is_anchor'],
                    'include_in_menu' => $row['include_in_menu'],
                    'url_key' => $row['url_key'] ?? '',
                    'store_id' => $storeViewId
                ];
                $category = $this->categoryFactory->create();
                $category->setData($data)
                    ->setPath($parentCategory->getData('path'))
                    ->setAttributeSetId($category->getDefaultAttributeSetId());
                $this->setAdditionalData($row, $category);

                $category->save();
            } else {
                $this->helper->logMessage("-Cannot find the parent category for " . $row['name'].
                " in the path " . $row['path'] . ". That category has been skipped", "warning");
            }
        }

        return true;
    }

    /**
     * Get additional data for category
     *
     * @param array $row
     * @param Category $category
     * @return void
     */
    protected function setAdditionalData(array $row, Category $category)
    {
        $additionalAttributes = [
            'position',
            'display_mode',
            'page_layout',
            'image',
            'description',
            'landing_page',
            'custom_design'
        ];

        foreach ($additionalAttributes as $categoryAttribute) {
            if (!empty($row[$categoryAttribute])) {
                if ($categoryAttribute == 'landing_page') {
                    $attributeData = [$categoryAttribute => $this->getCmsBlockId($row[$categoryAttribute])];
                } elseif ($categoryAttribute == 'custom_design') {
                    $attributeData = [$categoryAttribute =>
                    $this->getThemeId($row[$categoryAttribute], $row['store_view_code'])];
                } else {
                    $attributeData = [$categoryAttribute => $this->converter->convertContent($row[$categoryAttribute])];
                }

                $category->addData($attributeData);
            }
        }
    }

    /**
     * Get theme id by store and path
     *
     * @param mixed $theme
     * @param mixed $storeViewCode
     * @return mixed
     * @throws NoSuchEntityException
     */
    protected function getThemeId($theme, $storeViewCode)
    {
        $storeViewId = $this->storeRepository->get($storeViewCode)->getId();
        $themeId = $this->themeCollection->getThemeByFullPath('frontend/' . $theme)->getThemeId();
        if (!$themeId) {
            //if the theme doesnt exist, get the theme assigned to the store
            $themeId = $this->scopeConfig->getValue('design/theme/theme_id', 'stores', $storeViewId);
            if (!$themeId) {
            //if the theme doesnt exist, return empty string
                $themeId = '';
            }
        }
        return $themeId;
    }

    /**
     * Get category name by path
     *
     * @param string $path
     * @param string $storeViewCode
     * @return Node
     */
    protected function getCategoryByPath(string $path, $storeViewCode)
    {
        //replace escaped / charcters in category names
        $path = str_replace('\/', '~~', $path);
        $names = array_filter(explode('/', $path));
        //if the first element in the path is a root category, use that root id and drop from array
        //else, use the root category for the default store
        //$store = $this->stores->getView(['store_view_code'=>$storeViewCode]);
        $rootCatId = $this->storeRepository->get($storeViewCode)->getGroup()->getDefaultStore()->getRootCategoryId();

        $tree = $this->getTree($rootCatId);
        foreach ($names as $name) {
            $tree = $this->findTreeChild($tree, str_replace('~~', '/', $name));
            if (!$tree) {
                $tree = $this->findTreeChild($this->getTree($rootCatId, true), str_replace('~~', '/', $name));
            }

            if (!$tree) {
                break;
            }
        }

        return $tree;
    }

    /**
     * Get child categories
     *
     * @param Node $tree
     * @param string $name
     * @return mixed
     */
    protected function findTreeChild(Node $tree, string $name)
    {
        $foundChild = null;
        if ($name) {
            foreach ($tree->getChildren() as $child) {
                if ($child->getName() == $name) {
                    $foundChild = $child;
                    break;
                }
            }
        }

        return $foundChild;
    }

    /**
     * Get category tree
     *
     * @param int|null $rootNode
     * @param bool $reload
     * @return Node
     */
    protected function getTree($rootNode = null, $reload = false)
    {
        if (!$this->categoryTree || $reload) {
            if ($rootNode === null) {
                $rootNode = $this->storeManager->getDefaultStoreView()->getRootCategoryId();
            }

            $tree = $this->resourceCategoryTreeFactory->create();
            $node = $tree->loadNode($rootNode)->loadChildren();

            $tree->addCollectionData(null, false, $rootNode);

            $this->categoryTree = $node;
        }

        return $this->categoryTree;
    }

    /**
     * Get block id by name
     *
     * @param string $blockName
     * @return int
     */
    protected function getCmsBlockId(string $blockName)
    {
        $block = $this->blockFactory->create();
        $block->load($blockName, 'identifier');
        return $block->getId();
    }

    /**
     * Set landing page for category
     *
     * @param int $blockId
     * @param int $categoryId
     */
    protected function setCategoryLandingPage(int $blockId, int $categoryId)
    {
        $categoryCms = [
            'landing_page' => $blockId,
            'display_mode' => 'PRODUCTS_AND_PAGE',
        ];
        if (!empty($categoryId)) {
            $category = $this->categoryRepository->get($categoryId);
            $category->setData($categoryCms);
            $this->categoryRepository->save($categoryId);
        }
    }
}
