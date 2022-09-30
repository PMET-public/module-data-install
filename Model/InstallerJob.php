<?php
namespace MagentoEse\DataInstall\Model;

use MagentoEse\DataInstall\Api\Data\DataPackInterface;
use MagentoEse\DataInstall\Api\Data\InstallerJobInterface;

class InstallerJob implements InstallerJobInterface
{
    /** @var string */
    protected $jobId;

    /** @var string */
    protected $status;

    /** @var string */
    protected $statusMessage;

    /** @var DataPackInterface */
    protected $dataPack;

    /**
     * Get scheduled job id
     *
     * @return string
     */
    public function getJobId()
    {
        return $this->jobId;
    }

    /**
     * set scheduled job id
     * @param string $jobId
     * @return void
     */
    public function setJobId(string $jobId)
    {
        $this->jobId = $jobId;
    }

    /**
     * Set scheduled job Status
     *
     * @param string $status
     * @return void
     */
    public function setStatus(string $status)
    {
        $this->status = $status;
    }

    /**
     * Get scheduled job status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     *
     * Set scheduled job status
     *
     * @param string $statusMessage
     * @return void
     */
    public function setStatusMessage(string $statusMessage)
    {
        $this->statusMessage = $statusMessage;
    }

    /**
     * Get scheduled job status
     *
     * @return string
     */
    public function getStatusMessage()
    {
        return $this->statusMessage;
    }

    /**
     * Set datapack for job
     *
     * @param DataPackInterface $dataPack
     * @return void
     */
    public function setDataPack(DataPackInterface $dataPack)
    {
        $this->dataPack = $dataPack;
    }

    /**
     * Get datapack for job
     *
     * @return DataPackInterface
     */
    public function getDataPack()
    {
        return $this->dataPack;
    }

    /**
     *
     * @param DataPackInterface $dataPack
     * @return string
     */
    public function scheduleJob(DataPackInterface $dataPack)
    {
    }
}
