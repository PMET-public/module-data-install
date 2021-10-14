<?php

/**
 * Copyright © Adobe. All rights reserved.
 */

 //Warnings around using native php filesytem functions are suppressed e.g. file_exists()
 //core is still using some of those functions

namespace MagentoEse\DataInstall\Model;

use MagentoEse\DataInstall\Helper\Helper;
use MagentoEse\DataInstall\Api\Data\InstallerInterfaceFactory;
use MagentoEse\DataInstall\Api\InstallerRepositoryInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\File\Csv;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\DriverInterface;
use Magento\Framework\Setup\SampleData\Context as SampleDataContext;
use Magento\Framework\Setup\SampleData\FixtureManager;
use MagentoEse\DataInstall\Model\Conf;

class Process
{
    /** @var array */
    protected $settings;

    /** @var Csv  */
    protected $csvReader;

    /** @var FixtureManager  */
    protected $fixtureManager;

    /** @var Helper */
    protected $helper;

    /** @var InstallerInterfaceFactory */
    protected $dataInstallerInterface;

    /** @var InstallerRepositoryInterface */
    protected $dataInstallerRepository;

    /** @var Conf  */
    protected $conf;
    
    /** @var DataTypes\Stores  */
    protected $storeInstall;

    /**
     * Process constructor.
     * @param CopyMedia $copyMedia
     * @param DirectoryList $directoryList
     * @param DriverInterface $driverInterface
     * @param Helper $helper
     * @param InstallerInterfaceFactory $dataInstallerInterface
     * @param InstallerRepositoryInterface $dataInstallerRepository
     * @param SampleDataContext $sampleDataContext
     * @param Validate $validate
     * @param Conf $conf
     * @param DataTypes\Stores $stores
     **/
    public function __construct(
        CopyMedia $copyMedia,
        DirectoryList $directoryList,
        DriverInterface $driverInterface,
        Helper $helper,
        InstallerInterfaceFactory $dataInstallerInterface,
        InstallerRepositoryInterface $dataInstallerRepository,
        SampleDataContext $sampleDataContext,
        Validate $validate,
        Conf $conf,
        DataTypes\Stores $stores
    ) {
        $this->copyMedia = $copyMedia;
        $this->helper = $helper;
        $this->fixtureManager = $sampleDataContext->getFixtureManager();
        $this->csvReader = $sampleDataContext->getCsvReader();
        $this->directoryList = $directoryList;
        $this->validate = $validate;
        $this->driverInterface = $driverInterface;
        $this->dataInstallerInterface = $dataInstallerInterface;
        $this->dataInstallerRepository = $dataInstallerRepository;
        $this->conf = $conf;
        $this->storeInstall = $stores;
    }
    /**
     * @param $fileSource
     * @param string $fixtureDirectory
     * @param array|string[] $fileOrder
     * @param int $reload
     * @return bool
     * @throws LocalizedException
     * @throws FileSystemException
     */
    public function loadFiles($fileSource, $load='', array $fileOrder=[], $reload = 0)
    {
        $fixtureDirectory = "data";
        //bypass if data is already installed
        if ($this->isModuleInstalled($fileSource)==1 && $reload===0) {
            //output reload option if cli is used
            //if ($this->isCli()) {
                $this->helper->printMessage(
                    $fileSource." has already been installed.  Add the -r option if you want to reinstall",
                    "warning"
                );
            //}
            return true;
        } else {
            $this->registerModule($fileSource);
        }

        //if there is no load value, check for .default flag
        $filePath = $this->getDataPath($fileSource);
        if ($load=='') {
            try {
                $load = $this->driverInterface->fileGetContents($filePath.$fixtureDirectory.'/.default');
            } catch (FileSystemException $fe) {
                $fixtureDirectory = $filePath;
            }
        }
        $fixtureDirectory = 'data/'.$load;

        $fileCount = 0;
        if (count($fileOrder)==0) {
            $fileOrder=$this->conf->getProcessConfiguration();
            //$fileOrder=conf::ALL_FILES;
        }
        // if (count($fileOrder)==1) {
        //     //for setting files when start, stores and end is used in place of file list
        //     switch (strtolower($fileOrder[0])) {
        //         case "stores":
        //             $fileOrder = Conf::STORE_FILES;
        //             break;
        //         case "start":
        //             $fileOrder = Conf::STAGE1;
        //             break;
        //         case "end":
        //             $fileOrder = Conf::STAGE2;
        //             break;
        //     }
        // }
        //$filePath = $this->getDataPath($fileSource);
        $this->helper->printMessage("Copying Media", "info");
        $this->copyMedia->moveFiles($filePath);
        $this->settings = $this->getConfiguration($filePath, $fixtureDirectory);

        foreach ($fileOrder as $nextFile) {
            //get processing instructions based on filename
            //returns ['filename','process','class','label'];
            $fileInfo = $this->getProcessInstructions($nextFile);
            if ($fileInfo) {
                $fileName = $filePath . $fixtureDirectory . "/" . $fileInfo['filename'];
                // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
                if (basename($fileName)==$fileInfo['filename'] && file_exists($fileName)) {
                    $fileCount++;
                    // phpcs:ignore Magento2.Functions.DiscouragedFunction.DiscouragedWithAlternative
                    if (pathinfo($fileName, PATHINFO_EXTENSION) == 'json') {
                        $fileContent = $this->driverInterface->fileGetContents($fileName);
                    } else {
                        $rows = $this->csvReader->getData($fileName);
                        $header = array_shift($rows);
                        //Remove hidden character Excel adds to the first cell of a document
                        $header = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $header);
                        //validate that number of elements in header and rows is equal
                        if (!$this->validate->validateCsvFile($header, $rows)) {
                            $this->helper->printMessage("Skipping File ".$fileInfo['filename'].
                            ". The number of columns in the header does not match the number of column of ".
                            "data in one or more rows", "warning");
                            continue;
                        }
                        //validate that the file is not empty
                        if (empty($rows)) {
                            $this->helper->printMessage("Skipping File ".$fileInfo['filename'].
                            ". The file is empty or not properly formatted", "warning");
                            continue;
                        }
                    }

                    //determine path to module code for image import
                    // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
                    $modulePath = str_replace("/" . $fixtureDirectory . "/" . basename($fileName), "", $fileName);
                    $this->helper->printMessage($fileInfo['label'], "info");
                    if ($fileInfo['process']=='file') {
                        $this->processFile($rows, $header, $fileInfo['class'], $modulePath);
                    } elseif ($fileInfo['process']=='json') {
                        $this->processJson($fileContent, $fileInfo['class']);
                    } else {
                        $this->processRows($rows, $header, $fileInfo['class']);
                    }
                }
            }
        }
        if ($fileCount==0) {
            return false;
        } else {
            if ($this->isCli() || $this->isRecurring()) {
                $this->setModuleInstalled($fileSource);
            }
            return true;
        }
    }

    private function getProcessInstructions($file)
    {
        //Is there processing information passed on with the filename?
        //If not, then we need to look up the configuration for that file name
        if (is_array($file)) {
            $filename = key($file);
            //validate the correct keys are present
            if (empty($file[$filename]['process']) && empty($file[$filename]['class'])) {
                $this->helper->printMessage(
                    "File " .$filename .
                    " does not include the correct processing instructions - skipped",
                    "error"
                );
                return false;
            }
            if (empty($file[$filename]['label'])) {
                $file[$filename]['label']='Processing '.$filename;
            }
            return ['filename'=>$filename,'process'=>$file[$filename]['process'],
            'class'=>$file[$filename]['class'],'label'=>$file[$filename]['label']];
        } else {
                //get processing instructions based on default configuration information
                $allFiles = $this->conf->getProcessConfiguration();
            foreach ($allFiles as $key) {
                if (key($key)==$file) {
                    return ['filename'=>$file,'process'=>$key[$file]['process'],
                    'class'=>$key[$file]['class'],'label'=>$key[$file]['label']];
                }
            }
            $this->helper->printMessage(
                "File " .$file .
                " does not have processing instructions - skipped",
                "error"
            );
            return false;
        }
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
                $this->helper->printMessage(
                    "Installing " . $this->getClassName(get_class($redo['process'])) .
                    " was not fully successful, likely due to a dependency on other sample data that doesnt exist",
                    "error"
                );
            }
        }
    }

    /**
     * @param $className
     * @return false|int|string
     */
    private function getClassName($className)
    {
        if ($pos = strrpos($className, '\\')) {
            return substr($className, $pos + 1);
        }

        return $pos;
    }

    /**
     * @return bool
     */
    private function isRecurring()
    {
        $callingClass = $this->getCallingClass();
        $arr = explode("\\", $callingClass);
        $className = end($arr);
        if ($className=="RecurringData") {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param string $filePath
     * @param string $fixtureDirectory
     * @return array
     * @throws LocalizedException
     */
    private function getConfiguration(string $filePath, string $fixtureDirectory): array
    {
        $valid = false;
        $this->settings = Conf::SETTINGS;
        $setupArray=$this->settings;
        $setupFile = $filePath . $fixtureDirectory . "/settings.csv";
        // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
        if (file_exists($setupFile)) {
            $setupRows = $this->csvReader->getData($setupFile);
            $setupHeader = array_shift($setupRows);
            //Remove hidden character Excel adds to the first cell of a document
            $setupHeader = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $setupHeader);
            foreach ($setupRows as $setupRow) {
                if (!empty($setupRow[1])) {
                    $setupArray[$setupRow[0]] = $setupRow[1];
                }
            }
        }
        //if website requested is "base" get the default website code in case it has changed
        //the is mostly to get around changed webside codes for livesearch environments
        $setupArray['site_code'] = $this->storeInstall->replaceBaseWebsiteCode($setupArray['site_code']);
        return $setupArray;
    }

    /**
     * @param $filePath
     * @param $fixtureDirectory
     * @throws \Exception
     */
    private function processB2B($filePath, $fixtureDirectory)
    {
        $b2bData = [];
        $stopFlag = 0;
        //do we have all the files we need
        foreach (Conf::B2B_REQUIRED_FILES as $nextFile) {
            $fileName = $filePath . $fixtureDirectory . "/" . $nextFile;
            // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
            if (basename($fileName)==$nextFile && file_exists($fileName)) {
                $rows = $this->csvReader->getData($fileName);
                $header = array_shift($rows);
                //Remove hidden character Excel adds to the first cell of a document
                 $header = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $header);
                //validate that number of elements in header and rows is equal
                if (!$this->validate->validateCsvFile($header, $rows)) {
                    $this->helper->printMessage($nextFile." is invalid. The number of columns in the header does not ".
                    "match the number of column of data in one or more rows", "error");
                    $stopFlag = 1;
                    break;
                }
                $b2bData[$nextFile] = ['header'=>$header,'rows'=>$rows];
            } else {
                $this->helper->printMessage("You are missing the required B2B file - ".$nextFile.
                ". B2B setup did not complete", "error");
                $stopFlag = 1;
                break;
            }
        }
        if ($stopFlag == 0) {
            //validate referential integrity of the data
            if (!$this->validate->validateB2bData($b2bData)) {
                $this->helper->printMessage("Bad Data", "error");
                    ///probably need to throw an error to roll back everything
            }
            $salesReps = $this->buildB2bDataArrays($b2bData['b2b_sales_reps.csv']);
            $companies = $this->buildB2bDataArrays($b2bData['b2b_companies.csv']);
            $customers = $this->buildB2bDataArrays($b2bData['b2b_customers.csv']);

            //load customers (normal process)
            $this->helper->printMessage("Loading B2B Customers", "info");
            $this->processFile(
                $b2bData['b2b_customers.csv']['rows'],
                $b2bData['b2b_customers.csv']['header'],
                $this->customerInstall,
                ''
            );
            //load sales reps (admin user process)
            $this->helper->printMessage("Loading B2B Sales Reps", "info");
            $this->processRows(
                $b2bData['b2b_sales_reps.csv']['rows'],
                $b2bData['b2b_sales_reps.csv']['header'],
                $this->adminUsersInstall
            );
            //create company (add on company admin from customers, and sales rep);

            $companiesData = $this->mergeCompanyData($companies, $customers, $salesReps);
            $this->helper->printMessage("Loading B2B Companies", "info");

            foreach ($companiesData as $companyData) {
                $this->companiesInstall->install($companyData, $this->settings);
            }

            //add company roles
            $this->helper->printMessage("Loading B2B Company Roles", "info");
            $this->processFile(
                $b2bData['b2b_company_roles.csv']['rows'],
                $b2bData['b2b_company_roles.csv']['header'],
                $this->companyRolesInstall,
                ''
            );
            //assign roles to customers
            $this->processRows(
                $b2bData['b2b_customers.csv']['rows'],
                $b2bData['b2b_customers.csv']['header'],
                $this->companyUserRolesInstall
            );
            $this->helper->printMessage("Loading B2B Teams and Company Structure", "info");
            //create company structure
            $this->processRows(
                $b2bData['b2b_teams.csv']['rows'],
                $b2bData['b2b_teams.csv']['header'],
                $this->companyTeamsInstall
            );
        }
    }
    /**
     * @param $companies
     * @param $customers
     * @param $salesReps
     * @return array
     */
    //copy data that may be needed from one array into another
    private function mergeCompanyData($companies, $customers, $salesReps)
    {
        $revisedCompany = [];
        foreach ($companies as $company) {
            $company['company_customers']=[];
             //copy email from customers to company admin_email
            foreach ($customers as $customer) {
                if ($customer['company']==$company['company_name'] && $customer['company_admin'] == 'Y') {
                    $company['admin_email'] = $customer['email'];
                } elseif ($customer['company']==$company['company_name']) {
                    $company['company_customers'][] = $customer['email'];
                }
            }

             //copy email from salesreps to company salesrep_email
            foreach ($salesReps as $rep) {
                if ($rep['company']==$company['company_name']) {
                    $company['sales_rep'] = $rep['username'];
                }
            }

            $revisedCompany[]=$company;
        }
        return($revisedCompany);
    }

    /**
     * @param $rowData
     * @return array
     */
    private function buildB2bDataArrays($rowData)
    {
        $result = [];
        foreach ($rowData['rows'] as $row) {
            $data = [];
            foreach ($row as $key => $value) {
                $data[$rowData['header'][$key]] = $value;
            }
            $result[]=$data;
        }
        return $result;
    }

    /**
     * @param $array
     * @param $keyToFind
     * @param $valueToFind
     * @param $keyToReturn
     * @return array
     */
    private function matchKeyValue($array, $keyToFind, $valueToFind, $keyToReturn)
    {
        foreach ($array as $key => $value) {
            if ($key==$keyToFind && $value==$valueToFind) {
                return [$key=>$value];
            }
        }
    }

    /**
     * @return string
     */
    private function getModuleName()
    {
        return $this->helper->getModuleName($this->getCallingClass());
    }

    /**
     * @return mixed
     */
    private function getCallingClass()
    {

        //get the trace
        $trace = debug_backtrace();

        // Get the class that is asking
        $class = $trace[1]['class'];

        $count = count($trace);
        for ($i=1; $count; $i++) {
            if (isset($trace[$i])) { // is it set?
                if ($class != $trace[$i]['class']) { // is it a different class
                    return $trace[$i]['class'];
                }
            }
        }
    }

    /**
     * @param $moduleName
     * @return int
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function registerModule($moduleName)
    {
        $tracker = $this->dataInstallerRepository->getByModuleName($moduleName);
        if ($tracker->getId()) {
            $this->dataInstallerRepository->delete($tracker);
            $tracker = $this->dataInstallerInterface->create();
        }
        $tracker->setModuleName($moduleName);
        //$this->dataInstallerRepository->delete($tracker);
        $tracker->setIsInstalled(0);
        $this->dataInstallerRepository->save($tracker);
        return $tracker->getId();
    }

    /**
     * @param $moduleName
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function isModuleInstalled($moduleName)
    {
        $tracker = $this->dataInstallerInterface->create();
        $tracker = $this->dataInstallerRepository->getByModuleName($moduleName);
        $f=$tracker->isInstalled();
        return $tracker->isInstalled();
    }

    /**
     * @param $moduleName
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function setModuleInstalled($moduleName)
    {
        $tracker = $this->dataInstallerInterface->create();
        $tracker = $this->dataInstallerRepository->getByModuleName($moduleName);
        $tracker->setIsInstalled(1);
        $this->dataInstallerRepository->save($tracker);
    }

    /**
     * @param $fileLocation
     * @return string
     * @throws LocalizedException
     */
    private function getDataPath($fileLocation)
    {
        if (preg_match('/[A-Z,a-z,,0-9]+_[A-Z,a-z,0-9]+/', $fileLocation)==1) {
            $filePath = $this->fixtureManager->getFixture($fileLocation . "::");
            //if its not a valid module, the file path will just be the fixtures directory,
            //so then assume it may a relative path that looks like a module name;
            if ($filePath=='/') {
                return $fileLocation.'/';
            } else {
                return $filePath;
            }
        } else {
            //otherwise assume relative or absolute path
            return $this->driverInterface->getRealPath($fileLocation).'/';
        }
    }

    /**
     * @return bool
     */
    public function isCli()
    {
        // phpcs:ignore Magento2.PHP.LiteralNamespaces.LiteralClassUsage
        if ($this->getCallingClass() === 'MagentoEse\DataInstall\Console\Command\Install') {
            return true;
        } else {
            return false;
        }
    }
}
