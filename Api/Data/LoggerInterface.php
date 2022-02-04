<?php
/**
 * Copyright © Adobe  All rights reserved.
 */
namespace MagentoEse\DataInstall\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

interface LoggerInterface extends ExtensibleDataInterface
{
    /** @var string  */
    const ID = 'id';

    /** @var string  */
    const MESSAGE = 'message';

    /** @var string  */
    const LEVEL = 'level';
    
    /** @var string  */
    const DATAPACK = 'datapack';

    /** @var string  */
    const JOBID = 'job_id';

    /**
     * @return int
     */
    public function getId();

    /**
     * @param $id
     * @return int
     */
    public function setId($id);

    /**
     * @return string
     */
    public function getJobId();

    /**
     * @param $jobId
     * @return string
     */
    public function setJobId($jobId);

    /**
     * @return string
     */
    public function getMessage();

    /**
     * @param $message
     * @return string
     */
    public function setMessage($message);

   /**
     * @return string
     */
    public function getLevel();

    /**
     * @param $level
     * @return string
     */
    public function setLevel($level);

    /**
     * @return string
     */
    public function getDataPack();

    /**
     * @param $dataPack
     * @return string
     */
    public function setDataPack($dataPack);
}