<?php
/**
 * Copyright © Magento. All rights reserved.
 */
namespace MagentoEse\DataInstall\Model;

use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Eav\Model\Entity\Attribute\Set;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\Collection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory;
use Magento\Eav\Model\Entity\Attribute\SetFactory;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory as OptionCollectionFactory;
use Magento\Catalog\Helper\Product;
use Magento\Eav\Model\Config as EavConfig;

class ProductAttributes
{

    const DEFAULT_ATTRIBUTE_SET = 'Default';

    /** @var AttributeFactory  */
    protected $attributeFactory;

    /** @var SetFactory  */
    protected $attributeSetFactory;

    /** @var OptionCollectionFactory  */
    protected $attrOptionCollectionFactory;

   /** @var Product  */
    protected $productHelper;

   /** @var EavConfig  */
    protected $eavConfig;

   /** @var  */
    protected $entityTypeId;

    /** @var StoreManagerInterface  */
    protected $storeManager;

    /**
     * ProductAttributes constructor.
     * @param AttributeFactory $attributeFactory
     * @param SetFactory $attributeSetFactory
     * @param OptionCollectionFactory $attrOptionCollectionFactory
     * @param Product $productHelper
     * @param EavConfig $eavConfig
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        AttributeFactory $attributeFactory,
        SetFactory $attributeSetFactory,
        OptionCollectionFactory $attrOptionCollectionFactory,
        Product $productHelper,
        EavConfig $eavConfig,
        StoreManagerInterface $storeManager
    ) {
        $this->attributeFactory = $attributeFactory;
        $this->attributeSetFactory = $attributeSetFactory;
        $this->attrOptionCollectionFactory = $attrOptionCollectionFactory;
        $this->productHelper = $productHelper;
        $this->eavConfig = $eavConfig;
        $this->storeManager = $storeManager;
    }

    /**
     * @param array $data
     * @return bool
     * @throws LocalizedException
     */
    public function install(array $data)
    {
        /** @var Attribute $attribute */
        $attribute = $this->eavConfig->getAttribute('catalog_product', $this->validateCode($data['attribute_code']));
        if (!$attribute) {
            $attribute = $this->attributeFactory->create();
        }
        //TODO:split out between default_label (frontend)and default store view label * may not be necessary
        //TODO: validate frontend_input values
        //TODO: swatch colors or images
        $frontendLabel = explode("\n", $data['frontend_label']);
        if (count($frontendLabel) > 1) {
            $data['frontend_label'] = [];
            $data['frontend_label'][Store::DEFAULT_STORE_ID] = $frontendLabel[0];
            $data['frontend_label'][$this->storeManager->getDefaultStoreView()->getStoreId()] =
                $frontendLabel[1];
        }
        $data['option'] = $this->getOption($attribute, $data);
        $data['source_model'] = $this->productHelper->getAttributeSourceModelByInputType(
            $data['frontend_input']
        );
        $data['backend_model'] = $this->productHelper->getAttributeBackendModelByInputType(
            $data['frontend_input']
        );
        $data += ['is_filterable' => 0, 'is_filterable_in_search' => 0];
        $data['backend_type'] = $attribute->getBackendTypeByInput($data['frontend_input']);

        $attribute->addData($data);
        $attribute->setIsUserDefined(1);

        $attribute->setEntityTypeId($this->getEntityTypeId());
        $attribute->save();
        $attributeId = $attribute->getId();
        //if attribute_set is empty, or not included, set to default
        if (empty($data['attribute_set'])) {
            $data['attribute_set'] = [self::DEFAULT_ATTRIBUTE_SET];
        } else {
            $data['attribute_set'] = explode("\n", $data['attribute_set']);
        }
        if (is_array($data['attribute_set'])) {
            foreach ($data['attribute_set'] as $setName) {
                $setName = trim($setName);
                $attributeSet = $this->processAttributeSet($setName);
                $attributeGroupId = $attributeSet->getDefaultGroupId();

                $attribute = $this->attributeFactory->create()->load($attributeId);
                $attribute
                    ->setAttributeGroupId($attributeGroupId)
                    ->setAttributeSetId($attributeSet->getId())
                    ->setEntityTypeId($this->getEntityTypeId())
                    ->setSortOrder(!empty($data['position']) ?$data['position'] : 999)
                    ->save();
            }
        }

        $this->eavConfig->clear();

        return true;
    }

    /**
     * @param Attribute $attribute
     * @param array $data
     * @return array
     */
    protected function getOption(Attribute $attribute, array $data)
    {
        $result = [];
        $data['option'] = explode("\n", $data['option']);
        /** @var Collection $options */
        $options = $this->attrOptionCollectionFactory->create()
            ->setAttributeFilter($attribute->getId())
            ->setPositionOrder('asc', true)
            ->load();
        foreach ($data['option'] as $value) {
            if (!$options->getItemByColumnValue('value', $value)) {
                $result[] = $value;
            }
        }
        return $result ? $this->convertOption($result) : $result;
    }

    /**
     * Converting attribute options from csv to correct sql values
     *
     * @param array $values
     * @return array
     */
    protected function convertOption(array $values)
    {
        $result = ['order' => [], 'value' => []];
        $i = 0;
        foreach ($values as $value) {
            $result['order']['option_' . $i] = (string)$i;
            $result['value']['option_' . $i] = [0 => $value, 1 => ''];
            $i++;
        }
        return $result;
    }

    /**
     * @return mixed
     * @throws LocalizedException
     */
    protected function getEntityTypeId()
    {
        if (!$this->entityTypeId) {
            $this->entityTypeId = $this->eavConfig->getEntityType(\Magento\Catalog\Model\Product::ENTITY)->getId();
        }
        return $this->entityTypeId;
    }

    /**
     * @param string $setName
     * @return bool|Set|AbstractModel
     * @throws LocalizedException
     */
    protected function processAttributeSet(string $setName)
    {
        /** @var Set $attributeSet */
        $attributeSet = $this->attributeSetFactory->create();
        $setCollection = $attributeSet->getResourceCollection()
            ->addFieldToFilter('entity_type_id', $this->getEntityTypeId())
            ->addFieldToFilter('attribute_set_name', $setName)
            ->load();
        $attributeSet = $setCollection->fetchItem();

        if (!$attributeSet) {
            $attributeSet = $this->attributeSetFactory->create();
            $attributeSet->setEntityTypeId($this->getEntityTypeId());
            $attributeSet->setAttributeSetName($setName);
            $attributeSet->save();
            $defaultSetId = $this->eavConfig->getEntityType(\Magento\Catalog\Model\Product::ENTITY)
                ->getDefaultAttributeSetId();
            $attributeSet->initFromSkeleton($defaultSetId);
            $attributeSet->save();
        }
        return $attributeSet;
    }

    /**
     * @param string $code
     * @return string
     */
    private function validateCode(string $code)
    {
        /*Code may only contain letters (a-z), numbers (0-9) or underscore (_),
        and the first character must be a letter.*/
        //remove all invalid characters
        $code = preg_replace("/[^A-Za-z0-9_]/", '', $code);
        //if the first character is not a letter, add an "m"
        if (!ctype_alpha($code[0])) {
            $code = "m".$code;
        }
        return $code;
    }
}
