<?php
/**
 * Copyright © Adobe  All rights reserved.
 */
namespace MagentoEse\DataInstall\Model;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\NoSuchEntityException;
use MagentoEse\DataInstall\Api\Data\InstallerInterface;
use MagentoEse\DataInstall\Api\Data\InstallerSearchResultInterfaceFactory;
use MagentoEse\DataInstall\Api\InstallerRepositoryInterface;
use MagentoEse\DataInstall\Model\ResourceModel\Installer;
use MagentoEse\DataInstall\Model\ResourceModel\Installer\CollectionFactory;

class InstallerRepository implements InstallerRepositoryInterface
{

    /**
     * @var InstallerFactory
     */
    private $installerFactory;

    /**
     * @var Installer
     */
    private $installerResource;

    /**
     * @var InstallerCollectionFactory
     */
    private $installerCollectionFactory;

    /**
     * @var InstallerSearchResultInterfaceFactory
     */
    private $searchResultFactory;
    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;

    /**
     * InstallerRepository constructor
     *
     * @param InstallerFactory $installerFactory
     * @param Installer $installerResource
     * @param CollectionFactory $installerCollectionFactory
     * @param InstallerSearchResultInterfaceFactory $installerSearchResultInterfaceFactory
     * @param CollectionProcessorInterface $collectionProcessor
     */
    public function __construct(
        InstallerFactory $installerFactory,
        Installer $installerResource,
        CollectionFactory $installerCollectionFactory,
        InstallerSearchResultInterfaceFactory $installerSearchResultInterfaceFactory,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->installerFactory = $installerFactory;
        $this->installerResource = $installerResource;
        $this->installerCollectionFactory = $installerCollectionFactory;
        $this->searchResultFactory = $installerSearchResultInterfaceFactory;
        $this->collectionProcessor = $collectionProcessor;
    }

    /**
     * Get by id
     *
     * @param int $id
     * @return InstallerInterface
     * @throws NoSuchEntityException
     */
    public function getById($id)
    {
        $installer = $this->installerFactory->create();
        $this->installerResource->load($installer, $id);
        if (!$installer->getId()) {
            //throw new NoSuchEntityException(__('Unable to find Module with ID "%1"', $id));
            $IntentialyEmpty = 0;
        }
        return $installer;
    }

    /**
     * Get by module name
     *
     * @param string $moduleName
     * @return InstallerInterface
     * @throws NoSuchEntityException
     */
    public function getByModuleName($moduleName)
    {
        $connection = $this->installerResource->getConnection();
        $tableName = $connection->getTableName('magentoese_data_installer_recurring');
        $select = $connection->select()
        ->from($tableName)
        ->where('module_name = ?', $moduleName);

        $moduleId = $connection->fetchOne($select);
        return $this->getById($moduleId);
    }

    /**
     * Save installer
     *
     * @param InstallerInterface $installer
     * @return InstallerInterface
     * @throws LocalizedException
     */
    public function save(InstallerInterface $installer)
    {
        $this->installerResource->save($installer);
        return $installer;
    }

    /**
     * Delete installer
     *
     * @param InstallerInterface $installer
     * @return bool true on success
     * @throws \CouldNotDeleteException
     */
    public function delete(InstallerInterface $installer)
    {
        try {
            $this->installerResource->delete($installer);
        } catch (\Exception $exception) {
             throw new CouldNotDeleteException(
                 __('Could not delete the entry: %1', $exception->getMessage())
             );
        }

        return true;
    }

    /**
     * Get installer list
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return InstallerSearchResultInterface
     * @throws LocalizedException
     */
    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        $collection = $this->installerCollectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);
        $searchResults = $this->searchResultFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        return $searchResults;
    }
}
