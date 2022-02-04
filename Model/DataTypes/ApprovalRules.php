<?php
/**
 * Copyright © Adobe. All rights reserved.
 */
namespace MagentoEse\DataInstall\Model\DataTypes;

use Magento\PurchaseOrderRule\Api\Data\RuleInterfaceFactory;
use Magento\PurchaseOrderRule\Api\Data\RuleInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Company\Api\RoleManagementInterface;
use Magento\PurchaseOrderRule\Model\Rule\ConditionBuilderFactory;
use Magento\PurchaseOrderRule\Model\RuleConditionPool;
use Magento\PurchaseOrderRule\Api\RuleRepositoryInterface;
use MagentoEse\DataInstall\Helper\Helper;

class ApprovalRules
{
    const RULE_TYPES = ['grand_total', 'shipping_incl_tax', 'number_of_skus'];
    
    /** @var RuleInterfaceFactory */
    protected $ruleFactory;

    /** @var SearchCriteriaBuilder */
    protected $searchCriteriaBuilder;

    /** @var ConditionBuilderFactory */
    protected $conditionBuilderFactory;

    /** @var RuleConditionPool */
    protected $ruleConditionPool;

    /** @var RuleRepositoryInterface */
    protected $ruleRepository;

    /** @var Companies */
    protected $companies;

    /** @var CompanyUserRoles */
    protected $companyUserRoles;
    
    /** @var Helper */
    protected $helper;

    /**
     * ApprovalRules constructor.
     * @param RuleInterfaceFactory $ruleFactory
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param RoleManagementInterface $roleManagement
     * @param ConditionBuilderFactory $conditionBuilderFactory
     * @param RuleConditionPool $ruleConditionPool
     * @param RuleRepositoryInterface $ruleRepository
     * @param Helper $helper
     */
    public function __construct(
        RuleInterfaceFactory $ruleFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RoleManagementInterface  $roleManagement,
        ConditionBuilderFactory $conditionBuilderFactory,
        RuleConditionPool $ruleConditionPool,
        RuleRepositoryInterface $ruleRepository,
        Companies $companies,
        CompanyUserRoles $companyUserRoles,
        Helper $helper
    ) {
        $this->ruleFactory = $ruleFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->roleManagement = $roleManagement;
        $this->conditionBuilderFactory = $conditionBuilderFactory;
        $this->ruleConditionPool = $ruleConditionPool;
        $this->ruleRepository = $ruleRepository;
        $this->companies = $companies;
        $this->companyUserRoles = $companyUserRoles;
        $this->helper = $helper;
    }

    /**
     * @param array $row
     * @return bool
     * @throws LocalizedException
     */
    public function install(array $row, array $settings)
    {
         //required all, except description, is_active, currency_code in skus
        //company,name,description,is_active,apply_to_roles,rule_type,rule,amount_value,currency_code,approval_from
        if (empty($row['name'])) {
            $this->helper->logMessage("Approval rules missing name, row skipped", "warning");
            return true;
        }
        if (empty($row['company_name'])) {
            $this->helper->logMessage("Approval rule ".$row['name'].
            " missing Company name, row skipped", "warning");
            return true;
        } elseif (!$this->companies->getCompanyByName($row['company_name'])) {
            $this->helper->logMessage("Approval rules company ".$row['company_name'].
            " does not exist, row skipped", "warning");
            return true;
        }
        if (empty($row['apply_to_roles'])) {
            $this->helper->logMessage("Approval rule ".$row['name'].
            " missing apply_to_roles, row skipped", "warning");
            return true;
        }
        $row['apply_to_roles'] = explode(",", $row['apply_to_roles']);
        if (!$this->validateRoles(
            $row['apply_to_roles'],
            $this->companies->getCompanyByName($row['company_name'])->getId()
        )) {
            $this->helper->logMessage("Approval rule ".$row['name'].
            " has invalid apply_to_roles value, row skipped", "warning");
            return true;
        }
        if (empty($row['rule_type'])) {
            $this->helper->logMessage("Approval rule ".$row['name']." missing rule_type, row skipped", "warning");
            return true;
        } elseif (!in_array(trim($row['rule_type']), self::RULE_TYPES)) {
            $this->helper->logMessage("Approval rule ".$row['name']." rule_type is invalid, row skipped", "warning");
            return true;
        }
        if (empty($row['rule'])) {
            $this->helper->logMessage("Approval rule ".$row['name']." missing rule, row skipped", "warning");
            return true;
        } elseif (!in_array(trim($row['rule']), ['>','<','>=','<='])) {
            $this->helper->logMessage("Approval rule ".$row['name'].
            " rule value must be one of: >,<,>= or<=, row skipped", "warning");
            return true;
        }
        if (empty($row['amount_value'])) {
            $this->helper->logMessage("Approval rule ".$row['name']." missing amount_value, row skipped", "warning");
            return true;
        }
        if (empty($row['approval_from'])) {
            $this->helper->logMessage("Approval rule ".$row['name']." missing approval_from, row skipped", "warning");
            return true;
        }
        $row['approval_from'] = explode(",", $row['approval_from']);
        if (!$this->validateRoles(
            $row['approval_from'],
            $this->companies->getCompanyByName($row['company_name'])->getId(),
            'approval_from'
        )) {
            $this->helper->logMessage("Approval rule ".$row['name'].
            " has invalid approval_from value, row skipped", "warning");
            return true;
        }
        if (empty($row['description'])) {
            $row['description']='';
        }
        //convert data row to usable values
        $ruleData = $this->convertRow($row);
        //get rule if exists to update
        $ruleSearch =  $this->searchCriteriaBuilder
        ->addFilter(RuleInterface::KEY_COMPANY_ID, $ruleData['company_id'], 'eq')
        ->addFilter(RuleInterface::KEY_NAME, $ruleData['name'], 'eq')
        ->create()->setPageSize(1)->setCurrentPage(1);
        $ruleList = $this->ruleRepository->getList($ruleSearch);
        /** @var RuleInterface $rule */
        $rule = $this->ruleFactory->create();
        if ($ruleList->getTotalCount()==1) {
            /** @var StructureInterface $teamStruct */
            $rule = current($ruleList->getItems());
        }
            $rule->setName($ruleData['name']);
        $rule->setDescription($ruleData['description']);
        $this->setRuleApprovers($rule, $ruleData['approval_roles']);
        if ($ruleData['applies_to_all'] === '1') {
            $rule->setAppliesToAll(true);
        } else {
            $rule->setAppliesToAll(false);
            $rule->setAppliesToRoleIds($ruleData['apply_to_roles']);
        }
        $rule->setIsActive($ruleData['is_active']);
        $rule->setConditionsSerialized($this->buildSerializedCondition([$ruleData['conditions']]));
        $rule->setCompanyId($ruleData['company_id']);
        $rule->setCreatedBy((int) $this->companies->getCompanyByName($ruleData['company_name'])->getSuperUserId());
        $rule->setAdminApprovalRequired($ruleData['requires_admin_approval']);
        $rule->setManagerApprovalRequired($ruleData['requires_manager_approval']);
        $this->ruleRepository->save($rule);
        return true;
    }
/**
 * @param array $rolesToValidate
 * @param int $companyId
 * @param string $approvalType
 * @return bool
 */
    private function validateRoles(array $rolesToValidate, int $companyId, $approvalType = 'apply_to_roles')
    {
        //Company Administrator and Purchaser's Manager are available as default roles for approval_from
        if ($approvalType=='approval_from') {
            $approvedRoles=["Company Administrator","Purchaser's Manager"];
        } else {
            $approvedRoles=[];
        }
          
        $allRoles = $this->roleManagement->getRolesByCompanyId($companyId, true);
        foreach ($allRoles as $role) {
            //a role like Company Administrator is nested, so it is skipped
            if (is_string($role->getRoleName())) {
                $approvedRoles[]=$role->getRoleName();
            }
        }
        foreach ($rolesToValidate as $inputRole) {
            if (!in_array($inputRole, $approvedRoles)) {
                return false;
            }
        }
        return true;
    }
    /**
     * Set the approver role IDs required for the rule and whether admin or manager approval is required.
     *
     * @param RuleInterface $rule
     * @param array $roleIds
     */
    private function setRuleApprovers(RuleInterface $rule, array $roleIds)
    {
        $adminIndex = array_search($this->roleManagement->getCompanyAdminRoleId(), $roleIds);
        if (false !== $adminIndex) {
            $rule->setAdminApprovalRequired(true);
            unset($roleIds[$adminIndex]);
        } else {
            $rule->setAdminApprovalRequired(false);
        }
        $managerIndex = array_search($this->roleManagement->getCompanyManagerRoleId(), $roleIds);
        if (false !== $managerIndex) {
            $rule->setManagerApprovalRequired(true);
            unset($roleIds[$managerIndex]);
        } else {
            $rule->setManagerApprovalRequired(false);
        }
        $rule->setApproverRoleIds($roleIds);
    }

    /**
     * @param array $row
     * @return array
     * @throws LocalizedException
     */
    private function convertRow(array $row)
    {
        //convert company to company_id
        $row['company_id'] = $this->companies->getCompanyByName($row['company_name'])->getId();
        //convert is_active to 1/0
        $row['is_active'] = $row['is_active']== 'Y' ? 1:0;
        //convert app_to_roles to list of roles
        if ($row['apply_to_roles']=='all') {
            $row['apply_to_roles']=='';
            $row['applies_to_all'] = 1;
        } else {
            $row['applies_to_all'] = 0;
            $row['apply_to_roles'] = $this->convertRoleNamesToIds($row['company_id'], $row['apply_to_roles']);
        }

        //default currency code to USD
        if (empty($row['currency_code'])) {
            $row['currency_code']='USD';
        }
        //convert rule information to conditions array
        $row['conditions'] = ['attribute'=>$row['rule_type'],'operator'=>$row['rule'],
        'value'=>$row['amount_value'],'currency_code'=>$row['currency_code']];
        //convert approval_roles to list of roles
        $row['approval_roles'] = $this->convertRoleNamesToIds($row['company_id'], $row['approval_from']);
        if (in_array("Company Administrator", $row['approval_from'])) {
            $row['requires_admin_approval']=true;
        } else {
            $row['requires_admin_approval']=false;
        }

        if (in_array("Purchaser's Manager", $row['approval_from'])) {
            $row['requires_manager_approval']=true;
        } else {
            $row['requires_manager_approval']=false;
        }
                return $row;
    }

    /**
     * @param $companyId
     * @param $roles
     * @return array
     */
    private function convertRoleNamesToIds($companyId, $roleNames)
    {
        $roleIds = [];
        //get roles for company
        $companyRoles = $this->roleManagement->getRolesByCompanyId($companyId);
        foreach ($roleNames as $roleName) {
            foreach ($companyRoles as $companyRole) {
                if ($companyRole->getRoleName()==$roleName) {
                    $roleIds[]=$companyRole->getId();
                    break;
                }
            }
        }
        return $roleIds;
    }

    /**
     * Build up conditions for the rule based on the users input
     *
     * @param array $conditions
     * @return string
     */
    private function buildSerializedCondition(array $conditions)
    {
        $combineCondition = $this->conditionBuilderFactory->create()
            ->setType(Combine::class)
            ->setAttribute(null)
            ->setOperator(null)
            ->setValue('1')
            ->setIsValueProcessed(null)
            ->setAggregator('all');

        // For each condition in the request add a condition into the serialized string
        foreach ($conditions as $condition) {
            $conditionRule = $this->ruleConditionPool->getType($condition['attribute']);
            if ($conditionRule) {
                $combineCondition->addCondition(
                    $this->conditionBuilderFactory->create()
                        ->setType(get_class($conditionRule))
                        ->setAttribute((string) $condition['attribute'])
                        ->setOperator((string) $condition['operator'])
                        ->setValue((string) $condition['value'])
                        ->setCurrencyCode(isset($condition['currency_code']) ?
                            (string) $condition['currency_code'] : '')
                        ->setIsValueProcessed(false)
                        ->create()
                );
            }
        }

        return $combineCondition->create()->toString();
    }
}
