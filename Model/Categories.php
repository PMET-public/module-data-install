<?php
/**
 * Copyright © Magento. All rights reserved.
 */
namespace MagentoEse\DataInstall\Model;

use Magento\Catalog\Api\Data\CategoryInterfaceFactory;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Category\TreeFactory;
use Magento\Cms\Api\Data\BlockInterfaceFactory;
use Magento\Framework\Data\Tree\Node;
use Magento\Store\Api\Data\StoreInterfaceFactory;
use Magento\Store\Model\StoreManagerInterface;

class Categories
{
    /** @var array */
    protected $settings;

    /** @var CategoryInterfaceFactory  */
    protected $categoryFactory;

    /** @var StoreManagerInterface  */
    protected $storeManager;

    /** @var TreeFactory  */
    protected $resourceCategoryTreeFactory;

    /** @var Node */
    protected $categoryTree;

    /** @var StoreInterfaceFactory  */
    protected $storeFactory;

    /** @var BlockInterfaceFactory  */
    protected $blockFactory;

    /** @var Configuration  */
    protected $configuration;

    /**
     * Categories constructor.
     * @param CategoryInterfaceFactory $categoryFactory
     * @param TreeFactory $resourceCategoryTreeFactory
     * @param StoreManagerInterface $storeManager
     * @param StoreInterfaceFactory $storeFactory
     * @param BlockInterfaceFactory $blockFactory
     * @param Configuration $configuration
     */
    public function __construct(
        CategoryInterfaceFactory $categoryFactory,
        TreeFactory $resourceCategoryTreeFactory,
        StoreManagerInterface $storeManager,
        StoreInterfaceFactory $storeFactory,
        BlockInterfaceFactory $blockFactory,
        Configuration $configuration
    ) {
        $this->categoryFactory = $categoryFactory;
        $this->resourceCategoryTreeFactory = $resourceCategoryTreeFactory;
        $this->storeManager = $storeManager;
        $this->storeFactory = $storeFactory;
        $this->blockFactory = $blockFactory;
        $this->configuration = $configuration;
    }

    /**
     * @param array $row
     * @param array $settings
     * @return bool
     */
    public function install(array $row, array $settings)
    {
        //TODO:Support for non default settings
        //TODO:Content block additions to categories
        $this->settings = $settings;

        $category = $this->getCategoryByPath($row['path'] . '/' . $row['name']);
        if (!$category) {
            $parentCategory = $this->getCategoryByPath($row['path']);
            if ($parentCategory) {
                $data = [
                    'parent_id' => $parentCategory->getId(),
                    'name' => $row['name'],
                    'is_active' => $row['active'] ?? 1,
                    'is_anchor' => $row['is_anchor'] ?? 1,
                    'include_in_menu' => $row['include_in_menu'] ?? 1,
                    'url_key' => $row['url_key'] ?? '',
                    'store_id' => 0
                ];
                $category = $this->categoryFactory->create();
                $category->setData($data)
                    ->setPath($parentCategory->getData('path'))
                    ->setAttributeSetId($category->getDefaultAttributeSetId());
                $this->setAdditionalData($row, $category);

                $category->save();
            } else {
                $this->helper->printMessage("-Cannot find the parent category for " . $row['name'] . " in the path " . $row['path'] . ". That category has been skipped","warning");
            }
        }

        return true;
    }

    /**
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
            'landing_page'
        ];

        foreach ($additionalAttributes as $categoryAttribute) {
            if (!empty($row[$categoryAttribute])) {
                if ($categoryAttribute == 'landing_page') {
                    $attributeData = [$categoryAttribute => $this->getCmsBlockId($row[$categoryAttribute])];
                } else {
                    $attributeData = [$categoryAttribute => $row[$categoryAttribute]];
                }

                $category->addData($attributeData);
            }
        }
    }

    /**
     * Get category name by path
     *
     * @param string $path
     * @return Node
     */
    protected function getCategoryByPath(string $path)
    {
        $names = array_filter(explode('/', $path));
        //if the first element in the path is a root category, use that root id and drop from array
        //else, use the root category for the default store
        $store = $this->storeFactory->create();
        $store->load($this->settings['store_view_code']);
        $rootCatId = $store->getGroup()->getDefaultStore()->getRootCategoryId();

        $tree = $this->getTree($rootCatId);
        foreach ($names as $name) {
            $tree = $this->findTreeChild($tree, $name);
            if (!$tree) {
                $tree = $this->findTreeChild($this->getTree($rootCatId, true), $name);
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
     * @param array $row
     * @return void
     */

    /**
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
