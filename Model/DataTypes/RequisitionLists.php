<?php

/**
 * Copyright © Adobe. All rights reserved.
 */
namespace MagentoEse\DataInstall\Model\DataTypes;

use Magento\RequisitionList\Api\Data\RequisitionListInterfaceFactory;
use Magento\RequisitionList\Api\Data\RequisitionListInterface;
use Magento\RequisitionList\Api\Data\RequisitionListItemInterfaceFactory;
use Magento\RequisitionList\Api\Data\RequisitionListItemInterface;
use Magento\RequisitionList\Api\RequisitionListRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use MagentoEse\DataInstall\Helper\Helper;

class RequisitionLists
{
    /** @var Helper */
    protected $helper;

    /** @var RequisitionListInterfaceFactory */
    protected $requisitionListFactory;

    /** @var RequisitionListRepositoryInterface */
    protected $requisitionListRepository;

    /** @var CustomerRepositoryInterface */
    protected $customerRepository;

    /** @var SearchCriteriaBuilder */
    protected $searchCriteriaBuilder;

    /** @var RequisitionListItemInterfaceFactory */
    protected $requisitionListItemFactory;
    
    public function __construct(
        Helper $helper,
        RequisitionListInterfaceFactory $requisitionListFactory,
        RequisitionListRepositoryInterface $requisitionListRepository,
        CustomerRepositoryInterface $customerRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequisitionListItemInterfaceFactory $requisitionListItemFactory
    ) {
        $this->helper = $helper;
        $this->requisitionListFactory = $requisitionListFactory;
        $this->requisitionListRepository = $requisitionListRepository;
        $this->customerRepository = $customerRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->requisitionListItemFactory = $requisitionListItemFactory;
    }

    /**
     * @param array $row
     * @param array $settings
     * @return bool
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function install(array $row, array $settings)
    {
        //validate user
        try {
            $customer = $this->customerRepository->get($row['customer']);
        } catch (\Exception $e) {
            $this->helper->printMessage(
                "Requistion list ".$row['name']." cannot be created. Customer ".$row['customer']." does not exist",
                "warning"
            );
            return true;
        }

        $skus = explode(",", $row["skus"]);
        //get list if exists, otherwise create
        /** @var RequisitionListInterface $requisitionList */
        $requisitionList = $this->getRequisitionListByName($row['name'], $customer->getId());
        if (!$requisitionList) {
            $requisitionList = $this->requisitionListFactory->create();
        }
        $requisitionList->setName($row['name']);
        $requisitionList->setCustomerId($customer->getId());
        $requisitionList->setDescription($row['description']);

        //remove existing items
        $requisitionList->setItems([]);

        //add items to list
        $listItems=[];
        foreach ($skus as $sku) {
            /** @var RequisitionListItemInterface $listItem */
            $listItem = $this->requisitionListItemFactory->create();
            $listItem->setSku($sku);
            $listItem->setQty(1);
            $listItems[]=$listItem;
        }
        $requisitionList->setItems($listItems);
        $this->requisitionListRepository->save($requisitionList);

        return true;
    }

    /**
     * @param $listName
     * @param $customerId
     * @return \Magento\Framework\Api\ExtensibleDataInterface
     */
    private function getRequisitionListByName($listName, $customerId)
    {
        $listSearch = $this->searchCriteriaBuilder
        ->addFilter(RequisitionListInterface::NAME, $listName, 'eq')
        ->addFilter(RequisitionListInterface::CUSTOMER_ID, $customerId, 'eq')
        ->create()->setPageSize(1)->setCurrentPage(1);
        $lists = $this->requisitionListRepository->getList($listSearch);
        return current($lists->getItems());
    }
}
