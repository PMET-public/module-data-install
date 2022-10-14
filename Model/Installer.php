<?php
/**
 * Copyright 2022 Adobe, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace MagentoEse\DataInstall\Model;

use Magento\Framework\Model\AbstractExtensibleModel;
use MagentoEse\DataInstall\Api\Data\InstallerInterface;

class Installer extends AbstractExtensibleModel implements InstallerInterface
{
    /**
     * Installer constructor
     */
    //phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore,Magento2.Annotation.MethodArguments.NoCommentBlock
    protected function _construct()
    {
        $this->_init(ResourceModel\Installer::class);
    }

    /**
     * Get id
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
     * Get module name
     */
    public function getModuleName()
    {
        return parent::getData(self::MODULE_NAME);
    }

    /**
     * Set module name
     *
     * @param string $moduleName
     */
    public function setModuleName($moduleName)
    {
        return $this->setData(self::MODULE_NAME, $moduleName);
    }

    /**
     * Is installed
     */
    public function isInstalled()
    {
        return parent::getData(self::IS_INSTALLED);
    }

    /**
     * Set is installed
     *
     * @param string $isInstalled
     */
    public function setIsInstalled($isInstalled)
    {
        return $this->setData(self::IS_INSTALLED, $isInstalled);
    }
}
