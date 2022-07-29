<?php
/**
 * Copyright © Adobe  All rights reserved.
 */

namespace MagentoEse\DataInstall\Api;

interface InstallerRepositoryInterface
{
    /**
     * Get Job By Id
     *
     * @param int $id
     * @return \MagentoEse\DataInstall\Api\Data\InstallerInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($id);

     /**
      * Get job by module name
      *
      * @param string $moduleName
      * @return \MagentoEse\DataInstall\Api\Data\InstallerInterface
      * @throws \Magento\Framework\Exception\NoSuchEntityException
      */
    public function getByModuleName($moduleName);

    /**
     * Save job information
     *
     * @param \MagentoEse\DataInstall\Api\Data\InstallerInterface $installer
     * @return \MagentoEse\DataInstall\Api\Data\InstallerInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(\MagentoEse\DataInstall\Api\Data\InstallerInterface $installer);

    /**
     * Delete Job Information
     *
     * @param \MagentoEse\DataInstall\Api\Data\StudentInterface $installer
     * @return bool true on success
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function delete(\MagentoEse\DataInstall\Api\Data\InstallerInterface $installer);

    /**
     * Get List of jobs
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \MagentoEse\DataInstall\Api\Data\StudentSearchResultInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);
}
