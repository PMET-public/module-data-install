<?php
/**
 * Copyright © Magento. All rights reserved.
 */
namespace MagentoEse\DataInstall\Model\DataTypes;

use Magento\Company\Api\Data\TeamInterfaceFactory;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Company\Api\Data\StructureInterfaceFactory;
use Magento\Company\Model\StructureRepository;
use Magento\Framework\Api\SearchCriteriaInterface;
use MagentoEse\DataInstall\Helper\Helper;
use Magento\Framework\Exception\NoSuchEntityException;

class Teams
{
    
    /** @var Helper */
    protected $helper;
    
    /** @var TeamInterfaceFactory */
    protected $teamFactory;

    /** @var CompanyRepositoryInterface */
    protected $companyRepository;

    /** @var SearchCriteriaBuilder */
    protected $searchCriteriaBuilder;

    /** @var CustomerRepositoryInterface */
    protected $customerRepository;

    /** @var StructureInterfaceFactory */
    protected $structureFactory;

    /** @var StructureRepository  */
    protected $structureRepository;

     /** @var SearchCriteriaInterface  */
     protected $searchCriteriaInterface;

    /** @var int */
    protected $companyId;

    public function __construct(
        Helper $helper,
        TeamInterfaceFactory $teamFactory,
        CompanyRepositoryInterface $companyRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        CustomerRepositoryInterface $customerRepository,
        StructureInterfaceFactory $structureFactory,
        SearchCriteriaInterface $searchCriteriaInterface,
        StructureRepository $structureRepository
    ) {
        $this->helper = $helper;
        $this->teamFactory = $teamFactory;
        $this->companyRepository = $companyRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->customerRepository = $customerRepository;
        $this->structureFactory = $structureFactory;
        $this->structureRepository = $structureRepository;
        $this->searchCriteriaInterface = $searchCriteriaInterface;
    }
    
    public function install($row, $header)
    {
        $data['members'] = explode(",", $row['members']);
        //create array from members addresses
        // Create Team
        $newTeam = $this->teamFactory->create();
        $newTeam->setName($row['name']);
        $newTeam->save();

        //get admin user id
        $adminUserId = $this->getCompanyAdminIdByName($row['company_name']);
        //get admins structure
        $parentId = $this->getStructureByEntity($adminUserId, 0)->getDataByKey('structure_id');
        $teamId =($newTeam->getId());
        //put team under admin users
        $teamStruct = $this->addTeamToTree($teamId, $parentId);
        //loop over team members
        foreach ($data['members'] as $companyCustomerEmail) {
            //get user id from email
            try{
                 $userId = $this->customerRepository->get(trim($companyCustomerEmail))->getId();
            }catch(NoSuchEntityException $e){
                $this->helper->printMessage("User ".$companyCustomerEmail." was not found and will not be added to team ".$row['name']." for company ".$row['company_name'],"warning");
                break;
            }
           
            //delete structure that the user belongs to
            $userStruct = $this->getStructureByEntity($userId, 0);
            if ($userStruct) {
                $structureId = $userStruct->getDataByKey('structure_id');
                $this ->structureRepository->deleteById($structureId);
            }

            //add them to the new team
            $this->addUserToTeamTree($userId, $teamStruct->getId(), $teamStruct->getPath());
            
            
        }

        return true;
    }

     /**
      * @param int $userId
      * @param int $parentId
      * @param string $path
      * @return \Magento\Company\Model\Structure
      */
    private function addUserToTeamTree($userId, $parentId, $path)
    {
        $newStruct = $this->structureFactory->create();
        $newStruct->setEntityId($userId);
        $newStruct->setEntityType(0);
        $newStruct->setParentId($parentId);
        $newStruct->setLevel(2);
        $newStruct->save();
        $newStruct->setPath($path.'/'.$newStruct->getId());
        $newStruct->save();
        return $newStruct;
    }

      /**
       * @param $entityId
       * @param $entityType
       * @return \Magento\Company\Api\Data\StructureInterface|mixed
       */
    private function getStructureByEntity($entityId, $entityType)
    {
        $builder = $this->searchCriteriaBuilder;
        $builder->addFilter('entity_id', $entityId);
        $builder->addFilter('entity_type', $entityType);
        $structures = $this->structureRepository->getList($builder->create())->getItems();
        return reset($structures);
    }
        
    private function getCompanyAdminIdByName($name)
    {
        $companySearch = $this->searchCriteriaBuilder
        ->addFilter('company_name', $name, 'eq')->create()->setPageSize(1)->setCurrentPage(1);
        $companyList = $this->companyRepository->getList($companySearch);
        /** @var CompanyInterface $company */
        $company = current($companyList->getItems());
    
        if (!$company) {
            $this->helper->printMessage("The company ". $name ." requested in b2b_teams.csv does not exist","warning");
        } else {
            /**@var CompanyInterface $company */
            return $company->getSuperUserId();
        }
    }

    private function addTeamToTree($teamId, $parentId)
    {
        //path is structure_id of admin user / structure_id of team)
        $newStruct = $this->structureFactory->create();
        $newStruct->setEntityId($teamId);
        $newStruct->setEntityType(1);
        $newStruct->setParentId($parentId);
        $newStruct->setLevel(1);
        $newStruct->save();
        $newStruct->setPath($parentId.'/'.$newStruct->getId());
        $newStruct->save();
        return $newStruct;
    }
}
