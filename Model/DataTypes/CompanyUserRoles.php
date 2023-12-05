<?php
/**
 * Copyright 2022 Adobe, Inc. All rights reserved.
 * See LICENSE for license details.
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
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Company\Api\AclInterface;
use MagentoEse\DataInstall\Helper\Helper;

class CompanyUserRoles
{

    /** @var Helper */
    protected $helper;

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

    /** @var Stores */
    protected $stores;

    /**
     * CompanyUserRoles constructor
     *
     * @param Helper $helper
     * @param RoleFactory $roleFactory
     * @param RoleRepositoryInterface $roleRepositoryInterface
     * @param PermissionFactory $permissionFactory
     * @param CompanyRepositoryInterface $companyRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param CustomerRepositoryInterface $customerRepository
     * @param AclInterface $aclInterface
     * @param Stores $stores
     */
    public function __construct(
        Helper $helper,
        RoleFactory $roleFactory,
        RoleRepositoryInterface $roleRepositoryInterface,
        PermissionFactory $permissionFactory,
        CompanyRepositoryInterface $companyRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        CustomerRepositoryInterface $customerRepository,
        AclInterface $aclInterface,
        Stores $stores
    ) {
        $this->helper = $helper;
        $this->roleRepository = $roleRepositoryInterface;
        $this->roleFactory = $roleFactory;
        $this->permissionFactory = $permissionFactory;
        $this->companyRepository = $companyRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->customerRepository = $customerRepository;
        $this->acl = $aclInterface;
        $this->stores = $stores;
    }

    /**
     * Install
     *
     * @param array $row
     * @param array $settings
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function install($row, $settings)
    {
        if (empty($row['site_code'])) {
            $row['site_code'] = $settings['site_code'];
        }

        //add site code override
        if (!empty($settings['is_override'])) {
            if (!empty($settings['site_code'])) {
                $row['site_code'] = $settings['site_code'];
            }
        }

        $websiteId = $this->stores->getWebsiteId($row['site_code']);
        //skip company admin roles
        if (!empty($row['role']) && $row['company_admin']!='Y') {
            $companyid = $this->getCompanyId($row['company']);
            if (!$companyid) {
                $this->helper->logMessage("The company ". $row['company'] .
                " in setting user roles does not exist", "warning");
                return true;
            }
            //does role exist, print message if it doesnt
            $role = $this->getCompanyRole($row['company'], $row['role']);
            if ($role) {
                //add user to role
                $userId = $this->customerRepository->get(trim($row['email']), $websiteId)->getId();
                //assign role to user
                     $this->acl->assignUserDefaultRole($userId, $this->getCompanyId($row['company']));
                     $this->acl->assignRoles($userId, [$role]);
            } else {
                $this->helper->logMessage("The role ". $row['role'] ." for company ".$row['company'].
                " does not exist", "warning");
            }
        }

        return true;
    }

    /**
     * Get company role by name
     *
     * @param string $companyName
     * @param string $role
     * @return RoleInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCompanyRole($companyName, $role)
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

    /**
     * Get company id by name
     *
     * @param string $companyName
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
            $this->helper->logMessage("The company ". $companyName .
            " in setting user roles does not exist", "warning");
            return false;
        } else {
            return $company->getId();
        }
    }

    /**
     * Set company role
     *
     * @param id $companyId
     * @param string $roleName
     * @param array $rolePermissions
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
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
