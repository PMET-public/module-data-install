<?php


namespace MagentoEse\DataInstall\Model;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Api\Data\RegionInterface;
use Magento\Customer\Api\Data\RegionInterfaceFactory;
use Magento\Directory\Model\CountryFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\App\State;

class Customers
{

    /** @var array $customerDataProfile */
    protected $customerDataProfile;

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

    /** @var CustomerInterfaceFactory  */
    protected $customerInterfaceFactory;

    /** @var RegionInterfaceFactory  */
    protected $regionInterfaceFactory;

    /** @var DataObjectHelper */
    protected $dataObjectHelper;

    /** @var State  */
    protected $appState;

    public function __construct(CustomerGroups $customerGroups,
                                Stores $stores,AccountManagementInterface $accountManagement,
                                AddressInterfaceFactory $addressInterfaceFactory,
                                CustomerInterfaceFactory $customerInterfaceFactory,
                                RegionInterfaceFactory $regionInterfaceFactory,
                                DataObjectHelper $dataObjectHelper,State $appState)
    {
        $this->customerGroups=$customerGroups;
        $this->stores = $stores;
        $this->accountManagement = $accountManagement;
        $this->addressInterfaceFactory = $addressInterfaceFactory;
        $this->customerInterfaceFactory = $customerInterfaceFactory;
        $this->regionInterfaceFactory = $regionInterfaceFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->appState = $appState;
    }

    public function install($row)
    {
        $customerData['profile'] = $this->convertRowData($row, $this->getDefaultCustomerProfile());
        //if the email exists, skip
        if (!$this->accountManagement->isEmailAvailable($customerData['profile']['email'])) {
            return true;
        }
        $customer = $this->customerInterfaceFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $customer,
            $customerData['profile'],
            '\Magento\Customer\Api\Data\CustomerInterface'
        );

        $this->appState->emulateAreaCode(
            'frontend',
            [$this->accountManagement, 'createAccount'],
            [$customer, $row['password']]
        );
        return true;
    }

    protected function getDefaultCustomerProfile()
    {
        if (!$this->customerDataProfile) {
            $this->customerDataProfile = [
                'website_id' => $this->stores->getStoreId($this->stores->getDefaultWebsiteCode()),
                'group_id' => $this->customerGroups->getCustomerGroupId($this->customerGroups->getDefaultCustomerGroup()),
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
                'confirmation' => false,
                'sendemail' => false,
            ];
        }
        return $this->customerDataProfile;
    }

    protected function convertRowData($row, $data)
        //TODO: what if store or group is invalid
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
                $data[$rowField] = $rowValue;
            }
        }
        return $data;
    }

}
