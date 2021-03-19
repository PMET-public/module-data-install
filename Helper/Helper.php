<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace MagentoEse\DataInstall\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Cache\Frontend\Pool;
use Magento\Indexer\Model\IndexerFactory;
use Magento\Indexer\Model\Indexer\Collection;


class Helper extends AbstractHelper
{
    protected $foreground_colors = array();
	protected $background_colors = array();

    /** @var TypeListInterface */
    protected $cacheTypeList;
    
    /** @var Pool */
    protected $cacheFrontendPool;

    /** @var IndexerFactory */
    private $indexFactory;

    /** @var Collection */
    private $indexCollection;

    public function __construct(
        Context $context,
        Pool $cacheFrontendPool,
        TypeListInterface $cacheTypeList,
        IndexerFactory $indexFactory,
        Collection $indexCollection
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
    }

    public function reindex()
    {
        $indexes = $this->indexCollection->getAllIds();
        foreach ($indexes as $index){
            $indexFactory = $this->indexFactory->create()->load($index);
            //$indexFactory->reindexAll($index);
            print_r($index."\n");
            if($index!='catalogrule_rule'){
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

    
    
    public function printMessage($string, $foreground_color = null, $background_color = null){
        print_r($this->getColoredString($string, $foreground_color, $background_color)."\n");
    }

    public function getColoredString($string, $foreground_color = null, $background_color = null) {
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

    // Returns all foreground color names
    public function getForegroundColors() {
        return array_keys($this->foreground_colors);
    }

    // Returns all background color names
    public function getBackgroundColors() {
        return array_keys($this->background_colors);
    }

    public function getModuleName($class){
        //$class = get_class($module);
        $temp = explode("\\",$class);
        return $temp[0]."_".$temp[1];
    }



}