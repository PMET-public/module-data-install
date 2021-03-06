<?php

namespace MagentoEse\DataInstall\Api;


interface InstallerRepositoryInterface
{
    /**
     * @param int $id
     * @return \MagentoEse\DataInstall\Api\Data\InstallerInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($id);

     /**
     * @param string $moduleName
     * @return \MagentoEse\DataInstall\Api\Data\InstallerInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getByModuleName($moduleName);

    /**
     * @param \MagentoEse\DataInstall\Api\Data\InstallerInterface $installer
     * @return \MagentoEse\DataInstall\Api\Data\InstallerInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(\MagentoEse\DataInstall\Api\Data\InstallerInterface $installer);

    /**
     * @param \MagentoEse\DataInstall\Api\Data\StudentInterface $installer
     * @return bool true on success
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function delete(\MagentoEse\DataInstall\Api\Data\InstallerInterface $installer);

    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \MagentoEse\DataInstall\Api\Data\StudentSearchResultInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);
}
