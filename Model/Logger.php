<?php
/**
 * Copyright 2022 Adobe, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace MagentoEse\DataInstall\Model;

use Magento\Framework\Model\AbstractExtensibleModel;
use MagentoEse\DataInstall\Api\Data\LoggerInterface;

class Logger extends AbstractExtensibleModel implements LoggerInterface
{
    /**
     * Logger constructor
     */
    //phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore,Magento2.Annotation.MethodArguments.NoCommentBlock
    protected function _construct()
    {
        $this->_init(ResourceModel\Logger::class);
    }
    /**
     * Get Id
     */
    public function getId()
    {
        return parent::getData(self::ID);
    }

    /**
     * Set id
     *
     * @param int $id
     */
    public function setId($id)
    {
        return $this->setData(self::ID, $id);
    }

    /**
     * Get Job Id
     */
    public function getJobId()
    {
        return parent::getData(self::JOBID);
    }

    /**
     * Set job id
     *
     * @param int $jobId
     */
    public function setJobId($jobId)
    {
        return $this->setData(self::JOBID, $jobId);
    }

    /**
     * Get messate
     */
    public function getMessage()
    {
        return parent::getData(self::MESSAGE);
    }

    /**
     * Set message
     *
     * @param string $moduleName
     */
    public function setMessage($moduleName)
    {
        return $this->setData(self::MESSAGE, $moduleName);
    }

    /**
     * Get message level
     */
    public function getLevel()
    {
        return parent::getData(self::LEVEL);
    }

    /**
     * Set message level
     *
     * @param string $level
     */
    public function setLevel($level)
    {
        return $this->setData(self::LEVEL, $level);
    }

    /**
     * Get data pack
     */
    public function getDataPack()
    {
        return parent::getData(self::DATAPACK);
    }

    /**
     * Set data pack
     *
     * @param string $dataPack
     */

    public function setDataPack($dataPack)
    {
        return $this->setData(self::DATAPACK, $dataPack);
    }

    /**
     * Get Add Date
     */
    public function getAddDate()
    {
        return parent::getData(self::ADDDATE);
    }
}
