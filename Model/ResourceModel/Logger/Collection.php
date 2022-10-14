<?php
/**
 * Copyright 2022 Adobe, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace MagentoEse\DataInstall\Model\ResourceModel\Logger;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use MagentoEse\DataInstall\Model\ResourceModel\Logger;

class Collection extends AbstractCollection
{
    /**
     * Collection constructor.
     */
    protected function __construct()
    {
        $this->_init(
            \MagentoEse\DataInstall\Model\Logger::class,
            Logger::class
        );
    }
}
