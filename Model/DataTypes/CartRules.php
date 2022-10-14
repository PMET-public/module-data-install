<?php
/**
 * Copyright 2022 Adobe, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace MagentoEse\DataInstall\Model\DataTypes;

use Magento\SalesRule\Model\Data\Rule;
use Magento\SalesRule\Model\RuleFactory as RuleFactory;
use Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory as RuleCollection;
use Magento\SalesRule\Api\Data\ConditionInterfaceFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\State;
use Magento\Framework\App\Area as AppArea;
use MagentoEse\DataInstall\Model\Converter;
use MagentoEse\DataInstall\Helper\Helper;

class CartRules
{
    /** @var RuleFactory */
    protected $ruleFactory;

    /** @var RuleRepositoryInterface */
    protected $ruleRepositoryInterface;

    /** @var CustomerGroups */
    protected $customerGroups;

    /** @var Stores */
    protected $stores;

    /** @var ConditionInterfaceFactory */
    protected $conditionInterfaceFactory;

    /** @var SearchCriteriaBuilder */
    protected $searchCriteriaBuilder;

    /** @var State */
    protected $appState;

    /** @var Converter */
    protected $converter;

    /** @var RuleCollection */
    protected $ruleCollection;

    /** @var Helper */
    protected $helper;

    /**
     * CartRules constructor
     *
     * @param RuleFactory $ruleFactory
     * @param RuleCollection $ruleCollection
     * @param CustomerGroups $customerGroups
     * @param Stores $stores
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param State $appState
     * @param Converter $converter
     * @param ConditionInterfaceFactory $conditionInterfaceFactory
     * @param Helper $helper
     */
    public function __construct(
        RuleFactory $ruleFactory,
        RuleCollection $ruleCollection,
        CustomerGroups $customerGroups,
        Stores $stores,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        State $appState,
        Converter $converter,
        ConditionInterfaceFactory $conditionInterfaceFactory,
        Helper $helper
    ) {
        $this->ruleFactory = $ruleFactory;
        $this->ruleCollection = $ruleCollection;
        $this->customerGroups = $customerGroups;
        $this->stores = $stores;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->appState = $appState;
        $this->converter = $converter;
        $this->conditionInterfaceFactory = $conditionInterfaceFactory;
        $this->helper = $helper;
    }

    /**
     * Install
     *
     * @param array $row
     * @param array $settings
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function install(array $row, array $settings)
    {
        //required name
        if (empty($row['name'])) {
            $this->helper->logMessage(
                "name is required in the cart_rules data file. Row has been skipped.",
                "warning"
            );
            return true;
        }

        //reject if coupon code is being used by a different rule
        if (!empty($row['coupon_code'])) {
            if ($this->isCouponCodeConflict($row['name'], $row['coupon_code'])) {
                $this->helper->logMessage("The coupon code ".$row['coupon_code']." in cart rule ".$row['name'].
                " is being used in a different rule. Row has been skipped", "warning");
                return true;
            }
        }

        //if websites not defined, use default
        if (empty($row['site_code'])) {
            $row['site_code'] = $settings['site_code'];
        }
        $websiteInputArray = explode(",", $row['site_code']);
        $websiteIds = [];
        foreach ($websiteInputArray as $website) {
            $websiteIds[] = $this->stores->getWebsiteId(trim($website));
        }
        if (count($websiteIds)==0 || $websiteIds[0]=='') {
            $websiteIds[0] = $this->stores->getWebsiteId(trim($settings['site_code']));
        }

        //get customer id groups. get all if defined as 'all' or empty
        if (empty($row['customer_group']) || $row['customer_group']=='' || $row['customer_group']=='all') {
            $groupIds = $this->customerGroups->getAllCustomerGroupIds();
        } else {
            $groupInputArray = explode(",", $row['customer_group']);
            $groupIds = [];
            foreach ($groupInputArray as $group) {
                if ($this->customerGroups->getCustomerGroupId(trim($group))) {
                    $groupIds[]=$this->customerGroups->getCustomerGroupId(trim($group));
                }
            }
            if (empty($groupIds)) {
                $this->helper->logMessage(
                    "A customer group for cart rule ".$row["name"]." does not exist. Row has been skipped.",
                    "warning"
                );
                return true;
            }
        }

        if (empty($row['is_active'])) {
            $row['is_active']=1;
        }
        if ($row['is_active']== 'Y') {
            $row['is_active']=1;
        } elseif ($row['is_active']== 'N') {
            $row['is_active']=0;
        }

        //get existing rule
        $rule = $this->getCartRuleByName($row['name']);

        $row['customer_group_ids'] = $groupIds;
        $row['website_ids'] = $websiteIds;

        if (!empty($row['conditions_serialized'])) {
            $row['conditions_serialized'] = $this->converter->convertContent($row['conditions_serialized']);
        }
        if (!empty($row['actions_serialized'])) {
            $row['actions_serialized'] = $this->converter->convertContent($row['actions_serialized']);
        }
        $rule->loadPost($row);

        //set for amasty, should be done with plugin in amasty module
        // $attributes = $rule->getExtensionAttributes();
        // $attributes['limit'] = 0;
        // $attributes['count'] = 0;
        // $rule->setExtensionAttributes($attributes);

        $this->appState->emulateAreaCode(
            AppArea::AREA_ADMINHTML,
            [$rule, 'save']
        );

        return true;
    }
    /**
     * Get cart rule by name
     *
     * @param string $ruleName
     * @return Rule
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCartRuleByName($ruleName)
    {
        $rule = $this->ruleCollection->create()
        ->addFieldToFilter('name', ['eq' => $ruleName])->getFirstItem();
        if (!$rule) {
              $rule=$this->ruleFactory->create();
        }
        return $rule;
    }

    /**
     * Is there another rule that shares a coupon code
     *
     * @param string $ruleName
     * @param string $couponCode
     * @return boolean
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function isCouponCodeConflict($ruleName, $couponCode)
    {
        $rule = $this->ruleCollection->create()
        ->addFieldToFilter('name', ['neq' => $ruleName])
        ->addFieldToFilter('code', ['eq' => $couponCode])->count();
        if ($rule > 0) {
              return true;
        } else {
            return false;
        }
    }
}
