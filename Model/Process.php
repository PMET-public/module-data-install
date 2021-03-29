<?php

/**
 * Copyright © Adobe. All rights reserved.
 */
namespace MagentoEse\DataInstall\Model;

use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\File\Csv;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Setup\SampleData\Context as SampleDataContext;
use Magento\Framework\Setup\SampleData\FixtureManager;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Filesystem\DriverInterface;
use MagentoEse\DataInstall\Helper\Helper;
use MagentoEse\DataInstall\Api\Data\InstallerInterfaceFactory;
use MagentoEse\DataInstall\Api\InstallerRepositoryInterface;

class Process
{
    const ALL_FILES = ['stores.csv','config_vertical.json','config_secret.json','config.csv',
    'admin_roles.csv','admin_users.csv','customer_groups.csv','customer_attributes.csv','customers.csv','product_attributes.csv',
    'blocks.csv','categories.csv','products.csv','products2.csv','msi_inventory.csv','upsells.csv','blocks.csv','dynamic_blocks.csv',
    'pages.csv','templates.csv','reviews.csv','b2b_companies.csv','b2b_shared_catalogs.csv',
    'b2b_shared_catalog_categories.csv','b2b_requisition_lists.csv','advanced_pricing.csv','orders.csv'];

    const STORE_FILES = ['stores.csv'];

    const STAGE1 = ['config_default.json','config_vertical.json','config_secret.json','config.csv',
    'admin_roles.csv','admin_users.csv','customer_groups.csv','customer_attributes.csv','customers.csv','product_attributes.csv',
    'blocks.csv','categories.csv'];

    const STAGE2 = ['products.csv','products2.csv','msi_inventory.csv','upsells.csv','blocks.csv','dynamic_blocks.csv',
    'pages.csv','templates.csv','reviews.csv','b2b_companies.csv','b2b_shared_catalogs.csv',
    'b2b_shared_catalog_categories.csv','b2b_requisition_lists.csv','advanced_pricing.csv','orders.csv'];

    const B2B_REQUIRED_FILES = ['b2b_customers.csv','b2b_companies.csv','b2b_company_roles.csv','b2b_sales_reps.csv','b2b_teams.csv'];

    protected $redo=[];

    const SETTINGS = ['site_code'=>'base', 'store_code'=>'main_website_store','store_view_code'=>'default',
        'root_category' => 'Default Category', 'root_category_id' => '2'];

    /** @var array */
    private $settings;

    /** @var FixtureManager  */
    protected $fixtureManager;

    /** @var Csv  */
    protected $csvReader;

    /** @var DataTypes\Stores  */
    protected $storeInstall;

    /** @var DataTypes\ProductAttributes  */
    protected $productAttributesInstall;

    /** @var DataTypes\Categories  */
    protected $categoryInstall;

    /** @var DataTypes\Products  */
    protected $productInstall;

    /** @var DataTypes\DirectoryList  */
    protected $directoryList;

    /** @var DataTypes\Pages  */
    protected $pageInstall;

    /** @var DataTypes\Blocks  */
    protected $blockInstall;

    /** @var DataTypes\DynamicBlocks  */
    protected $dynamicBlockInstall;

    /** @var DataTypes\Configuration  */
    protected $configurationInstall;

    /** @var DataTypes\CustomerGroups  */
    protected $customerGroupInstall;

    /** @var DataTypes\CustomerAttributes  */
    protected $customerAttributeInstall;

    /** @var DataTypes\Customers  */
    protected $customerInstall;

    /** @var DataTypes\Reviews  */
    protected $reviewsInstall;

    /** @var Validate */
    protected $validate;

    /** @var DataTypes\Templates  */
    protected $templatesInstall;

    /** @var DataTypes\Upsells */
    protected $upsellsInstall;

    /** @var CopyMedia */
    protected $copyMedia;

    /** @var DataTypes\MsiInventory */
    protected $msiInventoryInstall;

    /** @var ObjectManagerInterface  */
    protected $objectManager;

    /** @var DataTypes\AdminUsers  */
    protected $adminUsersInstall;

    /** @var DataTypes\AdminRoles  */
    protected $adminRolesInstall;

    /** @var DriverInterface */
    protected $driverInterface;

    /** @var DataTypes\AdvancedPricing */
    protected $advancedPricingInstall;

    /** @var DataTypes\Orders */
    protected $orderInstall;

    /** @var Helper */
    protected $helper;

    /** @var InstallerInterfaceFactory */
    protected $dataInstallerInterface;

    /** @var InstallerRepositoryInterface */
    protected $dataInstallerRepository;

    /**
     * Process constructor.
     * @param Helper $helper
     * @param SampleDataContext $sampleDataContext
     * @param DataTypes\Stores $stores
     * @param DataTypes\ProductAttributes $productAttributes
     * @param DataTypes\Categories $categories
     * @param DataTypes\Products $products
     * @param DirectoryList $directoryList
     * @param DataTypes\Pages $pages
     * @param DataTypes\Blocks $blocks
     * @param DataTypes\DynamicBlocks $dynamicBlocks
     * @param DataTypes\Configuration $configuration
     * @param DataTypes\CustomerGroups $customerGroups
     * @param DataTypes\CustomerAttributes $customerAttributes
     * @param DataTypes\Customers $customers
     * @param DataTypes\Reviews $reviews
     * @param DataTypes\Templates $templates
     * @param Validate $validate
     * @param DataTypes\Upsells $upsells
     * @param CopyMedia $copyMedia
     * @param DataTypes\MsiInventory $msiInventory
     * @param ObjectManagerInterface $objectManager
     * @param DataTypes\AdminUsers $adminUsers
     * @param DataTypes\AdminRoles $adminRoles
     * @param DriverInterface $driverInterface
     * @param DataTypes\AdvancedPricing $advancedPricing
     * @param InstallerInterfaceFactory $dataInstallerInterface
     * @param InstallerRepositoryInterface $dataInstallerRepository
     */
    public function __construct(
        Helper $helper,
        SampleDataContext $sampleDataContext,
        DataTypes\Stores $stores,
        DataTypes\ProductAttributes $productAttributes,
        DataTypes\Categories $categories,
        DataTypes\Products $products,
        DirectoryList $directoryList,
        DataTypes\Pages $pages,
        DataTypes\Blocks $blocks,
        DataTypes\DynamicBlocks $dynamicBlocks,
        DataTypes\Configuration $configuration,
        DataTypes\CustomerGroups $customerGroups,
        DataTypes\CustomerAttributes $customerAttributes,
        DataTypes\Customers $customers,
        DataTypes\Reviews $reviews,
        DataTypes\Templates $templates,
        Validate $validate,
        DataTypes\Upsells $upsells,
        CopyMedia $copyMedia,
        DataTypes\MsiInventory $msiInventory,
        ObjectManagerInterface $objectManager,
        DataTypes\AdminUsers $adminUsers,
        DataTypes\AdminRoles $adminRoles,
        DriverInterface $driverInterface,
        DataTypes\AdvancedPricing $advancedPricing,
        DataTypes\Orders $orders,
        InstallerInterfaceFactory $dataInstallerInterface,
        InstallerRepositoryInterface $dataInstallerRepository
    ) {
        $this->helper = $helper;
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
        $this->templatesInstall = $templates;
        $this->validate = $validate;
        $this->upsellsInstall = $upsells;
        $this->copyMedia = $copyMedia;
        $this->msiInventoryInstall = $msiInventory;
        $this->objectManager = $objectManager;
        $this->adminUsersInstall = $adminUsers;
        $this->adminRolesInstall = $adminRoles;
        $this->driverInterface = $driverInterface;
        $this->advancedPricingInstall = $advancedPricing;
        $this->dataInstallerInterface = $dataInstallerInterface;
        $this->dataInstallerRepository = $dataInstallerRepository;
        $this->ordersInstall = $orders;
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

    public function loadFiles($fileSource, $fixtureDirectory = "fixtures", array $fileOrder = self::ALL_FILES, $reload = 0)
    {
        //TODO: Absolute path - need to copy files

        //bypass if data is already installed
        if ($this->isModuleInstalled($fileSource)==1 && $reload===0) {
            //output reload option if cli is used
            if ($this->isCli()) {
                $this->helper->printMessage($fileSource." has already been installed.  Add the -r option if you want to reinstall", "warning");
            }
            return true;
        } else {
            $this->registerModule($fileSource);
        }

        $fileCount = 0;
        if (count($fileOrder)==0) {
            $fileOrder=self::ALL_FILES;
        }
        if (count($fileOrder)==1) {
            //for setting files when start, stores and end is used in place of file list
            switch (strtolower($fileOrder[0])) {
                case "stores":
                    $fileOrder = self::STORE_FILES;
                    break;
                case "start":
                    $fileOrder = self::STAGE1;
                    break;
                case "end":
                    $fileOrder = self::STAGE2;
                    break;
            }
        }
        $filePath = $this->getDataPath($fileSource);
        $this->helper->printMessage("Copying Media", "info");
        $this->copyMedia->moveFiles($filePath);
        $this->settings = $this->getConfiguration($filePath, $fixtureDirectory);

        foreach ($fileOrder as $nextFile) {
            $fileName = $filePath . $fixtureDirectory . "/" . $nextFile;

            if (basename($fileName)==$nextFile && file_exists($fileName)) {
                $fileCount++;
                if (pathinfo($fileName, PATHINFO_EXTENSION) == 'json') {
                    $fileContent = $this->driverInterface->fileGetContents($fileName);
                } else {
                    $rows = $this->csvReader->getData($fileName);
                    $header = array_shift($rows);
                    //Remove hidden character Excel adds to the first cell of a document
                    $header = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $header);
                    //validate that number of elements in header and rows is equal
                    if (!$this->validate->validateCsvFile($header, $rows)) {
                        $this->helper->printMessage("Skipping File ".$nextFile.". The number of columns in the header does not match the number of column of data in one or more rows", "warning");
                        continue;
                    }
                }

                //determine path to module code for image import
                $modulePath = str_replace("/" . $fixtureDirectory . "/" . basename($fileName), "", $fileName);

                switch (basename($fileName)) {
                    case "stores.csv":
                        $this->helper->printMessage("Loading Stores", "info");
                        $this->processRows($rows, $header, $this->storeInstall);
                        break;

                    case "customers.csv":
                        $this->helper->printMessage("Loading Customers", "info");
                        $this->processFile($rows, $header, $this->customerInstall, '');
                        break;

                    case "product_attributes.csv":
                        $this->helper->printMessage("Loading Product Attributes", "info");
                        $this->processRows($rows, $header, $this->productAttributesInstall);
                        break;

                    case "categories.csv":
                        $this->helper->printMessage("Loading Categories", "info");
                        $this->processRows($rows, $header, $this->categoryInstall);
                        break;

                    case "products.csv":
                        $this->processFile($rows, $header, $this->productInstall, $modulePath);
                        break;

                    case "advanced_pricing.csv":
                        $this->helper->printMessage("Loading Advanced Pricing", "info");
                        $this->processFile($rows, $header, $this->advancedPricingInstall, $modulePath);
                        break;

                    case "pages.csv":
                        $this->helper->printMessage("Loading Pages", "info");
                        $this->processRows($rows, $header, $this->pageInstall);
                        break;

                    case "blocks.csv":
                        $this->helper->printMessage("Loading Blocks", "info");
                        $this->processRows($rows, $header, $this->blockInstall);
                        break;

                    case "dynamic_blocks.csv":
                        $this->helper->printMessage("Loading Dynamic Blocks", "info");
                        $this->processRows($rows, $header, $this->dynamicBlockInstall);
                        break;

                    case "default_config.json":
                        $this->helper->printMessage("Loading Default Config Json", "info");
                        ;
                        $this->processJson($fileContent, $this->configurationInstall);
                        break;
                    case "config_default.json":
                        $this->helper->printMessage("Loading Config Default Json", "info");
                        $this->processJson($fileContent, $this->configurationInstall);
                        break;

                    case "config_vertical.json":
                        $this->helper->printMessage("Loading Config Vertical Json", "info");
                        $this->processJson($fileContent, $this->configurationInstall);
                        break;

                    case "config_secret.json":
                        $this->helper->printMessage("Loading Config Secret Json", "info");
                        $this->processJson($fileContent, $this->configurationInstall);
                        break;

                    case "config.json":
                        $this->helper->printMessage("Loading Config Json", "info");
                        $this->processJson($fileContent, $this->configurationInstall);
                        break;

                    case "config.csv":
                        $this->helper->printMessage("Loading Config.csv", "info");
                        $this->processRows($rows, $header, $this->configurationInstall);
                        break;

                    case "customer_groups.csv":
                        $this->helper->printMessage("Loading Customer Groups", "info");
                        $this->processRows($rows, $header, $this->customerGroupInstall);
                        break;

                    case "customer_attributes.csv":
                        $this->helper->printMessage("Loading Customer Attributes", "info");
                        $this->processRows($rows, $header, $this->customerAttributeInstall);
                        break;

                    case "reviews.csv":
                        $this->helper->printMessage("Loading Reviews & Ratings", "info");
                        $this->processRows($rows, $header, $this->reviewsInstall);
                        break;

                    case "templates.csv":
                        $this->helper->printMessage("Loading Page Builder Templates", "info");
                        $this->processRows($rows, $header, $this->templatesInstall);
                        break;
                    case "upsells.csv":
                        $this->helper->printMessage("Loading Related Products, Cross Sells and Upsells", "info");
                        $this->processRows($rows, $header, $this->upsellsInstall);
                        break;

                    case "msi_inventory.csv":
                        $this->helper->printMessage("Loading Msi Inventory", "info");
                        $this->processFile($rows, $header, $this->msiInventoryInstall, $modulePath);
                        break;

                    case "admin_users.csv":
                        $this->helper->printMessage("Loading Admin Users", "info");
                        $this->processRows($rows, $header, $this->adminUsersInstall);
                        break;

                    case "admin_roles.csv":
                        $this->helper->printMessage("Loading Admin Roles", "info");
                        $this->processFile($rows, $header, $this->adminRolesInstall, $modulePath);
                        break;

                    case "b2b_companies.csv":
                        try {
                            ///catch if b2b module is installed by trying to instantiate company
                            $companiesInstall = $this->objectManager->create('MagentoEse\DataInstall\Model\DataTypes\Companies');
                            $this->helper->printMessage("Loading B2B Data", "header");
                            $this->processB2B($filePath, $fixtureDirectory);
                        } catch (\ReflectionException $e) {
                            $this->helper->printMessage("Companies cannot be loaded. Check that B2B module is included", "error");
                        }
                        break;

                    case "b2b_shared_catalogs.csv":
                        $this->helper->printMessage("Loading B2B Shared Catalogs", "info");
                        try {
                            $sharedCatalogsInstall = $this->objectManager->create('MagentoEse\DataInstall\Model\DataTypes\SharedCatalogs');
                            $this->processRows($rows, $header, $sharedCatalogsInstall);
                        } catch (\ReflectionException $e) {
                            $this->helper->printMessage("Shared Catalogs cannot be loaded. Check that B2B module is included", "error");
                        }
                        break;
                    case "b2b_shared_catalog_categories.csv":
                        $this->helper->printMessage("Loading Shared Catalog Categories", "info");
                        try {
                            $sharedCatalogCategoriesInstall = $this->objectManager->create('MagentoEse\DataInstall\Model\DataTypes\SharedCatalogCategories');
                            $this->processFile($rows, $header, $sharedCatalogCategoriesInstall, $modulePath);
                        } catch (\ReflectionException $e) {
                            $this->helper->printMessage("Shared Catalog Categories cannot be loaded. Check that B2B module is included", "error");
                        }
                        break;
                    case "b2b_requisition_lists.csv":
                        $this->helper->printMessage("Loading Requisition Lists", "info");
                        try {
                            $requisitionListInstall = $this->objectManager->create('MagentoEse\DataInstall\Model\DataTypes\RequisitionLists');
                            $this->processRows($rows, $header, $requisitionListInstall);

                        } catch (\ReflectionException $e) {
                            $this->helper->printMessage("Requsition Lists cannot be loaded. Check that B2B module is included", "error");
                        }
                        break;

                    case "orders.csv":
                        $this->helper->printMessage("Loading Orders", "info");
                        $this->processRows($rows, $header, $this->ordersInstall);
                        break;
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
        $this->settings = self::SETTINGS;
        $setupArray=$this->settings;
        $setupFile = $filePath . $fixtureDirectory . "/settings.csv";
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
        foreach (self::B2B_REQUIRED_FILES as $nextFile) {
            $fileName = $filePath . $fixtureDirectory . "/" . $nextFile;
            if (basename($fileName)==$nextFile && file_exists($fileName)) {
                $rows = $this->csvReader->getData($fileName);
                $header = array_shift($rows);
                //Remove hidden character Excel adds to the first cell of a document
                 $header = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $header);
                //validate that number of elements in header and rows is equal
                if (!$this->validate->validateCsvFile($header, $rows)) {
                    $this->helper->printMessage($nextFile." is invalid. The number of columns in the header does not match the number of column of data in one or more rows", "error");
                    break;
                    $stopFlag = 1;
                }
                $b2bData[$nextFile] = ['header'=>$header,'rows'=>$rows];
            } else {
                $this->helper->printMessage("You are missing the required B2B file - ".$nextFile.". B2B setup did not complete", "error");
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
            $this->processFile($b2bData['b2b_customers.csv']['rows'], $b2bData['b2b_customers.csv']['header'], $this->customerInstall, '');
            //load sales reps (admin user process)
            $this->helper->printMessage("Loading B2B Sales Reps", "info");
            $this->processRows($b2bData['b2b_sales_reps.csv']['rows'], $b2bData['b2b_sales_reps.csv']['header'], $this->adminUsersInstall);
            //create company (add on company admin from customers, and sales rep);

            $companiesData = $this->mergeCompanyData($companies, $customers, $salesReps);
            $this->helper->printMessage("Loading B2B Companies", "info");

            $companiesInstall = $this->objectManager->create('MagentoEse\DataInstall\Model\DataTypes\Companies');
            foreach ($companiesData as $companyData) {
                $companiesInstall->install($companyData, $this->settings);
            }

            //add company roles
            $this->helper->printMessage("Loading B2B Company Roles", "info");
            $companyRolesInstall = $this->objectManager->create('MagentoEse\DataInstall\Model\DataTypes\CompanyRoles');
            $this->processFile($b2bData['b2b_company_roles.csv']['rows'], $b2bData['b2b_company_roles.csv']['header'], $companyRolesInstall, '');
            //assign roles to customers
            $companyUserRolesInstall = $this->objectManager->create('MagentoEse\DataInstall\Model\DataTypes\CompanyUserRoles');
            $this->processRows($b2bData['b2b_customers.csv']['rows'], $b2bData['b2b_customers.csv']['header'], $companyUserRolesInstall);

            $this->helper->printMessage("Loading B2B Teams and Company Structure", "info");
            //create company structure
            $companyTeamsInstall = $this->objectManager->create('MagentoEse\DataInstall\Model\DataTypes\Teams');
            $this->processRows($b2bData['b2b_teams.csv']['rows'], $b2bData['b2b_teams.csv']['header'], $companyTeamsInstall);
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

        // Get the class that is asking for who awoke it
        $class = $trace[1]['class'];

        // +1 to i cos we have to account for calling this function
        for ($i=1; $i<count($trace); $i++) {
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
        if ($this->getCallingClass() === 'MagentoEse\DataInstall\Console\Command\Install') {
            return true;
        } else {
            return false;
        }
    }
}
