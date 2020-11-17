<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

 namespace MagentoEse\DataInstall\Model;

 use Magento\Company\Api\Data\StructureInterfaceFactory;
 use Magento\Company\Model\ResourceModel\Customer;
 use Magento\CompanyCredit\Api\CreditLimitManagementInterface;
 use Magento\Customer\Api\CustomerRepositoryInterface;
 use Magento\Framework\Setup\SampleData\Context as SampleDataContext;
 use Magento\User\Model\UserFactory;
 use Magento\Directory\Model\RegionFactory;

 class Companies
 {

     /**
      * @var \Magento\Framework\Setup\SampleData\Context
      */
     protected $sampleDataContext;

     /**
      * @var \Magento\Company\Model\Customer\Company
      */
     protected $companyCustomer;

     /**
      * @var CustomerRepositoryInterface
      */
     protected $customer;

     /**
      * @var Customer
      */
     protected $customerResource;

     /**
      * @var StructureInterfaceFactory
      */
     protected $structure;

     /**
      * @var float
      */
     protected $creditLimit;

     /**
      * @var CreditLimitManagementInterface
      */
     protected $creditLimitManagement;

     /**
      * @var \Magento\User\Api\Data\UserInterfaceFactory
      */
     protected $userFactory;

     /**
      * @var RegionFactory
      */
     protected $region;

     /**
      * Company constructor.
      * @param SampleDataContext $sampleDataContext
      * @param \Magento\Company\Model\Customer\Company $companyCustomer
      * @param CustomerRepositoryInterface $customer
      * @param Customer $customerResource
      * @param StructureInterfaceFactory $structure
      * @param CreditLimitManagementInterface $creditLimitManagement
      * @param \Magento\User\Api\Data\UserInterfaceFactory $userInterfaceFactory
      */
     public function __construct(
         SampleDataContext $sampleDataContext,
         \Magento\Company\Model\Customer\Company $companyCustomer,
         CustomerRepositoryInterface $customer,
         Customer $customerResource,
        StructureInterfaceFactory $structure,
        CreditLimitManagementInterface $creditLimitManagement,
        UserFactory $userFactory,
        RegionFactory $region
     )
     {
         $this->fixtureManager = $sampleDataContext->getFixtureManager();
         $this->csvReader = $sampleDataContext->getCsvReader();
         $this->companyCustomer = $companyCustomer;
         $this->customer = $customer;
         $this->customerResource = $customerResource;
         $this->structure = $structure;
         $this->creditLimitManagement = $creditLimitManagement;
         $this->userFactory = $userFactory;
         $this->region = $region;
     }

     /**
      * @param array $fixtures
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
        //create company
        $newCompany = $this->companyCustomer->createCompany($adminCustomer, $row);
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

        ///taken out for testing as customers need to be created first in this scenerio
        if(count($row['company_customers']) > 0) {
            foreach ($row['company_customers'] as $companyCustomerEmail) {
                //tie other customers to company
                $companyCustomer = $this->customer->get(trim($companyCustomerEmail));
                $this->addCustomerToCompany($newCompany, $companyCustomer);
                /* add the customer in the tree under the admin user
                //They may be moved later on if they are part of a team */
                //$this->addToTree($companyCustomer->getId(), $adminCustomer->getId());

            }

        }
        return true;
     }


     /**
      * @param \Magento\Company\Model\Customer\Company $newCompany
      * @param \Magento\Company\Model\Customer\Company $companyCustomer
      */
     private function addCustomerToCompany($newCompany,$companyCustomer){

         //assign to company
         if ($companyCustomer->getExtensionAttributes() !== null
             && $companyCustomer->getExtensionAttributes()->getCompanyAttributes() !== null) {
             $companyAttributes = $companyCustomer->getExtensionAttributes()->getCompanyAttributes();
             $companyAttributes->setCustomerId($companyCustomer->getId());
             $companyAttributes->setCompanyId($newCompany->getId());
             $this->customerResource->saveAdvancedCustomAttributes($companyAttributes);
         }
     }

     /**
      * @param int $customerId
      * @param int $parentId
      */
     private function addToTree($customerId,$parentId){
         $newStruct = $this->structure->create();
         $newStruct->setEntityId($customerId);
         $newStruct->setEntityType(0);
         $newStruct->setParentId($parentId);
         $newStruct->setPath('1/2');
         $newStruct->setLevel(1);
         $newStruct->save();
     }
 }
