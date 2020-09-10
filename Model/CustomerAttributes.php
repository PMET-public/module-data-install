<?php
/**
 * Copyright © Magento. All rights reserved.
 */
namespace MagentoEse\DataInstall\Model;

use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Eav\Model\Config;
use Magento\Customer\Model\Customer;

class CustomerAttributes
{

    /** @var EavSetupFactory  */
    private $eavSetupFactory;

    /** @var Config  */
    private $eavConfig;

    /** @var AttributeRepositoryInterface  */
    private $attributeRepository;

    /**
     * CustomerAttributes constructor.
     * @param EavSetupFactory $eavSetupFactory
     * @param Config $eavConfig
     * @param AttributeRepositoryInterface $attributeRepository
     */
    public function __construct(
        EavSetupFactory $eavSetupFactory,
        Config $eavConfig,
        AttributeRepositoryInterface $attributeRepository
    ) {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->eavConfig = $eavConfig;
        $this->attributeRepository = $attributeRepository;
    }

    public function install(array $data)
    {
        //TODO; Check for minimum requirements - frontend_label,frontend_input, attribute_code
        $useInForms=['adminhtml_customer','adminhtml_checkout','customer_account_edit','customer_account_create'];
        /*$data= array();
        $data["attribute_code"] = 'preferred_activities';
        $data["frontend_label"] = 'Preferred Activities';
        $data["frontend_input"] = 'multiselect';
        $data["options"]="Running
        Crossfit
        Pilates
        Yoga";*/
        if (empty($data["position"])) {
            $data["position"]=100;
        }

        $mainSettings = [
            'type'         => 'varchar',
            'label'        => $data["frontend_label"],
            'input'        => $data["frontend_input"],
            'required'     => 0,
            'visible'      => 1,
            'is_used_in_grid' => 1,
            'is_filterable_in_grid' => 1,
            'user_defined' => 1,
            'position'     => $data["position"],
            'system'       => 0,
            'multiline_count' => 1,
            'backend' => 'Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend',
            'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Table'
        ];

        $newAttribute = $this->eavConfig->getAttribute(CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER, $data["attribute_code"]);
        $newAttribute->setData('used_in_forms', $useInForms);
        $newAttribute->setData('is_used_for_customer_segment', 1);
        $newAttribute->save($newAttribute);
        $eavSetup = $this->eavSetupFactory->create();
        $eavSetup->addAttribute(CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER, $data["attribute_code"], $mainSettings);
        $eavSetup->addAttributeToSet(
            CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER,
            CustomerMetadataInterface::ATTRIBUTE_SET_ID_CUSTOMER,
            null,
            $data["attribute_code"]
        );

        if (!empty($data["options"])) {
            $this->addOptions(0, $data["attribute_code"], explode(PHP_EOL, $data["options"]));
        }


        $useInForms=['adminhtml_customer','adminhtml_checkout','customer_account_edit','customer_account_create'];
        $attributeOptions = ['Running','Crossfit','Pilates','Yoga'];
        $attributeCode = 'preferred_activities';
        $mainSettings = [
            'type'         => 'varchar',
            'label'        => 'Preferred Activities',
            'input'        => 'multiselect',
            'required'     => 0,
            'visible'      => 1,
            'is_used_in_grid' => 1,
            'is_filterable_in_grid' => 1,
            'user_defined' => 1,
            'position'     => 100,
            'system'       => 0,
            'multiline_count' => 1,
            'backend' => 'Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend',
            'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Table'
        ];
        return true;
    }

    private function addOptions($store, $attributeCode, array $options)
    {
        $attribute = $this->attributeRepository->get(Customer::ENTITY, $attributeCode);
        $option=[];
        $option['attribute_id'] = $attribute->getAttributeId();
        foreach ($options as $key => $value) {
            $option['value'][$value][$store]=$value;
            //foreach($allStores as $store){
            //    $option['value'][$value][$store->getId()] = $value;
            //}
        }

        $eavSetup = $this->eavSetupFactory->create();

        $eavSetup->addAttributeOption($option);
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }
}
