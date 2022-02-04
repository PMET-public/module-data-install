<?php
/**
 * Copyright © Adobe  All rights reserved.
 */

namespace MagentoEse\DataInstall\Api;

interface LoggerRepositoryInterface
{
    /**
     * @param int $id
     * @return \MagentoEse\DataInstall\Api\Data\LoggerInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($id);

    /**
     * @param string $jobId
     * @return \MagentoEse\DataInstall\Api\Data\LoggerInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getByJobId($jobId);

     /**
      * @param string $dataPack
      * @return \MagentoEse\DataInstall\Api\Data\LoggerInterface
      * @throws \Magento\Framework\Exception\NoSuchEntityException
      */
    public function getByDataPack($dataPack);

    /**
     * @param \MagentoEse\DataInstall\Api\Data\LoggerInterface $logger
     * @return \MagentoEse\DataInstall\Api\Data\LoggerInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(\MagentoEse\DataInstall\Api\Data\LoggerInterface $logger);

    /**
     * @param \MagentoEse\DataInstall\Api\Data\StudentInterface $logger
     * @return bool true on success
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function delete(\MagentoEse\DataInstall\Api\Data\LoggerInterface $logger);

    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \MagentoEse\DataInstall\Api\Data\StudentSearchResultInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);
}
