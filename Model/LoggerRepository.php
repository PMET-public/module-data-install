<?php
/**
 * Copyright © Adobe  All rights reserved.
 */
namespace MagentoEse\DataInstall\Model;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use MagentoEse\DataInstall\Api\Data\LoggerInterface;
use MagentoEse\DataInstall\Api\Data\LoggerSearchResultInterfaceFactory;
use MagentoEse\DataInstall\Api\Data\LoggerSearchResultInterface;
use MagentoEse\DataInstall\Api\LoggerRepositoryInterface;
use MagentoEse\DataInstall\Model\ResourceModel\Logger;
use MagentoEse\DataInstall\Model\ResourceModel\Logger\CollectionFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\LocalizedException;

class LoggerRepository implements LoggerRepositoryInterface
{

    private const LOGGER_TABLE = 'magentoese_data_installer_log';

    /**
     * @var LoggerFactory
     */
    private $LoggerFactory;

    /**
     * @var Logger
     */
    private $LoggerResource;

    /**
     * @var LoggerCollectionFactory
     */
    private $LoggerCollectionFactory;

    /**
     * @var LoggerSearchResultInterfaceFactory
     */
    private $searchResultFactory;
    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;

    /**
     * LoggerRepository constructor
     *
     * @param LoggerFactory $LoggerFactory
     * @param Logger $LoggerResource
     * @param CollectionFactory $LoggerCollectionFactory
     * @param LoggerSearchResultInterfaceFactory $LoggerSearchResultInterfaceFactory
     * @param CollectionProcessorInterface $collectionProcessor
     */
    public function __construct(
        LoggerFactory $LoggerFactory,
        Logger $LoggerResource,
        CollectionFactory $LoggerCollectionFactory,
        LoggerSearchResultInterfaceFactory $LoggerSearchResultInterfaceFactory,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->LoggerFactory = $LoggerFactory;
        $this->LoggerResource = $LoggerResource;
        $this->LoggerCollectionFactory = $LoggerCollectionFactory;
        $this->searchResultFactory = $LoggerSearchResultInterfaceFactory;
        $this->collectionProcessor = $collectionProcessor;
    }

    /**
     * Get log by id
     *
     * @param int $id
     * @return LoggerInterface
     * @throws NoSuchEntityException
     */
    public function getById($id)
    {
        $Logger = $this->LoggerFactory->create();
        $this->LoggerResource->load($Logger, $id);
        if (!$Logger->getId()) {
            //throw new NoSuchEntityException(__('Unable to find Module with ID "%1"', $id));
            $IntentialyEmpty = 0;
        }
        return $Logger;
    }

    /**
     * Get log by data pack
     *
     * @param string $dataPack
     * @return LoggerInterface
     * @throws NoSuchEntityException
     */
    public function getByDataPack($dataPack)
    {
        $connection = $this->LoggerResource->getConnection();
        $tableName = $connection->getTableName(self::LOGGER_TABLE);
        $select = $connection->select()
        ->from($tableName)
        ->where('instr(datapack,?)', $dataPack)
        ->order('id', 'asc');
        $logs = $connection->fetchAll($select);
        return $logs;
    }

    /**
     * Get log by job id
     *
     * @param string $jobId
     * @return LoggerInterface
     * @throws NoSuchEntityException
     */
    public function getByJobId($jobId)
    {
        $connection = $this->LoggerResource->getConnection();
        $tableName = $connection->getTableName(self::LOGGER_TABLE);
        $select = $connection->select()
        ->from($tableName)
        ->where('job_id = ?', $jobId)
        ->order('id', 'asc');
        $logs = $connection->fetchAll($select);
        return $logs;
    }

    /**
     * Save log entry
     *
     * @param LoggerInterface $Logger
     * @return LoggerInterface
     * @throws LocalizedException
     */
    public function save(LoggerInterface $Logger)
    {
        $this->LoggerResource->save($Logger);
        return $Logger;
    }

    /**
     * Delete log entry
     *
     * @param LoggerInterface $Logger
     * @return bool true on success
     * @throws CouldNotDeleteException
     */
    public function delete(LoggerInterface $Logger)
    {
        try {
            $this->LoggerResource->delete($Logger);
        } catch (\Exception $exception) {
             throw new CouldNotDeleteException(
                 __('Could not delete the entry: %1', $exception->getMessage())
             );
        }

        return true;
    }

    /**
     * Get list of log records
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return LoggerSearchResultInterface
     * @throws LocalizedException
     */
    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        $collection = $this->LoggerCollectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);
        $searchResults = $this->searchResultFactory->create();

        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());

        return $searchResults;
    }
}
