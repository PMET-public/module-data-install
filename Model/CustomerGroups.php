<?php
/**
 * Copyright © Magento. All rights reserved.
 */

namespace MagentoEse\DataInstall\Model;

use Magento\Customer\Api\Data\GroupInterfaceFactory;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;

class CustomerGroups
{
    protected $defaultCustomerGroup = 'General';

    /** @var GroupInterfaceFactory  */
    protected $groupInterfaceFactory;

    /** @var GroupRepositoryInterface  */
    protected $groupRepository;

    /** @var SearchCriteriaBuilder  */
    protected $searchCriteriaBuilder;

    /**
     * CustomerGroups constructor.
     * @param GroupInterfaceFactory $groupInterfaceFactory
     * @param GroupRepositoryInterface $groupRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        GroupInterfaceFactory $groupInterfaceFactory,
        GroupRepositoryInterface $groupRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->groupInterfaceFactory = $groupInterfaceFactory;
        $this->groupRepository = $groupRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @param array $data
     * @return bool
     */
    public function install(array $data)
    {
        $group = $this->groupInterfaceFactory->create();
        $group->setCode($data['name'])->setTaxClassId(3);
        try {
            $this->groupRepository->save($group);
        } catch (\Exception $e) {
            //error will likely be trying to add duplicate group
            print_r("Customer Group ".$data['name']." not installed, another group with the same name likely exists");
        }

        return true;
    }

    /**
     * @param string $customerGroupCode
     * @return int|null
     * @throws \Magento\Framework\Exception\LocalizedException
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
     * @return string
     */
    public function getDefaultCustomerGroup()
    {
        return $this->defaultCustomerGroup;
    }
}
