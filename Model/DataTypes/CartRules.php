<?php
namespace MagentoEse\DataInstall\Model\DataTypes;

use Magento\SalesRule\Api\Data\RuleInterface;
use Magento\SalesRule\Api\Data\RuleInterfaceFactory;
use Magento\SalesRule\Api\RuleRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\State;
use MagentoEse\DataInstall\Model\Converter;

class CartRules
{
    /** @var RuleInterfaceFactory */
    protected $ruleInterfaceFactory;

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

    /**
     * @param RuleInterfaceFactory $ruleInterfaceFactory
     * @param RuleRepositoryInterface $ruleRepositoryInterface
     * @param CustomerGroups $customerGroups
     * @param Stores $stores
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param State $appState
     * @param Converter $converter
     */
    public function __construct(
        RuleInterfaceFactory $ruleInterfaceFactory,
        RuleRepositoryInterface $ruleRepositoryInterface,
        CustomerGroups $customerGroups,
        Stores $stores,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        State $appState,
        Converter $converter
    ) 
    {
        $this->ruleInterfaceFactory = $ruleInterfaceFactory;
        $this->ruleRepositoryInterface = $ruleRepositoryInterface;
        $this->customerGroups = $customerGroups;
        $this->stores = $stores;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->appState = $appState;
        $this->converter = $converter;
    }

    public function install(array $row, array $settings)
    {
        //required name
        if (empty($row['name'])){
            $this->helper->printMessage("name is required in the cart_rules data file. Row has been skipped.","warning");
            return true;
        }

        //if websites not defined, use default
        if(empty($row['websites'])){
            $row['websites'] = $settings['site_code'];
        }
        $websiteInputArray = explode(",",$row['websites']);
        $websiteIds = [];
        foreach($websiteInputArray as $website){
            $websiteIds[] = $this->stores->getWebsiteId(trim($website));
        }
        if(count($websiteIds)==0||$websiteIds[0]==''){
            $websiteIds[0] = $this->stores->getWebsiteId(trim($settings['site_code']));
        }

        //get customer id groups. get all if defined as 'all' or empty
        if(empty($row['customer_groups'])||$row['customer_groups']==''||$row['customer_groups']=='all'){
            $groupIds = $this->customerGroups->getAllCustomerGroupIds();
        }else{
            $groupInputArray = explode(",",$row['customer_groups']);
            $groupIds = [];
            foreach($groupInputArray as $group){
                $groupIds[]=$this->customerGroups->getCustomerGroupId(trim($group));
            }
        }
       

        //convert is_active to 1/0
        if(empty($row['is_active'])){
            $row['is_active']=1;
        }
        $row['is_active'] = $row['is_active']== 'Y' ? 1:0;

        //get existing rule
        $rule = $this->getCartRuleByName($row['name']);
        if(!$rule){
            /** @var RuleInterface $rule */
            $rule=$this->ruleInterfaceFactory->create();
        }
        print_r(get_class_methods($rule));
        $rule->setName($row['name']);
        $rule->setWebsiteIds($websiteIds);
        $rule->setCustomerGroupIds($groupIds);
        $rule->setIsActive($row['is_active']);
        if(!empty($row['sort_order'])){
            $rule->setSortOrder($row['sort_order']);
        }
        if(!empty($row['description'])){
            $rule->setDescription($row['description']);
        }
        if(!empty($row['uses_per_customer'])){
            $rule->setUsesPerCustomer($row['uses_per_customer']);
        }
        $c = json_decode($this->converter->convertContent($row['conditions_serialized']),true);
        if(!empty($row['conditions_serialized'])){
            $rule->setCondition(null,json_decode($this->converter->convertContent($row['conditions_serialized']),true));
        }
        if(!empty($row['actions_serialized'])){
            $rule->setActionCondition(null,json_decode($this->converter->convertContent($row['actions_serialized']),true));
        }
        if(!empty($row['uses_per_customer'])){
            $rule->setStopRulesProcessing($row['uses_per_customer']);
        }
        if(!empty($row['uses_per_customer'])){
            $rule->setIsAdvanced($row['uses_per_customer']);
        }
        if(!empty($row['uses_per_customer'])){
            $rule->setSimpleAction($row['uses_per_customer']);
        }
    
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
        $this->ruleRepositoryInterface->save($rule);

    }
    /**
     * @param $ruleName
     * @return RuleInterfaceFactory
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCartRuleByName($ruleName)
    {
        $ruleSearch = $this->searchCriteriaBuilder
        ->addFilter('name', $ruleName, 'eq')->create()->setPageSize(1)->setCurrentPage(1);
        $ruleList = $this->ruleRepositoryInterface->getList($ruleSearch);
        return current($ruleList->getItems());
    }
}
