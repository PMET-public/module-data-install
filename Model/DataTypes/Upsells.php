<?php
/**
 * Copyright 2022 Adobe, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace MagentoEse\DataInstall\Model\DataTypes;

use Exception;
use Magento\CustomerSegment\Model\ResourceModel\Segment\CollectionFactory as SegmentCollection;
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
    /** @var RuleFactory */
    protected $ruleFactory;

    /** @var SegmentCollection */
    protected $segmentCollection;

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
     * Upsells constructor
     *
     * @param SegmentCollection $segmentCollection
     * @param RuleFactory $ruleFactory
     * @param Converter $converter
     * @param ResourceModel $resourceModel
     * @param SerializerInterface $serializerInterface
     * @param State $state
     * @param RuleCollection $ruleCollection
     * @param Helper $helper
     */
    public function __construct(
        SegmentCollection $segmentCollection,
        RuleFactory $ruleFactory,
        Converter $converter,
        ResourceModel $resourceModel,
        SerializerInterface $serializerInterface,
        State $state,
        RuleCollection $ruleCollection,
        Helper $helper
    ) {
        $this->segmentCollection = $segmentCollection;
        $this->ruleFactory = $ruleFactory;
        $this->converter = $converter;
        $this->resourceModel = $resourceModel;
        $this->serializerInterface = $serializerInterface;
        $this->appState = $state;
        $this->ruleCollection = $ruleCollection;
        $this->helper = $helper;
    }

    /**
     * Install
     *
     * @param array $row
     * @param array $settings
     * @return bool
     * @throws Exception
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
            $row['is_active'] = 'Y';
        }

        if (empty($row['customer_segments'])) {
            $segmentIds = [];
        } else {
            $segmentIds = $this->getCustomerSegmentIds($row['customer_segments']);
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
        $cs=$this->converter->convertContent($row['conditions_serialized']??'');
        $as=$this->converter->convertContent($row['actions_serialized']??'');
        $upsellModel->setPositionsLimit($row['positions_limit']??1);
        $upsellModel->setApplyTo($applyTo);
        $upsellModel->setSortOrder($row['sort_order']??1);
        if (!empty($segmentIds)) {
            $upsellModel->setUseCustomerSegment(1);
        }
        $upsellModel->setCustomerSegmentIds($segmentIds);
        $this->appState->emulateAreaCode(
            Area::AREA_ADMINHTML,
            [$this->resourceModel, 'save'],
            [$upsellModel]
        );

        return true;
    }

    /**
     * Get Customer Segment Ids
     *
     * @param string $segmentNames
     */
    private function getCustomerSegmentIds($segmentNames)
    {
        $segmentIds = [];
        $segmentArray = explode(",", $segmentNames);
        foreach ($segmentArray as $segmentName) {
            $segment = $this->segmentCollection->create()->addFieldToFilter('name', ['eq' => $segmentName])
            ->getFirstItem();
            if ($segment) {
                $segmentIds[]=$segment->getId();
            }
        }
        return $segmentIds;
    }
}
