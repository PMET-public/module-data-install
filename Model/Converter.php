<?php
/**
 * Copyright © Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MagentoEse\DataInstall\Model;

use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\CustomerSegment\Model\ResourceModel\Segment\CollectionFactory as SegmentCollectionFactory;
use Magento\Eav\Model\Config;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as ProductAttributeCollectionFactory;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory  as AttributeOptionCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Cms\Api\Data\BlockInterface;
use Magento\Cms\Api\BlockRepositoryInterface;
use Magento\Banner\Model\ResourceModel\Banner\CollectionFactory as BannerCollection;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Customer\Model\ResourceModel\Attribute\CollectionFactory as CustomerAttributeCollectionFactory;
use Magento\Store\Api\StoreRepositoryInterface;

/**
 * Class Converter
 */
class Converter
{
    /**
     * @var CategoryCollectionFactory
     */
    protected $categoryFactory;

    /**
     * @var ProductAttributeCollectionFactory
     */
    protected $productAttributeCollectionFactory;

    /**
     * @var AttributeOptionCollectionFactory
     */
    protected $attrOptionCollectionFactory;

    /**
     * @var array
     */
    protected $productAttributeCodeOptionsPair;

    /**
     * @var array
     */
    protected $productAttributeCodeOptionValueIdsPair;

    /**
     * @var array
     */
    protected $customerAttributeCodeOptionsPair;

    /**
     * @var array
     */
    protected $customerAttributeCodeOptionValueIdsPair;

    /**
     * @var ProductCollectionFactory
     */
    protected $productCollectionFactory;

    /** @var Config  */
    protected $eavConfig;

    /** @var SegmentCollectionFactory  */
    protected $segmentCollectionFactory;

    /** @var BlockRepositoryInterface  */
    protected $blockRepository;

    /** @var BannerCollection  */
    protected $bannerCollection;

    /** @var AttributeSetRepositoryInterface  */
    protected $attributeSetRepository;

    /** @var GroupRepositoryInterface  */
    protected $groupRepository;

    /** @var CustomerAttributeCollectionFactory  */
    protected $customerAttributeCollectionFactory;

    /** @var SearchCriteriaBuilder  */
    protected $searchCriteriaBuilder;

    /** @var StoreRepositoryInterface  */
    protected $storeRepository;

    /**
     * Converter constructor.
     * @param CategoryCollectionFactory $categoryFactory
     * @param Config $eavConfig
     * @param ProductAttributeCollectionFactory $productAttributeCollectionFactory
     * @param AttributeOptionCollectionFactory $attrOptionCollectionFactory
     * @param ProductCollectionFactory $productCollectionFactory
     * @param SegmentCollectionFactory $segmentCollectionFactory
     * @param BlockRepositoryInterface $blockRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param BannerCollection $bannerCollection
     * @param AttributeSetRepositoryInterface $attributeSetRepository
     * @param GroupRepositoryInterface $groupRepository
     * @param CustomerAttributeCollectionFactory $customerAttributeCollectionFactory
     */
    public function __construct(
        CategoryCollectionFactory $categoryFactory,
        Config $eavConfig,
        ProductAttributeCollectionFactory $productAttributeCollectionFactory,
        AttributeOptionCollectionFactory $attrOptionCollectionFactory,
        ProductCollectionFactory $productCollectionFactory,
        SegmentCollectionFactory $segmentCollectionFactory,
        BlockRepositoryInterface $blockRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        BannerCollection $bannerCollection,
        AttributeSetRepositoryInterface $attributeSetRepository,
        GroupRepositoryInterface $groupRepository,
        CustomerAttributeCollectionFactory $customerAttributeCollectionFactory,
        StoreRepositoryInterface $storeRepository
    ) {
        $this->categoryFactory = $categoryFactory;
        $this->eavConfig = $eavConfig;
        $this->productAttributeCollectionFactory = $productAttributeCollectionFactory;
        $this->attrOptionCollectionFactory = $attrOptionCollectionFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->segmentCollectionFactory = $segmentCollectionFactory;
        $this->blockRepository = $blockRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->bannerCollection = $bannerCollection;
        $this->attributeSetRepository = $attributeSetRepository;
        $this->groupRepository = $groupRepository;
        $this->customerAttributeCollectionFactory = $customerAttributeCollectionFactory;
        $this->storeRepository = $storeRepository;
    }
    //TODO: What to return if something is missing, like a block that requires a banner that doesnt exist yet

    public function convertContent($content)
    {
        return $this->replaceMatches($content);
    }

    /**
     * Get formatted array value
     *
     * @param mixed $value
     * @param string $separator
     * @return array
     */
    protected function getArrayValue($value, $separator = "/")
    {
        if (is_array($value)) {
            return $value;
        }
        if (false !== strpos($value, $separator)) {
            $value = array_filter(explode($separator, $value));
        }
        return !is_array($value) ? [$value] : $value;
    }

    /**
     * @param string $content
     * @return mixed
     */
    protected function replaceMatches($content)
    {
        $matches = $this->getMatches($content);
        if (!empty($matches['value'])) {
            $replaces = $this->getReplaces($matches);
            $content = preg_replace($replaces['regexp'], $replaces['value'], $content);
        }
        return $content;
    }

    /**
     * @param string $content
     * @return array
     */
    protected function getMatches($content)
    {
        $regexp = '/{{(category[^ ]*) key="([^"]+)"}}/';
        preg_match_all($regexp, $content, $matchesCategory);
        $regexp = '/{{(producturl[^ ]*) sku="([^"]+)"}}/';
        preg_match_all($regexp, $content, $matchesProductUrl);
        $regexp = '/{{(productattribute) code="([^"]*)"}}/';
        preg_match_all($regexp, $content, $matchesProductAttribute);
        $regexp = '/{{(customerattribute) code="([^"]*)"}}/';
        preg_match_all($regexp, $content, $matchesCustomerAttribute);
        $regexp = '/{{(segment) name="([^"]*)"}}/';
        preg_match_all($regexp, $content, $matchesSegment);
        $regexp = '/{{(block) code="([^"]*)"}}/';
        preg_match_all($regexp, $content, $matchesBlock);
        $regexp = '/{{(dynamicblock) name="([^"]*)"}}/';
        preg_match_all($regexp, $content, $matchesDynamicBlock);
        $regexp = '/{{(attributeset) name="([^"]*)"}}/';
        preg_match_all($regexp, $content, $matchesAttributeSet);
        $regexp = '/{{(customergroup) name="([^"]*)"}}/';
        preg_match_all($regexp, $content, $matchesCustomerGroup);
        return [
            'type' => $matchesCategory[1] + $matchesProductAttribute[1]
                + $matchesCustomerAttribute[1]
                + $matchesSegment[1]
                + $matchesBlock[1]
                + $matchesDynamicBlock[1]
                + $matchesAttributeSet[1]
                + $matchesCustomerGroup[1]
                + $matchesProductUrl[1],
            'value' => $matchesCategory[2] + $matchesProductAttribute[2]
                + $matchesCustomerAttribute[2]
                + $matchesSegment[2]
                + $matchesBlock[2]
                + $matchesDynamicBlock[2]
                + $matchesAttributeSet[2]
                + $matchesCustomerGroup[2]
                + $matchesProductUrl[2]
        ];
    }

    /**
     * @param array $matches
     * @return array
     */
    protected function getReplaces($matches)
    {
        $replaceData = [];

        foreach ($matches['value'] as $matchKey => $matchValue) {
            $callback = "matcher" . ucfirst(trim($matches['type'][$matchKey]));
            $matchResult = call_user_func_array([$this, $callback], [$matchValue]);
            if (!empty($matchResult)) {
                $replaceData = array_merge_recursive($replaceData, $matchResult);
            }
        }
        return $replaceData;
    }

    /**
     * @param string $urlAttributes
     * @return string
     */
    protected function getUrlFilter($urlAttributes)
    {
        $separatedAttributes = $this->getArrayValue($urlAttributes, ';');
        $urlFilter = null;
        foreach ($separatedAttributes as $attributeNumber => $attributeValue) {
            $attributeData = $this->getArrayValue($attributeValue, '=');
            $attributeOptions = $this->productConverter->getAttributeOptions($attributeData[0]);
            $attributeValue = $attributeOptions->getItemByColumnValue('value', $attributeData[1]);
            if ($attributeNumber == 0) {
                $urlFilter = $attributeData[0] . '=' . $attributeValue->getId();
                continue;
            }
            $urlFilter .= '&' . $attributeData[0] . '=' . $attributeValue->getId();
        }
        return $urlFilter;
    }

    /**  ****************  */
    /**  CUSTOMER GROUP   */
    /**  *************   */

    public function matcherCustomergroup($matchValue)
    {
        //* use _customerGroup_name as token */
        $search = $this->searchCriteriaBuilder
            ->addFilter(GroupInterface::CODE, $matchValue, 'eq')->create();
        $groupList = $this->groupRepository->getList($search)->getItems();
        foreach ($groupList as $group) {
            $groupId = $group->getId();
            $replaceData['regexp'][] = '/{{customergroup name="' . $matchValue . '"}}/';
            $replaceData['value'][] = $groupId;
        }
        return $replaceData;
    }

    /**  ****************  */
    /**  ATTRIBUTE SET    */
    /**  *************   */

    public function matcherAttributeset($matchValue)
    {
        $replaceData = [];
        $search = $this->searchCriteriaBuilder
            ->addFilter('attribute_set_name', $matchValue, 'eq')
            ->addFilter('entity_type_id', 4, 'eq')->create();
        $attributeSetList = $this->attributeSetRepository->getList($search)->getItems();
        foreach ($attributeSetList as $attributeSet) {
            $attributeSetId = $attributeSet->getId();
            $replaceData['regexp'][] = '/{{attributeset name="' . $matchValue . '"}}/';
            $replaceData['value'][] = $attributeSetId;
        }

        return $replaceData;
    }

    /**  ********  */
    /**  BLOCKS    */
    /**  *******   */

    /**
     * @param $matchValue
     * @return array
     * @throws LocalizedException
     */
    protected function matcherBlock($matchValue)
    {
        $replaceData = [];
        $search = $this->searchCriteriaBuilder
            ->addFilter(BlockInterface::IDENTIFIER, $matchValue, 'eq')->create();
        $blockList = $this->blockRepository->getList($search)->getItems();
        foreach ($blockList as $block) {
            $blockId = $block->getId();
            $replaceData['regexp'][] = '/{{block code="' . $matchValue . '"}}/';
            $replaceData['value'][] = $blockId;
        }

        return $replaceData;
    }

    /**  *****************  */
    /**  DYNAMIC BLOCKS    */
    /**  **************   */

    /**
     * @param $matchValue
     * @return array
     * @throws LocalizedException
     */
    protected function matcherDynamicblock($matchValue)
    {
        $replaceData = [];
        $banner = $this->bannerCollection->create()->addFieldToFilter('name', $matchValue)->getFirstItem();
        if (!empty($banner)) {
            $bannerId = $banner->getId();
            $replaceData['regexp'][] = '/{{dynamicblock name="' . $matchValue . '"}}/';
            $replaceData['value'][] = $bannerId;
        }

        return $replaceData;
    }

    /**  ********   */
    /**  SEGMENTS   */
    /**  ********   */

    /**
     * @param $matchValue
     * @return array
     */
    protected function matcherSegment($matchValue)
    {
        $replaceData = [];
        $segment = $this->segmentCollectionFactory->create()->addFieldToFilter('name', $matchValue) ->getFirstItem();
        if (!empty($segment)) {
            $segmentId = $segment->getId();
            $replaceData['regexp'][] = '/{{segment name="' . $matchValue . '"}}/';
            $replaceData['value'][] = $segmentId;
        }
        return $replaceData;
    }

    /**  ********   */
    /**  CATEGORY   */
    /**  ********   */

    /**
     * @param string $matchValue
     * @return array
     * @throws LocalizedException
     */
    protected function matcherCategory($matchValue)
    {
        $replaceData = [];
        $category = $this->getCategoryByUrlKey($matchValue);
        if (!empty($category)) {
            $categoryUrl = $category->getRequestPath();
            $replaceData['regexp'][] = '/{{category key="' . $matchValue . '"}}/';
            $replaceData['value'][] = '{{store url=""}}' . $categoryUrl;
        }
        return $replaceData;
    }

    /**
     * @param $urlKey
     * @return DataObject
     * @throws LocalizedException
     */
    protected function getCategoryByUrlKey($urlKey)
    {
        $category = $this->categoryFactory->create()
            ->addAttributeToFilter('url_key', $urlKey)
            ->addUrlRewriteToResult()
            ->getFirstItem();
        return $category;
    }

    /**  *******   */
    /**  PRODUCT   */
    /**  *******   */

    /**
     * @param string $matchValue
     * @return array
     */
    protected function matcherProductUrl($matchValue)
    {
        $replaceData = [];
        $productCollection = $this->productCollectionFactory->create();
        $productItem = $productCollection->addAttributeToFilter('sku', $matchValue)
            ->addUrlRewrite()
            ->getFirstItem();
        $productUrl = null;
        if ($productItem) {
            $productUrl = '{{store url=""}}' .  $productItem->getRequestPath();
        }
        $replaceData['regexp'][] = '/{{product sku="' . $matchValue . '"}}/';
        $replaceData['value'][] = $productUrl;
        return $replaceData;
    }

    /**  ******************   */
    /**  CUSTOMER ATTRIBUTES  */
    /**  ****************   */
    //TODO:do we need to worry about non select attribute types
    /**
     * @param string $matchValue
     * @return array
     */
    protected function matcherCustomerattribute($matchValue)
    {
        $replaceData = [];
        if (strpos($matchValue, ':') === false) {
            return $replaceData;
        }
        list($code, $value) = explode(':', $matchValue);

        if (!empty($code) && !empty($value)) {
            $replaceData['regexp'][] = '/{{customerattribute code="' . $matchValue . '"}}/';
            $replaceData['value'][] = sprintf('%03d', $this->getCustomerAttributeOptionValueId($code, $value));
        }
        return $replaceData;
    }

    /**
     * Get attribute options by attribute code
     *
     * @param string $attributeCode
     * @return \Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\Collection|null
     */
    protected function getCustomerAttributeOptions($attributeCode)
    {
        if (!$this->customerAttributeCodeOptionsPair || !isset($this->customerAttributeCodeOptionsPair[$attributeCode])) {
            $this->loadCustomerAttributeOptions($attributeCode);
        }
        return isset($this->customerAttributeCodeOptionsPair[$attributeCode])
            ? $this->customerAttributeCodeOptionsPair[$attributeCode]
            : null;
    }

    /**
     * Loads all attributes with options for attribute
     *
     * @param string $attributeCode
     * @return $this
     */
    protected function loadCustomerAttributeOptions($attributeCode)
    {
        /** @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection $collection */
        $collection = $this->customerAttributeCollectionFactory->create();
        $collection->addFieldToSelect(['attribute_code', 'attribute_id']);
        $collection->addFieldToFilter('attribute_code', $attributeCode);
        $collection->setFrontendInputTypeFilter(['in' => ['select', 'multiselect']]);
        foreach ($collection as $item) {
            $options = $this->attrOptionCollectionFactory->create()
                ->setAttributeFilter($item->getAttributeId())->setPositionOrder('asc', true)->load();
            $this->customerAttributeCodeOptionsPair[$item->getAttributeCode()] = $options;
        }
        return $this;
    }

    /**
     * Find attribute option value pair
     *
     * @param string $attributeCode
     * @param string $value
     * @return mixed
     */
    protected function getCustomerAttributeOptionValueId($attributeCode, $value)
    {
        if (!empty($this->customerAttributeCodeOptionValueIdsPair[$attributeCode][$value])) {
            return $this->customerAttributeCodeOptionValueIdsPair[$attributeCode][$value];
        }

        $options = $this->getCustomerAttributeOptions($attributeCode);
        $opt = [];
        if ($options) {
            foreach ($options as $option) {
                $opt[$option->getValue()] = $option->getId();
            }
        }
        $this->customerAttributeCodeOptionValueIdsPair[$attributeCode] = $opt;
        if (isset($this->customerAttributeCodeOptionValueIdsPair[$attributeCode][$value])) {
            return $this->customerAttributeCodeOptionValueIdsPair[$attributeCode][$value];
        } else {
            return $value;
        }
    }

    /**  ******************   */
    /**  PRODUCT ATTRIBUTES  */
    /**  ****************   */
    //TODO:do we need to worry about non select attribute types
    /**
     * @param string $matchValue
     * @return array
     */
    protected function matcherProductAttribute($matchValue)
    {
        $replaceData = [];
        if (strpos($matchValue, ':') === false) {
            return $replaceData;
        }
        list($code, $value) = explode(':', $matchValue);

        if (!empty($code) && !empty($value)) {
            $replaceData['regexp'][] = '/{{productattribute code="' . $matchValue . '"}}/';
            $replaceData['value'][] = sprintf('%03d', $this->getProductAttributeOptionValueId($code, $value));
        }
        return $replaceData;
    }

    /**
     * Get attribute options by attribute code
     *
     * @param string $attributeCode
     * @return \Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\Collection|null
     */
    protected function getProductAttributeOptions($attributeCode)
    {
        if (!$this->productAttributeCodeOptionsPair || !isset($this->productAttributeCodeOptionsPair[$attributeCode])) {
            $this->loadProductAttributeOptions($attributeCode);
        }
        return isset($this->productAttributeCodeOptionsPair[$attributeCode])
            ? $this->productAttributeCodeOptionsPair[$attributeCode]
            : null;
    }

    /**
     * Loads all attributes with options for attribute
     *
     * @param string $attributeCode
     * @return $this
     */
    protected function loadProductAttributeOptions($attributeCode)
    {
        /** @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection $collection */
        $collection = $this->productAttributeCollectionFactory->create();
        $collection->addFieldToSelect(['attribute_code', 'attribute_id']);
        $collection->addFieldToFilter('attribute_code', $attributeCode);
        $collection->setFrontendInputTypeFilter(['in' => ['select', 'multiselect']]);
        foreach ($collection as $item) {
            $options = $this->attrOptionCollectionFactory->create()
                ->setAttributeFilter($item->getAttributeId())->setPositionOrder('asc', true)->load();
            $this->productAttributeCodeOptionsPair[$item->getAttributeCode()] = $options;
        }
        return $this;
    }

    /**
     * Find attribute option value pair
     *
     * @param string $attributeCode
     * @param string $value
     * @return mixed
     */
    protected function getProductAttributeOptionValueId($attributeCode, $value)
    {
        if (!empty($this->productAttributeCodeOptionValueIdsPair[$attributeCode][$value])) {
            return $this->productAttributeCodeOptionValueIdsPair[$attributeCode][$value];
        }

        $options = $this->getProductAttributeOptions($attributeCode);
        $opt = [];
        if ($options) {
            foreach ($options as $option) {
                $opt[$option->getValue()] = $option->getId();
            }
        }
        $this->productAttributeCodeOptionValueIdsPair[$attributeCode] = $opt;
        if (isset($this->productAttributeCodeOptionValueIdsPair[$attributeCode][$value])) {
            return $this->productAttributeCodeOptionValueIdsPair[$attributeCode][$value];
        } else {
            return $value;
        }
    }

    public function getStoreidByCode($storeCode){
        return $this->storeRepository->get($storeCode)->getId();
    }
}
