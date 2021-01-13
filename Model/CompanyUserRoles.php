<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
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
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Company\Api\AclInterface;

class CompanyUserRoles
{

    /** @var RoleFactory */
    protected $roleFactory;

    /** @var RoleRepositoryInterface */
    protected $roleRepository;

    /** @var PermissionFactory */
    protected $permissionFactory;

    /** @var CompanyRepositoryInterface */
    protected $companyRepository;

    /** @var SearchCriteriaBuilder */
    protected $searchCriteriaBuilder;

    /** @var CustomerRepositoryInterface */
    protected $customerRepository;

    /** @var AclInterface */
    protected $acl;

    /**
     * CompanyUserRoles constructor.
     * @param RoleFactory $roleFactory
     * @param RoleRepositoryInterface $roleRepositoryInterface
     * @param PermissionFactory $permissionFactory
     * @param CompanyRepositoryInterface $companyRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param CustomerRepositoryInterface $customerRepository
     * @param AclInterface $aclInterface
     */
    public function __construct(
        RoleFactory $roleFactory,
        RoleRepositoryInterface $roleRepositoryInterface,
        PermissionFactory $permissionFactory,
        CompanyRepositoryInterface $companyRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        CustomerRepositoryInterface $customerRepository,
        AclInterface $aclInterface
    ) {
        $this->roleRepository = $roleRepositoryInterface;
        $this->roleFactory = $roleFactory;
        $this->permissionFactory = $permissionFactory;
        $this->companyRepository = $companyRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->customerRepository = $customerRepository;
        $this->acl = $aclInterface;
    }

    public function install($row, $settings)
    {
        //skip company admin roles
        if (!empty($row['role']) && $row['company_admin']!='Y') {
            //does role exist, print message if it doesnt
            $role = $this->getCompanyRole($row['company'], $row['role']);
            if ($role) {
                //add user to role
                $userId = $this->customerRepository->get(trim($row['email']))->getId();
                //assign role to user
                     $this->acl->assignUserDefaultRole($userId, $this->getCompanyId($row['company']));
                     $this->acl->assignRoles($userId, [$role]);
            } else {
                print_r("The role ". $row['role'] ." for company ".$row['company']." does not exist\n");
            }

        }

        return true;
    }

    private function getCompanyRole($companyName, $role)
    {
        $companyId = $this->getCompanyId($companyName);
        if ($companyId) {
            //get role
            $roleSearch = $this->searchCriteriaBuilder
                ->addFilter(RoleInterface::ROLE_NAME, $role, 'eq')
                ->addFilter(RoleInterface::COMPANY_ID, $companyId, 'eq')->create()->setPageSize(1)->setCurrentPage(1);
            $roleList = $this->roleRepository->getList($roleSearch);
            $role = current($roleList->getItems());
        }
        return $role;
    }

    private function getCompanyId($companyName)
    {

        $companySearch = $this->searchCriteriaBuilder
            ->addFilter(CompanyInterface::NAME, $companyName, 'eq')->create()->setPageSize(1)->setCurrentPage(1);
        $companyList = $this->companyRepository->getList($companySearch);
        /** @var CompanyInterface $company */
        $company = current($companyList->getItems());
        if (!$company) {
            print_r("The company ". $companyName ." requested in b2b_company_user_roles.csv does not exist\n");
            return false;
        } else {
            return $company->getId();
        }
    }

    private function setCompanyRole($companyId, $roleName, $rolePermissions)
    {
        /** @var RoleInterface $salesRole */
        $salesRole = $this->roleFactory->create();
        $salesRole->setCompanyId($companyId);
        $salesRole->setRoleName($roleName);
        /** @var PermissionInterface $permission */
        $permissionsToSet = [];
        foreach ($rolePermissions as $rolePermission) {
            $permission = $this->permissionFactory->create();
            $permission->setResourceId($rolePermission);
            $permissionsToSet[] = $permission;
        }
        $salesRole->setPermissions($permissionsToSet);
        $this->roleRepository->save($salesRole);
    }
}
