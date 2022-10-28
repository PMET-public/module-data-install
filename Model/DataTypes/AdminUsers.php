<?php
/**
 * Copyright 2022 Adobe, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace MagentoEse\DataInstall\Model\DataTypes;

use Exception;
use Magento\User\Api\Data\UserInterfaceFactory;
use Magento\User\Api\Data\UserInterface;
use Magento\Authorization\Model\RoleFactory;
use Magento\Authorization\Model\ResourceModel\Role\CollectionFactory as RoleCollection;
use Magento\Authorization\Model\Acl\Role\Group as RoleGroup;
use Magento\Authorization\Model\UserContextInterface;
use Magento\User\Model\ResourceModel\User\CollectionFactory as UserCollection;
use MagentoEse\DataInstall\Helper\Helper;

class AdminUsers
{
    /** @var UserInterfaceFactory */
    protected $userFactory;

    /** @var RoleFactory */
    protected $roleFactory;

    /** @var RoleCollection */
    protected $roleCollection;

    /** @var UserCollection */
    protected $userCollection;

    /** @var Helper */
    protected $helper;

    /**
     * AdminUsers constructor
     *
     * @param Helper $helper
     * @param UserInterfaceFactory $userFactory
     * @param RoleFactory $roleFactory
     * @param RoleCollection $roleCollection
     * @param UserCollection $userCollection
     */
    public function __construct(
        Helper $helper,
        UserInterfaceFactory $userFactory,
        RoleFactory $roleFactory,
        RoleCollection $roleCollection,
        UserCollection $userCollection
    ) {
        $this->userFactory = $userFactory;
        $this->roleFactory = $roleFactory;
        $this->roleCollection = $roleCollection;
        $this->userCollection = $userCollection;
        $this->helper = $helper;
    }

    /**
     * Install
     *
     * @param array $row
     * @param array $settings
     * @return bool
     * @throws \Exception
     */
    public function install(array $row, array $settings)
    {
        if (empty($row['username']) || empty($row['firstname']) || empty($row['lastname']) || empty($row['password'])) {
            $this->helper->logMessage("Required data for admin_users file is missing. Row skipped", "warning");
            return true;
        }
        $user = $this->userCollection->create()->addFieldToFilter('username', ['eq' => $row['username']])
        ->getFirstItem();
        //create user if it doesnt exist
        if (!$user->getData('username')) {
            $user = $this->userFactory->create();
        }
        $user->setEmail($row['email']);
        $user->setFirstName($row['firstname']);
        $user->setLastName($row['lastname']);
        $user->setUserName($row['username']);
        $user->setPassword($row['password']);
        try {
            $user->save();
        } catch (Exception $e) {
            $messages = $e->getMessages();
            foreach ($messages as $message) {
                $this->helper->logMessage($message->getText(), "warning");
            }
            return true;
        }

        $this->addUserToRole($user, $row);

        return true;
    }

    /**
     * Add user to admin role
     *
     * @param UserInterface $user
     * @param array $row
     * @throws \Exception
     */
    private function addUserToRole($user, $row)
    {
        if (!empty($row['role'])) {
            //is there the group role, if not skip
            $role = $this->roleCollection->create()
            ->addFieldToFilter('role_name', ['eq' => $row['role']])->getFirstItem();
            if ($role->getData('role_name')) {
                //is there the role for the user?
                $userRole = $this->roleCollection->create()
                ->addFieldToFilter('role_name', ['eq' => $user->getUserName()])->getFirstItem();
                if (!$userRole->getId()) {
                    $userRole=$this->roleFactory->create();
                }
                $userRole->setParentId($role->getId());
                $userRole->setTreeLevel(2);
                $userRole->setRoleType('U');
                $userRole->setUserId($user->getId());
                $userRole->setUserType(2);
                $userRole->setRoleName($user->getUserName());
                $userRole->save();
            } else {
                $this->helper->logMessage(
                    "Role ".$row['role']." for user ".$row['username']." does not exist",
                    "warning"
                );
            }
        } else {
            $this->helper->logMessage(
                "Role ".$row['role']." for user ".$row['username']." does not exist",
                "warning"
            );
        }
    }

    /**
     * Create Sales Rep Role
     *
     * @return \Magento\Authorization\Model\Role
     */
    private function createSalesRepRole()
    {
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
        } catch (\Exception $e) {
            //ignore
            $ignore=1;
        }
    }
}
