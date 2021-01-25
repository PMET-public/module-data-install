<?php
namespace MagentoEse\DataInstall\Model;

use Magento\SharedCatalog\Api\CompanyManagementInterface;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterfaceFactory;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface;

class SharedCatalogs{

    /** @var SharedCatalogInterface */
    protected $sharedCatalogInterface;

    /** @var SharedCatalogRepositoryInterface */
    protected $sharedCatalogRepository;

    /** @var CustomerGroups */
    protected $customerGroups;

    /** @var CompanyManagementInterface */
    protected $companyManagementInterface;

    /** @var Companies */
    protected $companies;

    public function __construct(SharedCatalogInterfaceFactory $sharedCatalogInterface, 
    SharedCatalogRepositoryInterface $sharedCatalogRepositoryInterface,
    CustomerGroups $customerGroups,CompanyManagementInterface $companyManagementInterface,Companies $companies)
    {
        $this->sharedCatalogInterfaceFactory = $sharedCatalogInterface;
        $this->sharedCatalogRepository = $sharedCatalogRepositoryInterface;
        $this->customerGroups = $customerGroups;
        $this->companyManagementInterface = $companyManagementInterface;
        $this->companies = $companies;
    }

    public function install(array $row, array $settings){
        //TODO:validate row data
        //TODO:what happens when wanting to add a second public catalog
        //create customer group
        $this->customerGroups->install(['name'=>$row['name']]);
        //TODO:check for existing shared catalog to update
        /** @var SharedCatalogInterface $sharedCatalog */
        $sharedCatalog = $this->sharedCatalogInterfaceFactory->create();
        $sharedCatalog->setName($row['name']);
        $sharedCatalog->setCustomerGroupId($this->customerGroups->getCustomerGroupId($row['name']));
        $sharedCatalog->setCreatedBy(1);
        $sharedCatalog->setTaxClassId(3);
        if(!empty($row['description'])){
            $sharedCatalog->setDescription($row['description']);
        }
        if(empty($row['type']) || $row['type']=='Custom'){
            $sharedCatalog->setType(SharedCatalogInterface::TYPE_CUSTOM);
        }else{
            $sharedCatalog->setType(SharedCatalogInterface::TYPE_PUBLIC);
        }
        $this->sharedCatalogRepository->save($sharedCatalog);
        
        //assign catalog to company
        $r = $sharedCatalog->getId();
        //TODO:get all compainies and return to array
        $this->companyManagementInterface->assignCompanies($sharedCatalog->getId(),[$this->companies->getCompanyByName('Vandelay Industries')]);
        //$r = $u;
        return true;
    }
}
