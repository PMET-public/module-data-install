<?php

namespace MagentoEse\DataInstall\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

interface InstallerInterface extends ExtensibleDataInterface
{
    const ID = 'id';
    const MODULE_NAME = 'module_name';
    const IS_INSTALLED = 'is_installed';

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
    public function getModuleName();

    /**
     * @param $name
     * @return string
     */
    public function setModuleName($name);

    /**
     * @return string
     */
    public function isInstalled();

    /**
     * @param $rollNumber
     * @return string
     */
    public function setIsInstalled($rollNumber);

}