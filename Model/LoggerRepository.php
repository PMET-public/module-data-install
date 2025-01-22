<?php
/**
 * Copyright 2022 Adobe, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace MagentoEse\DataInstall\Model;

use DomainException;
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
use Zend_Db_Select_Exception;

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
     * Get installed data packs
     *
     * @return LoggerInterface
     * @throws DomainException
     * @throws Zend_Db_Select_Exception
     */
    public function getInstalledDataPacks()
    {
        $connection = $this->LoggerResource->getConnection();
        $tableName = $connection->getTableName(self::LOGGER_TABLE);
        // $select = $connection->select()
        $queryString = "SELECT log.id, log.datapack, log.message, log.level, log.add_date, log.job_id, 
                        log.datapack as 'datapack_name', log.message as 'metadata'
                        FROM magentoese_data_installer_log log LEFT JOIN magentoese_data_installer_log meta_log 
                        ON log.datapack = meta_log.datapack AND meta_log.level = 'metadata' 
                        LEFT JOIN magentoese_data_installer_log start_log ON log.datapack = start_log.datapack 
                        AND start_log.message = 'Start Data Installer Process' WHERE log.id = 
                        (SELECT id FROM magentoese_data_installer_log WHERE datapack = log.datapack 
                        AND level = 'metadata' ORDER BY add_date DESC LIMIT 1) OR (log.id = 
                        (SELECT id FROM magentoese_data_installer_log WHERE datapack = log.datapack
                        AND message = 'Start Data Installer Process' ORDER BY add_date DESC LIMIT 1) AND NOT EXISTS 
                        (SELECT 1 FROM magentoese_data_installer_log WHERE datapack = log.datapack 
                        AND level = 'metadata')) GROUP BY log.datapack ORDER BY log.id asc";
        $select = $connection->query($queryString);
        $logs =  $select->fetchAll();

        // Process the logs to extract the required data
        foreach ($logs as &$log) {
            if ($log['level'] === 'metadata') {
                $metaData = json_decode($log['message'], true);
                if (json_last_error() === JSON_ERROR_NONE && isset($metaData['datapack_name'])) {
                    $log['datapack_name'] = $metaData['datapack_name'];
                } else {
                    $log['datapack_name'] = basename($log['datapack']);
                }
            } else {
                $log['datapack_name'] = basename($log['datapack']);
                $log['metadata'] = null;
            }
        }
    
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
