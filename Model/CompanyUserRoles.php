<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace MagentoEse\DataInstall\Model;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Company\Api\Data\RoleInterface;
use Magento\Company\Api\RoleRepositoryInterface;
use Magento\Company\Model\RoleFactory;
use Magento\Company\Model\PermissionFactory;
use Magento\Company\Api\Data\PermissionInterface;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;

class CompanyUserRoles
{
    protected $userRoles = [['resource'=>'Magento_Company::index','permission'=>'allow'],
        ['resource'=>'Magento_Sales::all','permission'=>'allow'],
        ['resource'=>'Magento_Sales::place_order','permission'=>'allow'],
        ['resource'=>'Magento_Sales::payment_account','permission'=>'allow'],
        ['resource'=>'Magento_Sales::view_orders','permission'=>'allow'],
        ['resource'=>'Magento_Sales::view_orders_sub','permission'=>'deny'],
        ['resource'=>'Magento_NegotiableQuote::all','permission'=>'allow'],
        ['resource'=>'Magento_NegotiableQuote::view_quotes','permission'=>'allow'],
        ['resource'=>'Magento_NegotiableQuote::manage','permission'=>'allow'],
        ['resource'=>'Magento_NegotiableQuote::checkout','permission'=>'allow'],
        ['resource'=>'Magento_NegotiableQuote::view_quotes_sub','permission'=>'deny'],
        ['resource'=>'Magento_PurchaseOrder::all','permission'=>'allow'],
        ['resource'=>'Magento_PurchaseOrder::view_purchase_orders','permission'=>'allow'],
        ['resource'=>'Magento_PurchaseOrder::view_purchase_orders_for_subordinates','permission'=>'deny'],
        ['resource'=>'Magento_PurchaseOrder::view_purchase_orders_for_company','permission'=>'deny'],
        ['resource'=>'Magento_PurchaseOrder::autoapprove_purchase_order','permission'=>'deny'],
        ['resource'=>'Magento_PurchaseOrderRule::super_approve_purchase_order','permission'=>'deny'],
        ['resource'=>'Magento_PurchaseOrderRule::view_approval_rules','permission'=>'deny'],
        ['resource'=>'Magento_PurchaseOrderRule::manage_approval_rules','permission'=>'deny'],
        ['resource'=>'Magento_Company::view','permission'=>'allow'],
        ['resource'=>'Magento_Company::view_account','permission'=>'allow'],
        ['resource'=>'Magento_Company::edit_account','permission'=>'deny'],
        ['resource'=>'Magento_Company::view_address','permission'=>'allow'],
        ['resource'=>'Magento_Company::edit_address','permission'=>'deny'],
        ['resource'=>'Magento_Company::contacts','permission'=>'allow'],
        ['resource'=>'Magento_Company::payment_information','permission'=>'allow'],
        ['resource'=>'Magento_Company::user_management','permission'=>'deny'],
        ['resource'=>'Magento_Company::roles_view','permission'=>'deny'],
        ['resource'=>'Magento_Company::roles_edit','permission'=>'deny'],
        ['resource'=>'Magento_Company::users_view','permission'=>'deny'],
        ['resource'=>'Magento_Company::users_edit','permission'=>'deny'],
        ['resource'=>'Magento_Company::credit','permission'=>'allow'],
        ['resource'=>'Magento_Company::credit_history','permission'=>'allow']];

    protected $managerRoles = [['resource'=>'Magento_Company::index','permission'=>'allow'],
        ['resource'=>'Magento_Sales::all','permission'=>'allow'],
        ['resource'=>'Magento_Sales::place_order','permission'=>'allow'],
        ['resource'=>'Magento_Sales::payment_account','permission'=>'allow'],
        ['resource'=>'Magento_Sales::view_orders','permission'=>'allow'],
        ['resource'=>'Magento_Sales::view_orders_sub','permission'=>'allow'],
        ['resource'=>'Magento_NegotiableQuote::all','permission'=>'allow'],
        ['resource'=>'Magento_NegotiableQuote::view_quotes','permission'=>'allow'],
        ['resource'=>'Magento_NegotiableQuote::manage','permission'=>'allow'],
        ['resource'=>'Magento_NegotiableQuote::checkout','permission'=>'allow'],
        ['resource'=>'Magento_NegotiableQuote::view_quotes_sub','permission'=>'allow'],
        ['resource'=>'Magento_PurchaseOrder::all','permission'=>'allow'],
        ['resource'=>'Magento_PurchaseOrder::view_purchase_orders','permission'=>'allow'],
        ['resource'=>'Magento_PurchaseOrder::view_purchase_orders_for_subordinates','permission'=>'allow'],
        ['resource'=>'Magento_PurchaseOrder::view_purchase_orders_for_company','permission'=>'allow'],
        ['resource'=>'Magento_PurchaseOrder::autoapprove_purchase_order','permission'=>'allow'],
        ['resource'=>'Magento_PurchaseOrderRule::super_approve_purchase_order','permission'=>'allow'],
        ['resource'=>'Magento_PurchaseOrderRule::view_approval_rules','permission'=>'allow'],
        ['resource'=>'Magento_PurchaseOrderRule::manage_approval_rules','permission'=>'allow'],
        ['resource'=>'Magento_Company::view','permission'=>'allow'],
        ['resource'=>'Magento_Company::view_account','permission'=>'allow'],
        ['resource'=>'Magento_Company::edit_account','permission'=>'allow'],
        ['resource'=>'Magento_Company::view_address','permission'=>'allow'],
        ['resource'=>'Magento_Company::edit_address','permission'=>'allow'],
        ['resource'=>'Magento_Company::contacts','permission'=>'allow'],
        ['resource'=>'Magento_Company::payment_information','permission'=>'allow'],
        ['resource'=>'Magento_Company::user_management','permission'=>'allow'],
        ['resource'=>'Magento_Company::roles_view','permission'=>'allow'],
        ['resource'=>'Magento_Company::roles_edit','permission'=>'allow'],
        ['resource'=>'Magento_Company::users_view','permission'=>'allow'],
        ['resource'=>'Magento_Company::users_edit','permission'=>'allow'],
        ['resource'=>'Magento_Company::credit','permission'=>'allow'],
        ['resource'=>'Magento_Company::credit_history','permission'=>'allow']];

    /** @var RoleFactory */
    protected $roleFactory;

    /** @var RoleRepositoryInterface */
    protected $roleRepository;

    /** @var PermissionFactory */
    protected $permissionFactory;

    /**
     * @var CompanyRepositoryInterface
     */
    private $companyRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    public function __construct(RoleFactory $roleFactory, RoleRepositoryInterface $roleRepositoryInterface,
                                PermissionFactory $permissionFactory, CompanyRepositoryInterface $companyRepository,
                                SearchCriteriaBuilder $searchCriteriaBuilder)
    {
        $this->roleRepository = $roleRepositoryInterface;
        $this->roleFactory = $roleFactory;
        $this->permissionFactory = $permissionFactory;
        $this->companyRepository = $companyRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    public function apply()
    {
         //set company roles
        $filter = $this->searchCriteriaBuilder;
        $filter->addFilter('entity_id','0','neq');
        $companyList = $this->companyRepository->getList($filter->create())->getItems();
        foreach($companyList as $company){
            //$this->setManagerRoles($company->getId());
            $this->setSalesRoles($company->getId());
        }
    }


    private function setSalesRoles($companyId): void
    {
        /** @var RoleInterface $salesRole */
        $salesRole = $this->roleFactory->create();
        $salesRole->setCompanyId($companyId);
        $salesRole->setRoleName('Purchaser');
        /** @var PermissionInterface $permission */
        $permissionsToSet = [];
        foreach ($this->userRoles as $userRole) {
            $permission = $this->permissionFactory->create();
            $permission->setResourceId($userRole['resource']);
            $permission->setPermission($userRole['permission']);
            $permissionsToSet[] = $permission;
        }
        $salesRole->setPermissions($permissionsToSet);
        $this->roleRepository->save($salesRole);
    }

    private function setManagerRoles($companyId): void
    {
        /** @var RoleInterface $salesRole */
        $salesRole = $this->roleFactory->create();
        $salesRole->setCompanyId($companyId);
        $salesRole->setRoleName('Manager');
        /** @var PermissionInterface $permission */
        $permissionsToSet = [];
        foreach ($this->managerRoles as $managerRole) {
            $permission = $this->permissionFactory->create();
            $permission->setResourceId($managerRole['resource']);
            $permission->setPermission($managerRole['permission']);
            $permissionsToSet[] = $permission;
        }
        $salesRole->setPermissions($permissionsToSet);
        $this->roleRepository->save($salesRole);
    }
}