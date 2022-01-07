<?php
/**
 * Copyright © Adobe. All rights reserved.
 */

 namespace MagentoEse\DataInstall\Model\DataTypes;

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
 use Magento\CompanyCredit\Api\CreditLimitRepositoryInterface;
 use Magento\Framework\App\State;
 use Magento\Framework\Exception\NoSuchEntityException;
 use MagentoEse\DataInstall\Helper\Helper;

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

    /** @var CreditLimitRepositoryInterface  */
    protected $creditLimitRepository;

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

    /** @var State */
    protected $appState;

    /** @var Stores */
    protected $stores;

    /** @var Helper */
    protected $helper;

    /**
     * Companies constructor.
     * @param CompanyCustomer $companyCustomer
     * @param CustomerRepositoryInterface $customer
     * @param Customer $customerResource
     * @param StructureInterfaceFactory $structure
     * @param CreditLimitManagementInterface $creditLimitManagement
     * @param CreditLimitRepositoryInterface $creditLimitRepository
     * @param UserFactory $userFactory
     * @param RegionFactory $region
     * @param StructureRepository $structureRepository
     * @param CompanyRepositoryInterface $companyRepositoryInterface
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param Stores $stores
     * @param Helper $helper
     */
    public function __construct(
        CompanyCustomer $companyCustomer,
        CustomerRepositoryInterface $customer,
        Customer $customerResource,
        StructureInterfaceFactory $structure,
        CreditLimitManagementInterface $creditLimitManagement,
        CreditLimitRepositoryInterface $creditLimitRepository,
        UserFactory $userFactory,
        RegionFactory $region,
        StructureRepository $structureRepository,
        CompanyRepositoryInterface $companyRepositoryInterface,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        State $appState,
        Stores $stores,
        Helper $helper
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
        $this->appState = $appState;
        $this->stores = $stores;
        $this->helper = $helper;
        $this->creditLimitRepository = $creditLimitRepository;
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
        //validate and set optional values
        //required company_name,company_admin,street,city,country_id,region,postcode,telephone,company_admin
        //set default - sales_rep ,company_email,site_code,credit_limit

        if (empty($row['company_name'])) {
            $this->helper->printMessage("Company missing name, row skipped", "warning");
            return true;
        }
        
        if (empty($row['street'])) {
            $this->helper->printMessage("Company ".$row['company_name'].
            " missing street, row skipped", "warning");
            return true;
        }

        if (empty($row['company_admin'])) {
            $this->helper->printMessage("Company ".$row['company_name'].
            " missing company_admin, row skipped", "warning");
            return true;
        }

        if (empty($row['city'])) {
            $this->helper->printMessage("Company ".$row['company_name'].
            "  missing city, row skipped", "warning");
            return true;
        }

        if (empty($row['region'])) {
            $this->helper->printMessage("Company ".$row['company_name'].
            "  missing region, row skipped", "warning");
            return true;
        }

        if (empty($row['country_id'])) {
            $this->helper->printMessage("Company ".$row['company_name'].
            "  missing country_id, row skipped", "warning");
            return true;
        }
        
        $region = $this->region->create();
        $row['region_id'] = $region->loadByCode($row['region'], $row['country_id'])->getId();
        if (empty($row['region_id'])) {
            $this->helper->printMessage("Either your region or country_id is invalid for "
            .$row['company_name'].", row skipped", "warning");
            return true;
        }

        if (empty($row['telephone'])) {
            $this->helper->printMessage("Company ".$row['company_name'].
            "  missing telephone, row skipped", "warning");
            return true;
        }

        if (empty($row['postcode'])) {
            $this->helper->printMessage("Company ".$row['company_name'].
            "  missing postcode, row skipped", "warning");
            return true;
        }

        if (empty($row['site_code'])) {
            $row['site_code'] = $settings['site_code'];
        }
        $websiteId = $this->stores->getWebsiteId($row['site_code']);
        
        try {
            $adminCustomer = $this->customer->get($row['company_admin'], $websiteId);
        } catch (NoSuchEntityException $e) {
            $this->helper->printMessage("Company admin user ".$row['company_admin'].
            " is missing, row skipped", "warning");
            return true;
        }

        //get sales rep, use admin as default
        if (empty($row['sales_rep'])) {
            $row['sales_rep'] = 'admin';
        }
        $salesRep = $this->userFactory->create();
        $salesRep->loadByUsername($row['sales_rep']);

        //if company email isn't defined, use the admin email
        if (empty($row['company_email'])) {
            $row['company_email']=$row['company_admin'];
        }

        //set credit_limit if not defined
        if (empty($row['credit_limit'])) {
            $row['credit_limit']=0;
        }

        /** @var CompanyInterface $newCompany */
        $newCompany = $this->getCompanyByName($row['company_name']);
        //create company
        if (!$newCompany) {
            $newCompany = $this->appState->emulateAreaCode(
                'frontend',
                [$this->companyCustomer, 'createCompany'],
                [$adminCustomer, $row]
            );
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
        $this->creditLimitRepository->save($creditLimit);

        if (count($row['company_customers']) > 0) {
            foreach ($row['company_customers'] as $companyCustomerEmail) {
                //tie other customers to company

                $companyCustomer = $this->customer->get(trim($companyCustomerEmail), $websiteId);
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
     * @param $newCompany
     * @param $companyCustomer
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\State\InputMismatchException
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
     * @param $customerId
     * @param $parentId
     * @throws \Magento\Framework\Exception\CouldNotSaveException
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
     * @param $companyName
     * @return CompanyInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCompanyByName($companyName)
    {
        $companySearch = $this->searchCriteriaBuilder
        ->addFilter(CompanyInterface::NAME, $companyName, 'eq')->create()->setPageSize(1)->setCurrentPage(1);
        $companyList = $this->companyRepositoryInterface->getList($companySearch);
        return current($companyList->getItems());
    }
}
