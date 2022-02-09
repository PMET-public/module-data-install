<?php
/**
 * Copyright © Adobe. All rights reserved.
 */

namespace MagentoEse\DataInstall\Model\DataTypes;

use FireGento\FastSimpleImport\Model\ImporterFactory as Importer;
use MagentoEse\DataInstall\Helper\Helper;

class CustomerAddresses
{
    /** @var Stores  */
    protected $stores;

    /** @var Importer  */
    protected $importer;

     /** @var Helper  */
    protected $helper;

     protected $importUnsafeColumns=[''];

    /**
     * Customers constructor.
     * @param Stores $stores
     * @param Importer $importer
     * @param Helper $helper
     */
    public function __construct(
        Stores $stores,
        Importer $importer,
        Helper $helper
    ) {
        $this->stores = $stores;
        $this->importer = $importer;
        $this->helper = $helper;
    }

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

        if (!empty($settings['product_validation_strategy'])) {
            $productValidationStrategy = $settings['product_validation_strategy'];
        } else {
            $productValidationStrategy =  'validation-skip-errors';
        }

        foreach ($rows as $row) {
            $customerArray[] = array_combine($header, $row);
        }
        $cleanCustomerArray = $this->cleanDataForImport($customerArray);
        $this->import($cleanCustomerArray, $productValidationStrategy, 'customer_address');

        return true;
    }

    /**
     * @param $customerArray
     * @return array
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
     * @param $customerArray
     * @param $productValidationStrategy
     * @param $importMethod
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
            $importerModel->processImport($customerArray);
        } catch (\Exception $e) {
            $this->helper->logMessage($e->getMessage());
        }

        $this->helper->logMessage($importerModel->getLogTrace());
        $this->helper->logMessage($importerModel->getErrorMessages());

        unset($importerModel);
    }
}
