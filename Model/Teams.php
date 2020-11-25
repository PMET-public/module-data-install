<?php
namespace MagentoEse\DataInstall\Model;

use Magento\Company\Api\Data\TeamInterfaceFactory;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Company\Api\TeamRepositoryInterface;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Company\Api\Data\StructureInterfaceFactory;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;

class Teams {
    
    /** @var TeamInterfaceFactory */
    protected $teamFactory;

    /** @var CompanyRepositoryInterface */
    protected $companyRepository;

    /** @var SearchCriteriaBuilder */
    protected $searchCriteriaBuilder;

    /** @var CustomerRepositoryInterface */
    protected $customerRepository;

    /** @var TeamRepositoryInterface */
    protected $teamRepository;

    /** @var CompanyManagmentInterface */
    protected $companyManagement;

     /** @var StructureInterfaceFactory */
     protected $structureFactory;

    public function __construct(TeamInterfaceFactory $teamFactory, CompanyRepositoryInterface $companyRepository,
    SearchCriteriaBuilder $searchCriteriaBuilder, CustomerRepositoryInterface $customerRepository,TeamRepositoryInterface $teamRepository,
    CompanyManagementInterface $companyManagement, StructureInterfaceFactory $structureFactory){
        $this->teamFactory = $teamFactory;
        $this->companyRepository = $companyRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->customerRepository = $customerRepository;
        $this->teamRepository = $teamRepository;
        $this->companyManagement = $companyManagement;
        $this->structureFactory = $structureFactory;
    }

    public function install($row,$header){
        //company_name,team,parent
        //get company
        $companySearch = $this->searchCriteriaBuilder
            ->addFilter('company_name', $row['company_name'], 'eq')->create()->setPageSize(1)->setCurrentPage(1);
        $companyList = $this->companyRepository->getList($companySearch);
        /** @var CompanyInterface $company */
        $company = current($companyList->getItems());
        if(!$company){
            print_r("The company ". $row['company_name'] ." requested in b2b_teams.csv does not exist\n");
        }else{
            //Get the admin user for the company
            //Remove structure the existing user is in
            
        }
        return true;
    }

    private function addToTeam($company,$row){
        $companyAdminId = $this->companyManagement->getAdminByCompanyId($company->getId());
        
        //is the element we are adding a customer
        if($this->getCustomerId($row['team'])){

        }
        //if parent is empty, put it under the company admin
            if(empty($row['parent'])){
            //if parent is customer, add to that
                //else, check if team exists and create if needed
            }
        
        //$team = $this->teamFactory->create();
        //$team->setName();
    }

    private function createTeam($name){
        $team = $this->teamFactory->create();
        $team->setName($name);
        $this->teamRepository->save($team);
    }

    private function addTeamToTree($teamId,$parentId){
        //path is structure_id of admin user / structure_id of team)
        $newStruct = $this->structureFactory->create();
        $newStruct->setEntityId($teamId);
        $newStruct->setEntityType(1);
        $newStruct->setParentId($parentId);
        //$newStruct->setPath('1/2');
        $newStruct->setLevel(1);
        $newStruct->save();
        $newStruct->setPath($parentId.'/'.$newStruct->getId());
        $newStruct->save();
        return $newStruct;
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
        //TODO: Level needs to be determined 
        $newStruct->setLevel(1);
        $this->structureRepository->save($newStruct);
        $newStruct->setPath($parentId.'/'.$newStruct->getId());
        $this->structureRepository->save($newStruct);
    }

    private function getCustomerId($email){
        $customerSearch = 
        $this->searchCriteriaBuilder
        ->addFilter('email', $email, 'eq')->create()->setPageSize(1)->setCurrentPage(1);
        $customerList = $this->customerRepository->getList($customerSearch);
        $customer = current($customerList->getItems());
        if($customer){
            return $customer->getId();
        } else{
            return null;
        }
    }
}