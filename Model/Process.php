<?php
/**
 * Copyright 2022 Adobe, Inc. All rights reserved.
 * See LICENSE for license details.
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
use Magento\Framework\Setup\SampleData\Context as SampleDataContext;
use Magento\Framework\Setup\SampleData\FixtureManager;
use MagentoEse\DataInstall\Model\Conf;
use Magento\Framework\Event\ManagerInterface as EventManager;
use MagentoEse\DataInstall\Model\DataTypes\B2bGraphQl;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use MagentoEse\DataInstall\Api\Data\DataPackInterface;
use MagentoEse\DataInstall\Api\Data\InstallerJobInterface;
use MagentoEse\DataInstall\Api\Data\InstallerJobInterfaceFactory;
use MagentoEse\DataInstall\Model\Queue\ScheduleBulk;
use MagentoEse\DataInstall\Model\DataTypes\Stores;

class Process
{
    protected const FIXTURE_DIRECTORY = 'data';
    
    /** @var array */
    protected $redo;

    /** @var array */
    protected $settings;

    /** @var CopyMedia  */
    protected $copyMedia;

    /** @var Validate  */
    protected $validate;

    /** @var File  */
    protected $driverInterface;

    /** @var DirectoryList  */
    protected $directoryList;
        
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

    /** @var Queue\ScheduleBulk */
    protected $scheduleBulk;

    /** @var EventManager */
    protected $eventManager;

    /** @var B2bGraphQl */
    protected $b2bGraphQl;

    /** @var InstallerJobInterfaceFactory */
    protected $installerJob;

   /**
    *
    * @param CopyMedia $copyMedia
    * @param DirectoryList $directoryList
    * @param File $driverInterface
    * @param Helper $helper
    * @param InstallerInterfaceFactory $dataInstallerInterface
    * @param InstallerRepositoryInterface $dataInstallerRepository
    * @param SampleDataContext $sampleDataContext
    * @param Validate $validate
    * @param ScheduleBulk $scheduleBulk
    * @param Conf $conf
    * @param Stores $stores
    * @param EventManager $eventManager
    * @param B2bGraphQl $b2bGraphQl
    * @param InstallerJobInterfaceFactory $installerJob
    * @return void
    */
    public function __construct(
        CopyMedia $copyMedia,
        DirectoryList $directoryList,
        File $driverInterface,
        Helper $helper,
        InstallerInterfaceFactory $dataInstallerInterface,
        InstallerRepositoryInterface $dataInstallerRepository,
        SampleDataContext $sampleDataContext,
        Validate $validate,
        Queue\ScheduleBulk $scheduleBulk,
        Conf $conf,
        DataTypes\Stores $stores,
        EventManager $eventManager,
        B2bGraphQl $b2bGraphQl,
        InstallerJobInterfaceFactory $installerJob
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
        $this->scheduleBulk = $scheduleBulk;
        $this->conf = $conf;
        $this->storeInstall = $stores;
        $this->eventManager = $eventManager;
        $this->b2bGraphQl = $b2bGraphQl;
        $this->installerJob = $installerJob;
    }
    /**
     * Load files
     *
     * @param DataPackInterface $dataPack
     * @return bool
     * @throws LocalizedException
     * @throws FileSystemException
     */
    // phpcs:ignore Generic.Metrics.NestingLevel.TooHigh,Magento2.Annotation.MethodArguments.NoCommentBlock
    public function loadFiles($dataPack)
    {
        $jobSettings['filesource'] = $dataPack->getDataPackLocation();
        $jobSettings['load'] = $dataPack->getLoad();
        $jobSettings['fileorder'] = $dataPack->getFiles();
        $jobSettings['reload'] = $dataPack->getReload();
        $jobSettings['host'] = $dataPack->getHost();
        $jobSettings['jobid'] = $dataPack->getJobId();
        $jobSettings['isDefaultWebsite'] = $dataPack->getIsDefaultWebsite();

        $fileSource = $jobSettings['filesource'];
        $load = $jobSettings['load'];
        $fileOrder = $jobSettings['fileorder'];
        $reload = $jobSettings['reload'];
        $host = $jobSettings['host'];

        $fixtureDirectory = self::FIXTURE_DIRECTORY;
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

        $this->settings = $this->getConfiguration($filePath, $fixtureDirectory);
        $this->settings['job_settings'] = $jobSettings;
        $this->helper->setSettings($this->settings);
        //dispatch start event
        $this->eventManager->dispatch('magentoese_datainstall_install_start', ['eventData' => $this->settings]);

        //bypass if data is already installed
        $fileSource = $filePath.$fixtureDirectory;
        if ($this->isModuleInstalled($fileSource)==1) {
            if($reload===0 or $reload==='false') {
                $this->helper->logMessage(
                    $fileSource." has already been installed.  Add the -r option if you want to reinstall",
                    "warning"
                );
                return true;
            }
        } else {
            $this->registerModule($fileSource);
        }

        $fileCount = 0;
        if (count($fileOrder)==0) {
            $fileOrder=$this->conf->getProcessConfiguration();
        }

        $this->helper->logMessage("Copying Media", "info");

        $this->copyMedia->moveFiles($filePath);

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
                            $this->helper->logMessage("Skipping File ".$fileInfo['filename'].
                            ". The number of columns in the header does not match the number of column of ".
                            "data in one or more rows", "warning");
                            continue;
                        }
                        //validate that the file is not empty
                        if (empty($rows)) {
                            $this->helper->logMessage("Skipping File ".$fileInfo['filename'].
                            ". The file is empty or not properly formatted", "warning");
                            continue;
                        }
                    }

                    //determine path to module code for image import
                    // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
                    $modulePath = str_replace("/" . $fixtureDirectory . "/" . basename($fileName), "", $fileName);
                    $this->helper->logMessage($fileInfo['label'], "info");
                    switch ($fileInfo['process']) {
                        case 'file':
                            $this->processFile($rows, $header, $fileInfo['class'], $modulePath, $host);
                            break;
                        case 'json':
                            $this->processJson($fileContent, $fileInfo['class'], $host);
                            break;
                        case 'b2b':
                            $fileData = $this->processB2BFiles($filePath, $fixtureDirectory, $fileInfo['class']);
                            if (empty($fileData)) {
                                return true;
                            } else {
                                $this->processB2B($fileData, $fileInfo['class']);
                            }
                            break;
                        case 'graphqlrows':
                            $fileData = $this->convertGraphQlJson($fileContent);
                            $this->processRows($fileData['rows'], $fileData['header'], $fileInfo['class'], $host);
                            break;
                        case 'graphqlfile':
                            $fileData = $this->convertGraphQlJson($fileContent);
                            $this->processFile(
                                $fileData['rows'],
                                $fileData['header'],
                                $fileInfo['class'],
                                $modulePath,
                                $host
                            );
                            break;
                        case 'graphqlexport':
                            $fileData = $this->convertGraphQlExport($fileContent);
                            $this->processFile(
                                $fileData['rows'],
                                $fileData['header'],
                                $fileInfo['class'],
                                $modulePath,
                                $host
                            );
                            break;
                        case 'b2bgraphql':
                            $fileData = $this->b2bGraphQl->processB2BGraphql($fileContent);
                            $this->processB2B($fileData, $fileInfo['class']);
                            break;
                        default:
                            $this->processRows($rows, $header, $fileInfo['class'], $host);
                    }
                }
            }
        }
        $this->eventManager->dispatch('magentoese_datainstall_install_end', ['eventData' => $this->settings]);
        if ($fileCount==0) {
            return false;
        } else {
            //$this->setModuleInstalled($fileSource);
            return true;
        }
    }

    /**
     * Set defaults
     *
     * @param arrray $jobSettings
     * @return mixed
     */
    private function setDefaults(array $jobSettings)
    {
        if (empty($jobSettings['filesource'])) {
            $jobSettings['filesource'] = '';
        }
        if (empty($jobSettings['load'])) {
            $jobSettings['load'] ='';
        }
        if (empty($jobSettings['fileorder'])) {
            $jobSettings['fileorder'] =[];
        }
        if (empty($jobSettings['reload'])) {
            $jobSettings['reload']=0;
        }
        if (empty($jobSettings['host'])) {
            $jobSettings['host'] = null;
        }
        if (empty($jobSettings['jobid'])) {
            $jobSettings['jobid']='';
        }
        return $jobSettings;
    }

    /**
     * Get process instructions
     *
     * @param string $file
     * @return array|false
     */
    private function getProcessInstructions($file)
    {
        //Is there processing information passed on with the filename?
        //If not, then we need to look up the configuration for that file name
        if (is_array($file)) {
            $filename = key($file);
            //validate the correct keys are present
            if (empty($file[$filename]['process']) && empty($file[$filename]['class'])) {
                $this->helper->logMessage(
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
            $this->helper->logMessage(
                "File " .$file .
                " does not have processing instructions - skipped",
                "error"
            );
            return false;
        }
    }

    /**
     * Process rows
     *
     * @param array $rows
     * @param array $header
     * @param object $process
     * @param string $host
     */
    private function processRows(array $rows, array $header, object $process, $host): void
    {
        foreach ($rows as $row) {
            $data = [];
            foreach ($row as $key => $value) {
                $data[$header[$key]] = $value;
            }
            $process->install($data, $this->settings, $host);
        }
    }

    /**
     * Process json
     *
     * @param string $fileContent
     * @param object $process
     * @param string $host
     */
    private function processJson(string $fileContent, object $process, $host): void
    {
        $process->installJson($fileContent, $this->settings, $host);
    }

    /**
     * Process by file
     *
     * @param array $rows
     * @param array $header
     * @param object $process
     * @param string $modulePath
     * @param string $host
     */
    private function processFile(array $rows, array $header, object $process, string $modulePath, $host): void
    {
        $process->install($rows, $header, $modulePath, $this->settings, $host);
    }

     /**
      * Converts result of a GraphQl query into format that can be used by processFile
      *
      * @param string $json
      * @return array
      */
    // phpcs:ignore Generic.Metrics.NestingLevel.TooHigh,Magento2.Annotation.MethodArguments.NoCommentBlock
    public function convertGraphQlJson(string $json)
    {
        //TODO: Validate json
        try {
            //convert to array of objects. Remove the parent query name node
            $currentKey=key(get_object_vars(json_decode($json)->data));
            $fileData = json_decode($json)->data->$currentKey;
        } catch (\Exception $e) {
            $this->helper->logMessage("The JSON in your data file may be invalid", "error");
            return true;
        }
        $header=[];
        //if items exists it is a multi value file
        if (property_exists($fileData, 'items')) {
            $fileData = $fileData->items;
            $i=0;
            foreach ($fileData as $element) {
                //if there is an id column we want to use this as the array key for each row
                if (property_exists($element, 'id')) {
                     $i = $element->id;
                }
                foreach ($element as $key => $item) {
                    //special case for attribute options
                    if ($key=='attribute_options') {
                        $header[]='option';
                        $row[$i][] = $this->getAttributeOptions($item);
                    }
                    //if item is an object like attibute settings, flatten it
                    if (is_object($item)) {
                        foreach ($item as $subKey => $subItem) {
                            if (!in_array($subKey, $header)) {
                                $header[]=$subKey;
                            }
                            $row[$i][]=$subItem;
                        }
                    }
                    if (!in_array($key, $header)) {
                        $header[]=$key;
                    }
                    $row[$i][]=$item;
                }
                $i ++;
            }
        } else {
            ///single row file
            foreach ($fileData as $key => $item) {
                $header[]=$key;
                $row[0][]=$item;
            }
        }
        ///sort array by keys for cases then order is important
        ksort($row);
        return ['header'=>$header,'rows'=>$row];
    }

     /**
      * Converts result of a GraphQl export into format that can be used by processFile
      *
      * @param string $json
      * @return array
      */
    public function convertGraphQlExport(string $json)
    {
        try {
            //convert to array of objects. Remove the parent query name node
            $currentKey=key(get_object_vars(json_decode($json)->data));
            $fileData = json_decode(json_decode($json)->data->$currentKey->data);
            //$data = json_decode(json_decode($json)->data->export->data);
        } catch (\Exception $e) {
            $this->helper->logMessage("The JSON in your data file may be invalid", "error");
            return true;
        }
        $header=$fileData[0];
        unset($fileData[0]);
        //there may be an additional row that has a null value based on the encoding/decoding data
        //verify all row data matches header count and delete any bad rows
        $headerCount = count($header);
        foreach ($fileData as $key => $row) {
            if (count($row) != $headerCount) {
                unset($fileData[$key]);
            }
        }
        return ['header'=>$header,'rows'=>$fileData];
    }

    /**
     * Get options for attribute
     *
     * @param array $attributeOptions
     * @return string
     */
    private function getAttributeOptions($attributeOptions)
    {
        $attributeString = '';
        foreach ($attributeOptions as $option => $value) {
            $attributeString .=$value->label."\n";
        }
        return trim($attributeString);
    }

    /**
     * Get name of class from string
     *
     * @param string $className
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
     * Is it a recurring data function (deprecated)
     *
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
     * Get Configuration
     *
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
        } else {
            //check for .json version
            try {
                $settingsJson = $this->driverInterface->fileGetContents($filePath.$fixtureDirectory.'/settings.json');
                $settings = $this->convertGraphQlJson($settingsJson);
                $i=0;
                foreach ($settings['header'] as $header) {
                    if (!empty($settings['rows'][0][$i])) {
                        $setupArray[$header] = $settings['rows'][0][$i];
                    }
                    $i++;
                }
            // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedCatch  
            } catch (FileSystemException $fe) {
                 //ignore if file does not exist
            }
        }
        //if website requested is "base" get the default website code in case it has changed
        //the is mostly to get around changed webside codes for livesearch environments
        $setupArray['site_code'] = $this->storeInstall->replaceBaseWebsiteCode($setupArray['site_code']);
        $setupArray['fixture_directory'] = $fixtureDirectory;
        $setupArray['file_path'] = $filePath;
        return $setupArray;
    }
    
    /**
     * Process B2B files
     *
     * @param string $filePath
     * @param string $fixtureDirectory
     * @param mixed $classes
     * @throws \Exception
     * @return array
     */
    private function processB2BFiles($filePath, $fixtureDirectory, $classes)
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
                    $this->helper->logMessage($nextFile." is invalid. The number of columns in the header does not ".
                    "match the number of column of data in one or more rows", "error");
                    return [];
                }
                $b2bData[$nextFile] = ['header'=>$header,'rows'=>$rows];
            } else {
                $this->helper->logMessage("You are missing the required B2B file - ".$nextFile.
                ". B2B setup did not complete", "error");
                return[];
            }
        }
        return $b2bData;
    }
    
    /**
     * Process all b2b data, make sure cross file data matches
     *
     * @param array $b2bData
     * @param array $classes
     * @throws \Exception
     */
    private function processB2B($b2bData, $classes)
    {
        
        //validate referential integrity of the data
        if (!$this->validate->validateB2bData($b2bData)) {
            $this->helper->logMessage("Bad Data", "error");
                ///probably need to throw an error to roll back everything
        }
        $salesReps = $this->buildB2bDataArrays($b2bData['b2b_sales_reps.csv']);
        $companies = $this->buildB2bDataArrays($b2bData['b2b_companies.csv']);
        $customers = $this->buildB2bDataArrays($b2bData['b2b_customers.csv']);

        //load customers (normal process)
        $this->helper->logMessage("Loading B2B Customers", "info");
        $this->processFile(
            $b2bData['b2b_customers.csv']['rows'],
            $b2bData['b2b_customers.csv']['header'],
            $classes['customerInstall'],
            '',
            ''
        );
        //load sales reps (admin user process)
        $this->helper->logMessage("Loading B2B Sales Reps", "info");
        $this->processRows(
            $b2bData['b2b_sales_reps.csv']['rows'],
            $b2bData['b2b_sales_reps.csv']['header'],
            $classes['adminUsersInstall'],
            ''
        );
        //create company (add on company admin from customers, and sales rep);

        $companiesData = $this->mergeCompanyData($companies, $customers, $salesReps);
        $this->helper->logMessage("Loading B2B Companies", "info");

        foreach ($companiesData as $companyData) {
            $classes['companiesInstall']->install($companyData, $this->settings);
        }

        //add company roles
        $this->helper->logMessage("Loading B2B Company Roles", "info");
        $this->processFile(
            $b2bData['b2b_company_roles.csv']['rows'],
            $b2bData['b2b_company_roles.csv']['header'],
            $classes['companyRolesInstall'],
            '',
            ''
        );
        //assign roles to customers
        $this->processRows(
            $b2bData['b2b_customers.csv']['rows'],
            $b2bData['b2b_customers.csv']['header'],
            $classes['companyUserRolesInstall'],
            ''
        );
        $this->helper->logMessage("Loading B2B Teams and Company Structure", "info");
        //create company structure
        $this->processRows(
            $b2bData['b2b_teams.csv']['rows'],
            $b2bData['b2b_teams.csv']['header'],
            $classes['companyTeamsInstall'],
            ''
        );

        //if requisition lists are part of the structure (coming from graphql), process them now
        if (!empty($b2bData['b2b_requisition_lists.csv'])) {
            $this->helper->logMessage("Loading B2B Requisition Lists", "info");
             $this->processRows(
                 $b2bData['b2b_requisition_lists.csv']['rows'],
                 $b2bData['b2b_requisition_lists.csv']['header'],
                 $classes['requisitionListsInstall'],
                 ''
             );
        }

        //if shared catalogs are part of the structure (coming from graphql), process them now
        if (!empty($b2bData['b2b_shared_catalogs.csv'])) {
            $this->helper->logMessage("Loading B2B Shared Catalogs", "info");
             $this->processRows(
                 $b2bData['b2b_shared_catalogs.csv']['rows'],
                 $b2bData['b2b_shared_catalogs.csv']['header'],
                 $classes['sharedCatalogInstall'],
                 ''
             );
        }

        if (!empty($b2bData['b2b_shared_catalog_categories.csv'])) {
            $this->helper->logMessage("Loading B2B Shared Catalog Categories", "info");
             $this->processFile(
                 $b2bData['b2b_shared_catalog_categories.csv']['rows'],
                 $b2bData['b2b_shared_catalog_categories.csv']['header'],
                 $classes['sharedCatalogCategoriesInstall'],
                 '',
                 $this->settings,
                 ''
             );
        }

         //if approval rules are part of the structure (coming from graphql), process them now
        if (!empty($b2bData['b2b_approval_rules.csv'])) {
            $this->helper->logMessage("Loading B2B Approval Rules", "info");
            $this->processRows(
                $b2bData['b2b_approval_rules.csv']['rows'],
                $b2bData['b2b_approval_rules.csv']['header'],
                $classes['approvalRulesInstall'],
                ''
            );
        }
    }
    /**
     * Copy data that may be needed from one array into another
     *
     * @param array $companies
     * @param array $customers
     * @param array $salesReps
     * @return array
     */
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
     * Build array with B2B data
     *
     * @param array $rowData
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
     * Match by key/value
     *
     * @param array $array
     * @param string $keyToFind
     * @param string $valueToFind
     * @param string $keyToReturn
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
     * Get calling class
     *
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
     * Register module as installed
     *
     * @param string $moduleName
     * @return int
     * @throws CouldNotDeleteException
     * @throws CouldNotSaveException
     * @throws NoSuchEntityException
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
     * Is module installed
     *
     * @param string $moduleName
     * @return string
     * @throws NoSuchEntityException
     */
    private function isModuleInstalled($moduleName)
    {
        $tracker = $this->dataInstallerInterface->create();
        $tracker = $this->dataInstallerRepository->getByModuleName($moduleName);
        return $tracker->isInstalled();
    }

    /**
     * Set module as installed
     *
     * @param string $moduleName
     * @throws CouldNotSaveException
     * @throws NoSuchEntityException
     */
    public function setModuleInstalled($moduleName)
    {
        $tracker = $this->dataInstallerInterface->create();
        $tracker = $this->dataInstallerRepository->getByModuleName($moduleName);
        $tracker->setIsInstalled(1);
        $this->dataInstallerRepository->save($tracker);
    }

    /**
     * Get data path for file
     *
     * @param string $fileLocation
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
     * Is it called from CLI
     *
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
