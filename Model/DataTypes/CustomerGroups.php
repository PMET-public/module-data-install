<?php
/**
 * Copyright 2022 Adobe, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace MagentoEse\DataInstall\Model\DataTypes;

use Magento\Customer\Api\Data\GroupInterfaceFactory;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use MagentoEse\DataInstall\Helper\Helper;

class CustomerGroups
{
    
    /** @var string */
    protected $defaultCustomerGroup = 'General';

    /** @var GroupInterfaceFactory  */
    protected $groupInterfaceFactory;

    /** @var GroupRepositoryInterface  */
    protected $groupRepository;

    /** @var SearchCriteriaBuilder  */
    protected $searchCriteriaBuilder;

    /** @var Helper */
    protected $helper;

    /**
     * CustomerGroups constructor
     *
     * @param Helper $helper
     * @param GroupInterfaceFactory $groupInterfaceFactory
     * @param GroupRepositoryInterface $groupRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        Helper $helper,
        GroupInterfaceFactory $groupInterfaceFactory,
        GroupRepositoryInterface $groupRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->helper = $helper;
        $this->groupInterfaceFactory = $groupInterfaceFactory;
        $this->groupRepository = $groupRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Install
     *
     * @param array $row
     * @return bool
     */
    public function install(array $row)
    {
        if (empty($row['name'])) {
            $this->helper->logMessage("Customer group missing name, row skipped", "warning");
            return true;
        }

        $group = $this->groupInterfaceFactory->create();
        $group->setCode($row['name'])->setTaxClassId(3);
        try {
            $this->groupRepository->save($group);
        } catch (\Exception $e) {
            //error will likely be trying to add duplicate group
            $this->helper->logMessage("
            Customer Group ".$row['name']." not installed, another group with the same name likely exists", "warning");
        }

        return true;
    }

    /**
     * Get customer group id by code
     *
     * @param string $customerGroupCode
     * @return int|null
     * @throws LocalizedException
     */
    public function getCustomerGroupId(string $customerGroupCode)
    {
        $search = $this->searchCriteriaBuilder
            ->addFilter('code', $customerGroupCode, 'eq')->create();
        $groupList = $this->groupRepository->getList($search)->getItems();
        foreach ($groupList as $group) {
            return $group->getId();
        }
    }

     /**
      * Get customer group by code
      *
      * @param string $customerGroupCode
      * @return null
      * @throws LocalizedException
      */
    public function deleteCustomerGroupByCode(string $customerGroupCode)
    {
        $search = $this->searchCriteriaBuilder
            ->addFilter('code', $customerGroupCode, 'eq')->create();
        $groupList = $this->groupRepository->getList($search)->getItems();
        foreach ($groupList as $group) {
            $this->groupRepository->delete($group);
        }
    }

    /**
     * Get all customer group ids
     *
     * @return array
     * @throws LocalizedException
     */
    public function getAllCustomerGroupIds()
    {
        $groupIds=[];
        $search = $this->searchCriteriaBuilder
            ->addFilter('code', '', 'neq')->create();
        $groupList = $this->groupRepository->getList($search)->getItems();
        foreach ($groupList as $group) {
            $groupIds[] = $group->getId();
        }
        return $groupIds;
    }

    /**
     * Get default customer group
     *
     * @return string
     */
    public function getDefaultCustomerGroup()
    {
        return $this->defaultCustomerGroup;
    }
}
