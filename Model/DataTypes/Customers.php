<?php
/**
 * Copyright 2022 Adobe, Inc. All rights reserved.
 * See LICENSE for license details.
 */

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
use MagentoEse\DataInstall\Model\Import\Importer\ImporterFactory as Importer;
use Magento\Framework\Exception\NoSuchEntityException;
use MagentoEse\DataInstall\Helper\Helper;
use Magento\Reward\Model\RewardFactory;
use Magento\Reward\Model\Reward;
use Magento\Framework\App\Area as AppArea;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Customer\Model\Customer;

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
    private $importer;

     /** @var Helper  */
     protected $helper;

    /** @var CustomerRepositoryInterface */
     protected $customerRepositoryInterface;

    /** @var RewardFactory  */
    protected $rewardFactory;

    /** @var IndexerRegistry */
    protected $indexerRegistry;

    /** @var array */
    protected $importUnsafeColumns=['company_admin', 'role', 'add_to_autofill','group',
     'reward_points','store_credit','company','website'];

    /**
     * Customers constructor
     *
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
     * @param Importer $importer
     * @param Helper $helper
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     * @param AddressRepositoryInterface $addressRepositoryInterface
     * @param RewardFactory $rewardFactory
     * @param IndexerRegistry $indexerRegistry
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
        AddressRepositoryInterface $addressRepositoryInterface,
        RewardFactory $rewardFactory,
        IndexerRegistry $indexerRegistry
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
        $this->rewardFactory = $rewardFactory;
        $this->indexerRegistry = $indexerRegistry;
    }

    /**
     * Import
     *
     * @param array $rows
     * @param array $header
     * @param string $modulePath
     * @param array $settings
     * @return bool
     * @throws LocalizedException
     */
    
    // phpcs:ignore Generic.Metrics.NestingLevel.TooHigh,Magento2.Annotation.MethodArguments.NoCommentBlock
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

        //support for a customer file without addresses (pure customer export),
        //or single file which would need to be created manually
        //Customer addresses export done as a separate file
        $cleanCustomerArray = $this->cleanDataForImport($customerArray);
        if (in_array('street', $header)) {
            $importMethod = 'customer_composite';
        } else {
            $importMethod = 'customer';
        }

        $this->import($cleanCustomerArray, $productValidationStrategy, $importMethod);

        $indexer = $this->indexerRegistry->get(Customer::CUSTOMER_GRID_INDEXER_ID);
        $indexer->reindexAll();

        //add rewards points
        $this->addRewardsPoints($customerArray);

        //add to autofill
        $startingElement = 1;
        foreach ($customerArray as $autoFillCustomer) {
            if (!empty($autoFillCustomer['add_to_autofill']) && $autoFillCustomer['add_to_autofill'] == 'Y') {
                $startingElement = $this->addToAutofill($autoFillCustomer, $startingElement);
            }
        }
       
        foreach ($cleanCustomerArray as $row) {
            try {
                $customer = $this->customerRepositoryInterface->get(
                    $row['email'],
                    $this->stores->getWebsiteId($row['_website'])
                );

                if (!empty($row['store_view_code'])) {
                    $customer->setCreatedIn($this->stores->getViewName($row['store_view_code']));
                } else {
                    $customer->setCreatedIn($this->stores->getViewName($this->settings['store_view_code']));
                }

                $this->appState->emulateAreaCode(
                    AppArea::AREA_FRONTEND,
                    [$this->customerRepositoryInterface, 'save'],
                    [$customer]
                );

                ///set addresses as default
                $addresses = $customer->getAddresses();
                $addressesToKeep=[];
                foreach ($addresses as $address) {
                    $removed = false;
                    foreach ($addressesToKeep as $checking) {
                        if ($checking->getStreet()==$address->getStreet()) {
                            //remove duplicate
                            try {
                                $this->addressRespository->delete($address);
                            } catch (Exception $e) {
                                //  There is an edge case on a data reload that if a reindex
                                //  doesnt occur after the original load, the reload can fail deleting an address.
                                $leftEmpty=0;
                            }

                            $removed = true;
                            break;
                        }
                    }
                    if (!$removed) {
                        $address->setIsDefaultBilling(true);
                        $address->setIsDefaultShipping(true);
                        $this->addressRespository->save($address);
                        $addressesToKeep[]=$address;
                    }
                }
            } catch (NoSuchEntityException $e) {
                $this->helper->logMessage("Customer ". $row['email']." wasn't imported", "error");
            }
        }

        return true;
    }

    /**
     * Remove columns that aren't importer friendly
     *
     * @param array $customerArray
     * @return array
     * @throws LocalizedException
     */
    private function cleanDataForImport($customerArray)
    {

        $newCustomerArray=[];
        foreach ($customerArray as $customer) {
            //change website column if incorrect
            if (!empty($customer['site_code'])) {
                $customer['_website']=$this->stores->replaceBaseWebsiteCode($customer['site_code']);
                unset($customer['site_code']);
            }
            if (!empty($customer['website']) && $customer['website']!='') {
                $customer['_website']=$this->stores->replaceBaseWebsiteCode($customer['website']);
                unset($customer['website']);
            }

            if (empty($customer['_website'])) {
                $customer['_website']=$this->settings['site_code'];
            }
            //add or change store code
            if (!empty($customer['store_view_code'])) {
                $customer['_store']=$customer['store_view_code'];
                unset($customer['store_view_code']);
            }
            if (empty($customer['_store'])) {
                $customer['_store']=$this->settings['store_view_code'];
            }

            //override site and store
            if (!empty($this->settings['is_override'])) {
                if (!empty($this->settings['store_view_code'])) {
                    $customer['_store'] = $this->settings['store_view_code'];
                }
                if (!empty($this->settings['site_code'])) {
                    $customer['_website'] = $this->settings['site_code'];
                }
            }

            //add group_id column if it doesnt exist
            if (empty($customer['group_id'])) {
                $customer['group_id']=1;
            }
            //if there is a group column, convert to id
            if (!empty($customer['group'])) {
                $customer['group_id']=$this->customerGroups->getCustomerGroupId($customer['group']);
            }

            //if it is a composite file these fields need to be populated
            //if its not a composite file, adding these fields will generate an error

            if (!empty($customer['street'])) {
                //add _address_firstname, _address_lastname if not present
                if (empty($customer['_address_firstname'])) {
                    if (!empty($customer['firstname'])) {
                        $customer['_address_firstname']=$customer['firstname'];
                    }
                }
                if (empty($customer['_address_lastname'])) {
                    if (!empty($customer['firstname'])) {
                        $customer['_address_lastname']=$customer['lastname'];
                    }
                }
            }

            //remove columns used for other purposes, but throw errors on import
            foreach ($this->importUnsafeColumns as $column) {
                unset($customer[$column]);
            }
            $newCustomerArray[]=$customer;
        }
        return $newCustomerArray;
    }

    /**
     * Add rewards points to customers
     *
     * @param array $customerArray
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function addRewardsPoints($customerArray)
    {
        foreach ($customerArray as $rewardCustomer) {
            if (!empty($rewardCustomer['reward_points'])) {
             //change website column if incorrect
                if (!empty($rewardCustomer['site_code'])) {
                    $rewardCustomer['_website']=$this->stores->replaceBaseWebsiteCode($rewardCustomer['site_code']);
                    unset($rewardCustomer['site_code']);
                }
                if (!empty($rewardCustomer['website']) && $rewardCustomer['website']!='') {
                    $rewardCustomer['_website']=$this->stores->replaceBaseWebsiteCode($rewardCustomer['website']);
                    unset($rewardCustomer['website']);
                }

                if (empty($rewardCustomer['_website'])) {
                    $rewardCustomer['_website']=$this->settings['site_code'];
                }
                //try to load with the given website id. Then fail back to null (website id 1)
                //in case the customer was loaded outside of the current data load
                try {
                    $customer = $this->customerRepositoryInterface->get(
                        $rewardCustomer['email'],
                        $this->stores->getWebsiteId($rewardCustomer['_website'])
                    );
                } catch (NoSuchEntityException $e) {
                    try {
                        $customer = $this->customerRepositoryInterface->get($rewardCustomer['email']);
                    } catch (NoSuchEntityException $e) {
                        $this->helper->logMessage("Rewards points cannot be added to customer: "
                        .$e->getMessage(), "warning");
                        continue;
                    }
                }
                 
             /** @var Reward $reward */
                $reward = $this->rewardFactory->create();
                $reward->setWebsiteId($this->stores->getWebsiteId($rewardCustomer['_website']));
                $reward->setCustomer($customer);
                $reward->setPointsBalance($rewardCustomer['reward_points']);
                $reward->save();
            }
        }
    }

    /**
     * Call Importer
     *
     * @param array $customerArray
     * @param string $productValidationStrategy
     * @param string $importMethod
     */
    private function import($customerArray, $productValidationStrategy, $importMethod)
    {
        $importerModel = $this->importer->create();
        $importerModel->setEntityCode($importMethod);
        $importerModel->setValidationStrategy($productValidationStrategy);
        $importerModel->setBehavior('add_update');
        if ($productValidationStrategy == 'validation-stop-on-errors') {
            $importerModel->setAllowedErrorCount(0);
        } else {
            $importerModel->setAllowedErrorCount(100);
        }
        try {
            $this->appState->emulateAreaCode(
                AppArea::AREA_ADMINHTML,
                [$importerModel, 'processImport'],
                [$customerArray]
            );
        } catch (\Exception $e) {
            $this->helper->logMessage($e->getMessage(), "warning");
        }
        $errors = $importerModel->getErrorMessages();
        $this->helper->logMessage($importerModel->getLogTrace());
        unset($importerModel);
    }

    /**
     * Convert address data to address object
     *
     * @param array $row
     * @return mixed
     * @throws LocalizedException
     */
    protected function convertAddresses(array $row)
    {
        $customerData['address'] = $this->convertRowData($row, $this->getDefaultCustomerAddress());
        $customerData['address']['region_id'] = $this->getRegionId($customerData['address']);
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
            // phpcs:ignore Magento2.PHP.LiteralNamespaces.LiteralClassUsage
            '\Magento\Customer\Api\Data\RegionInterface'
        );

        $addresses = $this->addressInterfaceFactory->create();
        unset($customerData['address']['region']);
        $this->dataObjectHelper->populateWithArray(
            $addresses,
            $customerData['address'],
            // phpcs:ignore Magento2.PHP.LiteralNamespaces.LiteralClassUsage
            '\Magento\Customer\Api\Data\AddressInterface'
        );
        $addresses->setRegion($region)
           ->setIsDefaultBilling(true)
           ->setIsDefaultShipping(true);

        return $addresses;
    }

    /**
     * Add settings for autofill extension
     *
     * @param array $row
     * @param int $startingElement
     * @return int
     */
    protected function addToAutofill(array $row, int $startingElement)
    {
        $pathPrefix = 'magentoese_autofill/persona_';
        $elementCount = 17;

        //turn on autofill
        $this->configuration->saveConfig(
            'magentoese_autofill/general/enable_autofill',
            '1',
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            0
        );

        //find next empty autofill
        for ($x=$startingElement; $x <= $elementCount; $x++) {
            if (!$this->configuration->getConfig(
                $pathPrefix . $x . '/email_value',
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                'default'
            )) {
                $this->configuration->saveConfig(
                    $pathPrefix . $x . '/cc_month',
                    '02',
                    ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                    0
                );
                $this->configuration->saveConfig(
                    $pathPrefix . $x . '/cc_year',
                    '2029',
                    ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                    0
                );
                $this->configuration->saveConfig(
                    $pathPrefix . $x . '/cc_number',
                    '4111111111111111',
                    ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                    0
                );
                $this->configuration->saveConfig(
                    $pathPrefix . $x . '/cc_type',
                    'VI',
                    ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                    0
                );
                $this->configuration->saveConfig(
                    $pathPrefix . $x . '/cc_verification_number',
                    '123',
                    ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                    0
                );
                $this->configuration->saveConfig(
                    $pathPrefix . $x . '/enable',
                    '1',
                    ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                    0
                );
                if (!empty($row['street'])) {
                    $this->configuration->saveConfig(
                        $pathPrefix . $x . '/address_value',
                        $row['street'],
                        ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                        0
                    );
                }
                if (!empty($row['city'])) {
                    $this->configuration->saveConfig(
                        $pathPrefix . $x . '/city_value',
                        $row['city'],
                        ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                        0
                    );
                }
                if (!empty($row['country_id'])) {
                    $this->configuration->saveConfig(
                        $pathPrefix . $x . '/country_value',
                        $row['country_id'],
                        ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                        0
                    );
                }
                
                if (!empty($row['company'])) {
                    $this->configuration->saveConfig(
                        $pathPrefix . $x . '/company_value',
                        $row['company'],
                        ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                        0
                    );
                }

                $this->configuration->saveConfig(
                    $pathPrefix . $x . '/email_value',
                    $row['email'],
                    ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                    0
                );
                if (!empty($row['fax'])) {
                    $this->configuration->saveConfig(
                        $pathPrefix . $x . '/fax_value',
                        $row['fax'],
                        ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                        0
                    );
                }
                if (!empty($row['firstname'])) {
                    $this->configuration->saveConfig(
                        $pathPrefix . $x . '/firstname_value',
                        $row['firstname'],
                        ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                        0
                    );
                }
                if (!empty($row['firstname'])) {
                    $this->configuration->saveConfig(
                        $pathPrefix . $x . '/label',
                        $row['firstname'] . ' ' . $row['lastname'],
                        ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                        0
                    );
                }
                if (!empty($row['lastname'])) {
                    $this->configuration->saveConfig(
                        $pathPrefix . $x . '/lastname_value',
                        $row['lastname'],
                        ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                        0
                    );
                }
                
                $this->configuration->saveConfig(
                    $pathPrefix . $x . '/password_value',
                    $row['password'],
                    ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                    0
                );
                $this->configuration->saveConfig(
                    $pathPrefix . $x . '/state_value',
                    $this->getRegionId($row),
                    ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                    0
                );
                if (!empty($row['telephone'])) {
                    $this->configuration->saveConfig(
                        $pathPrefix . $x . '/telephone_value',
                        $row['telephone'],
                        ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                        0
                    );
                }
                if (!empty($row['postcode'])) {
                    $this->configuration->saveConfig(
                        $pathPrefix . $x . '/zip_value',
                        $row['postcode'],
                        ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                        0
                    );
                }
                return $x + 1;
            }
        }
        //if autofill slots are full, return the max value
        return $elementCount;
    }

    /**
     * Get default customer columns and values
     *
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
     * Get default address structure
     *
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
     * Covert codes to ids
     *
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
            }
        }

        return $data;
    }

    /**
     * Get region from address
     *
     * @param array $address
     * @return mixed
     */
    protected function getRegionId(array $address)
    {
        try {
            $country = $this->countryFactory->create()->loadByCode($address['country_id']);
            return $country->getRegionCollection()->
            addFieldToFilter('name', $address['region'])->getFirstItem()->getId();
        } catch (Exception $e) {
            return '';
        }
    }
}
