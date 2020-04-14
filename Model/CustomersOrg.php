<?php
/**
 * Copyright © Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MagentoEse\DataInstall\Model;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Api\Data\RegionInterface;
use Magento\Customer\Api\Data\RegionInterfaceFactory;
use Magento\Directory\Model\CountryFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Customer
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CustomersOrg
{

    /**
     * @var CustomerInterfaceFactory
     */
    protected $customerFactory;

    /**
     * @var CountryFactory
     */
    protected $countryFactory;

    /**
     * @var AddressInterfaceFactory
     */
    protected $addressFactory;

    /**
     * @var RegionInterfaceFactory
     */
    protected $regionFactory;

    /**
     * @var AccountManagementInterface
     */
    protected $accountManagement;

    /**
     * @var array $customerDataProfile
     */
    protected $customerDataProfile;

    /**
     * @var array $customerDataAddress
     */
    protected $customerDataAddress;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var DataObjectHelper
     */
    protected $dataObjectHelper;


    protected $appState;

    /**
     * @param CountryFactory $countryFactory
     * @param CustomerInterfaceFactory $customerFactory
     * @param AddressInterfaceFactory $addressFactory
     * @param RegionInterfaceFactory $regionFactory
     * @param AccountManagementInterface $accountManagement
     * @param StoreManagerInterface $storeManager
     * @param DataObjectHelper $dataObjectHelper
     * @param \Magento\Framework\App\State $appState
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        CountryFactory $countryFactory,
        CustomerInterfaceFactory $customerFactory,
        AddressInterfaceFactory $addressFactory,
        RegionInterfaceFactory $regionFactory,
        AccountManagementInterface $accountManagement,
        StoreManagerInterface $storeManager,
        DataObjectHelper $dataObjectHelper,
        \Magento\Framework\App\State $appState
    ) {
        $this->countryFactory = $countryFactory;
        $this->customerFactory = $customerFactory;
        $this->addressFactory = $addressFactory;
        $this->regionFactory = $regionFactory;
        $this->accountManagement = $accountManagement;
        $this->storeManager = $storeManager;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->appState = $appState;
    }

    /**
     * {@inheritdoc}
     */
    public function install($fixtures)
    {
        foreach ($fixtures as $fixture) {
            $filePath = $this->fixtureManager->getFixture($fixture);
            $rows = $this->csvReader->getData($filePath);
            $header = array_shift($rows);
            foreach ($rows as $row) {
                $data = [];
                foreach ($row as $key => $value) {
                    $data[$header[$key]] = $value;
                }
                $row = $data;
                // Collect customer profile and addresses data
                $customerData['profile'] = $this->convertRowData($row, $this->getDefaultCustomerProfile());
                if (!$this->accountManagement->isEmailAvailable($customerData['profile']['email'])) {
                    continue;
                }
                $customerData['address'] = $this->convertRowData($row, $this->getDefaultCustomerAddress());
                $customerData['address']['region_id'] = $this->getRegionId($customerData['address']);

                $address = $customerData['address'];
                $regionData = [
                    RegionInterface::REGION_ID => $address['region_id'],
                    RegionInterface::REGION => !empty($address['region']) ? $address['region'] : null,
                    RegionInterface::REGION_CODE => !empty($address['region_code']) ? $address['region_code'] : null,
                ];
                $region = $this->regionFactory->create();
                $this->dataObjectHelper->populateWithArray(
                    $region,
                    $regionData,
                    '\Magento\Customer\Api\Data\RegionInterface'
                );

                $addresses = $this->addressFactory->create();
                unset($customerData['address']['region']);
                $this->dataObjectHelper->populateWithArray(
                    $addresses,
                    $customerData['address'],
                    '\Magento\Customer\Api\Data\AddressInterface'
                );
                $addresses->setRegion($region)
                    ->setIsDefaultBilling(true)
                    ->setIsDefaultShipping(true);

                $customer = $this->customerFactory->create();
                $this->dataObjectHelper->populateWithArray(
                    $customer,
                    $customerData['profile'],
                    '\Magento\Customer\Api\Data\CustomerInterface'
                );
                $customer->setAddresses([$addresses]);
                $this->appState->emulateAreaCode(
                    'frontend',
                    [$this->accountManagement, 'createAccount'],
                    [$customer, $row['password']]
                );
            }
        }
    }

    /**
     * @return array
     */
    protected function getDefaultCustomerProfile()
    {
        if (!$this->customerDataProfile) {
            $this->customerDataProfile = [
                'website_id' => $this->storeManager->getWebsite()->getId(),
                'group_id' => $this->storeManager->getGroup()->getId(),
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
                'confirmation' => false,
                'sendemail' => false,
            ];
        }
        return $this->customerDataProfile;
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
     * @return array $data
     */
    protected function convertRowData($row, $data)
    {
        foreach ($row as $field => $value) {
            if (isset($data[$field])) {
                if ($field == 'street') {
                    $data[$field] = unserialize($value);
                    continue;
                }
                if ($field == 'password') {
                    continue;
                }
                $data[$field] = $value;
            }
        }
        return $data;
    }

    /**
     * @param array $address
     * @return mixed
     */
    protected function getRegionId($address)
    {
        $country = $this->countryFactory->create()->loadByCode($address['country_id']);
        return $country->getRegionCollection()->addFieldToFilter('name', $address['region'])->getFirstItem()->getId();
    }
}
