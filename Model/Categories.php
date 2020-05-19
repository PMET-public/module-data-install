<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MagentoEse\DataInstall\Model;

use Exception;
use Magento\Catalog\Api\Data\CategoryInterfaceFactory;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Category\TreeFactory;
use Magento\Cms\Api\Data\BlockInterfaceFactory;
use Magento\Framework\Data\Tree\Node;
use Magento\Framework\Setup\SampleData\FixtureManager;
use Magento\Store\Api\Data\StoreInterfaceFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Category
 */
class Categories
{

    /**
     * @var CategoryInterfaceFactory
     */
    protected $categoryFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var TreeFactory
     */
    protected $resourceCategoryTreeFactory;

    /**
     * @var Node
     */
    protected $categoryTree;

    /**
     * @var StoreInterfaceFactory
     */
    protected $storeFactory;

    /**
     * @var BlockInterfaceFactory
     */
    protected $blockFactory;

    /** @var Configuration  */
    protected $configuration;

    /** @var Stores  */
    protected $stores;

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
        Configuration $configuration,
        Stores $stores
    ) {
        $this->categoryFactory = $categoryFactory;
        $this->resourceCategoryTreeFactory = $resourceCategoryTreeFactory;
        $this->storeManager = $storeManager;
        $this->storeFactory = $storeFactory;
        $this->blockFactory = $blockFactory;
        $this->configuration = $configuration;
        $this->stores = $stores;
    }

    /**
     * @param $row array
     */
    public function install($row)
    {
        //TODO:Support for non default settings
        //TODO:Content block additions to categories
        $category = $this->getCategoryByPath($row['path'] . '/' . $row['name']);
        if (!$category) {
            $parentCategory = $this->getCategoryByPath($row['path']);
            $data = [
                'parent_id' => $parentCategory->getId(),
                'name' => $row['name'],
                'is_active' => $row['active'],
                'is_anchor' => $row['is_anchor'],
                'include_in_menu' => $row['include_in_menu'],
                'url_key' => $row['url_key'],
                'store_id' => 0
            ];
            $category = $this->categoryFactory->create();
            $category->setData($data)
                ->setPath($parentCategory->getData('path'))
                ->setAttributeSetId($category->getDefaultAttributeSetId());
            $this->setAdditionalData($row, $category);

            $category->save();

        }
        return true;
    }

    /**
     * @param array $row
     * @param Category $category
     * @return void
     */
    protected function setAdditionalData($row, $category)
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
    protected function getCategoryByPath($path)
    {
        $store = $this->storeFactory->create();
        $store->load($this->stores->getDefaultViewCode());
        $rootCatId = $store->getGroup()->getDefaultStore()->getRootCategoryId();
        $names = array_filter(explode('/', $path));
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
    protected function findTreeChild($tree, $name)
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
    protected function getCmsBlockId($blockName)
    {
        $block = $this->blockFactory->create();
        $block->load($blockName, 'identifier');
        return $block->getId();
    }


    protected function setCategoryLandingPage($blockId, $categoryId)
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
