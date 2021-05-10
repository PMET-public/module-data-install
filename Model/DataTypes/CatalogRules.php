<?php
/**
 * Copyright © Adobe. All rights reserved.
 */
namespace MagentoEse\DataInstall\Model\DataTypes;

use Magento\CatalogRule\Api\Data\RuleInterface;
use Magento\CatalogRule\Api\Data\RuleInterfaceFactory;
use Magento\CatalogRule\Api\CatalogRuleRepositoryInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Area as AppArea;
use Magento\Framework\App\State;
use MagentoEse\DataInstall\Model\Converter;
//rule repository does not have getList(), so collection needs to be used
use Magento\CatalogRule\Model\ResourceModel\Rule\CollectionFactory as RuleCollection;
use Magento\Banner\Model\ResourceModel\Banner\CollectionFactory as BannerCollection;
use Magento\Banner\Model\ResourceModel\BannerFactory;
use MagentoEse\DataInstall\Helper\Helper;

class CatalogRules {

    const SIMPLE_ACTIONS = ['by_percent','by_fixed','to_percent','to_fixed'];

    /** @var CatalogRuleRepositoryInterface */
    protected $ruleRepository;

    /** @var RuleInterfaceFactory */
    protected $ruleInterfaceFactory;

    /** @var GroupRepositoryInterface */
    protected $groupRepositoryInterface;

     /** @var SearchCriteriaBuilder */
    protected $searchCriteriaBuilder;

    /** @var State */
    protected $appState;

    /** @var Stores */
    protected $stores;

    /** @var Converter */
    protected $converter;

    /** @var RuleCollection */
    protected $ruleCollection;

    /** @var CustomerGroups */
    protected $customerGroups;

    /** @var BannerCollection */
    protected $bannerCollection;

    /** @var BannerFactory */
    protected $bannerFactory;

    /** @var Helper */
    protected $helper;

    /**
     * CatalogRules constructor.
     * @param RuleInterfaceFactory $ruleInterfaceFactory
     * @param CatalogRuleRepositoryInterface $catalogRuleRepositoryInterface
     * @param GroupRepositoryInterface $groupRepositoryInterface
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param State $appState
     * @param Stores $stores
     * @param Converter $converter
     * @param RuleCollection $ruleCollection
     * @param CustomerGroups $customerGroups
     * @param BannerCollection $bannerCollection
     * @param BannerFactory $bannerFactory
     * @param Helper $helper
     */
    function __construct(RuleInterfaceFactory $ruleInterfaceFactory,
        CatalogRuleRepositoryInterface $catalogRuleRepositoryInterface, GroupRepositoryInterface $groupRepositoryInterface,
        SearchCriteriaBuilder $searchCriteriaBuilder,State $appState, Stores $stores,Converter $converter,RuleCollection $ruleCollection,
        CustomerGroups $customerGroups, BannerCollection $bannerCollection, BannerFactory $bannerFactory, Helper $helper){
        $this->ruleRepository = $catalogRuleRepositoryInterface;
        $this->ruleInterfaceFactory = $ruleInterfaceFactory;
        $this->groupRepositoryInterface = $groupRepositoryInterface;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->appState = $appState;
        $this->stores = $stores;
        $this->converter = $converter;
        $this->ruleCollection = $ruleCollection;
        $this->customerGroups = $customerGroups;
        $this->bannerCollection = $bannerCollection;
        $this->bannerFactory = $bannerFactory;
        $this->helper = $helper;
    }
    //requires customer groups, product attributes, dynamic blocks
    public function install(array $row, array $settings)
    {
        //if there is no name, reject it
        if(empty($row['name'])) {
            $this->helper->printMessage("A row in the Catalog Rules file does not have a value for name. Row is skipped", "warning");
            return true;
        }

        //if discount_amount is non numeric, reject row
        if(empty($row['discount_amount']) || !is_numeric($row['discount_amount'])) {
            $this->helper->printMessage("A row in the Catalog Rules file does not have a valid value for discount_amount. Row is skipped", "warning");
            return true;
        }
        //if there is no simple_action, or if its invalid reject
        if(empty($row['simple_action']) || !$this->validateSimpleAction($row['simple_action'])) {
            $this->helper->printMessage("A row in the Catalog Rules file does not have a valid value for simple_action. Row is skipped", "warning");
            return true;
        }

        //if there is no site_code, take the default
        if(empty($row['site_code'])) {
            $row['site_code'] = $settings['site_code'];
        }
        //convert site codes to ids, put in array
        $siteCodes = explode(",", $row['site_code']);
        $siteIds = [];
        foreach($siteCodes as $siteCode){
            $siteId = $this->stores->getWebsiteId(trim($siteCode));
            if($siteId){
                $siteIds[] = $siteId;
            }

        }
         //set status as active if not defined properly
         $row['is_active']??='Y';
         $row['is_active'] = 'Y' ? 1:0;

        //if no stop_rules_processing, default to N
        if(empty($row['stop_rules_processing'])) {
            $row['stop_rules_processing']=0;
        }
        if(!is_numeric($row['stop_rules_processing'])){
            $row['stop_rules_processing'] = $row['stop_rules_processing']=='Y' ? 1:0;
        }

         //if no sort_order, default to 0
         if(empty($row['sort_order'])) {
            $row['sort_order']=0;
        }

        //if there is no customer_groups default to not logged in and general
        if(empty($row['customer_groups'])) {
            $row['customer_groups'] = 'NOT LOGGED IN,General';
        }
        //convert site codes to ids, put in array
        $groups = explode(",", $row['customer_groups']);
        $groupIds = [];
        foreach($groups as $group){
            $groupId = $this->customerGroups->getCustomerGroupId(trim($group));
            if(is_numeric($groupId)){
                $groupIds[] = $groupId;
            }

        }

        //convert tags in conditions_serialized and actions_serialized
        $row['conditions_serialized'] = $this->converter->convertContent($row['conditions_serialized']);

        //check json format of serialized fields
        $jsonValidate = json_decode($row['conditions_serialized'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->helper->printMessage("A row in the Catalog Rules file has invalid Json data for conditions_serialized. Row is skipped", "warning");
            return true;
        }

        //get banner ids
        $banners =  $groups = explode(",", $row['dynamic_blocks']);
        $bannerIds = [];
        foreach($banners as $banner){
            $bannerToAdd = $this->bannerCollection->create()->addFieldToFilter('name', ['eq' => trim($banner)])->getFirstItem();
            if($bannerToAdd->getId()){
                $bannerIds[] = $bannerToAdd->getId();
            } else{
                $this->helper->printMessage("Dynamic block ".$banner." for Catalog Rule ".$row['name']." does not exist", "warning");
            }
        }

        //load existing rule by name
        /** @var RuleInterface $rule */
        $rule = $this->ruleCollection->create()->addFieldToFilter('name', ['eq' => $row['name']])->getFirstItem();

        if(!$rule->getName()){
            $rule = $this->ruleInterfaceFactory->create();
        }

        $rule->setName($row['name']);
        $rule->setDescription($row['description']);
        $rule->setIsActive($row['is_active']);

        $rule->setConditionsSerialized($row['conditions_serialized']);
        $rule->setStopRulesProcessing($row['stop_rules_processing']);
        $rule->setSortOrder($row['sort_order']);
        $rule->setSimpleAction($row['simple_action']);
        $rule->setDiscountAmount($row['discount_amount']);
        $rule->addData(['website_ids'=>$siteIds]);
        $rule->addData(['customer_group_ids'=>$groupIds]);
        $this->ruleRepository->save($rule);

        //add rule to banners
        $this->bannerFactory->create()->bindBannersToCatalogRule($rule->getId(),$bannerIds);

    }


    /**
     * validateSimpleAction
     *
     * @param  string $simpleAction
     * @return bool
     */
    private function validateSimpleAction($simpleAction){
        if(in_array($simpleAction,self::SIMPLE_ACTIONS)){
            return true;
        }else{
            return false;
        }
    }
}
