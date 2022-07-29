<?php
/**
 * Copyright © Adobe  All rights reserved.
 */
namespace MagentoEse\DataInstall\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

interface InstallerInterface extends ExtensibleDataInterface
{
    /** @var string  */
    public const ID = 'id';

    /** @var string  */
    public const MODULE_NAME = 'module_name';

    /** @var string  */
    public const IS_INSTALLED = 'is_installed';

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
     * Get name/path of data module
     *
     * @return string
     */
    public function getModuleName();

    /**
     * Set name/path of data module
     *
     * @param string $name
     * @return string
     */
    public function setModuleName($name);

    /**
     * Is data pack installed
     *
     * @return string
     */
    public function isInstalled();

    /**
     * Set installed flag
     *
     * @param string $rollNumber
     * @return string string
     */
    public function setIsInstalled($rollNumber);
}
