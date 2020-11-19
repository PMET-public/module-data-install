<?php

namespace MagentoEse\DataInstall\Model;

use Magento\Authorization\Model\RoleFactory;
use Magento\Authorization\Model\Role;
use Magento\Authorization\Model\ResourceModel\Role\CollectionFactory as RoleCollection;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Authorization\Model\RulesFactory; 

class AdminRoles {
        

    /** @var RoleFactory */
    protected $roleFactory;

    /** @var RoleCollection */
    protected $roleCollection;


    /** @var SearchCriteriaBuilder */
    protected $searchCriteriaBuilder;

     /** @var RulesFactory */
     protected $rulesFactory;

    public function __construct(
                        RoleFactory $roleFactory,
                        SearchCriteriaBuilder $searchCriteriaBuilder,
                        RoleCollection $roleCollection,
                        RulesFactory $rulesFactory
                        ){
        $this->roleFactory = $roleFactory;
        $this->roleCollection = $roleCollection;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->rulesFactory = $rulesFactory;
    }

    function install($rows,$header){
        $rolesData = [];
        foreach ($rows as $row) {
            $rolesArray[] = array_combine($header, $row);
        }
        foreach($rolesArray as $roleRow){
            $rolesData[$roleRow['role']][]=$roleRow['resource_id'];
        }
        
        foreach($rolesData as $roleDataKey => $roleDataValue){
            $role = $this->roleCollection->create()
            ->addFieldToFilter('role_name', ['eq' => $roleDataKey])->getFirstItem();
            //create role if it doesnt exist
            if(!$role->getData('role_name')){
                $role = $this->roleFactory->create();
                $role->setParentId(0);
                $role->setTreeLevel(1);
                $role->setRoleType('G');
                $role->setRoleName($roleDataKey);
                $role->setUserType('2');
                $role->save();
            }
             //save resources to role
            $rule = $this->rulesFactory->create();
            $rule->setRoleId($role->getId());
            $rule->setResources($roleDataValue);
            //$rule->setPermission($row['permission']);
            $rule->saveRel();
        }
        
        return true;
    }
}