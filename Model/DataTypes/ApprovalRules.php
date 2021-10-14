<?php
/**
 * Copyright © Adobe. All rights reserved.
 */
namespace MagentoEse\DataInstall\Model\DataTypes;

use Magento\PurchaseOrderRule\Api\Data\RuleInterfaceFactory;
use Magento\PurchaseOrderRule\Api\Data\RuleInterface;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Company\Api\RoleManagementInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Company\Api\RoleRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\PurchaseOrderRule\Model\Rule\ConditionBuilderFactory;
use Magento\PurchaseOrderRule\Model\RuleConditionPool;
use Magento\PurchaseOrderRule\Api\RuleRepositoryInterface;

class ApprovalRules
{
    /** @var RuleInterface */
    protected $ruleFactory;

    /** @var CompanyRepositoryInterface */
    protected $companyRepository;

    /** @var SearchCriteriaBuilder */
    protected $searchCriteriaBuilder;

    /** @var RoleManagementInterface */
    protected $roleManagement;

    /** @var RoleRepositoryInterface */
    protected $roleRepository;

    /** @var ConditionBuilderFactory */
    protected $conditionBuilderFactory;

    /** @var RuleConditionPool */
    protected $ruleConditionPool;

    /** @var RuleRepositoryInterface */
    protected $ruleRepository;

    /**
     * ApprovalRules constructor.
     * @param RuleInterfaceFactory $ruleFactory
     * @param CompanyRepositoryInterface $companyRepositoryInterface
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param RoleManagementInterface $roleManagement
     * @param RoleRepositoryInterface $roleRepositoryInterface
     * @param ConditionBuilderFactory $conditionBuilderFactory
     * @param RuleConditionPool $ruleConditionPool
     * @param RuleRepositoryInterface $ruleRepository
     */
    public function __construct(
        RuleInterfaceFactory $ruleFactory,
        CompanyRepositoryInterface $companyRepositoryInterface,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RoleManagementInterface  $roleManagement,
        RoleRepositoryInterface $roleRepositoryInterface,
        ConditionBuilderFactory $conditionBuilderFactory,
        RuleConditionPool $ruleConditionPool,
        RuleRepositoryInterface $ruleRepository
    ) {
        $this->ruleFactory = $ruleFactory;
        $this->companyRepository = $companyRepositoryInterface;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->roleManagement = $roleManagement;
        $this->roleRepository = $roleRepositoryInterface;
        $this->conditionBuilderFactory = $conditionBuilderFactory;
        $this->ruleConditionPool = $ruleConditionPool;
        $this->ruleRepository = $ruleRepository;
    }

    /**
     * @param array $row
     * @return bool
     * @throws LocalizedException
     */
    public function install(array $row, array $settings)
    {
        //TODO:Allow updates to rule by name as key
        //convert data row to usable values
        $ruleData = $this->convertRow($row);
        //validate data
        if ($this->validate($ruleData)!='') {
            return true;
        }
        $this->helper->printMessage("Creating approval rule ".$ruleData['name'], "info");
        $rule = $this->ruleFactory->create();
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
        $rule->setCreatedBy((int) $this->getCompanyAdminIdByName($ruleData['company']));
        $this->ruleRepository->save($rule);
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
     * @param $ruleData
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function validate($ruleData)
    {
        // company,name,description,is_active,apply_to_roles,rule_type,rule,amount_value,currency_code,approval_roles
        $returnMessage='';
        //verify company id
        if (empty($ruleData['company_id'])) {
            $returnMessage.="Company missing or invalid\n";
        }

        // Verify that name is present
        if (empty($ruleData['name']) || trim($ruleData['name']) === "") {
            $returnMessage.="Approval rule is missing a name\n";
        }

        // Verify the conditions are present in the request and are an array with at least one entry
        if (!$this->validateParamArray($ruleData['conditions'])) {
            $returnMessage.="Rule conditions have not been configured\n";
        }

        if (!isset($ruleData['conditions']['attribute']) || !isset($ruleData['conditions']['operator']) ||
        !isset($ruleData['conditions']['value'])) {
            $returnMessage.="Required data is missing from a rule condition\n";
        }

        // Verify at least one approver is set
        if (!$this->validateParamArray($ruleData['approval_roles'])) {
            $returnMessage.="Verify the approver from the company is correct to configure this rule";
        }

        // Verify the rule is applied to all, or at least one approver is selected
        if ($ruleData['applies_to_all'] === '0'
            && !$ruleData['apply_to_roles']
        ) {
            $returnMessage.="This rule must apply to at least one or all roles\n";
        }

        // Validate roles for both the applies to & approvers
        $returnMessage.= $this->validateRoles($ruleData['apply_to_roles'], "Applies To");
        $returnMessage.= $this->validateRoles($ruleData['approval_roles'], "Approver");
        //TODO validate rule types
        //TODO validate rule
        //TODO validate amount value is numeric
        return $returnMessage;
    }
    //company,name,description,is_active,apply_to_roles,rule_type,rule,amount_value,currency_code,approval_roles

    /**
     * @param array $row
     * @return array
     * @throws LocalizedException
     */
    private function convertRow(array $row)
    {
        //convert company to company_id
        $row['company_id'] = $this->getCompanyIdbyName($row['company']);
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

        //convert rule information to conditions array
            //default currency code to USD
        if (empty($row['currency_code'])) {
            $row['currency_code']='USD';
        }
        $row['conditions'] = ['attribute'=>$row['rule_type'],'operator'=>$row['rule'],
        'value'=>$row['amount_value'],'currency_code'=>$row['currency_code']];
        //convert approval_roles to list of roles
        $row['approval_roles'] = $this->convertRoleNamesToIds($row['company_id'], $row['approval_roles']);

        return $row;
    }

    /**
     * @param $companyId
     * @param $roles
     * @return array
     */
    private function convertRoleNamesToIds($companyId, $roles)
    {
        $roleIds = [];
        //change list to array
        $roleNames = explode(',', $roles);
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
     * @param $name
     * @return int|null
     * @throws LocalizedException
     */
    private function getCompanyIdbyName($name)
    {
        $companySearch = $this->searchCriteriaBuilder
        ->addFilter('company_name', $name, 'eq')->create()->setPageSize(1)->setCurrentPage(1);
        $companyList = $this->companyRepository->getList($companySearch);
        /** @var CompanyInterface $company */
        $company = current($companyList->getItems());

        if (!$company) {
            $this->helper->printMessage(
                "The company ". $name ." requested in b2b_approval_rules.csv does not exist",
                "warning"
            );
        } else {
            return $company->getId();
        }
    }

    /**
     * @param $name
     * @return int
     * @throws LocalizedException
     */
    private function getCompanyAdminIdByName($name)
    {
        $companySearch = $this->searchCriteriaBuilder
        ->addFilter('company_name', $name, 'eq')->create()->setPageSize(1)->setCurrentPage(1);
        $companyList = $this->companyRepository->getList($companySearch);
        /** @var CompanyInterface $company */
        $company = current($companyList->getItems());

        if (!$company) {
            $this->helper->printMessage(
                "The company ". $name ." requested in b2b_approval_rules.csv does not exist",
                "warning"
            );
        } else {
            /**@var CompanyInterface $company */
            return $company->getSuperUserId();
        }
    }

    /**
     * Validate a request param of type array
     *
     * @param array $array
     * @return bool
     */
    private function validateParamArray($array)
    {
        return is_array($array) && count($array) > 0;
    }

    /**
     * @param array $conditions
     * @return string
     */
    private function validateConditions(array $conditions)
    {
        // Iterate through conditions and ensure all required data is present
        foreach ($conditions as $condition) {
            if (!isset($condition['attribute']) || !isset($condition['operator']) || !isset($condition['value'])) {
                return 'Required data is missing from a rule condition.';
            }
        }
    }

    /**
     * @param array $approvers
     * @param $message
     * @return string
     */
    private function validateRoles(array $approvers, $message)
    {
        // Verify all approvers exist and are assigned to the users company
        foreach ($approvers as $approver) {
            if ($approver == $this->roleManagement->getCompanyAdminRoleId() ||
                $approver == $this->roleManagement->getCompanyManagerRoleId()) {
                continue;
            }

            try {
                $companyRole = $this->roleRepository->get($approver);
            } catch (NoSuchEntityException $e) {
                return "One of the '".$message."' roles does not exist\n";
            }

            // If the role is not part of the users current company we throw a generic does not exist error
            //if (!$companyRole || $this->companyUser->getCurrentCompanyId() !== $companyRole->getCompanyId()) {
            //    throw new LocalizedException($errorMessage);
            //}
        }
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
