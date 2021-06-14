<?php
/**
 * Copyright © Adobe. All rights reserved.
 */

//TODO:Support for multiple addresses

namespace MagentoEse\DataInstall\Model\DataTypes;

use Exception;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Api\Data\RegionInterface;
use Magento\Customer\Api\Data\RegionInterfaceFactory;
use Magento\Directory\Model\CountryFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use FireGento\FastSimpleImport\Model\ImporterFactory as Importer;
use Magento\Framework\Exception\NoSuchEntityException;
use MagentoEse\DataInstall\Helper\Helper;

class Customers
{
    /** @var array */
    protected $settings;

    /** @var array $autoFillElements */
    protected $autoFillElements;

    /** @var array $customerDataAddress */
    protected $customerDataAddress;


    /** @var CustomerGroups  */
    protected $customerGroups;

    /** @var Stores  */
    protected $stores;

    /** @var AccountManagementInterface  */
    protected $accountManagement;

    /** @var AddressInterfaceFactory  */
    protected $addressInterfaceFactory;

    /** @var AddressRepositoryInterface  */
    protected $addressRespository;

    /** @var CustomerInterfaceFactory  */
    protected $customerInterfaceFactory;

    /** @var RegionInterfaceFactory  */
    protected $regionInterfaceFactory;

    /** @var DataObjectHelper */
    protected $dataObjectHelper;

    /** @var State  */
    protected $appState;

    /** @var Configuration  */
    protected $configuration;

    /** @var CountryFactory  */
    protected $countryFactory;

    /** @var Importer  */
    protected $importer;

     /** @var Helper  */
     protected $helper;
    /** @var CustomerRepositoryInterface */
     protected $customerRepositoryInterface;

     protected $importUnsafeColumns=['company_admin', 'role', 'add_to_autofill'];

    /**
     * Customers constructor.
     * @param CustomerGroups $customerGroups
     * @param Stores $stores
     * @param AccountManagementInterface $accountManagement
     * @param AddressInterfaceFactory $addressInterfaceFactory
     * @param CustomerInterfaceFactory $customerInterfaceFactory
     * @param RegionInterfaceFactory $regionInterfaceFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param State $appState
     * @param Configuration $configuration
     * @param CountryFactory $countryFactory
     */
    public function __construct(
        CustomerGroups $customerGroups,
        Stores $stores,
        AccountManagementInterface $accountManagement,
        AddressInterfaceFactory $addressInterfaceFactory,
        CustomerInterfaceFactory $customerInterfaceFactory,
        RegionInterfaceFactory $regionInterfaceFactory,
        DataObjectHelper $dataObjectHelper,
        State $appState,
        Configuration $configuration,
        CountryFactory $countryFactory,
        Importer $importer,
        Helper $helper,
        CustomerRepositoryInterface $customerRepositoryInterface,
        AddressRepositoryInterface $addressRepositoryInterface
    ) {
        $this->customerGroups=$customerGroups;
        $this->stores = $stores;
        $this->accountManagement = $accountManagement;
        $this->addressInterfaceFactory = $addressInterfaceFactory;
        $this->customerInterfaceFactory = $customerInterfaceFactory;
        $this->regionInterfaceFactory = $regionInterfaceFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->appState = $appState;
        $this->configuration=$configuration;
        $this->countryFactory = $countryFactory;
        $this->importer = $importer;
        $this->helper = $helper;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->addressRespository = $addressRepositoryInterface;
    }
    //TODO: validate input fields
    //TODO: add store and website codes if they dont exist
    //TODO: Change website to _website
    /**
     * @param array $rows
     * @param array $header
     * @param string $modulePath
     * @param array $settings
     * @return bool
     * @throws LocalizedException
     */
    public function install(array $rows, array $header, string $modulePath, array $settings)
    {
        $this->settings = $settings;

        if (!empty($settings['product_validation_strategy'])) {
            $productValidationStrategy = $settings['product_validation_strategy'];
        } else {
            $productValidationStrategy =  'validation-skip-errors';
        }

        foreach ($rows as $row) {
            $customerArray[] = array_combine($header, $row);
        }
        $cleanCustomerArray = $this->cleanDataForImport($customerArray);
        $this->import($cleanCustomerArray,$productValidationStrategy);
        //return true;
        $startingElement = 1;
        foreach ($rows as $row) {
            $data = [];
            foreach ($row as $key => $value) {
                $data[$header[$key]] = $value;
            }

            $row = $data;
            ///catch if customer doesnt exist
            try{
                $customer = $this->customerRepositoryInterface->get($row['email']);
            
            if(!empty($row['store_view_code'])){
                $customer->setCreatedIn($this->stores->getViewName($row['store_view_code']));
            } else{
                $customer->setCreatedIn($this->stores->getViewName($this->settings['store_view_code']));
            }
            $this->appState->emulateAreaCode(
                'frontend',
                [$this->customerRepositoryInterface, 'save'],
                [$customer]
            );

           // $this->customerRepositoryInterface->save($customer);
            
            ///set addresses as default
            $addresses = $customer->getAddresses();
            $addressesToKeep=[];
            foreach($addresses as $address){
                $removed = false;
                foreach($addressesToKeep as $checking){
                    if($checking->getStreet()==$address->getStreet()){
                        //remove duplicate
                        try{
                            $this->addressRespository->delete($address);
                        }catch(Exception $e){
                            // There is an edge case on a data reload that if a reindex doesnt occur after the original
                            // load, the reload can fail deleting an address.
                        }
                        
                        $removed = true;
                        break;
                    }
                }
                if(!$removed){
                    $address->setIsDefaultBilling(true);
                    $address->setIsDefaultShipping(true);
                    $this->addressRespository->save($address);
                    $addressesToKeep[]=$address;
                }
            }

            //add to autofille
            if (!empty($row['add_to_autofill']) && $row['add_to_autofill'] == 'Y') {
                $startingElement = $this->addToAutofill($row, $startingElement);
            }
            }catch(NoSuchEntityException $e){
                $this->helper->printMessage("Customer ". $row['email']." wasn't imported","error");
            }
           
        }

        return true;
    }

    private function cleanDataForImport($customerArray){
        //remove columns used for other purposes, but throw errors on import
        $newCustomerArray=[];
        foreach($customerArray as $customer){
            foreach($this->importUnsafeColumns as $column){
                unset($customer[$column]);
            }
            //change website column if incorrect
            if (!empty($customer['site_code'])) {
                $customer['_website']=$customer['site_code'];
                unset($customer['site_code']);
            }
            if (!empty($customer['website']) && $customer['website']!='') {
                $customer['_website']=$customer['website'];
                unset($customer['website']);
            }

            if(empty($customer['_website'])){
                $customer['_website']=$this->settings['site_code'];
            }
            //add or change store code
            if (!empty($customer['store_view_code'])) {
                $customer['_store']=$customer['store_view_code'];
                unset($customer['store_view_code']);
            }
            if(empty($customer['_store'])){
                $customer['_store']=$this->settings['store_view_code'];
            } 
           
            //add group_id column if it doesnt exist
            if(empty($customer['group_id'])){
                $customer['group_id']=1;
            }
            //add _address_firstname, _address_lastname if not present
            if(empty($customer['_address_firstname'])){
                $customer['_address_firstname']=$customer['firstname'];
            }
            if(empty($customer['_address_lastname'])){
                $customer['_address_lastname']=$customer['lastname'];
            }
            $newCustomerArray[]=$customer;
        }
        return $newCustomerArray;
    }

    private function import($customerArray, $productValidationStrategy)
    {
        $importerModel = $this->importer->create();
        $importerModel->setEntityCode('customer_composite');
        $importerModel->setValidationStrategy($productValidationStrategy);
        if ($productValidationStrategy == 'validation-stop-on-errors') {
            $importerModel->setAllowedErrorCount(1);
        } else {
            $importerModel->setAllowedErrorCount(100);
        }
        try {
            $importerModel->processImport($customerArray);
        } catch (\Exception $e) {
            $this->helper->printMessage($e->getMessage());
        }

        $this->helper->printMessage($importerModel->getLogTrace());
        $this->helper->printMessage($importerModel->getErrorMessages());

        unset($importerModel);
    }

    /**
     * @param array $row
     * @return mixed
     * @throws LocalizedException
     */
    protected function convertAddresses(array $row)
    {
        $customerData['address'] = $this->convertRowData($row, $this->getDefaultCustomerAddress());
        $customerData['address']['region_id'] = $this->getRegionId($customerData['address']);
        //$customerData['address']['country_id'] = $customerData['address']['country'];
        $address = $customerData['address'];
        $regionData = [
            RegionInterface::REGION_ID => $address['region_id'],
            RegionInterface::REGION => !empty($address['region']) ? $address['region'] : null,
            RegionInterface::REGION_CODE => !empty($address['region_code']) ? $address['region_code'] : null,
        ];
        $region = $this->regionInterfaceFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $region,
            $regionData,
            '\Magento\Customer\Api\Data\RegionInterface'
        );

        $addresses = $this->addressInterfaceFactory->create();
        unset($customerData['address']['region']);
        $this->dataObjectHelper->populateWithArray(
            $addresses,
            $customerData['address'],
            '\Magento\Customer\Api\Data\AddressInterface'
        );
        $addresses->setRegion($region)
            ->setIsDefaultBilling(true)
            ->setIsDefaultShipping(true);

        return $addresses;
    }

    /**
     * @param array $row
     * @param int $startingElement
     * @return int
     */
    protected function addToAutofill(array $row, int $startingElement)
    {
        $pathPrefix = 'magentoese_autofill/persona_';
        $elementCount = 17;

        //turn on autofill
        $this->configuration->saveConfig('magentoese_autofill/general/enable_autofill', '1', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);

        //find next empty autofill
        for ($x=$startingElement; $x <= $elementCount; $x++) {
            //echo "---".$pathPrefix.$x.'/email_value'.'___'.$this->configuration->getConfig($pathPrefix.$x.'/email_value',ScopeConfigInterface::SCOPE_TYPE_DEFAULT,'default')."---";
            if (!$this->configuration->getConfig($pathPrefix . $x . '/email_value', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 'default')) {
                $this->configuration->saveConfig($pathPrefix . $x . '/cc_month', '02', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);
                $this->configuration->saveConfig($pathPrefix . $x . '/cc_year', '2029', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);
                $this->configuration->saveConfig($pathPrefix . $x . '/cc_number', '4111111111111111', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);
                $this->configuration->saveConfig($pathPrefix . $x . '/cc_type', 'VI', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);
                $this->configuration->saveConfig($pathPrefix . $x . '/cc_verification_number', '123', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);
                $this->configuration->saveConfig($pathPrefix . $x . '/enable', '1', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);
                $this->configuration->saveConfig($pathPrefix . $x . '/address_value', $row['street'], ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);
                $this->configuration->saveConfig($pathPrefix . $x . '/city_value', $row['city'], ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);
                $this->configuration->saveConfig($pathPrefix . $x . '/country_value', $row['country_id'], ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);
                if (!empty($row['company'])) {
                    $this->configuration->saveConfig($pathPrefix . $x . '/company_value', $row['company'], ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);
                }

                $this->configuration->saveConfig($pathPrefix . $x . '/email_value', $row['email'], ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);
                if (!empty($row['fax'])) {
                    $this->configuration->saveConfig($pathPrefix . $x . '/fax_value', $row['fax'], ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);
                }

                $this->configuration->saveConfig($pathPrefix . $x . '/firstname_value', $row['firstname'], ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);
                $this->configuration->saveConfig($pathPrefix . $x . '/label', $row['firstname'] . ' ' . $row['lastname'], ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);
                $this->configuration->saveConfig($pathPrefix . $x . '/lastname_value', $row['lastname'], ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);
                $this->configuration->saveConfig($pathPrefix . $x . '/password_value', $row['password'], ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);
                $this->configuration->saveConfig($pathPrefix . $x . '/state_value', $this->getRegionId($row), ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);
                $this->configuration->saveConfig($pathPrefix . $x . '/telephone_value', $row['telephone'], ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);
                $this->configuration->saveConfig($pathPrefix . $x . '/zip_value', $row['postcode'], ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);
                return $x + 1;
            }
        }
    }

    /**
     * @return array
     * @throws LocalizedException
     */
    protected function getDefaultCustomerProfile()
    {
        $customerDataProfile = [
                'website_id' => $this->stores->getStoreId($this->settings['site_code']),
                ' _id' => $this->customerGroups->getCustomerGroupId($this->customerGroups->getDefaultCustomerGroup()),
                'disable_auto_group_change' => '0',
                'prefix',
                'firstname' => '',
                'middlename' => '',
                'lastname' => '',
                'suffix' => '',
                'email' => '',
                'dob' => '',
                'taxvat' => '',
                'gender' => '',
                'password' => '',
                'company' => '',
                'confirmation' => false,
                'sendemail' => false,
            ];
        return $customerDataProfile;
    }

    /**
     * @return array
     */
    protected function getDefaultCustomerAddress()
    {
        if (!$this->customerDataAddress) {
            $this->customerDataAddress = [
                'prefix' => '',
                'firstname' => '',
                'middlename' => '',
                'lastname' => '',
                'suffix' => '',
                'company' => '',
                'street' => [
                    0 => '',
                    1 => '',
                ],
                'city' => '',
                'country_id' => '',
                'region' => '',
                'postcode' => '',
                'telephone' => '',
                'fax' => '',
                'vat_id' => '',
                'default_billing' => true,
                'default_shipping' => true,
            ];
        }

        return $this->customerDataAddress;
    }

    /**
     * @param array $row
     * @param array $data
     * @return array
     * @throws LocalizedException
     */
    protected function convertRowData(array $row, array $data)
    {
        foreach ($row as $rowField => $rowValue) {
            if (isset($data[$rowField])) {
                if ($rowField == 'site_code') {
                    $data['website_id'] = $this->stores->getStoreId($rowValue);
                    continue;
                }

                if ($rowField == 'group_id') {
                    $data['group'] = $this->customerGroups->getCustomerGroupId($rowValue);
                    continue;
                }

                if ($rowField == 'street') {
                    $data[$rowField][0] = $rowValue;
                    continue;
                }

                $data[$rowField] = $rowValue;
            }else{

            }
        }

        return $data;
    }

    /**
     * @param array $address
     * @return mixed
     */
    protected function getRegionId(array $address)
    {
        $country = $this->countryFactory->create()->loadByCode($address['country_id']);
        return $country->getRegionCollection()->addFieldToFilter('name', $address['region'])->getFirstItem()->getId();
    }
}
