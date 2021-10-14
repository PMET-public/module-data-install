<?php
/**
 * Copyright © Adobe, Inc. All rights reserved.
 */
namespace MagentoEse\DataInstall\Model;

use Magento\Framework\Model\AbstractExtensibleModel;
use MagentoEse\DataInstall\Api\Data\InstallerInterface;

class Installer extends AbstractExtensibleModel implements InstallerInterface
{
    //phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    protected function _construct()
    {
        $this->_init(ResourceModel\Installer::class);
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
    public function getModuleName()
    {
        return parent::getData(self::MODULE_NAME);
    }

    /**
     * @inheritDoc
     */
    public function setModuleName($moduleName)
    {
        return $this->setData(self::MODULE_NAME, $moduleName);
    }

    /**
     * @inheritDoc
     */
    public function isInstalled()
    {
        return parent::getData(self::IS_INSTALLED);
    }

    /**
     * @inheritDoc
     */
    public function setIsInstalled($isInstalled)
    {
        return $this->setData(self::IS_INSTALLED, $isInstalled);
    }
}
