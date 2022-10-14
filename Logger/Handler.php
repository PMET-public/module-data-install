<?php
/**
 * Copyright 2022 Adobe, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace MagentoEse\DataInstall\Logger;

use Monolog\Logger;
use Magento\Framework\Logger\Handler\Base;

class Handler extends Base
{
    /** @var string */
    protected $loggerType = Logger::INFO;

    /** @var string */
    protected $fileName = '/var/log/data_installer.log';
}
