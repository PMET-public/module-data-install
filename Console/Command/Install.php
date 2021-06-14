<?php
/**
 * Copyright © Adobe. All rights reserved.
 */

/** Usage
 * With installed module
 * bin/magento gxd:datainstall MagentoEse_Base
 *
 * relative to Magneto root
 * bin/magento gxd:datainstall vendor/story-store/grocery
 *
 * Options
 *
 * data=fixtures - can be any other sub directory in the data pack
 * files=stores.csv,products.csv - comma delimite list of specific files to load
 * -r force reload if already loaded
 **/

//https://magento.stackexchange.com/questions/155654/console-command-waiting-for-input-from-user

namespace MagentoEse\DataInstall\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use MagentoEse\DataInstall\Model\Process;
use Magento\Framework\ObjectManagerInterface;

class Install extends Command
{
    const MODULE = 'module';
    const FIXTURES = 'data';
    const FILES = 'files';
    const RELOAD_FLAG = 'reload';
    
    /** @var ObjectManagerInterface  */
    protected $objectManagerInterface;

    /** @var ModuleStatus */
    protected $moduleStatus;

    /**
     * Install constructor.
     * @param Process $process
     */
    public function __construct(ObjectManagerInterface $objectManagerInterface)
    {
        $this->objectManagerInterface = $objectManagerInterface;
        parent::__construct();
    }

    protected function configure()
    {
        $options = [
            new InputArgument(
                self::MODULE,
                InputArgument::REQUIRED,
                'Module'
            ),
            new InputOption(self::FIXTURES, null, InputOption::VALUE_OPTIONAL, 'Change default data directory', 'data'),
            new InputOption(self::FILES, null, InputOption::VALUE_OPTIONAL, 'Comma delimited list of individual files to load'),
            new InputOption(self::RELOAD_FLAG, '-r', InputOption::VALUE_OPTIONAL, 'Force Reload', 0)
        ];

        $this->setName('gxd:datainstall')
            ->setDescription('GXD Data Install')
            ->setDefinition($options);

        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return $this|int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $module = $input->getArgument(self::MODULE);
        $fixtures = $input->getOption(self::FIXTURES);
        $reload = $input->getOption(self::RELOAD_FLAG);
        $files = $input->getOption(self::FILES);
        if ($files=='') {
            $fileArray=[];
        } else {
            $fileArray = explode(",", $files);
        }
        $process = $this->objectManagerInterface->create(Process::class);
        if ($process->loadFiles($module, $fixtures, $fileArray, $reload)==0) {
            $output->writeln("No files found to load in " . $module);
        }
        return $this;
    }
}
