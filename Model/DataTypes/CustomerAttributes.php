<?php
/**
 * Copyright © Adobe. All rights reserved.
 */
namespace MagentoEse\DataInstall\Model\DataTypes;

use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Eav\Model\Config;
use Magento\Customer\Model\Customer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use MagentoEse\DataInstall\Helper\Helper;

class CustomerAttributes
{

    /** @var EavSetupFactory  */
    private $eavSetupFactory;

    /** @var Config  */
    private $eavConfig;

    /** @var AttributeRepositoryInterface  */
    private $attributeRepository;

    /** @var Helper  */
    private $helper;

    /**
     * CustomerAttributes constructor.
     * @param EavSetupFactory $eavSetupFactory
     * @param Config $eavConfig
     * @param AttributeRepositoryInterface $attributeRepository
     * @param Helper $helper
     */
    public function __construct(
        EavSetupFactory $eavSetupFactory,
        Config $eavConfig,
        AttributeRepositoryInterface $attributeRepository,
        Helper $helper
    ) {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->eavConfig = $eavConfig;
        $this->attributeRepository = $attributeRepository;
        $this->helper = $helper;
    }

    /**
     * @param array $data
     * @return bool
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function install(array $data)
    {
        //check for requried fields
        if (empty($data["attribute_code"])) {
            $this->helper->logMessage("attribute_code for customer attribute is required. Row Skipped", "warning");
            return true;
        }
        if (empty($data["frontend_label"])) {
            $this->helper->logMessage("frontend_label for customer attribute ".
            $data["attribute_code"]." is required. Row Skipped", "warning");
            return true;
        }
        if (empty($data["frontend_input"])) {
            $this->helper->logMessage("frontend_input for customer attribute ".
            $data["attribute_code"]." is required. Row Skipped", "warning");
            return true;
        }

        //flatten values coming in from json file attribute_options to option
        if (!empty($data['attribute_options'])) {
            $data = $this->flattenOptions($data, $data['attribute_options']);
        }

        ///set defaults if not included

        if (empty($data["is_used_in_grid"])) {
            $data["is_used_in_grid"]=1;
        }
        if (empty($data["is_filterable_in_grid"])) {
            $data["is_filterable_in_grid"]=1;
        }
        if (empty($data["is_visible_in_grid"])) {
            $data["is_visible_in_grid"]=1;
        }
        if (empty($data["is_searchable_in_grid"])) {
            $data["is_searchable_in_grid"]=1;
        }
        if (empty($data["is_used_for_customer_segment"])) {
            $data["is_used_for_customer_segment"]=1;
        }
        if (empty($data["is_required"])) {
            $data["is_required"]=0;
        }
        if (empty($data["use_in_forms"])) {
            $useInForms=['adminhtml_customer','adminhtml_checkout','customer_account_edit','customer_account_create'];
        } else {
            $useInForms = explode(",", $data["use_in_forms"]);
        }

        // phpcs:ignore Magento2.PHP.LiteralNamespaces.LiteralClassUsage
        $mainSettings = [
            'type'         => 'varchar',
            'label'        => $data["frontend_label"],
            'input'        => $data["frontend_input"],
            'required'     => $data["is_required"]=='Y' ? 1 : 0,
            'visible'      => 1,
            'is_used_in_grid' => $data["is_used_in_grid"]=='Y' ? 1 : 0,
            'is_filterable_in_grid' => $data["is_filterable_in_grid"]=='Y' ? 1 : 0,
            'is_visible_in_grid' => $data["is_used_in_grid"]=='Y' ? 1 : 0,
            'is_searchable_in_grid' => $data["is_searchable_in_grid"]=='Y' ? 1 : 0,
            'user_defined' => 1,
            'position'     => (empty($data["position"])) ? 100 : $data["position"],
            'system'       => 0,
            'multiline_count' => 1,
            // phpcs:ignore Magento2.PHP.LiteralNamespaces.LiteralClassUsage
            'backend' => 'Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend',
            // phpcs:ignore Magento2.PHP.LiteralNamespaces.LiteralClassUsage
            'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Table'
        ];
        $data['attribute_code'] = $this->validateCode($data['attribute_code']);
        $newAttribute = $this->eavConfig->getAttribute(
            CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER,
            $data["attribute_code"]
        );
        $newAttribute->setData('used_in_forms', $useInForms);
        $newAttribute->setData('is_used_for_customer_segment', $data["is_used_for_customer_segment"]=='Y' ? 1 : 0);
        $newAttribute->save($newAttribute);
        $eavSetup = $this->eavSetupFactory->create();
        $eavSetup->addAttribute(
            CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER,
            $data["attribute_code"],
            $mainSettings
        );
        $eavSetup->addAttributeToSet(
            CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER,
            CustomerMetadataInterface::ATTRIBUTE_SET_ID_CUSTOMER,
            null,
            $data["attribute_code"]
        );
        //add select options
        if (!empty($data["options"])) {
            $this->addOptions(0, $data["attribute_code"], explode(PHP_EOL, $data["options"]));
        }

        //TODO:default - need to get options if its multi or select

        return true;
    }

    /**
     * @param $store
     * @param $attributeCode
     * @param array $options
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function addOptions($store, $attributeCode, array $options)
    {
        $attribute = $this->attributeRepository->get(Customer::ENTITY, $attributeCode);

        $removeOptions = $attribute->getOptions();

        $optionsToRemove = [];
        foreach ($removeOptions as $removeOption) {
            if ($removeOption['value']) {
                $optionsToRemove['delete'][$removeOption['value']] = true;
                $optionsToRemove['value'][$removeOption['value']] = true;
            }
        }
        $option=[];
        $option['attribute_id'] = $attribute->getAttributeId();
        foreach ($options as $key => $value) {
            $option['value']['a'.(string)$value][$store]=trim($value);
        }

        $eavSetup = $this->eavSetupFactory->create();
        $eavSetup->addAttributeOption($optionsToRemove);
        $eavSetup->addAttributeOption($option);
    }

    /**
     * @param string $code
     * @return string|string[]|null
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
     * @param array $row
     * @param array $options
     * @return array
     */
    private function flattenOptions($row, $options)
    {
        $optionArray = [];
        foreach ($options as $key => $value) {
            $optionArray[] = $value->label;
        }
        $row['options'] = implode("\n", $optionArray);
        return $row;
    }
}
