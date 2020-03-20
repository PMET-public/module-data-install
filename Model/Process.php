<?php

/**
 * This class will take in an array of files, convert them to data arrays
and pass them on to the correct data loader
 */
namespace MagentoEse\DataInstall\Model;

use Magento\Framework\File\Csv;
use Magento\Framework\Setup\SampleData\Context as SampleDataContext;
use Magento\Framework\Setup\SampleData\FixtureManager;

class Process
{
    const FILE_ORDER = ['stores.csv','customers.csv','product_attributes.csv','categories.csv'];

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

    /**
     * Process constructor.
     * @param SampleDataContext $sampleDataContext
     * @param Stores $stores
     * @param ProductAttributes $productAttributes
     * @param Categories $categories
     */
    public function __construct(
        SampleDataContext $sampleDataContext,
        Stores $stores,
        ProductAttributes $productAttributes,
        Categories $categories
    ) {
        $this->fixtureManager = $sampleDataContext->getFixtureManager();
        $this->csvReader = $sampleDataContext->getCsvReader();
        $this->storeInstall = $stores;
        $this->productAttributesInstall = $productAttributes;
        $this->categoryInstall = $categories;
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

                    $rows = $this->csvReader->getData($fileName);
                    $header = array_shift($rows);
                    //Remove hidden character Excel adds to the first cell of a document
                    $header = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $header);

                    switch (basename($fileName)) {
                        case "stores.csv":
                            echo "loading Stores\n";
                            $this->processRows($rows, $header, $this->storeInstall);
                            break;

                        case "customers.csv":
                            echo "loading Customers\n";
                           // $this->processRows($rows, $header, $this->customerInstall);
                            break;

                        case "product_attributes.csv":
                            echo "loading Product Attributes\n";
                            $this->processRows($rows, $header, $this->productAttributesInstall);
                            //$this->productAttributesInstall->install($data);
                            break;

                        case "categories.csv":
                            echo "loading Categories\n";
                            $this->processRows($rows, $header, $this->categoryInstall);
                            //$this->categoryInstall->install($data);
                            break;
                    }


                }
            }

           }
        echo "\n\n\n\n\n\n\n";
        //$f=$RRRRf;
    }


    private function processRows(array $rows, $header, $process): void
    {
        foreach ($rows as $row) {
            $data = [];
            foreach ($row as $key => $value) {
                $data[$header[$key]] = $value;
            }
                $process->install($data);
            }

        }
}
