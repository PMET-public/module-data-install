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

    /**
     * Stores constructor.
     * @param SampleDataContext $sampleDataContext
     */
    public function __construct(SampleDataContext $sampleDataContext, Stores $stores)
    {
        $this->fixtureManager = $sampleDataContext->getFixtureManager();
        $this->csvReader = $sampleDataContext->getCsvReader();
        $this->storeInstall = $stores;
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
                    if(basename($fileName)=='stores.csv'){

                        $this->storeInstall->processStores($data);

                    }
                }
            }
        echo "\n\n\n\n\n\n\n";
        $f=$RRRRf;


    }

}
