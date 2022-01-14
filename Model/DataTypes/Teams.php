<?php
/**
 * Copyright © Adobe. All rights reserved.
 */
namespace MagentoEse\DataInstall\Model\DataTypes;

use Magento\Company\Api\Data\TeamInterfaceFactory;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\TeamRepositoryInterface;
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

     /** @var TeamRepositoryInterface */
     protected $teamRepository;

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

    /** @var Stores */
    protected $stores;

    /**
     * Teams constructor.
     * @param Helper $helper
     * @param TeamInterfaceFactory $teamFactory
     * @param CompanyRepositoryInterface $companyRepository
     * @param TeamRepositoryInterface $teamRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param CustomerRepositoryInterface $customerRepository
     * @param StructureInterfaceFactory $structureFactory
     * @param SearchCriteriaInterface $searchCriteriaInterface
     * @param StructureRepository $structureRepository
     * @param Stores $stores
     */
    public function __construct(
        Helper $helper,
        TeamInterfaceFactory $teamFactory,
        CompanyRepositoryInterface $companyRepository,
        TeamRepositoryInterface $teamRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        CustomerRepositoryInterface $customerRepository,
        StructureInterfaceFactory $structureFactory,
        SearchCriteriaInterface $searchCriteriaInterface,
        StructureRepository $structureRepository,
        Stores $stores
    ) {
        $this->helper = $helper;
        $this->teamFactory = $teamFactory;
        $this->companyRepository = $companyRepository;
        $this->teamRepository = $teamRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->customerRepository = $customerRepository;
        $this->structureFactory = $structureFactory;
        $this->structureRepository = $structureRepository;
        $this->searchCriteriaInterface = $searchCriteriaInterface;
        $this->stores = $stores;
    }

    /**
     * @param $row
     * @param $header
     * @return bool
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\StateException
     */
    public function install($row, $settings)
    {
        //company name and team name required
        if (empty($row['company_name'])) {
            $this->helper->printMessage("company_name is required in b2b_teams.csv, row skipped", "warning");
                return true;
        }

        if (empty($row['name'])) {
            $this->helper->printMessage("name is required in b2b_teams.csv, row skipped", "warning");
                return true;
        }

        if (empty($row['site_code'])) {
            $row['site_code'] = $settings['site_code'];
        }
        //get admin user id - will also validate that company exists
        $adminUserId = $this->getCompanyAdminIdByName($row['company_name']);
        if (!$adminUserId) {
            $this->helper->printMessage("Company ".$row['company_name'].
            " in b2b_teams.csv does not exist, row skipped", "warning");
            return true;
        }
        $websiteId = $this->stores->getWebsiteId($row['site_code']);
        //create array from members addresses
        $data['members'] = explode(",", $row['members']);
        
        // Create Team
        $newTeam = $this->teamFactory->create();
        $newTeam->setName($row['name']);
        $this->teamRepository->create($newTeam, $this->getCompanyIdByName($row['company_name']));
        //$this->teamRepository->save($newTeam);
        
        //get admins structure
        $parentId = $this->getStructureByEntity($adminUserId, 0)->getDataByKey('structure_id');
        $teamId =($newTeam->getId());
        //put team under admin users
        $teamStruct = $this->addTeamToTree($teamId, $parentId);
        //loop over team members
        foreach ($data['members'] as $companyCustomerEmail) {
            //get user id from email
            try {
                 $userId = $this->customerRepository->get(trim($companyCustomerEmail), $websiteId)->getId();
            } catch (NoSuchEntityException $e) {
                $this->helper->printMessage("User ".$companyCustomerEmail.
                " was not found and will not be added to team ".
                $row['name']." for company ".$row['company_name'], "warning");
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

    /**
     * @param $name
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getCompanyAdminIdByName($name)
    {
        $companySearch = $this->searchCriteriaBuilder
        ->addFilter('company_name', $name, 'eq')->create()->setPageSize(1)->setCurrentPage(1);
        $companyList = $this->companyRepository->getList($companySearch);
        /** @var CompanyInterface $company */
        $company = current($companyList->getItems());

        if (!$company) {
            $this->helper->printMessage("The company ". $name ." requested in b2b_teams.csv does not exist", "warning");
        } else {
            /**@var CompanyInterface $company */
            return $company->getSuperUserId();
        }
    }

    /**
     * @param $name
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getCompanyIdByName($name)
    {
        $companySearch = $this->searchCriteriaBuilder
        ->addFilter('company_name', $name, 'eq')->create()->setPageSize(1)->setCurrentPage(1);
        $companyList = $this->companyRepository->getList($companySearch);
        /** @var CompanyInterface $company */
        $company = current($companyList->getItems());

        if (!$company) {
            $this->helper->printMessage("The company ". $name ." requested in b2b_teams.csv does not exist", "warning");
        } else {
            /**@var CompanyInterface $company */
            return $company->getId();
        }
    }

    /**
     * @param $teamId
     * @param $parentId
     * @return \Magento\Company\Api\Data\StructureInterface
     */
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
