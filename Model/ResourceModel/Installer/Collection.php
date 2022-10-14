<?php
/**
 * Copyright 2022 Adobe, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace MagentoEse\DataInstall\Model\ResourceModel\Installer;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use MagentoEse\DataInstall\Model\ResourceModel\Installer;

class Collection extends AbstractCollection
{
    /**
     * Define resource model
     *
     * @return void
     */
    protected function __construct()
    {
        $this->_init(
            \MagentoEse\DataInstall\Model\Installer::class,
            Installer::class
        );
    }
}
