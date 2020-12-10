<?php
namespace MagentoEse\DataInstall\Model;

use Magento\Company\Api\Data\TeamInterfaceFactory;
use Magento\Company\Api\Data\TeamInterface;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Company\Api\TeamRepositoryInterface;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Company\Api\Data\StructureInterfaceFactory;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Company\Api\CompanyHierarchyInterface;
use Magento\Company\Api\Data\StructureInterface;
use Magento\Company\Model\StructureRepository;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilder;


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

       /** @var CompanyHierarchyInterface  */
    protected $companyHierarchy;

    /** @var StructureRepository  */
    protected $structureRepository;

     /** @var SearchCriteriaInterface  */
     protected $searchCriteriaInterface;


     /** @var FilterBuilder  */
     protected $filterBuilder;


     /** @var FilterGroupBuilder  */
     protected $filterGroupBuilder;

    /** @var int */
    protected $companyId;

    public function __construct(TeamInterfaceFactory $teamFactory, CompanyRepositoryInterface $companyRepository,
    SearchCriteriaBuilder $searchCriteriaBuilder, CustomerRepositoryInterface $customerRepository,TeamRepositoryInterface $teamRepository,
    CompanyManagementInterface $companyManagement, StructureInterfaceFactory $structureFactory,
    CompanyHierarchyInterface $companyHierarchy, SearchCriteriaInterface $searchCriteriaInterface, FilterBuilder $filterBuilder, FilterGroupBuilder $filterGroupBuilder,
    StructureRepository $structureRepository){
        $this->teamFactory = $teamFactory;
        $this->companyRepository = $companyRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->customerRepository = $customerRepository;
        $this->teamRepository = $teamRepository;
        $this->companyManagement = $companyManagement;
        $this->structureFactory = $structureFactory;
        $this->companyHierarchy = $companyHierarchy;
        $this->structureRepository = $structureRepository;
        $this->searchCriteriaInterface = $searchCriteriaInterface;
        $this->filterBuilder = $filterBuilder;
        $this->filterGroupBuilder = $filterGroupBuilder;
    }
    public function __destruct () {
        echo "Destructiong\n";
        unset($this->companyHierarchy);
    }
    public function install($row,$header){
        //company_name,team,parent
        //get company
        echo $row['team']."\n";
        $companySearch = $this->searchCriteriaBuilder
            ->addFilter('company_name', $row['company_name'], 'eq')->create()->setPageSize(1)->setCurrentPage(1);
        $companyList = $this->companyRepository->getList($companySearch);
        /** @var CompanyInterface $company */
        $company = current($companyList->getItems());
        
        if(!$company){
            print_r("The company ". $row['company_name'] ." requested in b2b_teams.csv does not exist\n");
        }else{
            $this->companyId = $company->getId();
            $companyAdminId = $this->companyManagement->getAdminByCompanyId($company->getId())->getId();
            $entityToAdd = $this->getEntityType($row['team']);
            //get path replaced with ids
            $newPath = $this->getPath($row['parent'],$companyAdminId);
            if(!empty($newPath['parent'])){
                $newStruct = $this->structureFactory->create();
                $newStruct->setEntityId($entityToAdd['id']);
                $newStruct->setEntityType($entityToAdd['type']);
                //TODO: parent id needs to be determined
                $newStruct->setParentId($companyAdminId);
                $newStruct->setLevel($newPath['level']);
                //$this->structureRepository->save($newStruct);
                //$this->setPath($newStruct,'');
                $newStruct->setPath($newPath['parent'].'/'.$newStruct->getId());
                print_r("original ". $row['parent'] ."---".$newPath['parent'].'/'.$newStruct->getId()."\n");
                //$this->structureRepository->save($newStruct);
                
            } else{
                print_r("The parent ". $row['parent'] ." requested in b2b_teams.csv is invalid and has been skipped\n");
            }
            
        }
        return true;
    }

    private function getPath($path,$companyAdminId){
        $newPath = ['parent'=>$companyAdminId,'level'=>1];
        if(!empty($path)){
            //turn path into array
            $pathArray = explode('/',$path);
            foreach($pathArray as $pathElement){
                //$element = $this->getEntityType($pathElement);
                $newPath['parent'] = $newPath['parent'].'/'.$this->getEntityType($pathElement)['id'];
                $currentTeams = $this->companyHierarchy->getCompanyHierarchy($this->companyId);
            }
            $newPath['level'] = count($pathArray);
        }
        unset($currentTeams);
       return $newPath;
    }

    private function getEntityType($entity){
        $type=[];
        //determine if it's a customer or team, return type and Id
        try{
            $customer = $this->customerRepository->get($entity);
            $entityId = $customer->getId();
            $entityType = StructureInterface::TYPE_CUSTOMER;
        }catch(NoSuchEntityException $e){
            //check does team exist
            //get teams under that name
            $teams = $this->getTeams($entity);
            //if there are none, then create
            if(count($teams)==0){
                return ['type'=>StructureInterface::TYPE_TEAM,'id'=>$this->createTeam($entity)];
            }
            //if there is one, then get from company structure with that entity type and entity id
            else{
                $teamStructureId = $this->getEntityIdOfTeamInStructure($teams);
                if($teamStructureId){
                    return ['type'=>StructureInterface::TYPE_TEAM,'id'=>$teamStructureId];
                }else{
                    return ['type'=>StructureInterface::TYPE_TEAM,'id'=>$this->createTeam($entity)];
                }
            }        
        }
        
        
        echo $entityType.'--'.$entityId."\n";
        return ['type'=>$entityType,'id'=>$entityId];
    }

    private function getTeams($entity){
        $teamFilter = $this->filterBuilder
            ->setField("name")->setConditionType("eq")->setValue($entity)->create();
            $teamFilterGroup = $this->filterGroupBuilder
            ->addFilter($teamFilter)->create();
            $teamSearch = $this->searchCriteriaBuilder->setFilterGroups([$teamFilterGroup])->create();
            $teams = $this->teamRepository->getList($teamSearch)->getItems();
            return $teams;
    }

    private function getEntityIdOfTeamInStructure($teams){
        foreach($teams as $team){
            $entityIdFilter = $this->filterBuilder
            ->setField("entity_id")->setConditionType("eq")->setValue($team->getId())->create();
            $entityIdFilterGroup = $this->filterGroupBuilder
            ->addFilter($entityIdFilter)->create();
            
            $entityTypeFilter = $this->filterBuilder
            ->setField("entity_type")->setConditionType("eq")->setValue(StructureInterface::TYPE_TEAM)->create();
            $entityTypeFilterGroup = $this->filterGroupBuilder
            ->addFilter($entityTypeFilter)->create();

            $structSearch = $this->searchCriteriaBuilder->setFilterGroups([$entityIdFilterGroup,$entityTypeFilterGroup])->create();
            $companyStructures = $this->structureRepository->getList($structSearch)->getItems();
            if(count($companyStructures)==1){
                return $team->getId();
                break;
            }
        }
        //if team doesnt exist under that company return false
        return false;
    }

    private function createTeam($name){
        //does the team exist, if not create it
        /** @var TeamInterface $team */
        $team = $this->teamFactory->create();
        $team->setName($name);
        $this->teamRepository->create($team, $this->companyId);
        echo "team=".$name."[".$team->getId()."}\n";
        // if($team->getId()==3){
        //     echo "moving\n"; 
        //     $this->companyHierarchy->moveNode(15,14);
        // }
       
        return $team->getId();
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

 }