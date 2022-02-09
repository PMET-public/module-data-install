<?php
/**
 * Copyright © Adobe, Inc. All rights reserved.
 * See COPYING.txt for license details.
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
use Magento\Framework\Filesystem\DriverInterface;

/**
 * Consumer for export message.
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

    /** @var DriverInterface */
    protected $driverInterface;

    /**
     * Consumer constructor.
     * @param LoggerInterface $logger
     * @param SerializerInterface $serializer
     * @param EntityManager $entityManager
     * @param Process $process
     * @param File $fileSystem
     * @param DriverInterface $driverInterface
     */
    public function __construct(
        LoggerInterface $logger,
        SerializerInterface $serializer,
        EntityManager $entityManager,
        Process $process,
        File $fileSystem,
        DriverInterface $driverInterface
    ) {
        $this->logger = $logger;
        $this->serializer = $serializer;
        $this->entityManager = $entityManager;
        $this->process = $process;
        $this->fileSystem = $fileSystem;
        $this->driverInterface = $driverInterface;
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
            $this->execute($data);
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
     * @param array $data
     *
     * @return void
     */
    private function execute($data): void
    {
       //loadFiles($fileSource, $load = '', array $fileOrder = [], $reload = 0, $host = null)
        $this->process->loadFiles($data);
        $data['fileorder'] = ['msi_inventory.csv'];
        $data['reload'] = 1;
        $this->process->loadFiles($data);
        //delete source files
        $this->fileSystem->deleteDirectory($data['filesource']);
        //delete the archive that is from a mac compress process
        $this->fileSystem->deleteDirectory($this->driverInterface->getParentDirectory($data['filesource'])."/__MACOSX");
    }
}
