<?php
/**
 * Copyright © Adobe, Inc. All rights reserved.
 */

namespace MagentoEse\DataInstall\Model\DataTypes;

use Magento\TargetRule\Model\RuleFactory as RuleFactory;
use Magento\TargetRule\Model\Rule;
use Magento\TargetRule\Model\ResourceModel\Rule as ResourceModel;
use Magento\TargetRule\Model\ResourceModel\Rule\CollectionFactory as RuleCollection;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\App\State;
use Magento\Framework\App\Area;
use MagentoEse\DataInstall\Model\Converter;
use MagentoEse\DataInstall\Helper\Helper;

class Upsells
{
    /** @var RuleFactory\ */
    protected $ruleFactory;

    /** @var Converter */
    protected $converter;

    /** @var SerializerInterface */
    protected $serializerInterface;

    /** @var ResourceModel */
    protected $resourceModel;

    /** @var State */
    protected $appState;

    /** @var RuleCollection */
    protected $ruleCollection;

    /** @var Helper */
    protected $helper;

    /**
     * Upsells constructor.
     * @param RuleFactory $ruleFactory
     * @param Converter $converter
     * @param ResourceModel $resourceModel
     * @param SerializerInterface $serializerInterface
     * @param State $state
     * @param RuleCollection $ruleCollection
     * @param Helper $helper
     */
    public function __construct(
        RuleFactory $ruleFactory,
        Converter $converter,
        ResourceModel $resourceModel,
        SerializerInterface $serializerInterface,
        State $state,
        RuleCollection $ruleCollection,
        Helper $helper
    ) {
        $this->ruleFactory = $ruleFactory;
        $this->converter = $converter;
        $this->resourceModel = $resourceModel;
        $this->serializerInterface = $serializerInterface;
        $this->appState = $state;
        $this->ruleCollection = $ruleCollection;
        $this->helper = $helper;
    }

    /**
     * @param array $row
     * @param array $settings
     * @return bool
     * @throws \Exception
     */
    public function install(array $row, array $settings)
    {
        if (empty($row['apply_to'])) {
            $this->helper->logMessage("Related Product Rule apply_to column is missing. Row skipped", "warning");
            return true;
        }
        switch (strtolower($row['apply_to'])) {
            case "upsell":
                $applyTo = Rule::UP_SELLS;
                break;

            case "crosssell":
                $applyTo = Rule::CROSS_SELLS;
                break;

            default:
                $applyTo = Rule::RELATED_PRODUCTS;
                break;
        }
        /** @var Rule $upsellModel */
        if (empty($row['name'])) {
            $this->helper->logMessage("Related Product Rule missing a name. Row skipped", "warning");
            return;
        }
        if (empty($row['is_active'])) {
            $row['is_active'] == 'Y';
        }
                
        $upsellModel = $this->ruleCollection->create()
            ->addFieldToFilter('name', ['eq' => $row['name']])->getFirstItem();
        if (!$upsellModel) {
            $upsellModel = $this->ruleFactory->create();
        }
        $upsellModel->setName($row['name']);
        $upsellModel->setIsActive($row['is_active'] =='Y' ? 1 : 0);
        $upsellModel->setConditionsSerialized($this->converter->convertContent($row['conditions_serialized']??''));
        $upsellModel->setActionsSerialized($this->converter->convertContent($row['actions_serialized']??''));
        $upsellModel->setPositionsLimit($row['positions_limit']??1);
        $upsellModel->setApplyTo($applyTo);
        $upsellModel->setSortOrder($row['sort_order']??1);
        $this->appState->emulateAreaCode(
            Area::AREA_ADMINHTML,
            [$this->resourceModel, 'save'],
            [$upsellModel]
        );

        return true;
    }
}
