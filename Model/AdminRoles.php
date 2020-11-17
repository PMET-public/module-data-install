<?php

namespace MagentoEse\DataInstall\Model;

use Magento\Company\Api\Data\RoleInterface;
use Magento\Company\Api\RoleRepositoryInterface;
use Magento\Company\Model\RoleFactory;
use Magento\Company\Api\Data\PermissionInterface;
use Magento\Company\Api\Data\PermissionInterfaceFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;

class AdminRoles {
        
    /** @var RoleRepositoryInterface */
    protected $roleRepositoryInterface;

    /** @var RoleInterface */
    protected $roleInterface;

    /** @var RoleFactory */
    protected $roleFactory;

    /** @var PermissionInterface */
    protected $permissionInterface;

    /** @var PermissionInterfaceFactory */
    protected $permissionInterfaceFactory;

    /** @var SearchCriteriaBuilder */
    protected $searchCriteriaBuilder;

    function __construct(RoleInterface $roleInterface, RoleRepositoryInterface $roleRepositoryInterface,
                        RoleFactory $roleFactory, PermissionInterfaceFactory $permissionInterfaceFactory, 
                        PermissionInterface $permissionInterface,
                        SearchCriteriaBuilder $searchCriteriaBuilder){
        
    }

    function install($row,$settings){

        return true;
    }
}