<?php
/**
 * Copyright © Adobe  All rights reserved.
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
