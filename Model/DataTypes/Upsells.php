<?php
/**
 * Copyright © Adobe, Inc. All rights reserved.
 */

namespace MagentoEse\DataInstall\Model\DataTypes;

use Magento\TargetRule\Model\RuleFactory as RuleFactory;
use Magento\TargetRule\Model\Rule;
use Magento\TargetRule\Model\ResourceModel\Rule as ResourceModel;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\App\State;
use Magento\Framework\App\Area;
use MagentoEse\DataInstall\Model\Converter;

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

    /**
     * Upsells constructor.
     * @param RuleFactory $ruleFactory
     * @param Converter $converter
     * @param ResourceModel $resourceModel
     * @param SerializerInterface $serializerInterface
     * @param State $state
     */
    public function __construct(
        RuleFactory $ruleFactory,
        Converter $converter,
        ResourceModel $resourceModel,
        SerializerInterface $serializerInterface,
        State $state
    ) {
        $this->ruleFactory = $ruleFactory;
        $this->converter = $converter;
        $this->resourceModel = $resourceModel;
        $this->serializerInterface = $serializerInterface;
        $this->appState = $state;
    }

    /**
     * @param array $row
     * @param array $settings
     * @return bool
     * @throws \Exception
     */
    public function install(array $row, array $settings)
    {
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
        $upsellModel = $this->ruleFactory->create();
        $upsellModel->setName($row['name']);
        $upsellModel->setIsActive($row['is_active'] =='Y' ? 1 : 0);
        $upsellModel->setConditionsSerialized($this->converter->convertContent($row['conditions_serialized']));
        $upsellModel->setActionsSerialized($this->converter->convertContent($row['actions_serialized']));
        $upsellModel->setPositionsLimit($row['positions_limit']);
        $upsellModel->setApplyTo($applyTo);
        $upsellModel->setSortOrder($row['sort_order']);
        $this->appState->emulateAreaCode(
            Area::AREA_ADMINHTML,
            [$this->resourceModel, 'save'],
            [$upsellModel]
        );

        //$this->resourceModel->save($upsellModel);
        return true;
    }

    /**
     * @param string $data
     * @return mixed
     */
    private function convertSerializedData($data)
    {
        $regexp = '/\%(.*?)\%/';
        preg_match_all($regexp, $data, $matches);
        $replacement = null;
        foreach ($matches[1] as $matchedId => $matchedItem) {
            $extractedData = array_filter(explode(",", $matchedItem));
            foreach ($extractedData as $extractedItem) {
                $separatedData = array_filter(explode('=', $extractedItem));
                if ($separatedData[0] == 'url_key') {
                    if (!$replacement) {
                        $replacement = $this->getCategoryReplacement($separatedData[1]);
                    } else {
                        $replacement .= ',' . $this->getCategoryReplacement($separatedData[1]);
                    }
                }
            }
            if (!empty($replacement)) {
                $data = preg_replace('/' . $matches[0][$matchedId] . '/', $this->serializerInterface->serialize($replacement), $data);
            }
        }
        return $data;
    }
}
