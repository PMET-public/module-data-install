<?php

namespace MagentoEse\DataInstall\Model;

use Magento\Framework\File\Csv;
use Magento\Framework\Setup\SampleData\Context as SampleDataContext;
use Magento\Framework\Setup\SampleData\FixtureManager;

class Settings
{

    protected $site_code;
    protected $store_code;
    protected $store_view_code;
    protected $root_category;
    protected $root_category_id;
    protected $product_image_import_directory;


    /** @var FixtureManager  */
    protected $fixtureManager;

    /** @var Csv  */
    protected $csvReader;
    public function __construct(SampleDataContext $sampleDataContext)
    {
        $this->fixtureManager = $sampleDataContext->getFixtureManager();
        $this->csvReader = $sampleDataContext->getCsvReader();
    }

    public function setSettings(string $moduleName, string $fixtureDirectory)
    {
        $setupArray=['site_code'=>'base', 'store_code'=>'main_website_store','store_view_code'=>'default','root_category' => 'Default Category', 'root_category_id' => '2','product_image_import_directory' =>''];
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
        $this->site_code = $setupArray['site_code'];
        $this->store_code = $setupArray['$store_code'];
        $this->store_view_code = $setupArray['$store_view_code'];
        $this->root_category = $setupArray['$root_category'];
        $this->root_category_id = $setupArray['$root_category_id'];
        $this->product_image_import_directory = $setupArray['$product_image_import_directory'];
    }
}
