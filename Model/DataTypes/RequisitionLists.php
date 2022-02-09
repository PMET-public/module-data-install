<?php

/**
 * Copyright © Adobe. All rights reserved.
 */
namespace MagentoEse\DataInstall\Model\DataTypes;

use Magento\Framework\Api\ExtensibleDataInterface;
use Magento\Framework\Exception\CouldNotSaveException;
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

    /** @var Stores */
    protected $stores;

    /**
     * RequisitionLists constructor.
     * @param Helper $helper
     * @param RequisitionListInterfaceFactory $requisitionListFactory
     * @param RequisitionListRepositoryInterface $requisitionListRepository
     * @param CustomerRepositoryInterface $customerRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param RequisitionListItemInterfaceFactory $requisitionListItemFactory
     * @param Stores $stores
     */
    public function __construct(
        Helper $helper,
        RequisitionListInterfaceFactory $requisitionListFactory,
        RequisitionListRepositoryInterface $requisitionListRepository,
        CustomerRepositoryInterface $customerRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequisitionListItemInterfaceFactory $requisitionListItemFactory,
        Stores $stores
    ) {
        $this->helper = $helper;
        $this->requisitionListFactory = $requisitionListFactory;
        $this->requisitionListRepository = $requisitionListRepository;
        $this->customerRepository = $customerRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->requisitionListItemFactory = $requisitionListItemFactory;
        $this->stores = $stores;
    }

    /**
     * @param array $row
     * @param array $settings
     * @return bool
     * @throws CouldNotSaveException
     */
    public function install(array $row, array $settings)
    {
        if (empty($row['name'])) {
            $this->helper->logMessage("Requisition List requires a name, row skipped", "warning");
            return true;
        }

        if (empty($row['site_code'])) {
            $row['site_code']=$settings['site_code'];
        }

        //get website id and validate
        $websiteId = $this->stores->getWebsiteId($row['site_code']);

        //validate customer
        try {
            $customer = $this->customerRepository->get($row['customer_email'], $websiteId);
        } catch (\Exception $e) {
            $this->helper->logMessage(
                "Requistion list ".$row['name']." cannot be created. Customer ".
                $row['customer_email']." does not exist",
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
        if ($skus[0]!="") {
            foreach ($skus as $sku) {
                $skuArray = explode("|", $sku);
                //if quantity isnt given, set it to 1
                if (count($skuArray)==1) {
                    $skuArray[1]=1;
                }
                /** @var RequisitionListItemInterface $listItem */
                $listItem = $this->requisitionListItemFactory->create();
                $listItem->setSku($skuArray[0]);
                $listItem->setQty($skuArray[1]);
                $listItems[]=$listItem;
            }
            $requisitionList->setItems($listItems);
        }

        $this->requisitionListRepository->save($requisitionList);

        return true;
    }

    /**
     * @param $listName
     * @param $customerId
     * @return ExtensibleDataInterface
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
