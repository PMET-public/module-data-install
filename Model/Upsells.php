<?php


namespace MagentoEse\DataInstall\Model;

use Magento\TargetRule\Model\RuleFactory as RuleFactory;
use Magento\TargetRule\Model\Rule;
use Magento\TargetRule\Model\ResourceModel\Rule as ResourceModel;
use Magento\Framework\Serialize\SerializerInterface;

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

    public function __construct(
        RuleFactory $ruleFactory,
        Converter $converter,
        ResourceModel $resourceModel,
        SerializerInterface $serializerInterface
    ) {
        $this->ruleFactory = $ruleFactory;
        $this->converter = $converter;
        $this->resourceModel = $resourceModel;
        $this->serializerInterfac = $serializerInterface;
    }

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
        $this->resourceModel->save($upsellModel);
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
