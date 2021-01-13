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
use Magento\Framework\ObjectManagerInterface;

class Process
{
    const FILE_ORDER = ['stores.csv','config_default.json','config_vertical.json','config_secret.json','config.csv',
    'admin_roles.csv','admin_users.csv','customer_groups.csv','customer_attributes.csv','customers.csv','product_attributes.csv',
    'blocks.csv','categories.csv','products.csv','msi_inventory.csv','upsells.csv','blocks.csv','dynamic_blocks.csv',
    'pages.csv','templates.csv','reviews.csv','b2b_companies.csv'];

    const B2B_FILES = ['b2b_customers.csv','b2b_companies.csv','b2b_company_roles.csv','b2b_salesreps.csv','b2b_teams.csv'];

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

    /** @var Validate */
    protected $validate;

    /** @var Templates  */
    protected $templatesInstall;

    /** @var Upsells */
    protected $upsellsInstall;

    /** @var CopyMedia */
    protected $copyMedia;

    /** @var MsiInventory */
    protected $msiInventoryInstall;

    /** @var ObjectManagerInterface  */
    protected $objectManager;

     /** @var AdminUsers  */
     protected $adminUsersInstall;

     /** @var AdminRoles  */
     protected $adminRolesInstall;

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
      * @param Templates $templates
      * @param Validate $validate
      * @param Upsells $upsells
      * @param CopyMedia $copyMedia
      * @param MsiInventory $msiInventory
      * @param ObjectManagerInterface $objectManager
      * @param AdminUsers $adminUsers
      * @param AdminRoles $adminRoles
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
        Reviews $reviews,
        Templates $templates,
        Validate $validate,
        Upsells $upsells,
        CopyMedia $copyMedia,
        MsiInventory $msiInventory,
        ObjectManagerInterface $objectManager,
        AdminUsers $adminUsers,
        AdminRoles $adminRoles
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
        $this->templatesInstall = $templates;
        $this->validate = $validate;
        $this->upsellsInstall = $upsells;
        $this->copyMedia = $copyMedia;
        $this->msiInventoryInstall = $msiInventory;
        $this->objectManager = $objectManager;
        $this->adminUsersInstall = $adminUsers;
        $this->adminRolesInstall = $adminRoles;
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
        print_r("Copying media files\n");

        $this->copyMedia->moveFiles($moduleName);

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
                    //validate that number of elements in header and rows is equal
                    if (!$this->validate->validateCsvFile($header, $rows)) {
                        print_r("Skipping File ".$nextFile.". The number of columns in the header does not match the number of column of data in one or more rows\n");
                        continue;
                    }
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

                    case "config_secret.json":
                        print_r("loading Config Secretd Json\n");
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

                    case "templates.csv":
                        print_r("loading Page Builder Templates\n");
                        $this->processRows($rows, $header, $this->templatesInstall);
                        break;
                    case "upsells.csv":
                        print_r("loading Related Proudcts, Cross Sells and Upsells\n");
                        $this->processRows($rows, $header, $this->upsellsInstall);
                        break;

                    case "msi_inventory.csv":
                        print_r("Loading Msi Inventory\n");
                        $this->processFile($rows, $header, $this->msiInventoryInstall, $modulePath);
                        break;

                    case "admin_users.csv":
                        print_r("Loading Admin Users\n");
                        $this->processRows($rows, $header, $this->adminUsersInstall);
                        break;

                    case "admin_roles.csv":
                        print_r("Loading Admin Roles\n");
                        $this->processFile($rows, $header, $this->adminRolesInstall, $modulePath);
                        break;

                    case "b2b_companies.csv":
                        print_r("Loading B2B\n");
                        $this->processB2B($moduleName, $fixtureDirectory);
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
        $valid = false;
        $setupArray=$this->settings;
        $setupFile = $this->fixtureManager->getFixture($moduleName . "::" . $fixtureDirectory . "/settings.csv");
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

    private function processB2B($moduleName, $fixtureDirectory)
    {
        $b2bData = [];
        //do we have all the files we need
        foreach (self::B2B_FILES as $nextFile) {
            $fileName = $this->fixtureManager->getFixture($moduleName . "::" . $fixtureDirectory . "/" . $nextFile);
            if (basename($fileName)==$nextFile && file_exists($fileName)) {
                $rows = $this->csvReader->getData($fileName);
                $header = array_shift($rows);
                //Remove hidden character Excel adds to the first cell of a document
                 $header = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $header);
                //validate that number of elements in header and rows is equal
                if (!$this->validate->validateCsvFile($header, $rows)) {
                    print_r($nextFile." is invalid. The number of columns in the header does not match the number of column of data in one or more rows\n");
                    break;
                }
                $b2bData[$nextFile] = ['header'=>$header,'rows'=>$rows];
            } else {
                print_r("You are missing the required B2B file - ".$nextFile."\n");
                break;
            }
        }
        //validate referential integrity of the data
        if (!$this->validate->validateB2bData($b2bData)) {
            print_r("Bad Data");
                ///probaby need to throw an error to roll back everything
        }
        $salesReps = $this->buildB2bDataArrays($b2bData['b2b_salesreps.csv']);
        $companies = $this->buildB2bDataArrays($b2bData['b2b_companies.csv']);
        $customers = $this->buildB2bDataArrays($b2bData['b2b_customers.csv']);

        //load customers (normal process)
        print_r("Loading B2B Customers\n");
        $this->processFile($b2bData['b2b_customers.csv']['rows'], $b2bData['b2b_customers.csv']['header'], $this->customerInstall, '');
        //load sales reps (admin user process)
        print_r("Loading B2B Sales Reps\n");
        $this->processRows($b2bData['b2b_salesreps.csv']['rows'], $b2bData['b2b_salesreps.csv']['header'], $this->adminUsersInstall);
        //create company (add on company admin from customers, and sales rep);

        $companiesData = $this->mergeCompanyData($companies, $customers, $salesReps);
        print_r("Loading B2B Companies\n");
        $companiesInstall = $this->objectManager->create('MagentoEse\DataInstall\Model\Companies');
        foreach ($companiesData as $companyData) {
            $companiesInstall->install($companyData, $this->settings);
        }
        
        //add company roles
        print_r("Loading B2B Company Roles\n");
        $companyRolesInstall = $this->objectManager->create('MagentoEse\DataInstall\Model\CompanyRoles');
        $this->processFile($b2bData['b2b_company_roles.csv']['rows'], $b2bData['b2b_company_roles.csv']['header'], $companyRolesInstall, '');
        //assign roles to customers
        $companyUserRolesInstall = $this->objectManager->create('MagentoEse\DataInstall\Model\CompanyUserRoles');
        $this->processRows($b2bData['b2b_customers.csv']['rows'], $b2bData['b2b_customers.csv']['header'], $companyUserRolesInstall);

        print_r("Loading B2B Teams and Company Structure\n");
        //create company structure
        $companyTeamsInstall = $this->objectManager->create('MagentoEse\DataInstall\Model\Teams');
        $this->processRows($b2bData['b2b_teams.csv']['rows'], $b2bData['b2b_teams.csv']['header'], $companyTeamsInstall);

        //$t=$r;
    }
    //copy data that may be needed from one array into another
    private function mergeCompanyData($companies, $customers, $salesReps)
    {
        $revisedCompany = [];
        foreach ($companies as $company) {
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

    private function matchKeyValue($array, $keyToFind, $valueToFind, $keyToReturn)
    {
        foreach ($array as $key => $value) {
            if ($key==$keyToFind && $value==$valueToFind) {
                return [$key=>$value];
            }
        }
    }
}
