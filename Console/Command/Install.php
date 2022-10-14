<?php
/**
 * Copyright 2022 Adobe, Inc. All rights reserved.
 * See LICENSE for license details.
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
 * --load=<directory> - can be any other sub directory in the data pack, module or remote url
 * --files=stores.csv,products.csv - comma delimite list of specific files to load
 * -r force reload if already loaded
 * --authtoken=<token> - token needed for remote data retreival
 * -remote - flag if data pack is rempote
 **/

//https://magento.stackexchange.com/questions/155654/console-command-waiting-for-input-from-user

namespace MagentoEse\DataInstall\Console\Command;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use MagentoEse\DataInstall\Model\Process;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\App\State;
use Magento\Framework\App\Area as AppArea;
use MagentoEse\DataInstall\Api\Data\DataPackInterfaceFactory;
use MagentoEse\DataInstall\Api\Data\InstallerJobInterfaceFactory;
use Symfony\Component\Console\Exception\InvalidArgumentException;

class Install extends Command
{
    public const DATAPACK = 'datapack';
    public const LOAD = 'load';
    public const FILES = 'files';
    public const HOST = 'host';
    public const RELOAD_FLAG = 'reload';
    public const AUTH_TOKEN = 'authtoken';
    public const IS_REMOTE = 'remote';

    /** @var ObjectManagerInterface  */
    protected $objectManagerInterface;

    /** @var ModuleStatus */
    protected $moduleStatus;

    /** @var State */
    protected $appState;

    /** @var DataPackInterfaceFactory */
    protected $dataPackInterface;

    /** @var InstallerJobInterfaceFactory */
    protected $installerJobInterface;

    /**
     *
     * @param ObjectManagerInterface $objectManagerInterface
     * @param State $appState
     * @param DataPackInterfaceFactory $dataPackInterface
     * @param InstallerJobInterfaceFactory $installerJobInterface
     * @return void
     * @throws InvalidArgumentException
     */
    public function __construct(
        ObjectManagerInterface $objectManagerInterface,
        State $appState,
        DataPackInterfaceFactory $dataPackInterface,
        InstallerJobInterfaceFactory $installerJobInterface
    ) {
        parent::__construct();
        $this->objectManagerInterface = $objectManagerInterface;
        $this->appState = $appState;
        $this->dataPackInterface = $dataPackInterface;
        $this->installerJobInterface = $installerJobInterface;
    }

    /**
     * Configure cli menu
     */
    protected function configure()
    {
        $options = [
            new InputArgument(
                self::DATAPACK,
                InputArgument::REQUIRED,
                'Module name, absolute path to datapack or remote url'
            ),
            new InputOption(self::LOAD, null, InputOption::VALUE_OPTIONAL, 'Data directory to load', ''),
            new InputOption(
                self::FILES,
                null,
                InputOption::VALUE_OPTIONAL,
                'Comma delimited list of individual files to load'
            ),
            new InputOption(
                self::HOST,
                null,
                InputOption::VALUE_OPTIONAL,
                'Override of host value in stores.csv file'
            ),
            new InputOption(
                self::AUTH_TOKEN,
                null,
                InputOption::VALUE_OPTIONAL,
                'Auth token if needed for remote retrieval'
            ),
            new InputOption(self::RELOAD_FLAG, '-r', InputOption::VALUE_OPTIONAL, 'Force Reload', 0),
            new InputOption(self::IS_REMOTE, '-remote', InputOption::VALUE_OPTIONAL, 'Is data pack remote', false)
        ];

        $this->setName('gxd:datainstall')
            ->setDescription('GXD Data Install')
            ->setDefinition($options);

        parent::configure();
    }
    
    /**
     * Execute
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return $this|int
     * @throws \Exception
     */

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $module = $input->getArgument(self::DATAPACK);
        $load = $input->getOption(self::LOAD);
        $reload = $input->getOption(self::RELOAD_FLAG);
        $files = $input->getOption(self::FILES);
        $host = $input->getOption(self::HOST);
        $isRemote = $input->getOption(self::IS_REMOTE);
        $authToken = $input->getOption(self::AUTH_TOKEN);
        if ($files=='') {
            $fileArray=[];
        } else {
            $fileArray = explode(",", $files);
        }
        if ($reload === null) {
             $reload = 1;
        }

        if ($isRemote === null) {
             $isRemote = true;
        }
        $dataPack = $this->dataPackInterface->create();
        $dataPack->setDataPackLocation($module);
        $dataPack->setFiles($fileArray);
        $dataPack->setLoad($load);
        $dataPack->setReload($reload);
        $dataPack->setHost($host);
        $dataPack->setIsRemote($isRemote);
        $dataPack->setAuthToken($authToken);

        //if data pack is rempote, retrieve it
        if ($dataPack->getIsRemote()) {
            $dataPack->setDataPackLocation($dataPack->getRemoteDataPack(
                $dataPack->getDataPackLocation(),
                $dataPack->getAuthToken()
            ));
            $dataPack->unZipDataPack();
            if (!$dataPack->getDataPackLocation()) {
                throw new Exception(__('Data Pack could not be unzipped. Please check file format'));
            }
        }
        $process = $this->appState->emulateAreaCode(
            AppArea::AREA_ADMINHTML,
            [$this->objectManagerInterface, 'create'],
            [Process::class]
        );
        //$process = $this->objectManagerInterface->create(Process::class);
        if ($process->loadFiles($dataPack)==0) {
            $output->writeln("No files found to load in " . $module.
            " Check the your values of --load or --files if used, or the default set in the datapack");
        }
        return $this;
    }
}
