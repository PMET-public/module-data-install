<?php
/**
 * Copyright © Adobe  All rights reserved.
 */
declare(strict_types=1);
// phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
namespace MagentoEse\DataInstall\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Cache\Frontend\Pool;
use Magento\Indexer\Model\IndexerFactory;
use Magento\Indexer\Model\Indexer\Collection;
use MagentoEse\DataInstall\Logger\Logger;
use MagentoEse\DataInstall\Api\Data\LoggerInterfaceFactory;
use MagentoEse\DataInstall\Api\LoggerRepositoryInterface;

class Helper extends AbstractHelper
{
    /** @var array  */
    protected $foreground_colors = [];

    /** @var array  */
    protected $background_colors = [];

    /** @var TypeListInterface */
    protected $cacheTypeList;

    /** @var Pool */
    protected $cacheFrontendPool;

    /** @var IndexerFactory */
    private $indexFactory;

    /** @var Collection */
    private $indexCollection;

    /** @var Logger */
    private $logger;

    /** @var array */
    private $settings;

    /** @var LoggerInterfaceFactory */
    protected $loggerInterface;

    /** @var LoggerRepositoryInterface */
    protected $loggerRepository;

    /**
     * Helper constructor.
     * @param Context $context
     * @param Pool $cacheFrontendPool
     * @param TypeListInterface $cacheTypeList
     * @param IndexerFactory $indexFactory
     * @param Collection $indexCollection
     * @param Logger $logger
     * @param LoggerInterfaceFactory $loggerInterface
     * @param LoggerRepositoryInterface $loggerRepository
     */
    public function __construct(
        Context $context,
        Pool $cacheFrontendPool,
        TypeListInterface $cacheTypeList,
        IndexerFactory $indexFactory,
        Collection $indexCollection,
        Logger $logger,
        LoggerInterfaceFactory $loggerInterface,
        LoggerRepositoryInterface $loggerRepository
    ) {
        parent::__construct($context);
        // Set up shell colors
            $this->foreground_colors['black'] = '0;30';
            $this->foreground_colors['dark_gray'] = '1;30';
            $this->foreground_colors['blue'] = '0;34';
            $this->foreground_colors['light_blue'] = '1;34';
            $this->foreground_colors['green'] = '0;32';
            $this->foreground_colors['light_green'] = '1;32';
            $this->foreground_colors['cyan'] = '0;36';
            $this->foreground_colors['light_cyan'] = '1;36';
            $this->foreground_colors['red'] = '0;31';
            $this->foreground_colors['light_red'] = '1;31';
            $this->foreground_colors['purple'] = '0;35';
            $this->foreground_colors['light_purple'] = '1;35';
            $this->foreground_colors['brown'] = '0;33';
            $this->foreground_colors['yellow'] = '1;33';
            $this->foreground_colors['light_gray'] = '0;37';
            $this->foreground_colors['white'] = '1;37';

            $this->background_colors['black'] = '40';
            $this->background_colors['red'] = '41';
            $this->background_colors['green'] = '42';
            $this->background_colors['yellow'] = '43';
            $this->background_colors['blue'] = '44';
            $this->background_colors['magenta'] = '45';
            $this->background_colors['cyan'] = '46';
            $this->background_colors['light_gray'] = '47';

            $this->cacheFrontendPool = $cacheFrontendPool;
            $this->cacheTypeList = $cacheTypeList;
            $this->indexCollection = $indexCollection;
            $this->indexFactory = $indexFactory;
            $this->logger = $logger;
            $this->loggerInterface = $loggerInterface;
            $this->loggerRepository = $loggerRepository;
    }

    /**
     * @param array $settings
     */
    public function setSettings(array $settings)
    {
        $this->settings = $settings;
    }

    public function reindex()
    {
        $indexes = $this->indexCollection->getAllIds();
        foreach ($indexes as $index) {
            $indexFactory = $this->indexFactory->create()->load($index);
            $this->printMessage($index, "info");
            if ($index!='catalogrule_rule') {
                $indexFactory->reindexRow($index);
            }
        }
    }

    public function flushCache()
    {
        $_types = [
                'config',
                'layout',
                'block_html',
                'collections',
                'reflection',
                'db_ddl',
                'eav',
                'config_integration',
                'config_integration_api',
                'full_page',
                'translate',
                'config_webservice'
                ];

        foreach ($_types as $type) {
            $this->cacheTypeList->cleanType($type);
        }
        foreach ($this->cacheFrontendPool as $cacheFrontend) {
            $cacheFrontend->getBackend()->clean();
        }
    }

    /**
     * @param $string
     * @param null $foreground_color
     */
    public function printMessage($string, $foreground_color = null)
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
        print_r($this->getColoredString($string, $foreground_color, null)."\n");
    }

    /**
     * @param $string
     * @param string $messageType
     */
    public function logMessage($string, $messageType = 'info')
    {
        //print to terminal
        $this->printMessage($string, $messageType);
        //write to log
        $foreground_color = ($foreground_color='header')?'info':$foreground_color;
        $this->logger->$foreground_color($string);
        //write to db
        $this->setDbLog($string, $messageType);
    }

    /**
     * @param $message
     * @param $messageType
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    private function setDbLog($message, $messageType)
    {
        $logger = $this->loggerInterface->create();
        $logger->setjobId($this->settings['job_settings']['jobid']);
        $logger->setMessage($message);
        $logger->setLevel($messageType);
        $logger->setDataPack($this->settings['job_settings']['filesource']);
        $this->loggerRepository->save($logger);
    }

    /**
     * @param $string
     * @param null $foreground_color
     * @param null $background_color
     * @return string
     */
    public function getColoredString($string, $foreground_color = null, $background_color = null)
    {
        $colored_string = "";

        switch ($foreground_color) {
            case "error":
                $background_color = "red";
                break;
            case "warning":
                $foreground_color = "yellow";
                break;
            case "info":
                $foreground_color = "cyan";
                break;
            case "header":
                $foreground_color = "light_cyan";
                break;
        }
        // Check if given foreground color found
        if (isset($this->foreground_colors[$foreground_color])) {
            $colored_string .= "\033[" . $this->foreground_colors[$foreground_color] . "m";
        }
        // Check if given background color found
        if (isset($this->background_colors[$background_color])) {
            $colored_string .= "\033[" . $this->background_colors[$background_color] . "m";
        }

        // Add string and end coloring
        $colored_string .=  $string . "\033[0m";

        return $colored_string;
    }
    /**
     * @return array
     */
    // Returns all foreground color names
    public function getForegroundColors()
    {
        return array_keys($this->foreground_colors);
    }

    /**
     * @return array
     */
    // Returns all background color names
    public function getBackgroundColors()
    {
        return array_keys($this->background_colors);
    }
}
