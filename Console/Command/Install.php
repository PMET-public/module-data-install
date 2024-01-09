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
 * --files=stores.csv,products.csv - comma delimited list of specific files to load
 * -r force reload if already loaded
 * --authtoken=<token> - token needed for remote data retrieval
 * -remote - flag if data pack is remote
 * --make-default-website - Set this site as default regardless of data pack settings
 * -o - Override site/store settings
 * --site-code - Site Code that overrides Data Pack data
 * --site-name - Site Name that overrides Data Pack data
 * --store-code - Store Code that overrides Data Pack data
 * --store-name - Store Name that overrides Data Pack data
 * --store-view-code - Store View Code that overrides Data Pack data
 * --store-view-name - Store View Name that overrides Data Pack data
 * --restrict-products-from-views - Restrict incoming products from other store views
 * --additional-parameters - Json encoded object of Additional parameters to pass to the data pack
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
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Magento\Framework\Console\Cli;

class Install extends Command
{
    public const DATAPACK = 'datapack';
    public const LOAD = 'load';
    public const FILES = 'files';
    public const HOST = 'host';
    public const RELOAD_FLAG = 'reload';
    public const AUTH_TOKEN = 'authtoken';
    public const IS_REMOTE = 'remote';
    public const MAKE_DEFAULT_SITE = 'make-default-website';
    public const OVERRIDE_FLAG = 'override-settings';
    public const SITE_CODE = 'site-code';
    public const SITE_NAME = 'site-name';
    public const STORE_CODE = 'store-code';
    public const STORE_NAME = 'store-name';
    public const STORE_VIEW_CODE = 'store-view-code';
    public const STORE_VIEW_NAME = 'store-view-name';
    public const RESTRICT_PRODUCTS_FROM_VIEWS = 'restrict-products-from-views';
    public const ADDITIONAL_PARAMETERS = 'additional-parameters';

    /** @var ObjectManagerInterface  */
    protected $objectManagerInterface;

    /** @var ModuleStatus */
    protected $moduleStatus;

    /** @var State */
    protected $appState;

    /** @var DataPackInterfaceFactory */
    protected $dataPackInterface;

    /**
     *
     * @param ObjectManagerInterface $objectManagerInterface
     * @param State $appState
     * @param DataPackInterfaceFactory $dataPackInterface
     * @return void
     * @throws InvalidArgumentException
     */
    public function __construct(
        ObjectManagerInterface $objectManagerInterface,
        State $appState,
        DataPackInterfaceFactory $dataPackInterface
    ) {
        parent::__construct();
        $this->objectManagerInterface = $objectManagerInterface;
        $this->appState = $appState;
        $this->dataPackInterface = $dataPackInterface;
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
            new InputOption(
                self::LOAD,
                null,
                InputOption::VALUE_OPTIONAL,
                'Data directory to load',
                ''
            ),
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
            new InputOption(
                self::RELOAD_FLAG,
                '-r',
                InputOption::VALUE_OPTIONAL,
                'Force Reload',
                0
            ),
            new InputOption(
                self::OVERRIDE_FLAG,
                '-o',
                InputOption::VALUE_OPTIONAL,
                'Override site/store settings',
                0
            ),
            new InputOption(
                self::MAKE_DEFAULT_SITE,
                '-make-default-website',
                InputOption::VALUE_OPTIONAL,
                'Set this site as default regardless of data pack settings',
                0
            ),
            new InputOption(
                self::IS_REMOTE,
                '-remote',
                InputOption::VALUE_OPTIONAL,
                'Is data pack remote',
                false
            ),
            new InputOption(
                self::SITE_CODE,
                '-site-code',
                InputOption::VALUE_OPTIONAL,
                'Site Code that overrides Data Pack data',
                ''
            ),
            new InputOption(
                self::SITE_NAME,
                '-site-name',
                InputOption::VALUE_OPTIONAL,
                'Site Name that overrides Data Pack data',
                ''
            ),
            new InputOption(
                self::STORE_CODE,
                '-store-code',
                InputOption::VALUE_OPTIONAL,
                'Store Code that overrides Data Pack data',
                ''
            ),
            new InputOption(
                self::STORE_NAME,
                '-store-name',
                InputOption::VALUE_OPTIONAL,
                'Store Name that overrides Data Pack data',
                ''
            ),
            new InputOption(
                self::STORE_VIEW_CODE,
                '-store-view-code',
                InputOption::VALUE_OPTIONAL,
                'Store View Code that overrides Data Pack data',
                ''
            ),
            new InputOption(
                self::STORE_VIEW_NAME,
                '-store-view-name',
                InputOption::VALUE_OPTIONAL,
                'Store View Name that overrides Data Pack data',
                ''
            ),
            new InputOption(
                self::RESTRICT_PRODUCTS_FROM_VIEWS,
                '-restrict-products-from-views',
                InputOption::VALUE_OPTIONAL,
                'Restrict incoming products from other store views',
                false
            ),
            new InputOption(
                self::ADDITIONAL_PARAMETERS,
                '-additional-parameters',
                InputOption::VALUE_OPTIONAL,
                'Json encoded object of Additional parameters',
                ''
            )

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
        $override = $input->getOption(self::OVERRIDE_FLAG);
        $restrictProducts = $input->getOption(self::RESTRICT_PRODUCTS_FROM_VIEWS);
        $files = $input->getOption(self::FILES);
        $host = $input->getOption(self::HOST);
        $isRemote = $input->getOption(self::IS_REMOTE);
        $authToken = $input->getOption(self::AUTH_TOKEN);
        $makeDefaultSite = $input->getOption(self::MAKE_DEFAULT_SITE);
        if ($files=='') {
            $fileArray=[];
        } else {
            $fileArray = explode(",", $files);
        }
        if ($reload === null) {
             $reload = 1;
        }

        if ($override === null) {
            $override = 1;
        }

        if ($restrictProducts === null) {
            $restrictProducts = 1;
        }

        if ($makeDefaultSite === null) {
            $makeDefaultSite = 1;
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
        $dataPack->setIsDefaultWebsite($makeDefaultSite);
        $dataPack->setIsOverride($override);
        $dataPack->setSiteCode($input->getOption(self::SITE_CODE));
        $dataPack->setSiteName($input->getOption(self::SITE_NAME));
        $dataPack->setStoreCode($input->getOption(self::STORE_CODE));
        $dataPack->setStoreName($input->getOption(self::STORE_NAME));
        $dataPack->setStoreViewCode($input->getOption(self::STORE_VIEW_CODE));
        $dataPack->setStoreViewName($input->getOption(self::STORE_VIEW_NAME));
        $dataPack->setRestrictProductsFromViews($restrictProducts);
        $dataPack->setAdditionalParameters($input->getOption(self::ADDITIONAL_PARAMETERS));

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
        return Cli::RETURN_SUCCESS;
    }
}
