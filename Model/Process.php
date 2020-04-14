<?php

/**
 * This class will take in an array of files, convert them to data arrays
and pass them on to the correct data loader
 */
namespace MagentoEse\DataInstall\Model;

use Magento\Framework\File\Csv;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Setup\SampleData\Context as SampleDataContext;
use Magento\Framework\Setup\SampleData\FixtureManager;

class Process
{
    const FILE_ORDER = ['stores.csv','config_default.json','config.json','config.csv','customer_groups.csv','customer_attributes.csv','customers.csv','product_attributes.csv','categories.csv','products.csv','blocks.csv','dynamic_blocks.csv','pages.csv'];

    protected $redo=array();

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
        Customers $customers
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
    }

    /**
     * @param array $fixtures
     * @throws \Exception
     */
    public function loadFiles(array $fixtures)
    {
        //validate files
        foreach (self::FILE_ORDER as $nextFile) {
            foreach ($fixtures as $fileName) {
                $fileName = $this->fixtureManager->getFixture($fileName);
                if (basename($fileName)==$nextFile && file_exists($fileName)) {

                    if(pathinfo($fileName, PATHINFO_EXTENSION)=='json'){
                       $fileContent = file_get_contents($fileName);
                    }else {
                        $rows = $this->csvReader->getData($fileName);
                        $header = array_shift($rows);
                        //Remove hidden character Excel adds to the first cell of a document
                        $header = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $header);
                    }
                    //determine path to module code for image import
                    $modulePath =  str_replace("/fixtures/".basename($fileName), "", $fileName);

                    switch (basename($fileName)) {
                        case "stores.csv":
                            echo "loading Stores\n";
                            $this->processRows($rows, $header, $this->storeInstall);
                            break;

                        case "customers.csv":
                            echo "loading Customers\n";
                            $this->processRows($rows, $header, $this->customerInstall);
                            break;

                        case "product_attributes.csv":
                            echo "loading Product Attributes\n";
                            $this->processRows($rows, $header, $this->productAttributesInstall);
                            break;

                        case "categories.csv":
                            echo "loading Categories\n";
                            $this->processRows($rows, $header, $this->categoryInstall);
                            break;

                        case "products.csv":
                            echo "loading products\n";
                            $this->processFile($rows, $header, $this->productInstall, $modulePath);
                            break;

                        case "pages.csv":
                            echo "loading Pages\n";
                            $this->processRows($rows, $header, $this->pageInstall);
                            break;

                        case "blocks.csv":
                            echo "loading Blocks\n";
                            $this->processRows($rows, $header, $this->blockInstall);
                            break;

                        case "dynamic_blocks.csv":
                            echo "loading Dynamic Blocks\n";
                            $this->processRows($rows, $header, $this->dynamicBlockInstall);
                            break;

                        case "default_config.json":
                            echo "loading Default Config Json\n";
                            $this->processJson($fileContent, $this->configurationInstall);
                            break;

                        case "config.json":
                            echo "loading Config Json\n";
                            $this->processJson($fileContent, $this->configurationInstall);
                            break;

                        case "config.csv":
                            echo "loading Config.csv\n";
                            $this->processRows($rows, $header, $this->configurationInstall);
                            break;

                        case "customer_groups.csv":
                            echo "loading Customer Groups\n";
                            $this->processRows($rows, $header, $this->customerGroupInstall);
                            break;

                        case "customer_attributes.csv":
                            echo "loading Customer Attributes\n";
                            $this->processRows($rows, $header, $this->customerAttributeInstall);
                            break;

                    }
                }
            }
        }
        echo "\n\n";
        $this->processRedos();
        //$f=$RRRRf;
    }

    private function processRows(array $rows, array $header, object $process): void
    {
        foreach ($rows as $row) {
            $data = [];
            foreach ($row as $key => $value) {
                $data[$header[$key]] = $value;
            }
                $this->collectRedos($process->install($data),$row, $header,$process);
        }
    }

    private function processJson(string $fileContent, object $process): void
    {
        $process->installJson($fileContent);
    }


    private function processFile(array $rows, array $header, object $process, string $modulePath): void
    {
        $process->install($rows, $header, $modulePath);
    }

    private function collectRedos($success,$row,$header,$process){
        if(!$success){
            $failed = [];
            $failed['row'][]= $row;
            $failed['header']= $header;
            $failed['process']= $process;
            $this->redo[] = $failed;
            print_r(count($this->redo));
            //echo "failed " . $this->get_class_name(get_class($process)) . "\n";
        }
    }

    private function processRedos(){
        //copy over and reset redo
        $redos = $this->redo;
        $this->redo = array();
        foreach($redos as $redo){
            $this->processRows($redo['row'],$redo['header'],$redo['process']);
        }
        ///if its failed again, fail the process
        if(count($this->redo) > 0){
            foreach($this->redo as $redo){
                echo "Installing ".$this->get_class_name(get_class($redo['process']))." was not fully successful, likely due to a dependency on other sample data that doesnt exist";
            }
        }
    }

    private function get_class_name($classname)
    {
        if ($pos = strrpos($classname, '\\')) return substr($classname, $pos + 1);
        return $pos;
    }


}
