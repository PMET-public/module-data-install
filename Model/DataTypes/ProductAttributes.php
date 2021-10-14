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
use Magento\Catalog\Model\ResourceModel\Eav\Attribute as eavAttribute;

class ProductAttributes
{
    const DEFAULT_ATTRIBUTE_SET = 'Default';
    
    //input types not supported swatch_visual,swatch_text,media_image
    const VALID_INPUT_TYPES = ['text','textarea','textedit','pagebuilder','date','datetime',
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

        //validate frontend_input values
        if (!empty($row['frontend_input']) && !$this->validateFrontendInputs($row['frontend_input'])) {
            $this->helper->printMessage(
                "frontend_input value in product_attributes.csv is invalid. ".$row['attribute_code']. " row skipped",
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
                    "frontend_label and frontend_input are required when created a product attribute. "
                    .$row['attribute_code']. " row skipped",
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
            $row['swatches'] = $row['option'];
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
      
        //are there watches to be set
        if (!empty($row['additional_data'])) {
            //validate the correct information in additional_data column
            if ($this->isSwatchType($row['additional_data'])) {
                $this->setSwatches($attribute, $row);
            }
        }
        return true;
    }

    protected function setSwatches($attribute, $row)
    {
        //load current option values
        $attributeData['option'] = $this->getExistingOptions($attribute);
        $swatchInfo = $this->isSwatchType($row['additional_data']);
        $attributeData['frontend_input'] = $row['frontend_input'];
        $attributeData['swatch_input_type'] = $swatchInfo['swatch_input_type'];
        $attributeData['update_product_preview_image'] = $swatchInfo['update_product_preview_image'];
        $attributeData['use_product_image_for_swatch'] = $swatchInfo['use_product_image_for_swatch'];
        if ($swatchInfo['swatch_input_type']=='visual') {
            //set up data structure for swatch values
            $attributeData['optionvisual'] = $this->getOptionSwatch($attributeData);
            //get first swatch value and set as default - disabled
            //$attributeData['defaultvisual'] = $this->getOptionDefaultVisual($attributeData, $row['swatches']);
            //assign swatch value to option
            $attributeData['swatchvisual'] = $this->getOptionSwatchVisual($attributeData, $row['swatches']);
            $attribute->addData($attributeData);
            $attribute->save();
        } elseif ($swatchInfo['swatch_input_type']=='text') {
            $attributeData['swatchtext'] = $this->getOptionSwatch($attributeData);
            //get first swatch value and set as default - disabled
            //$attributeData['defaulttext'] = $this->getOptionDefaultText($attributeData, $row['swatches']);
            $attributeData['optiontext'] = $this->getOptionSwatchText($attributeData, $row['swatches']);
            $attribute->addData($attributeData);
            $attribute->save();
        }
    }

    /** Map swatch values to option value id keys
     * @param array $attributeData
     * @return array
     */
    private function getOptionSwatchVisual(array $attributeData, $swatches)
    {
        $optionSwatch = ['value' => []];
        $optionMap =  $this->getSwatchArray($swatches);
        foreach ($attributeData['option'] as $optionKey => $optionValue) {
            $optionSwatch['value'][$optionKey] = $optionMap[$optionValue];
        }
        return $optionSwatch;
    }

    /**
     * @param array $attributeData
     * @return array
     */
    private function getOptionSwatchText(array $attributeData, $swatches)
    {
        $optionSwatch = ['value' => []];
        $optionMap =  $this->getSwatchArray($swatches);
        foreach ($attributeData['option'] as $optionKey => $optionValue) {
            $optionSwatch['value'][$optionKey] = [$optionMap[$optionValue], ''];
        }
        return $optionSwatch;
    }

    private function getSwatchArray($swatchData)
    {
        $swatchArray = [];
        $swatchData = explode("\n", $swatchData);
        foreach ($swatchData as $swatch) {
            $swatchComponants = explode('|', $swatch);
            //if there is no key/value use the key as value
            if (empty($swatchComponants[1])) {
                $swatchComponants[1] = $swatchComponants[0];
            }
            $swatchArray[$swatchComponants[0]]=$swatchComponants[1];
        }
        return $swatchArray;
    }

    /** get the first defined option value to set as default
     * @param array $attributeData
     * @return array
     */
    private function getOptionDefaultVisual(array $attributeData, $swatchData)
    {
        $optionSwatch = $this->getOptionSwatchVisual($attributeData, $swatchData);
        return [array_keys($optionSwatch['value'])[0]];
    }

    /**
     * @param array $attributeData
     * @return array
     */
    private function getOptionDefaultText(array $attributeData, $swatchData)
    {
        $optionSwatch = $this->getOptionSwatchText($attributeData, $swatchData);
        return [array_keys($optionSwatch['value'])[0]];
    }

    /** return current options values with id as key
     * @param eavAttribute $attribute
     * @return array
     */
    private function getExistingOptions(eavAttribute $attribute)
    {
        $options = [];
        $attributeId = $attribute->getId();
        if ($attributeId) {
            $optionCollection = $this->loadOptionCollection($attributeId);
            /** @var \Magento\Eav\Model\Entity\Attribute\Option $option */
            foreach ($optionCollection as $option) {
                $options[$option->getId()] = $option->getValue();
            }
        }
        return $options;
    }

    /**
     * @param $attributeId
     * @return void
     */
    private function loadOptionCollection($attributeId)
    {
        $optionCollection = $this->attrOptionCollectionFactory->create()
            ->setAttributeFilter($attributeId)
            ->setPositionOrder('asc', true)
            ->load();
        return $optionCollection;
    }

    /** set up data structure for swatch values
     * @param array $attributeData
     * @return array
     */
    protected function getOptionSwatch(array $attributeData)
    {
        $optionSwatch = ['order' => [], 'value' => [], 'delete' => []];
        $i = 0;
        foreach ($attributeData['option'] as $optionKey => $optionValue) {
            $optionSwatch['delete'][$optionKey] = '';
            $optionSwatch['order'][$optionKey] = (string)$i++;
            $optionSwatch['value'][$optionKey] = [$optionValue, ''];
        }
        return $optionSwatch;
    }

    //return swatch type based on information in additional_data column
    protected function isSwatchType($additionalData)
    {
        // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
        //{"swatch_input_type":"visual","update_product_preview_image":"0","use_product_image_for_swatch":"0"}
        $swatchInfo = json_decode($additionalData, true);
        if ($swatchInfo['swatch_input_type']) {
            return $swatchInfo;
        } else {
            return false;
        }
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
            //if the option is formatted as a swatch (Green|#32faaa), remove the swatch
            if (strpos($value, "|")!==false) {
                $optionValue = substr($value, 0, strpos($value, "|"));
            } else {
                $optionValue = $value;
            }
            
            if (!$options->getItemByColumnValue('value', $optionValue)) {
                $result[] = $optionValue;
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
