<?php
/**
 * Copyright 2022 Adobe, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace MagentoEse\DataInstall\Api;

interface LoggerRepositoryInterface
{
    /**
     * Get Log By Id
     *
     * @param int $id
     * @return \MagentoEse\DataInstall\Api\Data\LoggerInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($id);

    /**
     * Get Log By Job Id
     *
     * @param string $jobId
     * @return \MagentoEse\DataInstall\Api\Data\LoggerInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getByJobId($jobId);

     /**
      * Get Log by Data Pack Name
      *
      * @param string $dataPack
      * @return \MagentoEse\DataInstall\Api\Data\LoggerInterface
      * @throws \Magento\Framework\Exception\NoSuchEntityException
      */
    public function getByDataPack($dataPack);

    /**
     * Get Installed Data Packs
     *
     * @return \MagentoEse\DataInstall\Api\Data\LoggerInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getInstalledDataPacks();

    /**
     * Save Log entry
     *
     * @param \MagentoEse\DataInstall\Api\Data\LoggerInterface $logger
     * @return \MagentoEse\DataInstall\Api\Data\LoggerInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(\MagentoEse\DataInstall\Api\Data\LoggerInterface $logger);

    /**
     * Delete Log Entry
     *
     * @param \MagentoEse\DataInstall\Api\Data\LoggerInterface $logger
     * @return bool true on success
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function delete(\MagentoEse\DataInstall\Api\Data\LoggerInterface $logger);

    /**
     * Get list of Log Entries
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \MagentoEse\DataInstall\Api\Data\LoggerSearchResultInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);
}
