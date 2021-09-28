<?php
/**
 * Copyright © Adobe. All rights reserved.
 */
namespace MagentoEse\DataInstall\Model\DataTypes;

use Magento\Catalog\Helper\Product;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Model\Entity\Attribute\Set;
use Magento\Eav\Model\Entity\Attribute\SetFactory;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\Collection;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory as OptionCollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use MagentoEse\DataInstall\Model\DataTypes\Stores;
use MagentoEse\DataInstall\Helper\Helper;

class ProductAttributes
{
    const DEFAULT_ATTRIBUTE_SET = 'Default';
    
    //input types not supported swatch_visual,swatch_text,media_image
    const VALID_INPUT_TYPES = ['text','textarea','texteditor','pagebuilder','date','datetime',
    'boolean','multiselect','select','price','weee'];

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

    /** @var Stores  */
    protected $stores;

     /** @var Helper  */
     protected $helper;

    /**
     * ProductAttributes constructor.
     * @param AttributeFactory $attributeFactory
     * @param SetFactory $attributeSetFactory
     * @param OptionCollectionFactory $attrOptionCollectionFactory
     * @param Product $productHelper
     * @param EavConfig $eavConfig
     * @param Stores $stores
     * @param Helper $helper
     */
    public function __construct(
        AttributeFactory $attributeFactory,
        SetFactory $attributeSetFactory,
        OptionCollectionFactory $attrOptionCollectionFactory,
        Product $productHelper,
        EavConfig $eavConfig,
        Stores $stores,
        Helper $helper
    ) {
        $this->attributeFactory = $attributeFactory;
        $this->attributeSetFactory = $attributeSetFactory;
        $this->attrOptionCollectionFactory = $attrOptionCollectionFactory;
        $this->productHelper = $productHelper;
        $this->eavConfig = $eavConfig;
        $this->stores = $stores;
        $this->helper = $helper;
    }

    /**
     * @param array $row
     * @return bool
     * @throws LocalizedException
     */
    public function install(array $row)
    {
        //Required:attribute_code
        if (empty($row['attribute_code'])) {
            $this->helper->printMessage(
                "attribute_code value is required in product_recs.csv. Row skipped",
                "warning"
            );
            return true;
        }
        
        if (!empty($row['store_view_code'])) {
            $storeViewId = $this->stores->getViewId($row['store_view_code']);
        } else {
            $storeViewId = 0;
            $row['store_view_code'] = 'admin';
        }

        //validate frontend_input values
        if (!empty($row['frontend_input']) && !$this->validateFrontendInputs($row['frontend_input'])) {
            $this->helper->printMessage(
                "frontend_input value in product_recs.csv is invalid. Row skipped",
                "warning"
            );
            return true;
        }

        //add/update colums if type is textedit or pagebuilder
        switch ($row['frontend_input']) {
            case "texteditor":
                $row['frontend_input']='textarea';
                $row['is_wysiwyg_enabled']='1';
                $row['is_pagebuilder_enabled']='0';
                break;
            case "pagebuilder":
                $row['frontend_input']='textarea';
                $row['is_wysiwyg_enabled']='1';
                $row['is_pagebuilder_enabled']='1';
                break;
        }
        /** @var Attribute $attribute */
        $attribute = $this->eavConfig->getAttribute('catalog_product', $this->validateCode($row['attribute_code']));
        if (!$attribute->getId()) {
            //Required if new - frontend_label, frontend_input
            if (empty($row['frontend_label']) || empty($row['frontend_input'])) {
                $this->helper->printMessage(
                    "frontend_label and frontend_input are required when created a product attribute. Row skipped",
                    "warning"
                );
                return true;
            }
            
            $attribute = $this->attributeFactory->create();
        } elseif (!empty($row['only_update_sets']) && $row['only_update_sets']=='Y') {
            //facilitate adding existing attributes to set without changes.  Most likely used for system attributes
            $this->setAttributeSets($row, $attribute);
            $this->eavConfig->clear();
            return true;
        }
        
        if (!empty($row['frontend_label'])) {
            $existingLabels = $attribute->getFrontendLabels();
            
            $frontEndLabels = [];
            /** @var FrontendLabel $label */
            foreach ($existingLabels as $label) {
                $frontEndLabels[$label->getStoreId()] = $label->getLabel();
            }

            if ($storeViewId==0) {
                $frontEndLabels[0] = $row['frontend_label'];
            } else {
                $existingLabels = $attribute->getFrontendLabels();
                $frontEndLabels[$storeViewId] = $row['frontend_label'];
                $frontEndLabels[0] = $attribute->getDefaultFrontendLabel();
            }
           
            $row['frontend_label'] = $frontEndLabels;
        }
        if (!empty($row['option'])) {
            $row['option'] = $this->getOption($attribute, $row);
        }
        if (!empty($row['frontend_input'])) {
            $row['source_model'] = $this->productHelper->getAttributeSourceModelByInputType($row['frontend_input']);
            $row['backend_model'] = $this->productHelper->getAttributeBackendModelByInputType($row['frontend_input']);
            $row['backend_type'] = $attribute->getBackendTypeByInput($row['frontend_input']);
        }
        
        $row += ['is_filterable' => 0, 'is_filterable_in_search' => 0];
        
        //remove empty array keys
        $row = $this->removeEmptyColumns($row);
        $attribute->addData($row);
        $attribute->setIsUserDefined(1);

        $attribute->setEntityTypeId($this->getEntityTypeId());
        
        $attribute->save();
        //$attributeId = $attribute->getId();
        $this->setAttributeSets($row, $attribute);

        $this->eavConfig->clear();

        return true;
    }

    protected function validateFrontendInputs($frontendInput)
    {
        // phpcs:ignore Magento2.PHP.ReturnValueCheck.ImproperValueTesting
        if (is_numeric(array_search($frontendInput, self::VALID_INPUT_TYPES))) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param Attribute $attribute
     * @param array $row
     * @return array
     */
    protected function getOption(Attribute $attribute, array $row)
    {
        $result = [];
        $row['option'] = explode("\n", $row['option']);
        /** @var Collection $options */
        $options = $this->attrOptionCollectionFactory->create()
            ->setAttributeFilter($attribute->getId())
            ->setPositionOrder('asc', true)
            ->load();
        foreach ($row['option'] as $value) {
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
            $code = "m" . $code;
        }

        return $code;
    }

    /**
     * @param array $row
     * @param Attribute $attribute
     * @throws LocalizedException
     */
    private function setAttributeSets(array $row, Attribute $attribute): void
    {
     //if attribute_set is empty, or not included, set to default
        if (empty($row['attribute_set'])) {
            $row['attribute_set'] = [self::DEFAULT_ATTRIBUTE_SET];
        } else {
            $row['attribute_set'] = explode("\n", $row['attribute_set']);
        }

        if (is_array($row['attribute_set'])) {
            foreach ($row['attribute_set'] as $setName) {
                $setName = trim($setName);
                $attributeSet = $this->processAttributeSet($setName);
                $attributeGroupId = $attributeSet->getDefaultGroupId();

                $attribute = $this->attributeFactory->create()->load($attribute->getId());
                $attribute
                    ->setAttributeGroupId($attributeGroupId)
                    ->setAttributeSetId($attributeSet->getId())
                    ->setEntityTypeId($this->getEntityTypeId())
                    ->setSortOrder(!empty($row['position']) ? $row['position'] : 999)
                    ->save();
            }
        }
    }

    private function removeEmptyColumns($row)
    {
        foreach ($row as $key => $value) {
            if ($row[$key]=='') {
                unset($row[$key]);
            }
        }
        return $row;
    }
}
