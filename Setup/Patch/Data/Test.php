<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace MagentoEse\DataInstall\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use MagentoEse\DataInstall\Model\Process;

class Test implements DataPatchInterface
{
    /** @var Process  */
    protected $process;

    public function __construct(Process $process)
    {
        $this->process = $process;
    }

    public function apply()
    {
//        $this->process->loadFiles(['MagentoEse_DataInstall::fixtures/categories.csv','MagentoEse_DataInstall::fixtures/stores.csv',
//            'MagentoEse_DataInstall::fixtures/product_attributes.csv']);
        $this->process->loadFiles(['MagentoEse_DataInstall::fixtures/categories.csv']);
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }
}
