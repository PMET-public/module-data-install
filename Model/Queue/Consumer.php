<?php
/**
 * Copyright 2022 Adobe, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace MagentoEse\DataInstall\Model\Queue;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\EntityManager\EntityManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\TemporaryStateExceptionInterface;
use Magento\Framework\Bulk\OperationInterface;
use MagentoEse\DataInstall\Model\Process;
use Psr\Log\LoggerInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Filesystem\Driver\File;
use MagentoEse\DataInstall\Api\Data\DataPackInterfaceFactory;
use Exception;
use MagentoEse\DataInstall\Api\Data\DataPackInterface;

/**
 * Consumer for export message.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Consumer
{
    /** @var LoggerInterface */
    protected $logger;

    /** @var SerializerInterface */
    protected $serializer;

    /** @var EntityManager */
    protected $entityManager;

    /** @var Process */
    protected $process;

    /** @var File */
    protected $fileSystem;

    /** @var DataPackInterfaceFactory */
    protected $dataPackInterface;

    /**
     *
     * @param LoggerInterface $logger
     * @param SerializerInterface $serializer
     * @param EntityManager $entityManager
     * @param Process $process
     * @param File $fileSystem
     * @param DataPackInterfaceFactory $dataPackInterface
     * @return void
     */
    public function __construct(
        LoggerInterface $logger,
        SerializerInterface $serializer,
        EntityManager $entityManager,
        Process $process,
        File $fileSystem,
        DataPackInterfaceFactory $dataPackInterface
    ) {
        $this->logger = $logger;
        $this->serializer = $serializer;
        $this->entityManager = $entityManager;
        $this->process = $process;
        $this->fileSystem = $fileSystem;
        $this->dataPackInterface = $dataPackInterface;
    }

    /**
     * Process
     *
     * @param OperationInterface $operation
     * @throws \Exception
     *
     * @return void
     */
    public function process(OperationInterface $operation)
    {
        try {
            $serializedData = $operation->getSerializedData();
            $data = $this->serializer->unserialize($serializedData);
            $data['jobid']=$operation->getBulkUuid();
            $this->execute($this->createDataPack($data));
        } catch (\Zend_Db_Adapter_Exception $e) {
            $this->logger->critical($e->getMessage());
            if ($e instanceof \Magento\Framework\DB\Adapter\LockWaitException
                || $e instanceof \Magento\Framework\DB\Adapter\DeadlockException
                || $e instanceof \Magento\Framework\DB\Adapter\ConnectionException
            ) {
                $status = OperationInterface::STATUS_TYPE_RETRIABLY_FAILED;
                $errorCode = $e->getCode();
                $message = $e->getMessage();
            } else {
                $status = OperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED;
                $errorCode = $e->getCode();
                $message = __(
                    'Sorry, something went wrong during data import. Please see log for details.'
                );
            }
        } catch (NoSuchEntityException $e) {
            $this->logger->critical($e->getMessage());
            $status = ($e instanceof TemporaryStateExceptionInterface)
                ? OperationInterface::STATUS_TYPE_RETRIABLY_FAILED
                : OperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED;
            $errorCode = $e->getCode();
            $message = $e->getMessage();
        } catch (LocalizedException $e) {
            $this->logger->critical($e->getMessage());
            $status = OperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED;
            $errorCode = $e->getCode();
            $message = $e->getMessage();
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            $status = OperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED;
            $errorCode = $e->getCode();
            $message = __('Sorry, something went wrong during data import. Please see log for details.');
        }

        $operation->setStatus($status ?? OperationInterface::STATUS_TYPE_COMPLETE)
            ->setErrorCode($errorCode ?? null)
            ->setResultMessage($message ?? null);

        $this->entityManager->save($operation);
    }

    /**
     * Execute
     *
     * @param DataPackInterface $dataPack
     * @return void
     */
    private function execute($dataPack): void
    {
        $this->process->loadFiles($dataPack);
        $dataPack->setFiles(['msi_inventory.csv']);
        $dataPack->setReload(1);
        $this->process->loadFiles($dataPack);
        //delete source files if it's an uploaded package
        if ($dataPack->deleteSourceFiles()) {
            if ($this->fileSystem->isExists($dataPack->getDataPackLocation())) {
                $this->fileSystem->deleteDirectory($dataPack->getDataPackLocation());
                //delete the archive that is from a mac compress process
                $macFile = $this->fileSystem->getParentDirectory($dataPack->getDataPackLocation())."/__MACOSX";
                if ($this->fileSystem->isExists($macFile)) {
                    $this->fileSystem->deleteDirectory($macFile);
                }
            }
        }
    }

    /**
     * Add job data to data pack
     *
     * @param mixed $data
     * @return DataPackInterface
     */
    private function createDataPack($data)
    {
        /** @var DataPackInterface $dataPack */
        $dataPack = $this->dataPackInterface->create();
        $dataPack->setDataPackLocation($data['filesource']);
        if ($data['fileorder']!=null) {
            $dataPack->setFiles($data['fileorder']);
        } else {
            $dataPack->setFiles([]);
        }
        $dataPack->setLoad($data['load']);
        $dataPack->setReload($data['reload']);
        $dataPack->setIsDefaultWebsite($data['isDefaultWebsite']);
        $dataPack->setHost($data['host']);
        $dataPack->setJobId($data['jobid']);
        if (!empty($data['override_settings'])) {
            if ($data['override_settings']) {
                $dataPack->setIsOverride(true);
                if (!empty($data['site_code'])) {
                    $dataPack->setSiteCode($data['site_code']);
                }
                if (!empty($data['site_name'])) {
                    $dataPack->setSiteName($data['site_name']);
                }
                if (!empty($data['store_code'])) {
                    $dataPack->setStoreCode($data['store_code']);
                }
                if (!empty($data['store_name'])) {
                    $dataPack->setStoreName($data['store_name']);
                }
                if (!empty($data['store_view_code'])) {
                    $dataPack->setStoreViewCode($data['store_view_code']);
                }
                if (!empty($data['store_view_name'])) {
                    $dataPack->setStoreViewName($data['store_view_name']);
                }
                if (!empty($data['restrict_products_from_views'])) {
                    $dataPack->setRestrictProductsFromViews($data['restrict_products_from_views']);
                }
            } else {
                $dataPack->setIsOverride(false);
            }
        }
        $dataPack->setDeleteSourceFiles($data['deleteSourceFiles']);
        return $dataPack;
    }
}
