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
    const FILE_ORDER = ['stores.csv'];

    /** @var FixtureManager  */
    protected $fixtureManager;

    /** @var Csv  */
    protected $csvReader;

    /** @var Stores  */
    protected $storeInstall;

    /** @var ProductAttributes  */
    protected $productAttributesInstall;

    /**
     * Process constructor.
     * @param SampleDataContext $sampleDataContext
     * @param Stores $stores
     * @param ProductAttributes $productAttributes
     */
    public function __construct(SampleDataContext $sampleDataContext, Stores $stores, ProductAttributes $productAttributes)
    {
        $this->fixtureManager = $sampleDataContext->getFixtureManager();
        $this->csvReader = $sampleDataContext->getCsvReader();
        $this->storeInstall = $stores;
        $this->productAttributesInstall = $productAttributes;
    }

    /**
     * @param array $fixtures
     * @throws \Exception
     */
    public function loadFiles(array $fixtures)
    {
        //validate files
        foreach ($fixtures as $fileName) {
            $fileName = $this->fixtureManager->getFixture($fileName);
            if (!file_exists($fileName)) {
                continue;
            }
        }

        foreach ($fixtures as $fileName) {
            $fileName = $this->fixtureManager->getFixture($fileName);
            if (!file_exists($fileName)) {
                continue;
            }
            $rows = $this->csvReader->getData($fileName);
            $header = array_shift($rows);
            //Remove hidden character Excel adds to the first cell of a document
            $header = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $header);
            foreach ($rows as $row) {
                $data = [];
                foreach ($row as $key => $value) {
                    $data[$header[$key]] = $value;
                }
                    switch(basename($fileName)){
                        case "stores.csv":
                            //$this->storeInstall->install($data);
                            break;

                        case "customers.csv":
                            //$this->customerInstall->install($data);
                            break;

                        case "product_attributes.csv":
                            $this->productAttributesInstall->install($data);
                            break;
                    }

                }
            }
        echo "\n\n\n\n\n\n\n";
        //$f=$RRRRf;


    }

}
