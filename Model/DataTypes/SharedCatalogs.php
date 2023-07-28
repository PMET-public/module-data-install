<?php
/**
 * Copyright 2022 Adobe, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace MagentoEse\DataInstall\Model\DataTypes;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\SharedCatalog\Api\CompanyManagementInterface;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterfaceFactory;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use MagentoEse\DataInstall\Helper\Helper;

class SharedCatalogs
{

    /** @var SharedCatalogInterface */
    protected $sharedCatalogInterfaceFactory;

    /** @var SharedCatalogRepositoryInterface */
    protected $sharedCatalogRepository;

    /** @var CustomerGroups */
    protected $customerGroups;

    /** @var CompanyManagementInterface */
    protected $companyManagementInterface;

    /** @var Companies */
    protected $companies;

    /** @var SearchCriteriaBuilder */
    protected $searchCriteriaBuilder;

    /** @var Helper */
    protected $helper;

    /** @var Stores */
    protected $stores;

    /**
     * SharedCatalogs constructor
     *
     * @param Helper $helper
     * @param SharedCatalogInterfaceFactory $sharedCatalogInterface
     * @param SharedCatalogRepositoryInterface $sharedCatalogRepositoryInterface
     * @param CustomerGroups $customerGroups
     * @param CompanyManagementInterface $companyManagementInterface
     * @param Companies $companies
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param Stores $stores
     */
    public function __construct(
        Helper $helper,
        SharedCatalogInterfaceFactory $sharedCatalogInterface,
        SharedCatalogRepositoryInterface $sharedCatalogRepositoryInterface,
        CustomerGroups $customerGroups,
        CompanyManagementInterface $companyManagementInterface,
        Companies $companies,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Stores $stores
    ) {
        $this->helper = $helper;
        $this->sharedCatalogInterfaceFactory = $sharedCatalogInterface;
        $this->sharedCatalogRepository = $sharedCatalogRepositoryInterface;
        $this->customerGroups = $customerGroups;
        $this->companyManagementInterface = $companyManagementInterface;
        $this->companies = $companies;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->stores = $stores;
    }

    /**
     * Install
     *
     * @param array $row
     * @param array $settings
     * @return bool
     * @throws CouldNotSaveException
     * @throws LocalizedException
     */
    public function install(array $row, array $settings)
    {
        //required - name, companies is optional, but set column if it doesnt exist
        if (empty($row['name'])) {
            $this->helper->logMessage("name is required in b2b_shared_catalogs file, row skipped", "warning");
                return true;
        }

        if (empty($row['companies'])) {
            $row['companies'] = '';
        }

        if (empty($row['store_code'])) {
            $row['store_code'] = $settings['store_code'];
        }
        //check for existing shared catalog to update
        $sharedCatalog = $this->getSharedCatalogByName($row['name']);

        if (!$sharedCatalog) {
            //create customer group *this may no longer be needed
            //$this->customerGroups->install(['name'=>$row['name']]);
            $sharedCatalog = $this->sharedCatalogInterfaceFactory->create();
            //delete customer group if it exists
            $this->customerGroups->deleteCustomerGroupByCode($row['name']);
        }

        $sharedCatalog->setName($row['name']);
        $sharedCatalog->setCreatedBy(1);
        $sharedCatalog->setTaxClassId(3);
        if (!empty($row['description'])) {
            $sharedCatalog->setDescription($row['description']);
        }
        if (empty($row['type']) || $row['type']=='Custom') {
            $sharedCatalog->setType(SharedCatalogInterface::TYPE_CUSTOM);
            $sharedCatalog->setStoreId($this->stores->getStoreId($row['store_code']));
        } else {
            $sharedCatalog->setType(SharedCatalogInterface::TYPE_PUBLIC);
            $sharedCatalog->setStoreId(0);
        }
        $this->sharedCatalogRepository->save($sharedCatalog);

        //assign catalog to company
        //get all compainies and return to array
        $companiesData = explode(',', $row['companies']);
        $companiesToAssign = [];
        foreach ($companiesData as $companyData) {
            $company = $this->companies->getCompanyByName($companyData);
            if ($company) {
                $companiesToAssign[]=$company;
            }
        }
        if (count($companiesToAssign)>0) {
            $this->companyManagementInterface->assignCompanies($sharedCatalog->getId(), $companiesToAssign);
        }

        return true;
    }

    /**
     * Get Shared Catalog by Name
     *
     * @param string $sharedCatalogName
     * @return SharedCatalogInterface
     * @throws LocalizedException
     */
    private function getSharedCatalogByName($sharedCatalogName)
    {
        $catalogSearch = $this->searchCriteriaBuilder
        ->addFilter(
            SharedCatalogInterface::NAME,
            $sharedCatalogName,
            'eq'
        )->create()->setPageSize(1)->setCurrentPage(1);
        $catalogList = $this->sharedCatalogRepository->getList($catalogSearch);
        return current($catalogList->getItems());
    }
}
