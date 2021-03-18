<?php

/**
 * Copyright © Magento. All rights reserved.
 */
namespace MagentoEse\DataInstall\Model;

use ArgumentSequence\ParentClass;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\File\Csv;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Setup\SampleData\Context as SampleDataContext;
use Magento\Framework\Setup\SampleData\FixtureManager;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Filesystem\DriverInterface;
use MagentoEse\DataInstall\Api\Data\InstallerInterface;
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
      * @param DriverInterface $driverInterface
      * @param AdvancedPricing $advancedPricing
      * @param Orders $orders
      * @param InstallerInterfaceFactory $$dataInstallerInterface;
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
        //Orders $orders,
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
       // $this->ordersInstall = $orders;
    }

    /**
     * @param $moduleName
     * @param string $fixtureDirectory
     * @param array|string[] $fileOrder
     * @throws LocalizedException
     */

    
    public function loadFiles($fileSource, $fixtureDirectory = "fixtures", array $fileOrder=[])
    {   
        $moduleName  = $fileSource;
        $this->copyMedia->moveFiles($moduleName);
        
        $this->settings = $this->getConfiguration($moduleName, $fixtureDirectory);

        $fromName = $this->fixtureManager->getFixture($moduleName . "::" . "media/" . $nextDirectory['from']);
        $toName = $this->directoryList->getRoot()."/".$nextDirectory['to'];

        //if fileOrder is defined then skip the determining load type
        if(count($fileOrder)==0){
            //for backwards compatibility, load type default case is full in case module name is still being passed in
            switch (strtolower($fileSource)) {
                case "stores":
                    $fileOrder = self::STORE_FILES;
                    break;
                case "start":
                    $fileOrder = self::STAGE1;
                    $this->helper->printMessage("Copying media files","info");
                    $this->copyMedia->moveFiles($moduleName);
                    break;
                case "end":
                    $fileOrder = self::STAGE2;
                    break;
                default:
                    $fileOrder = self::ALL_FILES;
                    $this->helper->printMessage("Copying media files","info");
                    $this->copyMedia->moveFiles($moduleName);
                }
        }
        
        $fileOrder = self::ALL_FILES;
        //see if we need to do any work for recurring, if not clear out the file list to bypass
        if($this->isRecurring()){
            if($this->isModuleInstalled($moduleName)){
                $fileOrder=[];
            }
        } 
      
   
        foreach ($fileOrder as $nextFile) {
            $fileName = $this->fixtureManager->getFixture($moduleName . "::" . $fixtureDirectory . "/" . $nextFile);
            if (basename($fileName)==$nextFile && file_exists($fileName)) {
                if (pathinfo($fileName, PATHINFO_EXTENSION) == 'json') {
                    $fileContent = $this->driverInterface->fileGetContents($fileName);
                } else {
                    $rows = $this->csvReader->getData($fileName);
                    $header = array_shift($rows);
                    //Remove hidden character Excel adds to the first cell of a document
                    $header = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $header);
                    //validate that number of elements in header and rows is equal
                    if (!$this->validate->validateCsvFile($header, $rows)) {
                        $this->helper->printMessage("Skipping File ".$nextFile.". The number of columns in the header does not match the number of column of data in one or more rows","warning");
                        continue;
                    }
                }

                //determine path to module code for image import
                $modulePath = str_replace("/" . $fixtureDirectory . "/" . basename($fileName), "", $fileName);

                switch (basename($fileName)) {
                    case "stores.csv":
                        $this->helper->printMessage("Loading Stores","info");
                        $this->processRows($rows, $header, $this->storeInstall);
                        break;

                    case "customers.csv":
                        $this->helper->printMessage("Loading Customers","info");
                        $this->processFile($rows, $header, $this->customerInstall, '');
                        break;

                    case "product_attributes.csv":
                        $this->helper->printMessage("Loading Product Attributes","info");
                        $this->processRows($rows, $header, $this->productAttributesInstall);
                        break;

                    case "categories.csv":
                        $this->helper->printMessage("Loading Categories","info");
                        $this->processRows($rows, $header, $this->categoryInstall);
                        break;

                    case "products.csv":
                        $this->processFile($rows, $header, $this->productInstall, $modulePath);
                        break;
                        
                    case "advanced_pricing.csv":
                        $this->helper->printMessage("Loading Advanced Pricing","info");
                        $this->processFile($rows, $header, $this->advancedPricingInstall, $modulePath);
                        break;
                        

                    case "pages.csv":
                        $this->helper->printMessage("Loading Pages","info");
                        $this->processRows($rows, $header, $this->pageInstall);
                        break;

                    case "blocks.csv":
                        $this->helper->printMessage("Loading Blocks","info");
                        $this->processRows($rows, $header, $this->blockInstall);
                        break;

                    case "dynamic_blocks.csv":
                        $this->helper->printMessage("Loading Dynamic Blocks","info");
                        $this->processRows($rows, $header, $this->dynamicBlockInstall);
                        break;

                    case "default_config.json":
                        $this->helper->printMessage("Loading Default Config Json","info");;
                        $this->processJson($fileContent, $this->configurationInstall);
                        break;
                    case "config_default.json":
                        $this->helper->printMessage("Loading Config Default Json","info");
                        $this->processJson($fileContent, $this->configurationInstall);
                        break;

                    case "config_vertical.json":
                        $this->helper->printMessage("Loading Config Vertical Json","info");
                        $this->processJson($fileContent, $this->configurationInstall);
                        break;

                    case "config_secret.json":
                        $this->helper->printMessage("Loading Config Secret Json","info");
                        $this->processJson($fileContent, $this->configurationInstall);
                        break;

                    case "config.json":
                        $this->helper->printMessage("Loading Config Json","info");
                        $this->processJson($fileContent, $this->configurationInstall);
                        break;

                    case "config.csv":
                        $this->helper->printMessage("Loading Config.csv","info");
                        $this->processRows($rows, $header, $this->configurationInstall);
                        break;

                    case "customer_groups.csv":
                        $this->helper->printMessage("Loading Customer Groups","info");
                        $this->processRows($rows, $header, $this->customerGroupInstall);
                        break;

                    case "customer_attributes.csv":
                        $this->helper->printMessage("Loading Customer Attributes","info");
                        $this->processRows($rows, $header, $this->customerAttributeInstall);
                        break;

                    case "reviews.csv":
                        $this->helper->printMessage("Loading Reviews & Ratings","info");
                        $this->processRows($rows, $header, $this->reviewsInstall);
                        break;

                    case "templates.csv":
                        $this->helper->printMessage("Loading Page Builder Templates","info");
                        $this->processRows($rows, $header, $this->templatesInstall);
                        break;
                    case "upsells.csv":
                        $this->helper->printMessage("Loading Related Proudcts, Cross Sells and Upsells","info");
                        $this->processRows($rows, $header, $this->upsellsInstall);
                        break;

                    case "msi_inventory.csv":
                        $this->helper->printMessage("Loading Msi Inventory","info");
                        $this->processFile($rows, $header, $this->msiInventoryInstall, $modulePath);
                        break;

                    case "admin_users.csv":
                        $this->helper->printMessage("Loading Admin Users","info");
                        $this->processRows($rows, $header, $this->adminUsersInstall);
                        break;

                    case "admin_roles.csv":
                        $this->helper->printMessage("Loading Admin Roles","info");
                        $this->processFile($rows, $header, $this->adminRolesInstall, $modulePath);
                        break;

                    case "b2b_companies.csv":
                        $this->helper->printMessage("Loading B2B Data","header");
                        $this->processB2B($moduleName, $fixtureDirectory);
                        break;

                    case "b2b_shared_catalogs.csv":
                        $this->helper->printMessage("Loading B2B Shared Catalogs","info");
                        $sharedCatalogsInstall = $this->objectManager->create('MagentoEse\DataInstall\Model\DataTypes\SharedCatalogs');
                        $this->processRows($rows, $header, $sharedCatalogsInstall);
                        break;
                    case "b2b_shared_catalog_categories.csv":
                        $this->helper->printMessage("Loading Shared Catalog Categories","info");
                        $sharedCatalogCategoriesInstall = $this->objectManager->create('MagentoEse\DataInstall\Model\DataTypes\SharedCatalogCategories');
                        $this->processFile($rows, $header, $sharedCatalogCategoriesInstall, $modulePath);
                        break;
                    case "b2b_requisition_lists.csv":
                        $this->helper->printMessage("Loading Requisition Lists","info");
                        $requisitionListInstall = $this->objectManager->create('MagentoEse\DataInstall\Model\DataTypes\RequisitionLists');
                        $this->processRows($rows, $header, $requisitionListInstall);
                        break;

                    case "orders.csv":
                        $this->helper->printMessage("Loading Orders","info");
                        $this->processRows($rows, $header, $this->ordersInstall);
                        break;
                }
            }
        }
        
        $this->processRedos();
        //register module status
        if(!$this->isRecurring()){
            $this->registerModule($moduleName);
        }else{
            $this->setModuleInstalled($moduleName);
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
                    " was not fully successful, likely due to a dependency on other sample data that doesnt exist"
                ,"error");
            }
        }
    }

    /**
     * @param string $classname
     * @return false|int|string
     */
    private function getClassName(string $className)
    {
        if ($pos = strrpos($className, '\\')) {
            return substr($className, $pos + 1);
        }

        return $pos;
    }

    private function isRecurring(){
        $callingClass = $this->getCallingClass();
        $arr = explode("\\",$callingClass);
        $className = end($arr);
        if($className=="RecurringData"){
            return true;
        }else{
            return false;
        }
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
        $this->settings = self::SETTINGS;
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
        $stopFlag = 0;
        //do we have all the files we need
        foreach (self::B2B_REQUIRED_FILES as $nextFile) {
            $fileName = $this->fixtureManager->getFixture($moduleName . "::" . $fixtureDirectory . "/" . $nextFile);
            if (basename($fileName)==$nextFile && file_exists($fileName)) {
                $rows = $this->csvReader->getData($fileName);
                $header = array_shift($rows);
                //Remove hidden character Excel adds to the first cell of a document
                 $header = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $header);
                //validate that number of elements in header and rows is equal
                if (!$this->validate->validateCsvFile($header, $rows)) {
                    $this->helper->printMessage($nextFile." is invalid. The number of columns in the header does not match the number of column of data in one or more rows","error");
                    break;
                    $stopFlag = 1;
                }
                $b2bData[$nextFile] = ['header'=>$header,'rows'=>$rows];
            } else {
                $this->helper->printMessage("You are missing the required B2B file - ".$nextFile.". B2B setup did not complete","error");
                $stopFlag = 1;
                break;
            }
        }
        if($stopFlag == 0){
            //validate referential integrity of the data
            if (!$this->validate->validateB2bData($b2bData)) {
                $this->helper->printMessage("Bad Data","error");
                    ///probaby need to throw an error to roll back everything
            }
            $salesReps = $this->buildB2bDataArrays($b2bData['b2b_sales_reps.csv']);
            $companies = $this->buildB2bDataArrays($b2bData['b2b_companies.csv']);
            $customers = $this->buildB2bDataArrays($b2bData['b2b_customers.csv']);

            //load customers (normal process)
            $this->helper->printMessage("Loading B2B Customers","info");
            $this->processFile($b2bData['b2b_customers.csv']['rows'], $b2bData['b2b_customers.csv']['header'], $this->customerInstall, '');
            //load sales reps (admin user process)
            $this->helper->printMessage("Loading B2B Sales Reps","info");
            $this->processRows($b2bData['b2b_sales_reps.csv']['rows'], $b2bData['b2b_sales_reps.csv']['header'], $this->adminUsersInstall);
            //create company (add on company admin from customers, and sales rep);

            $companiesData = $this->mergeCompanyData($companies, $customers, $salesReps);
            $this->helper->printMessage("Loading B2B Companies","info");
            $companiesInstall = $this->objectManager->create('MagentoEse\DataInstall\Model\DataTypes\Companies');
            foreach ($companiesData as $companyData) {
                $companiesInstall->install($companyData, $this->settings);
            }
            
            //add company roles
            $this->helper->printMessage("Loading B2B Company Roles","info");
            $companyRolesInstall = $this->objectManager->create('MagentoEse\DataInstall\Model\DataTypes\CompanyRoles');
            $this->processFile($b2bData['b2b_company_roles.csv']['rows'], $b2bData['b2b_company_roles.csv']['header'], $companyRolesInstall, '');
            //assign roles to customers
            $companyUserRolesInstall = $this->objectManager->create('MagentoEse\DataInstall\Model\DataTypes\CompanyUserRoles');
            $this->processRows($b2bData['b2b_customers.csv']['rows'], $b2bData['b2b_customers.csv']['header'], $companyUserRolesInstall);

            $this->helper->printMessage("Loading B2B Teams and Company Structure","info");
            //create company structure
            $companyTeamsInstall = $this->objectManager->create('MagentoEse\DataInstall\Model\DataTypes\Teams');
            $this->processRows($b2bData['b2b_teams.csv']['rows'], $b2bData['b2b_teams.csv']['header'], $companyTeamsInstall);
        }
        
    }
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

    private function getModuleName(){
        return $this->helper->getModuleName($this->getCallingClass());
    }

    private function getCallingClass() {

        //get the trace
        $trace = debug_backtrace();
    
        // Get the class that is asking for who awoke it
        $class = $trace[1]['class'];
    
        // +1 to i cos we have to account for calling this function
        for ( $i=1; $i<count( $trace ); $i++ ) {
            if ( isset( $trace[$i] ) ) // is it set?
                 if ( $class != $trace[$i]['class'] ) // is it a different class
                     return $trace[$i]['class'];
        }
    }

    private function registerModule($moduleName){
        $tracker = $this->dataInstallerRepository->getByModuleName($moduleName);
        if($tracker->getId()){
            $this->dataInstallerRepository->delete($tracker);
            $tracker = $this->dataInstallerInterface->create();
        }
        $tracker->setModuleName($moduleName);
        //$this->dataInstallerRepository->delete($tracker);
        $tracker->setIsInstalled(0);
        $this->dataInstallerRepository->save($tracker);
        return $tracker->getId();
    }

    private function isModuleInstalled($moduleName){
        $tracker = $this->dataInstallerInterface->create();
        $tracker = $this->dataInstallerRepository->getByModuleName($moduleName);
        $f=$tracker->isInstalled();
        return $tracker->isInstalled();
    }
    private function setModuleInstalled($moduleName){
        $tracker = $this->dataInstallerInterface->create();
        $tracker = $this->dataInstallerRepository->getByModuleName($moduleName);
        $tracker->setIsInstalled(1);
        $this->dataInstallerRepository->save($tracker);
    }
    
}
