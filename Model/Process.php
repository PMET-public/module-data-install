<?php

/**
 * Copyright © Magento. All rights reserved.
 */
namespace MagentoEse\DataInstall\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\File\Csv;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Setup\SampleData\Context as SampleDataContext;
use Magento\Framework\Setup\SampleData\FixtureManager;

class Process
{
    const FILE_ORDER = ['stores.csv','config_default.json','config_vertical.json','config.json','config.csv',
        'customer_groups.csv','customer_attributes.csv','customers.csv','product_attributes.csv','categories.csv',
        'products.csv','blocks.csv','dynamic_blocks.csv','pages.csv','reviews.csv'];

    protected $redo=[];

    protected $settings = ['site_code'=>'base', 'store_code'=>'main_website_store','store_view_code'=>'default',
        'root_category' => 'Default Category', 'root_category_id' => '2'];

    /** @var FixtureManager  */
    protected $fixtureManager;

    /** @var Csv  */
    protected $csvReader;

    /** @var Stores  */
    protected $storeInstall;

    /** @var ProductAttributes  */
    protected $productAttributesInstall;

    /** @var Categories  */
    protected $categoryInstall;

    /** @var Products  */
    protected $productInstall;

    /** @var DirectoryList  */
    protected $directoryList;

    /** @var Pages  */
    protected $pageInstall;

    /** @var Blocks  */
    protected $blockInstall;

    /** @var DynamicBlocks  */
    protected $dynamicBlockInstall;

    /** @var Configuration  */
    protected $configurationInstall;

    /** @var CustomerGroups  */
    protected $customerGroupInstall;

    /** @var CustomerAttributes  */
    protected $customerAttributeInstall;

    /** @var Customers  */
    protected $customerInstall;

    /** @var Reviews  */
    protected $reviewsInstall;

    /**
     * Process constructor.
     * @param SampleDataContext $sampleDataContext
     * @param Stores $stores
     * @param ProductAttributes $productAttributes
     * @param Categories $categories
     * @param Products $products
     * @param DirectoryList $directoryList
     * @param Pages $pages
     * @param Blocks $blocks
     * @param DynamicBlocks $dynamicBlocks
     * @param Configuration $configuration
     * @param CustomerGroups $customerGroups
     * @param CustomerAttributes $customerAttributes
     * @param Customers $customers
     * @param Reviews $reviews
     */
    public function __construct(
        SampleDataContext $sampleDataContext,
        Stores $stores,
        ProductAttributes $productAttributes,
        Categories $categories,
        Products $products,
        DirectoryList $directoryList,
        Pages $pages,
        Blocks $blocks,
        DynamicBlocks $dynamicBlocks,
        Configuration $configuration,
        CustomerGroups $customerGroups,
        CustomerAttributes $customerAttributes,
        Customers $customers,
        Reviews $reviews
    ) {
        $this->fixtureManager = $sampleDataContext->getFixtureManager();
        $this->csvReader = $sampleDataContext->getCsvReader();
        $this->storeInstall = $stores;
        $this->productAttributesInstall = $productAttributes;
        $this->categoryInstall = $categories;
        $this->productInstall = $products;
        $this->directoryList = $directoryList;
        $this->pageInstall = $pages;
        $this->blockInstall = $blocks;
        $this->dynamicBlockInstall = $dynamicBlocks;
        $this->configurationInstall = $configuration;
        $this->customerGroupInstall = $customerGroups;
        $this->customerAttributeInstall = $customerAttributes;
        $this->customerInstall = $customers;
        $this->reviewsInstall = $reviews;
    }

    /**
     * @param $moduleName
     * @param string $fixtureDirectory
     * @param array|string[] $fileOrder
     * @throws LocalizedException
     */
    public function loadFiles($moduleName, $fixtureDirectory = "fixtures", array $fileOrder = self::FILE_ORDER)
    {
        //set module configuration
        $this->settings = $this->getConfiguration($moduleName, $fixtureDirectory);

        foreach ($fileOrder as $nextFile) {
            $fileName = $this->fixtureManager->getFixture($moduleName . "::" . $fixtureDirectory . "/" . $nextFile);
            if (basename($fileName)==$nextFile && file_exists($fileName)) {
                if (pathinfo($fileName, PATHINFO_EXTENSION) == 'json') {
                    $fileContent = file_get_contents($fileName);
                } else {
                    $rows = $this->csvReader->getData($fileName);
                    $header = array_shift($rows);
                    //Remove hidden character Excel adds to the first cell of a document
                    $header = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $header);
                }

                //determine path to module code for image import
                $modulePath = str_replace("/" . $fixtureDirectory . "/" . basename($fileName), "", $fileName);

                switch (basename($fileName)) {
                    case "stores.csv":
                        print_r("loading Stores\n");
                        $this->processRows($rows, $header, $this->storeInstall);
                        break;

                    case "customers.csv":
                        print_r("loading Customers\n");
                        $this->processFile($rows, $header, $this->customerInstall, '');
                        break;

                    case "product_attributes.csv":
                        print_r("loading Product Attributes\n");
                        $this->processRows($rows, $header, $this->productAttributesInstall);
                        break;

                    case "categories.csv":
                        print_r("loading Categories\n");
                        $this->processRows($rows, $header, $this->categoryInstall);
                        break;

                    case "products.csv":
                        print_r("loading Products\n");
                        $this->processFile($rows, $header, $this->productInstall, $modulePath);
                        break;

                    case "pages.csv":
                        print_r("loading Pages\n");
                        $this->processRows($rows, $header, $this->pageInstall);
                        break;

                    case "blocks.csv":
                        print_r("loading Blocks\n");
                        $this->processRows($rows, $header, $this->blockInstall);
                        break;

                    case "dynamic_blocks.csv":
                        print_r("loading Dynamic Blocks\n");
                        $this->processRows($rows, $header, $this->dynamicBlockInstall);
                        break;

                    case "default_config.json":
                        print_r("loading Default Config Json\n");
                        $this->processJson($fileContent, $this->configurationInstall);
                        break;
                    case "config_default.json":
                        print_r("loading Config Default Json\n");
                        $this->processJson($fileContent, $this->configurationInstall);
                        break;

                    case "config_vertical.json":
                        print_r("loading Config Vertical Json\n");
                        $this->processJson($fileContent, $this->configurationInstall);
                        break;

                    case "config.json":
                        print_r("loading Config Json\n");
                        $this->processJson($fileContent, $this->configurationInstall);
                        break;

                    case "config.csv":
                        print_r("loading Config.csv\n");
                        $this->processRows($rows, $header, $this->configurationInstall);
                        break;

                    case "customer_groups.csv":
                        print_r("loading Customer Groups\n");
                        $this->processRows($rows, $header, $this->customerGroupInstall);
                        break;

                    case "customer_attributes.csv":
                        print_r("loading Customer Attributes\n");
                        $this->processRows($rows, $header, $this->customerAttributeInstall);
                        break;

                    case "reviews.csv":
                        print_r("loading Reviews & Ratings\n");
                        $this->processRows($rows, $header, $this->reviewsInstall);
                        break;
                }
            }
        }

        $this->processRedos();
    }

    /**
     * @param array $rows
     * @param array $header
     * @param object $process
     */
    private function processRows(array $rows, array $header, object $process): void
    {
        foreach ($rows as $row) {
            $data = [];
            foreach ($row as $key => $value) {
                $data[$header[$key]] = $value;
            }

            $this->collectRedos($process->install($data, $this->settings), $row, $header, $process);
        }
    }

    /**
     * @param string $fileContent
     * @param object $process
     */
    private function processJson(string $fileContent, object $process): void
    {
        $process->installJson($fileContent, $this->settings);
    }

    /**
     * @param array $rows
     * @param array $header
     * @param object $process
     * @param string $modulePath
     */
    private function processFile(array $rows, array $header, object $process, string $modulePath): void
    {
        $process->install($rows, $header, $modulePath, $this->settings);
    }

    private function collectRedos($success, $row, $header, $process)
    {
        if (!$success) {
            $failed = [];
            $failed['row'][]= $row;
            $failed['header']= $header;
            $failed['process']= $process;
            $this->redo[] = $failed;
            print_r(count($this->redo));
            //print_r("failed " . $this->getClassName(get_class($process)) . "\n";
        }
    }

    /**
     *
     */
    private function processRedos()
    {
        //copy over and reset redo
        $redos = $this->redo;
        $this->redo = [];
        foreach ($redos as $redo) {
            $this->processRows($redo['row'], $redo['header'], $redo['process']);
        }

        ///if its failed again, fail the process
        if (count($this->redo) > 0) {
            foreach ($this->redo as $redo) {
                print_r(
                    "Installing " . $this->getClassName(get_class($redo['process'])) .
                    " was not fully successful, likely due to a dependency on other sample data that doesnt exist"
                );
            }
        }
    }

    /**
     * @param string $classname
     * @return false|int|string
     */
    private function getClassName(string $classname)
    {
        if ($pos = strrpos($classname, '\\')) {
            return substr($classname, $pos + 1);
        }

        return $pos;
    }

    /**
     * @param string $moduleName
     * @param string $fixtureDirectory
     * @return array
     * @throws LocalizedException
     */
    private function getConfiguration(string $moduleName, string $fixtureDirectory): array
    {
        $setupArray=$this->settings;
        $setupFile = $this->fixtureManager->getFixture($moduleName . "::" . $fixtureDirectory . "/settings.csv");
        if (file_exists($setupFile)) {
            $setupRows = $this->csvReader->getData($setupFile);
            $setupHeader = array_shift($setupRows);
            //Remove hidden character Excel adds to the first cell of a document
            $setupHeader = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $setupHeader);
            foreach ($setupRows as $setupRow) {
                $setupArray[$setupRow[0]] = $setupRow[1];
            }
        }

        return $setupArray;
    }
}
