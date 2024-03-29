<?php
/**
 * Copyright 2022 Adobe, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace MagentoEse\DataInstall\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

interface InstallerJobInterface extends ExtensibleDataInterface
{
    /**
     * Get Id of scheduled job
     *
     * @return string
     */
    public function getJobId();

    /**
     * Set Id of scheduled job
     *
     * @param string $jobId
     */
    public function setJobId(string $jobId);

    /**
     * Set status of job
     *
     * @param mixed $status
     * @return void
     */
    public function setStatus(string $status);

    /**
     * Get status of job
     *
     * @return string
     */
    public function getStatus();

    /**
     * Set job status message
     *
     * @param string $statusMessage
     * @return void
     */
    public function setStatusMessage(string $statusMessage);

    /**
     * Get job status message
     *
     * @return string
     */
    public function getStatusMessage();

    /**
     * Set Data Pack for job
     *
     * @param DataPackInterface $dataPack
     * @return void
     */
    public function setDataPack(DataPackInterface $dataPack);

    /**
     * Get Data Pack for job
     *
     * @return DataPackInterface
     */
    public function getDataPack();

     /**
      * Schedule data pack import
      *
      * @param DataPackInterface $dataPack
      * @return string
      */
    public function scheduleImport(DataPackInterface $dataPack);
}
