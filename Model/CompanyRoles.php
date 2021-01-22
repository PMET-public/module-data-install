<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 */

namespace MagentoEse\DataInstall\Model;

use Magento\Company\Api\Data\RoleInterface;
use Magento\Company\Api\RoleRepositoryInterface;
use Magento\Company\Model\RoleFactory;
use Magento\Company\Model\PermissionFactory;
use Magento\Company\Api\Data\PermissionInterface;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Company\Api\Data\CompanyInterface;

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
     * CompanyRoles constructor.
     * @param RoleFactory $roleFactory
     * @param RoleRepositoryInterface $roleRepositoryInterface
     * @param PermissionFactory $permissionFactory
     * @param CompanyRepositoryInterface $companyRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        RoleFactory $roleFactory,
        RoleRepositoryInterface $roleRepositoryInterface,
        PermissionFactory $permissionFactory,
        CompanyRepositoryInterface $companyRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->roleRepository = $roleRepositoryInterface;
        $this->roleFactory = $roleFactory;
        $this->permissionFactory = $permissionFactory;
        $this->companyRepository = $companyRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    public function install($rows, $header)
    {
        $rolesData = [];
        foreach ($rows as $row) {
            $rolesArray[] = array_combine($header, $row);
        }
        //convert into company->role->permission structure
        foreach ($rolesArray as $roleRow) {
            $rolesData[$roleRow['company']][$roleRow['role']][]=$roleRow['resource_id'];
        }

        foreach ($rolesData as $companyName => $companyRoles) {
            $this->createCompanyRole($companyName, $companyRoles);
        }

        return true;
    }

    private function createCompanyRole($companyName, $companyRoles)
    {
        $companyId = $this->getCompanyId($companyName);
        if ($companyId) {
            foreach ($companyRoles as $rolename => $rolePermissions) {
                $this->setCompanyRole($companyId, $rolename, $rolePermissions);
            }
        }
    }

    private function getCompanyId($companyName)
    {

        $companySearch = $this->searchCriteriaBuilder
            ->addFilter(CompanyInterface::NAME, $companyName, 'eq')->create()->setPageSize(1)->setCurrentPage(1);
        $companyList = $this->companyRepository->getList($companySearch);
        /** @var CompanyInterface $company */
        $company = current($companyList->getItems());
        if (!$company) {
            print_r("The company ". $companyName ." requested in b2b_customers.csv does not exist\n");
            return false;
        } else {
            return $company->getId();
        }
    }

    private function setCompanyRole($companyId, $roleName, $rolePermissions)
    {
        $roleSearch = $this->searchCriteriaBuilder
            ->addFilter(RoleInterface::COMPANY_ID, $companyId, 'eq')
            ->addFilter(RoleInterface::ROLE_NAME, $roleName, 'eq')->create()->setPageSize(1)->setCurrentPage(1);
        $roleSearch = $this->roleRepository->getList($roleSearch);
        /** @var RoleInterface $salesRole */
        $salesRole = current($roleSearch->getItems());
        if(!$salesRole){
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
