<?php

/**
 * Copyright © Magento. All rights reserved.
 */
namespace MagentoEse\DataInstall\Model;

use Magento\RequisitionList\Api\Data\RequisitionListInterfaceFactory;
use Magento\RequisitionList\Api\Data\RequisitionListInterface;
use Magento\RequisitionList\Api\Data\RequisitionListItemInterfaceFactory;
use Magento\RequisitionList\Api\Data\RequisitionListItemInterface;
use Magento\RequisitionList\Api\RequisitionListRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;


class RequisitionLists
{
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

    public function __construct(RequisitionListInterfaceFactory $requisitionListFactory,
    RequisitionListRepositoryInterface $requisitionListRepository, CustomerRepositoryInterface $customerRepository,
    SearchCriteriaBuilder $searchCriteriaBuilder, RequisitionListItemInterfaceFactory $requisitionListItemFactory)
    {
        $this->requisitionListFactory = $requisitionListFactory;
        $this->requisitionListRepository = $requisitionListRepository;
        $this->customerRepository = $customerRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->requisitionListItemFactory = $requisitionListItemFactory;
    }
    public function install(array $row, array $settings)
    {
        //validate user
        try{
            $customer = $this->customerRepository->get($row['customer']);
        }catch (\Exception $e){
            print_r("Requistion list ".$row['name']." cannot be created. Customer ".$row['customer']." does not exist\n");
            return true;
        }
        
        $skus = explode(",",$row["skus"]);
        //get list if exists, otherwise create
        /** @var RequisitionListInterface $requisitionList */
        $requisitionList = $this->getRequisitionListByName($row['name'],$customer->getId());
        if(!$requisitionList){
            $requisitionList = $this->requisitionListFactory->create();
        }
        $requisitionList->setName($row['name']);
        $requisitionList->setCustomerId($customer->getId());
        $requisitionList->setDescription($row['description']);

        //remove existing items
        $requisitionList->setItems([]);
        
        //add items to list
        $listItems=[];
        foreach($skus as $sku){
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

    private function getRequisitionListByName($listName,$customerId){
        $listSearch = $this->searchCriteriaBuilder
        ->addFilter(RequisitionListInterface::NAME, $listName, 'eq')
        ->addFilter(RequisitionListInterface::CUSTOMER_ID,$customerId,'eq')
        ->create()->setPageSize(1)->setCurrentPage(1);
        $lists = $this->requisitionListRepository->getList($listSearch);
        return current($lists->getItems());
    }
     
}