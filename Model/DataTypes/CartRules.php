<?php
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
     * @param RuleFactory $ruleFactory
     * @param RuleCollection $RuleCollection
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
    ) 
    {
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

    public function install(array $row, array $settings)
    {
        //required name
        if (empty($row['name'])){
            $this->helper->printMessage("name is required in the cart_rules data file. Row has been skipped.","warning");
            return true;
        }

        //reject if coupon code is being used by a different rule
        if(!empty($row['coupon_code'])){
            if($this->isCouponCodeConflict($row['name'],$row['coupon_code'])){
                $this->helper->printMessage("The coupon code ".$row['coupon_code']." in cart rule ".$row['name']." is being used in a different rule. Row has been skipped","warning");
                return true;
            }
        }
        

        //if websites not defined, use default
        if(empty($row['site_code'])){
            $row['site_code'] = $settings['site_code'];
        }
        $websiteInputArray = explode(",",$row['site_code']);
        $websiteIds = [];
        foreach($websiteInputArray as $website){
            $websiteIds[] = $this->stores->getWebsiteId(trim($website));
        }
        if(count($websiteIds)==0||$websiteIds[0]==''){
            $websiteIds[0] = $this->stores->getWebsiteId(trim($settings['site_code']));
        }

        //get customer id groups. get all if defined as 'all' or empty
        if(empty($row['customer_group'])||$row['customer_group']==''||$row['customer_group']=='all'){
            $groupIds = $this->customerGroups->getAllCustomerGroupIds();
        }else{
            $groupInputArray = explode(",",$row['customer_group']);
            $groupIds = [];
            foreach($groupInputArray as $group){
                $groupIds[]=$this->customerGroups->getCustomerGroupId(trim($group));
            }
        }
       

        if(empty($row['is_active'])){
            $row['is_active']=1;
        }
        $row['is_active'] = $row['is_active']== 'Y' ? 1:0;

        //get existing rule
        $rule = $this->getCartRuleByName($row['name']);

        $row['customer_group_ids'] = $groupIds;
        $row['website_ids'] = $websiteIds;


        if(!empty($row['conditions_serialized'])){
            $row['conditions_serialized'] = $this->converter->convertContent($row['conditions_serialized']);
                   }
        if(!empty($row['actions_serialized'])){
            $row['actions_serialized'] = $this->converter->convertContent($row['actions_serialized']);
        }
        $rule->loadPost($row);

        // print_r(get_class_methods($rule));
        // $rule->setName($row['name']);
        // $rule->setWebsiteIds($websiteIds);
        // $rule->setCustomerGroupIds($groupIds);
        // $rule->setIsActive($row['is_active']);
        // if(!empty($row['sort_order'])){
        //     $rule->setSortOrder($row['sort_order']);
        // }
        // if(!empty($row['description'])){
        //     $rule->setDescription($row['description']);
        // }
        // if(!empty($row['uses_per_customer'])){
        //     $rule->setUsesPerCustomer($row['uses_per_customer']);
        // }
        // $c = json_decode($this->converter->convertContent($row['conditions_serialized']),true);

        
        // if(!empty($row['conditions_serialized'])){
        //     //$rule->setCondition(null,json_decode($this->converter->convertContent($row['conditions_serialized']),true));
        //     $condition = $this->conditionInterfaceFactory->create();
        //     $condition->setConditions(json_decode($this->converter->convertContent($row['conditions_serialized']),true));
        //     //$rule->setCondition($condition);
        //     $rule->setData('conditions_serialized',$row['conditions_serialized']);
        // }
        // if(!empty($row['actions_serialized'])){
        //     $rule->setActionCondition(null,json_decode($this->converter->convertContent($row['actions_serialized']),true));
        // }
        // if(!empty($row['uses_per_customer'])){
        //     $rule->setStopRulesProcessing($row['uses_per_customer']);
        // }
        // if(!empty($row['uses_per_customer'])){
        //     $rule->setIsAdvanced($row['uses_per_customer']);
        // }
        // if(!empty($row['uses_per_customer'])){
        //     $rule->setSimpleAction($row['uses_per_customer']);
        // }
        
    
    //setCondition
    //setActionCondition
    //setStopRulesProcessing
    //setIsAdvanced
    //setStoreLabels
    //setProductIds
   ///setSimpleAction
    //setDiscountAmount
    //setDiscountQty
    //setDiscountStep
    //setApplyToShipping
    //setTimesUsed
    //setCouponType
    //setUseAutoGeneration
    //setUsesPerCoupon
    //setSimpleFreeShipping
          

        //save
        //$rule->save();
        $this->appState->emulateAreaCode(
            AppArea::AREA_ADMINHTML,
            [$rule, 'save']
        );

    }
    /**
     * @param string $ruleName
     * @return Rule
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCartRuleByName($ruleName)
    {
        $rule = $this->ruleCollection->create()
        ->addFieldToFilter('name', ['eq' => $ruleName])->getFirstItem();
        if(!$rule){
              $rule=$this->ruleFactory->create();
        }
        return $rule;
    }

    /**
     * @param string $ruleName
     * @param string $couponCode
     * @return boolean
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function isCouponCodeConflict($ruleName,$couponCode)
    {
        $rule = $this->ruleCollection->create()
        ->addFieldToFilter('name', ['neq' => $ruleName])
        ->addFieldToFilter('code', ['eq' => $couponCode])->count();
        if($rule > 0){
              return true;
        }else{
            return false;
        }
    }
}
