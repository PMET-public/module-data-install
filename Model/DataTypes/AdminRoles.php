<?php
/**
 * Copyright 2022 Adobe, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace MagentoEse\DataInstall\Model\DataTypes;

use Magento\Authorization\Model\RoleFactory;
use Magento\Authorization\Model\ResourceModel\Role\CollectionFactory as RoleCollection;
use Magento\Authorization\Model\RulesFactory;
use MagentoEse\DataInstall\Helper\Helper;

class AdminRoles
{

    /** @var RoleFactory */
    protected $roleFactory;

    /** @var RoleCollection */
    protected $roleCollection;

    /** @var RulesFactory */
    protected $rulesFactory;

    /** @var Helper */
    protected $helper;

    /**
     * AdminRoles constructor
     *
     * @param RoleFactory $roleFactory
     * @param RoleCollection $roleCollection
     * @param RulesFactory $rulesFactory
     * @param Helper $helper
     */
    public function __construct(
        RoleFactory $roleFactory,
        RoleCollection $roleCollection,
        RulesFactory $rulesFactory,
        Helper $helper
    ) {
        $this->roleFactory = $roleFactory;
        $this->roleCollection = $roleCollection;
        $this->rulesFactory = $rulesFactory;
        $this->helper = $helper;
    }

    /**
     * Install
     *
     * @param array $rows
     * @param array $header
     * @return bool
     * @throws \Exception
     */
    public function install($rows, $header)
    {
        //this import is set as file type as the resources need to be added as a complete array
        //and connot be added individually
        $rolesData = [];
        foreach ($rows as $row) {
            $rolesArray[] = array_combine($header, $row);
        }
        foreach ($rolesArray as $roleRow) {
            if (!empty($roleRow['role']) && !empty($roleRow['resource_id'])) {
                $rolesData[$roleRow['role']][]=$roleRow['resource_id'];
            } else {
                $this->helper->logMessage("admin_role does not include role or resource_id, row skipped", "warning");
            }
        }

        foreach ($rolesData as $roleDataKey => $roleDataValue) {
            $role = $this->roleCollection->create()
            ->addFieldToFilter('role_name', ['eq' => $roleDataKey])->getFirstItem();
            //create role if it doesnt exist
            if (!$role->getData('role_name')) {
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
            $rule->saveRel();
        }

        return true;
    }
}
