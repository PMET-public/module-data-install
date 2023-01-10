<?php

namespace MagentoEse\DataInstall\Test\Unit\DataPack;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use MagentoEse\DataInstall\Model\DataPack;
use MagentoEse\DataInstall\Model\Process;
use PHPUnit\Framework\TestCase;

class ModuleWebsiteSetDefault extends TestCase
{
    /**
     * @var DataPack
     */
    protected $dataPack;

    /**
     * @var Process
     */
    protected $process;
    
    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->dataPack = $objectManager->getObject(DataPack::class);
        $this->dataPack->
        setDataPackLocation('/var/www/html/vendor/magentoese/module-data-install/Test/DataPackSample/DataPack');
        $this->dataPack->setFiles(['stores.json']);
        $this->dataPack->setIsDefaultWebsite(1);
        $this->process = $objectManager->getObject(Process::class);
    }

    protected function tearDown(): void
    {
        $this->dataPack = null;
        $this->process = null;
    }

    public function testGetMakeDefault()
    {
        $this->assertEquals(1, $this->dataPack->getIsDefaultWebsite());
    }

    public function testProcess()
    {
        $this->process->loadFiles($this->dataPack);
    }
}
