<?php
/**
 * Copyright 2022 Adobe, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace MagentoEse\DataInstall\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

interface LoggerInterface extends ExtensibleDataInterface
{
    /** @var string  */
    public const ID = 'id';

    /** @var string  */
    public const MESSAGE = 'message';

    /** @var string  */
    public const LEVEL = 'level';

    /** @var string  */
    public const DATAPACK = 'datapack';

    /** @var string  */
    public const JOBID = 'job_id';

    /** @var string  */
    public const ADDDATE = 'add_date';

    /**
     * Get Id
     *
     * @return int
     */
    public function getId();

    /**
     * Set Id
     *
     * @param int $id
     * @return int
     */
    public function setId($id);

    /**
     * Get Scheduled Job Id
     *
     * @return string
     */
    public function getJobId();

    /**
     * Set Scheduled Job Id
     *
     * @param string $jobId
     * @return string
     */
    public function setJobId($jobId);

    /**
     * Get Status Message
     *
     * @return string
     */
    public function getMessage();

    /**
     * Set status message
     *
     * @param string $message
     * @return string
     */
    public function setMessage($message);

   /**
    * Get Error Level
    *
    * @return string
    */
    public function getLevel();

    /**
     * Set Error Level
     *
     * @param string $level
     * @return string
     */
    public function setLevel($level);

    /**
     * Get Data Pack
     *
     * @return string
     */
    public function getDataPack();

    /**
     * Set Data Pack
     *
     * @param string $dataPack
     * @return string
     */
    public function setDataPack($dataPack);

     /**
      * Get Add Date
      *
      * @return string
      */
    public function getAddDate();
}
