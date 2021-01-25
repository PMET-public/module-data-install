<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

 namespace MagentoEse\DataInstall\Model;

 use Magento\Company\Api\Data\StructureInterfaceFactory;
 use Magento\Company\Model\StructureRepository;
 use Magento\Company\Model\Customer\Company as CompanyCustomer;
 use Magento\Company\Model\ResourceModel\Customer;
 use Magento\CompanyCredit\Api\CreditLimitManagementInterface;
 use Magento\Customer\Api\CustomerRepositoryInterface;
 use Magento\User\Api\Data\UserInterfaceFactory;
 use Magento\User\Model\UserFactory;
 use Magento\Directory\Model\RegionFactory;
 use Magento\Company\Api\CompanyRepositoryInterface;
 use Magento\Framework\Api\SearchCriteriaBuilder;
 use Magento\Company\Api\Data\CompanyInterface;

class Companies
{

    /** @var CompanyCustomer  */
    protected $companyCustomer;

    /** @var CustomerRepositoryInterface  */
    protected $customer;

    /** @var Customer  */
    protected $customerResource;

    /** @var StructureInterfaceFactory  */
    protected $structure;

    /** @var float */
    protected $creditLimit;

    /** @var CreditLimitManagementInterface  */
    protected $creditLimitManagement;

    /** @var UserInterfaceFactory  */
    protected $userFactory;

    /** @var RegionFactory  */
    protected $region;
     
    /** @var StructureRepository  */
    protected $structureRepository;

    /** @var CompanyRepositoryInterface  */
    protected $companyRepositoryInterface;

    /** @var SearchCriteriaBuilder */
    protected $searchCriteriaBuilder;

    /**
     * Companies constructor.
     * @param CompanyCustomer $companyCustomer
     * @param CustomerRepositoryInterface $customer
     * @param Customer $customerResource
     * @param StructureInterfaceFactory $structure
     * @param CreditLimitManagementInterface $creditLimitManagement
     * @param UserFactory $userFactory
     * @param RegionFactory $region
     * @param StructureRepository $structureRepository
     * @param CompanyRepositoryInterface $companyRepositoryInterface
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        CompanyCustomer $companyCustomer,
        CustomerRepositoryInterface $customer,
        Customer $customerResource,
        StructureInterfaceFactory $structure,
        CreditLimitManagementInterface $creditLimitManagement,
        UserFactory $userFactory,
        RegionFactory $region,
        StructureRepository $structureRepository,
        CompanyRepositoryInterface $companyRepositoryInterface,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->companyCustomer = $companyCustomer;
        $this->customer = $customer;
        $this->customerResource = $customerResource;
        $this->structure = $structure;
        $this->creditLimitManagement = $creditLimitManagement;
        $this->userFactory = $userFactory;
        $this->region = $region;
        $this->structureRepository = $structureRepository;
        $this->companyRepositoryInterface = $companyRepositoryInterface;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @param array $row
     * @param array $settings
     * @return bool
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function install(array $row, array $settings)
    {
        //TODO: Enable Purchase Orders

        $region = $this->region->create();

        $row['region_id'] = $region->loadByCode($row['region'], $row['country_id'])->getId();
        //$row['company_customers'] = explode(",", $row['company_customers']);
        //get customer for admin user
        $adminCustomer = $this->customer->get($row['admin_email']);
        //get sales rep
        $salesRep = $this->userFactory->create();
        $salesRep->loadByUsername($row['sales_rep']);

        $row['company_email']=$row['admin_email'];
       
        /** @var CompanyInterface $newCompany */
        $newCompany = $this->getCompanyByName($row['company_name']);
        //create company
        if(!$newCompany){
            $newCompany = $this->companyCustomer->createCompany($adminCustomer, $row);
        }
        
        $newCompany->setSalesRepresentativeId($salesRep->getId());
        $newCompany->setLegalName($row['company_name']);
        $newCompany->setStatus(1);
        $extensionAttributes = $newCompany->getExtensionAttributes();
        $newCompany->setExtensionAttributes($extensionAttributes->setIsPurchaseOrderEnabled(true));
        $newCompany->save();
        //set credit limit
        $creditLimit = $this->creditLimitManagement->getCreditByCompanyId($newCompany->getId());
        $creditLimit->setCreditLimit($row['credit_limit']);
        $creditLimit->save();

        if (count($row['company_customers']) > 0) {
            foreach ($row['company_customers'] as $companyCustomerEmail) {
                //tie other customers to company
                
                $companyCustomer = $this->customer->get(trim($companyCustomerEmail));
                $this->addCustomerToCompany($newCompany, $companyCustomer);
                /* add the customer in the tree under the admin user
                //They may be moved later on if they are part of a team */
                if ($row['admin_email']!='Y') {
                    $this->addToTree($companyCustomer->getId(), $adminCustomer->getId());
                }
            }
        }
        return true;
    }

    /**
     * @param CompanyCustomer $newCompany
     * @param CompanyCustomer $companyCustomer
     */
    private function addCustomerToCompany($newCompany, $companyCustomer)
    {

        //assign to company
        if ($companyCustomer->getExtensionAttributes() !== null
            && $companyCustomer->getExtensionAttributes()->getCompanyAttributes() !== null) {
            $companyAttributes = $companyCustomer->getExtensionAttributes()->getCompanyAttributes();
            $companyAttributes->setCustomerId($companyCustomer->getId());
            $companyAttributes->setCompanyId($newCompany->getId());
            $this->customerResource->saveAdvancedCustomAttributes($companyAttributes);
            $this->customer->save($companyCustomer);
        }
    }

    /**
     * @param int $customerId
     * @param int $parentId
     */
    private function addToTree($customerId, $parentId)
    {
        $newStruct = $this->structure->create();
        $newStruct->setEntityId($customerId);
        $newStruct->setEntityType(0);
        $newStruct->setParentId($parentId);
        $newStruct->setLevel(1);
        $this->structureRepository->save($newStruct);
        $newStruct->setPath($parentId.'/'.$newStruct->getId());
        $this->structureRepository->save($newStruct);
    }

    /**
     *
     * @param string $name
     * @return \Magento\Company\Api\Data\CompanyInterface[] $companies
     */
    public function getCompanyByName($companyName){
        $companySearch = $this->searchCriteriaBuilder
        ->addFilter(CompanyInterface::NAME, $companyName, 'eq')->create()->setPageSize(1)->setCurrentPage(1);
        $companyList = $this->companyRepositoryInterface->getList($companySearch);
        return current($companyList->getItems());
    }
}
