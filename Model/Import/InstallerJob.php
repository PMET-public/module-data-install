<?php
/**
 * Copyright 2022 Adobe, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace MagentoEse\DataInstall\Model\Import;

use MagentoEse\DataInstall\Api\Data\InstallerJobInterface;
use MagentoEse\DataInstall\Api\Data\DataPackInterface;
use MagentoEse\DataInstall\Model\Queue\ScheduleBulk;

class InstallerJob implements InstallerJobInterface
{

    /** @var ScheduleBulk */
    protected $scheduleBulk;

    /**
     *
     * @param ScheduleBulk $scheduleBulk
     * @return void
     */
    public function __construct(ScheduleBulk $scheduleBulk)
    {
        $this->scheduleBulk = $scheduleBulk;
    }
    
    /**
     * Get Id of scheduled job
     *
     * @return string
     */
    public function getJobId()
    {
        return $this->jobId;
    }

    /**
     * Set Id of scheduled job
     *
     * @param string $jobId
     */
    public function setJobId(string $jobId)
    {
        $this->jobId = $jobId;
    }

    /**
     * Set status of job
     *
     * @param mixed $status
     * @return void
     */
    public function setStatus(string $status)
    {
        $this->status = $status;
    }

    /**
     * Get status of job
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set job status message
     *
     * @param string $statusMessage
     * @return void
     */
    public function setStatusMessage(string $statusMessage)
    {
        $this->statusMessage = $statusMessage;
    }

    /**
     * Get job status message
     *
     * @return string
     */
    public function getStatusMessage()
    {
        return $this->statusMessage;
    }

    /**
     * Set Data Pack for job
     *
     * @param DataPackInterface $dataPack
     * @return void
     */
    public function setDataPack(DataPackInterface $dataPack)
    {
        $this->dataPack = $dataPack;
    }

    /**
     * Get Data Pack for job
     *
     * @return DataPackInterface
     */
    public function getDataPack()
    {
        return $this->dataPack;
    }

     /**
      * Schedule data pack import
      *
      * @param DataPackInterface $dataPack
      * @return string
      */
    public function scheduleImport(DataPackInterface $dataPack)
    {
        $operation = [];
        $operation['fileSource'] = $dataPack->getDataPackLocation();
        $operation['load' ]= $dataPack->getLoad();
        $operation['fileOrder'] = $dataPack->getFiles();
        $operation['reload'] = $dataPack->getReload();
        $operation['host'] = $dataPack->getHost();
        $operation['isDefaultWebsite'] = $dataPack->getIsDefaultWebsite();
        $jobId = $this->scheduleBulk->execute([$operation]);
        return $jobId;
    }
}
