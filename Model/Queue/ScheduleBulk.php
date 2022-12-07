<?php
/**
 * Copyright 2022 Adobe, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace MagentoEse\DataInstall\Model\Queue;

use Magento\Framework\Bulk\BulkManagementInterface;
use Magento\AsynchronousOperations\Api\Data\OperationInterface;
use Magento\AsynchronousOperations\Api\Data\OperationInterfaceFactory;
use Magento\Framework\DataObject\IdentityGeneratorInterface;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\UrlInterface;

class ScheduleBulk
{
    /**
     * @var BulkManagementInterface
     */
    private $bulkManagement;

    /**
     * @var OperationInterfaceFactory
     */
    private $operationFactory;

    /**
     * @var IdentityGeneratorInterface
     */
    private $identityService;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var UserContextInterface
     */
    private $userContext;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    private $jsonHelper;

    /**
     * @var int
     */
    private $bulkSize;

    /**
     * ScheduleBulk constructor
     *
     * @param BulkManagementInterface $bulkManagement
     * @param OperationInterfaceFactory $operationFactory
     * @param IdentityGeneratorInterface $identityService
     * @param UserContextInterface $userContextInterface
     * @param UrlInterface $urlBuilder
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     */
    public function __construct(
        BulkManagementInterface $bulkManagement,
        OperationInterfaceFactory $operationFactory,
        IdentityGeneratorInterface $identityService,
        UserContextInterface $userContextInterface,
        UrlInterface $urlBuilder,
        \Magento\Framework\Json\Helper\Data $jsonHelper
    ) {
        $this->userContext = $userContextInterface;
        $this->bulkManagement = $bulkManagement;
        $this->operationFactory = $operationFactory;
        $this->identityService = $identityService;
        $this->urlBuilder = $urlBuilder;
        $this->jsonHelper = $jsonHelper;
        $this->bulkSize = 100;
    }

    /**
     * Schedule new bulk operation
     *
     * @param array $operationData
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return void
     */
    public function execute($operationData)
    {
        $operationCount = count($operationData);
        if ($operationCount > 0) {
            $bulkUuid = $this->identityService->generateId();
            //phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
            if(is_array($operationData[0]['fileSource'])){
                $bulkDescription = 'Data Pack Import - Remote Zip File';
            }else{
                $bulkDescription = 'Data Pack Import - '.$operationData[0]['fileSource'];
            }
           
            $operations = [];
            foreach ($operationData as $operation) {
                $serializedData = [
                    'meta_information' => 'Data Pack Import',
                    //this data will be displayed in Failed item grid in the column "Meta Info"
                    'filesource'  => $operation['fileSource'],
                    'load' => $operation['load'],
                    'fileorder' => $operation['fileOrder'],
                    'reload' => $operation['reload'],
                    'host' => $operation['host'],
                    //for future use
                    'website'=>'base',
                    'store'=>'store'
                ];
                $data = [
                    'data' => [
                        'bulk_uuid' => $bulkUuid,
                        //topic name must be equal to data specified in the queue configuration files
                        'topic_name' => 'magentoese_datainstall.import',
                        'serialized_data' => $this->jsonHelper->jsonEncode($serializedData),
                        'status' => OperationInterface::STATUS_TYPE_OPEN,
                    ]
                ];

                /** @var OperationInterface $operation */
                $operation = $this->operationFactory->create($data);
                $operations[] = $operation;
            }
            $userId = 1;
            $result = $this->bulkManagement->scheduleBulk($bulkUuid, $operations, $bulkDescription, $userId);
            if (!$result) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Something went wrong while processing the request.')
                );
            }
            return $bulkUuid;
        }
    }
}
