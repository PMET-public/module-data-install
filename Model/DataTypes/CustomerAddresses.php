<?php
/**
 * Copyright 2022 Adobe, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace MagentoEse\DataInstall\Model\DataTypes;

use MagentoEse\DataInstall\Model\Import\Importer\ImporterFactory as Importer;
use MagentoEse\DataInstall\Helper\Helper;
use Magento\Framework\App\Area as AppArea;
use Magento\Framework\App\State;

class CustomerAddresses
{
    /** @var Stores  */
    protected $stores;

    /** @var Importer  */
    protected $importer;

    /** @var Helper  */
    protected $helper;

    /** @var State  */
    protected $appState;

    /** @var array */
    protected $importUnsafeColumns=[''];

    /**
     * Customers constructor
     *
     * @param Stores $stores
     * @param Importer $importer
     * @param Helper $helper
     * @param State $appState
     */
    public function __construct(
        Stores $stores,
        Importer $importer,
        Helper $helper,
        State $appState
    ) {
        $this->stores = $stores;
        $this->importer = $importer;
        $this->helper = $helper;
        $this->appState = $appState;
    }

    /**
     * Install
     *
     * @param array $rows
     * @param array $header
     * @param string $modulePath
     * @param array $settings
     * @return bool
     * @throws LocalizedException
     */
    public function install(array $rows, array $header, string $modulePath, array $settings)
    {

        if (!empty($settings['product_validation_strategy'])) {
            $productValidationStrategy = $settings['product_validation_strategy'];
        } else {
            $productValidationStrategy =  'validation-skip-errors';
        }

        foreach ($rows as $row) {
            $customerArray[] = array_combine($header, $row);
        }
        $cleanCustomerArray = $this->cleanDataForImport($customerArray, $settings);
        $this->import($cleanCustomerArray, $productValidationStrategy, 'customer_address');

        return true;
    }

    /**
     * Clean up data to make safe for import
     *
     * @param array $customerArray
     * @param array $settings
     * @return array
     */
    private function cleanDataForImport($customerArray, $settings)
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
                $customer['_website']=$settings['site_code'];
            }

            //override site and store
            if (!empty($settings['is_override'])) {
                if (!empty($settings['site_code'])) {
                    $customer['_website'] = $settings['site_code'];
                }
            }
            //_entity_id is a required column, but if its populated it may cause unexpected errors.
            $customer['_entity_id'] = '';

            //remove columns used for other purposes, but throw errors on import
            foreach ($this->importUnsafeColumns as $column) {
                unset($customer[$column]);
            }
            $newCustomerArray[]=$customer;
        }
        return $newCustomerArray;
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
            $this->helper->logMessage($e->getMessage());
        }

        $this->helper->logMessage($importerModel->getLogTrace());
        $this->helper->logMessage($importerModel->getErrorMessages());

        unset($importerModel);
    }
}
