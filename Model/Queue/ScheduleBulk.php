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
            if (is_array($operationData[0]['fileSource'])) {
                $bulkDescription = 'Data Pack Import - Remote Zip File';
            } else {
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
                    'isDefaultWebsite' => $operation['isDefaultWebsite'],
                    'host' => $operation['host'],
                    'deleteSourceFiles' => $operation['deleteSourceFiles'],
                    //for future use
                    'website'=>'base',
                    'store'=>'store',
                    'additional_parameters' => $operation['additional_parameters'] ?? ''
                ];
                //TODO: add store and website to serialized data
                if (!empty($operation['override_settings'])) {
                    if ($operation['override_settings']) {
                        $serializedData['override_settings'] = true;

                        if (!empty($operation['site_code'])) {
                            $serializedData['site_code'] = $operation['site_code'];
                        }
                        if (!empty($operation['site_name'])) {
                            $serializedData['site_name'] = $operation['site_name'];
                        }
                        if (!empty($operation['store_code'])) {
                            $serializedData['store_code'] = $operation['store_code'];
                        }
                        if (!empty($operation['store_name'])) {
                            $serializedData['store_name'] = $operation['store_name'];
                        }
                        if (!empty($operation['store_view_code'])) {
                            $serializedData['store_view_code'] = $operation['store_view_code'];
                        }
                        if (!empty($operation['store_view_name'])) {
                            $serializedData['store_view_name'] = $operation['store_view_name'];
                        }
                        if (!empty($operation['restrict_products_from_views'])) {
                            $serializedData['restrict_products_from_views'] =
                            $operation['restrict_products_from_views'];
                        }
                    } else {
                        $serializedData['override_settings'] = false;
                    }
                }
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
