<?php
/**
 * Copyright 2022 Adobe, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace MagentoEse\DataInstall\Model\DataTypes;

use Magento\Company\Api\Data\TeamInterfaceFactory;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\TeamRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Company\Api\Data\StructureInterfaceFactory;
use Magento\Company\Api\Data\StructureInterface;
use Magento\Company\Api\Data\TeamInterface;
use Magento\Company\Model\StructureRepository;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\StateException;
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
     * Teams constructor
     *
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
     * Install
     *
     * @param array $row
     * @param array $settings
     * @return bool
     * @throws NoSuchEntityException
     * @throws LocalizedException
     * @throws StateException
     */
    public function install($row, $settings)
    {
        //company name and team name required
        if (empty($row['company_name'])) {
            $this->helper->logMessage("company_name is required in b2b_teams.csv, row skipped", "warning");
                return true;
        }

        if (empty($row['name'])) {
            $this->helper->logMessage("name is required in b2b_teams.csv, row skipped", "warning");
                return true;
        }

        if (empty($row['site_code'])) {
            $row['site_code'] = $settings['site_code'];
        }
        //get admin user id - will also validate that company exists
        $adminUserId = $this->getCompanyAdminIdByName($row['company_name']);
        if (!$adminUserId) {
            $this->helper->logMessage("Company ".$row['company_name'].
            " in b2b_teams.csv does not exist, row skipped", "warning");
            return true;
        }
        $websiteId = $this->stores->getWebsiteId($row['site_code']);
        //create array from members addresses
        $data['members'] = explode(",", $row['members']);

        //get existing team
        $existingTeam = $this->getExistingTeam($row['name'], $adminUserId);
        // Delete existing team if needed
        if ($existingTeam) {
            foreach ($data['members'] as $companyCustomerEmail) {
                //get user id from email
                try {
                     $userId = $this->customerRepository->get(trim($companyCustomerEmail), $websiteId)->getId();
                } catch (NoSuchEntityException $e) {
                    $this->helper->logMessage("User ".$companyCustomerEmail.
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
            }
            $this->teamRepository->delete($existingTeam);
        }
        $newTeam = $this->teamFactory->create();
        $newTeam->setName($row['name']);
        $this->teamRepository->create($newTeam, $this->getCompanyIdByName($row['company_name']));
        $teamId =($newTeam->getId());

        //loop over team members
        foreach ($data['members'] as $companyCustomerEmail) {
            //get user id from email
            try {
                 $userId = $this->customerRepository->get(trim($companyCustomerEmail), $websiteId)->getId();
            } catch (NoSuchEntityException $e) {
                $this->helper->logMessage("User ".$companyCustomerEmail.
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
            $teamStruct = $this->getTeamStruct($teamId);
            $this->addUserToTeamTree($userId, $teamStruct->getId(), $teamStruct->getPath());
        }
        return true;
    }

    /**
     * Get Existing Team
     *
     * @param string $teamName
     * @param int $adminUserId
     * @return false|TeamInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function getExistingTeam($teamName, $adminUserId)
    {
        $teamSearch = $this->searchCriteriaBuilder
        ->addFilter(TeamInterface::NAME, $teamName, 'eq')->create();
        $teamList = $this->teamRepository->getList($teamSearch);
        //if there is no result return false
        if ($teamList->getTotalCount()==0) {
            return false;
        } elseif ($teamList->getTotalCount()==1) {
        //if there is one result, return it
            return current($teamList->getItems());
        } else {
        //if there is more than one result, filter further
            foreach ($teamList as $team) {
                $structSearch = $this->searchCriteriaBuilder
                ->addFilter(StructureInterface::ENTITY_TYPE, 1, 'eq')
                ->addFilter(StructureInterface::PARENT_ID, $adminUserId, 'eq')
                ->addFilter(StructureInterface::ENTITY_ID, $team->getId(), 'eq')
                ->create()->setPageSize(1)->setCurrentPage(1);
                $structList = $this->teamRepository->getList($structSearch);
                if ($structList->getTotalCount()==1) {
                    /** @var StructureInterface $teamStruct */
                    $teamStruct = current($structList->getItems());
                    return $this->teamRepository->get($teamStruct->getEntityId());
                }
            }
        }
    }

    /**
     * Add User To Team Tree
     *
     * @param int $userId
     * @param int $parentId
     * @param string $path
     * @return mixed
     * @throws CouldNotSaveException
     */
    private function addUserToTeamTree($userId, $parentId, $path)
    {
        $newStruct = $this->structureFactory->create();
        $newStruct->setEntityId($userId);
        $newStruct->setEntityType(0);
        $newStruct->setParentId($parentId);
        $newStruct->setLevel(2);
        $this->structureRepository->save($newStruct);
        $newStruct->setPath($path.'/'.$newStruct->getId());
        $this->structureRepository->save($newStruct);
        return $newStruct;
    }

      /**
       * Get Structure
       *
       * @param int $entityId
       * @param string $entityType
       * @return StructureInterface|mixed
       */
    private function getStructureByEntity($entityId, $entityType)
    {
        $builder = $this->searchCriteriaBuilder;
        $builder->addFilter(StructureInterface::ENTITY_ID, $entityId)
        ->addFilter(StructureInterface::ENTITY_TYPE, $entityType);
        $structures = $this->structureRepository->getList($builder->create())->getItems();
        return reset($structures);
    }

    /**
     * Get Company Admin Id
     *
     * @param string $name
     * @return int
     * @throws LocalizedException
     */
    private function getCompanyAdminIdByName($name)
    {
        $companySearch = $this->searchCriteriaBuilder
        ->addFilter(CompanyInterface::NAME, $name, 'eq')->create()->setPageSize(1)->setCurrentPage(1);
        $companyList = $this->companyRepository->getList($companySearch);
        /** @var CompanyInterface $company */
        $company = current($companyList->getItems());

        if (!$company) {
            $this->helper->logMessage("The company ". $name ." requested in b2b_teams.csv does not exist", "warning");
        } else {
            return $company->getSuperUserId();
        }
    }

    /**
     * Get Company Id
     *
     * @param string $name
     * @return int
     * @throws LocalizedException
     */
    private function getCompanyIdByName($name)
    {
        $companySearch = $this->searchCriteriaBuilder
        ->addFilter('company_name', $name, 'eq')->create()->setPageSize(1)->setCurrentPage(1);
        $companyList = $this->companyRepository->getList($companySearch);
        /** @var CompanyInterface $company */
        $company = current($companyList->getItems());

        if (!$company) {
            $this->helper->logMessage("The company ". $name ." requested in b2b_teams.csv does not exist", "warning");
        } else {
            /**@var CompanyInterface $company */
            return $company->getId();
        }
    }

    /**
     * Get Team Structure
     *
     * @param int $teamId
     * @return StructureInterface
     */
    private function getTeamStruct($teamId)
    {
        $teamStructSearch = $this->searchCriteriaBuilder
        ->addFilter(StructureInterface::ENTITY_ID, $teamId, 'eq')
        ->addFilter(StructureInterface::ENTITY_TYPE, 1, 'eq')->create()->setPageSize(1)->setCurrentPage(1);
        $teamStructList = $this->structureRepository->getList($teamStructSearch);
        /** @var CompanyInterface $company */
        return current($teamStructList->getItems());
    }

    /**
     * Add Team To Tree
     *
     * @param int $teamId
     * @param int $parentId
     * @return StructureInterface
     */
    private function addTeamToTree($teamId, $parentId)
    {
        //path is structure_id of admin user / structure_id of team)
        $newStruct = $this->structureFactory->create();
        $newStruct->setEntityId($teamId);
        $newStruct->setEntityType(1);
        $newStruct->setParentId($parentId);
        $newStruct->setLevel(1);
        $this->structureRepository->save($newStruct);
        $newStruct->setPath($parentId.'/'.$newStruct->getId());
        $this->structureRepository->save($newStruct);
        return $newStruct;
    }
}
