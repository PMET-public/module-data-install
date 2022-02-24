<?php
/**
 * Copyright © Adobe, Inc. All rights reserved.
 */
namespace MagentoEse\DataInstall\Model;

use Magento\Framework\Model\AbstractExtensibleModel;
use MagentoEse\DataInstall\Api\Data\LoggerInterface;

class Logger extends AbstractExtensibleModel implements LoggerInterface
{
    //phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    protected function _construct()
    {
        $this->_init(ResourceModel\Logger::class);
    }
    /**
     * @inheritDoc
     */
    public function getId()
    {
        return parent::getData(self::ID);
    }

    /**
     * @inheritDoc
     */
    public function setId($id)
    {
        return $this->setData(self::ID, $id);
    }

    /**
     * @inheritDoc
     */
    public function getJobId()
    {
        return parent::getData(self::JOBID);
    }

    /**
     * @inheritDoc
     */
    public function setJobId($jobId)
    {
        return $this->setData(self::JOBID, $jobId);
    }

    /**
     * @inheritDoc
     */
    public function getMessage()
    {
        return parent::getData(self::MESSAGE);
    }

    /**
     * @inheritDoc
     */
    public function setMessage($moduleName)
    {
        return $this->setData(self::MESSAGE, $moduleName);
    }

    /**
     * @inheritDoc
     */
    public function getLevel()
    {
        return parent::getData(self::LEVEL);
    }

    /**
     * @inheritDoc
     */
    public function setLevel($level)
    {
        return $this->setData(self::LEVEL, $level);
    }

    /**
     * @inheritDoc
     */
    public function getDataPack()
    {
        return parent::getData(self::DATAPACK);
    }

    /**
     * @inheritDoc
     */
    public function setDataPack($dataPack)
    {
        return $this->setData(self::DATAPACK, $dataPack);
    }

    /**
     * @inheritDoc
     */
    public function getAddDate()
    {
        return parent::getData(self::ADDDATE);
    }
}
