<?php
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
    }

    /**
     * Set Id of scheduled job
     *
     * @param string $jobId
     */
    public function setJobId(string $jobId)
    {
    }

    /**
     * Set status of job
     *
     * @param mixed $status
     * @return void
     */
    public function setStatus(string $status)
    {
    }

    /**
     * Get status of job
     *
     * @return string
     */
    public function getStatus()
    {
    }

    /**
     * Set job status message
     *
     * @param string $statusMessage
     * @return void
     */
    public function setStatusMessage(string $statusMessage)
    {
    }

    /**
     * Get job status message
     *
     * @return string
     */
    public function getStatusMessage()
    {
    }

    /**
     * Set Data Pack for job
     * @param DataPackInterface $dataPackInterface
     * @return void
     */
    public function setDataPack(DataPackInterface $dataPack)
    {
    }

    /**
     * Get Data Pack for job
     *
     * @return DataPackInterface $dataPackInterface
     */
    public function getDataPack()
    {
    }

     /**
     * Schedule data pack import
     *
     * @param DataPackInterface $dataPackInterface
     * @return string
     */
    public function scheduleImport(DataPackInterface $dataPack)
    {
        $operation = [];
        $operation['fileSource'] = $dataPack->getDataPackLocation();
        //$operation['packFile']=$directoryName;
        $operation['load' ]= $dataPack->getLoad();
        $operation['fileOrder'] = $dataPack->getFiles();
        $operation['reload'] = $dataPack->getReload();
        $operation['host'] = $dataPack->getHost();
        $jobId = $this->scheduleBulk->execute([$operation]);
        return $jobId;
    }
}
