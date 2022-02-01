<?php
/**
 * Copyright © Adobe, Inc. All rights reserved.
 */

namespace MagentoEse\DataInstall\Model\DataTypes;

use Magento\Company\Api\Data\RoleInterface;
use Magento\Company\Api\RoleRepositoryInterface;
use Magento\Company\Model\RoleFactory;
use Magento\Company\Model\PermissionFactory;
use Magento\Company\Api\Data\PermissionInterface;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Company\Api\Data\CompanyInterface;
use MagentoEse\DataInstall\Helper\Helper;

class CompanyRoles
{

    /** @var RoleFactory */
    protected $roleFactory;

    /** @var RoleRepositoryInterface */
    protected $roleRepository;

    /** @var PermissionFactory */
    protected $permissionFactory;

    /**
     * @var CompanyRepositoryInterface
     */
    protected $companyRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var Helper
     */
    protected $helper;

    /**
     * CompanyRoles constructor.
     * @param RoleFactory $roleFactory
     * @param RoleRepositoryInterface $roleRepositoryInterface
     * @param PermissionFactory $permissionFactory
     * @param CompanyRepositoryInterface $companyRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param Helper $helper
     */
    public function __construct(
        RoleFactory $roleFactory,
        RoleRepositoryInterface $roleRepositoryInterface,
        PermissionFactory $permissionFactory,
        CompanyRepositoryInterface $companyRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Helper $helper
    ) {
        $this->roleRepository = $roleRepositoryInterface;
        $this->roleFactory = $roleFactory;
        $this->permissionFactory = $permissionFactory;
        $this->companyRepository = $companyRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->helper = $helper;
    }

    /**
     * @param $rows
     * @param $header
     * @return bool
     */
    public function install($rows, $header)
    {
        $rolesData = [];
        foreach ($rows as $row) {
            $rolesArray[] = array_combine($header, $row);
        }
        if (!empty($rolesArray)) {
            //convert into company->role->permission structure
            foreach ($rolesArray as $roleRow) {
                $rolesData[$roleRow['company_name']][$roleRow['role']][]=$roleRow['resource_id'];
            }

            foreach ($rolesData as $companyName => $companyRoles) {
                $this->createCompanyRole($companyName, $companyRoles);
            }
        }
        
        return true;
    }

    /**
     * @param $companyName
     * @param $companyRoles
     */
    private function createCompanyRole($companyName, $companyRoles)
    {
        $companyId = $this->getCompanyId($companyName);
        if ($companyId) {
            foreach ($companyRoles as $rolename => $rolePermissions) {
                $this->setCompanyRole($companyId, $rolename, $rolePermissions);
            }
        }
    }

    /**
     * @param $companyName
     * @return false|int|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getCompanyId($companyName)
    {

        $companySearch = $this->searchCriteriaBuilder
            ->addFilter(CompanyInterface::NAME, $companyName, 'eq')->create()->setPageSize(1)->setCurrentPage(1);
        $companyList = $this->companyRepository->getList($companySearch);
        /** @var CompanyInterface $company */
        $company = current($companyList->getItems());
        if (!$company) {
            $this->helper->printMessage("The company ". $companyName .
            " requested in b2b_customers.csv does not exist", "warning");
            return false;
        } else {
            return $company->getId();
        }
    }

    /**
     * @param $companyId
     * @param $roleName
     * @param $rolePermissions
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function setCompanyRole($companyId, $roleName, $rolePermissions)
    {
        $roleSearch = $this->searchCriteriaBuilder
            ->addFilter(RoleInterface::COMPANY_ID, $companyId, 'eq')
            ->addFilter(RoleInterface::ROLE_NAME, $roleName, 'eq')->create()->setPageSize(1)->setCurrentPage(1);
        $roleSearch = $this->roleRepository->getList($roleSearch);
        /** @var RoleInterface $salesRole */
        $salesRole = current($roleSearch->getItems());
        if (!$salesRole) {
            $salesRole = $this->roleFactory->create();
            $salesRole->setCompanyId($companyId);
            $salesRole->setRoleName($roleName);
        }
        /** @var PermissionInterface $permission */
        $permissionsToSet = [];
        foreach ($rolePermissions as $rolePermission) {
            $permission = $this->permissionFactory->create();
            $permission->setResourceId($rolePermission);
            $permission->setPermission('allow');
            $permissionsToSet[] = $permission;
        }
        $salesRole->setPermissions($permissionsToSet);
        $this->roleRepository->save($salesRole);
    }
}
