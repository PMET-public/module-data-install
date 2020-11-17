<?php
/**
 * Copyright © Magento. All rights reserved.
 */
namespace MagentoEse\DataInstall\Model;


use Magento\User\Api\Data\UserInterfaceFactory;
use Magento\Authorization\Model\RoleFactory;
use Magento\Authorization\Model\RulesFactory;
use Magento\Authorization\Model\Acl\Role\Group as RoleGroup;
use Magento\Authorization\Model\UserContextInterface;

class AdminUsers{

    /** @var UserInterfaceFactory */
    protected $userFactory;

    /** @var RoleFactory */
    protected $roleFactory;

    /** @var RulesFactory */
    protected $rulesFactory;

    public function __construct(UserInterfaceFactory $userFactory,
                                RoleFactory $roleFactory,
                                RulesFactory $rulesFactory)
    {
        $this->userFactory = $userFactory;
        $this->roleFactory = $roleFactory;
        $this->rulesFactory = $rulesFactory;
    }

    public function install(array $row, array $settings){
        $user = $this->userFactory->create();
        $user->setEmail($row['email']);
        $user->setFirstName($row['firstname']);
        $user->setLastName($row['lastname']);
        $user->setUserName($row['username']);
        $user->setPassword($row['password']);
        $user->save();
        $this->addUserToRole($user,$row);
       
        return true;
    }

    private function addUserToRole($user,$row){
        if(!empty($row['role'])){
            //look up roll and add user to it
            //$userRole->setParentId($role->getId());
        }else{
            //add user to administrator role
            $userRole=$this->roleFactory->create();
            // add role for user
            //$role = $this->createSalesrepRole();
            
            $userRole->setParentId(1);
            $userRole->setTreeLevel(2);
            $userRole->setRoleType('U');
            $userRole->setUserId($user->getId());
            $userRole->setUserType(2);
            $userRole->setRoleName($user->getUserName());
            $userRole->save();
        }
    }

    private function createSalesrepRole(){
         try {
            $role=$this->roleFactory->create();
            $role->setName('Sales Rep') //Set Role Name Which you want to create
            ->setPid(0) //set parent role id of your role
            ->setRoleType(RoleGroup::ROLE_TYPE)
                ->setUserType(UserContextInterface::USER_TYPE_ADMIN);
            $role->save();

            /* Add resources we allow to this role */
            $resource=['Magento_Backend::admin',
                'Magento_Sales::sales'
            ];
            //save resources to role
            $this->rulesFactory->create()->setRoleId($role->getId())->setResources($resource)->saveRel();
            
            return $role;
         } catch (\Exception $e){
            //ignore
            echo "caught\n";
         }
         
    }

}