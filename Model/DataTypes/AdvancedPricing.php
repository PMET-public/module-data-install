<?php
/**
 * Copyright 2022 Adobe, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace MagentoEse\DataInstall\Model\DataTypes;

use MagentoEse\DataInstall\Model\Import\Importer\ImporterFactory as Importer;
use MagentoEse\DataInstall\Helper\Helper;

class AdvancedPricing
{
    /** @var string  */
    protected const DEFAULT_WEBSITE = 'All Websites [USD]';

    /** @var string  */
    protected const DEFAULT_CUSTOMER_GROUP = 'ALL GROUPS';

    /** @var Helper */
    protected $helper;

    /** @var Importer */
    protected $importer;

    /**
     * AdvancedPricing constructor
     *
     * @param Helper $helper
     * @param Importer $importer
     */
    public function __construct(
        Helper $helper,
        Importer $importer
    ) {
        $this->importer = $importer;
        $this->helper = $helper;
    }

    /**
     * Install
     *
     * @param array $rows
     * @param array $header
     * @param string $modulePath
     * @param array $settings
     * @return bool
     */
    public function install(array $rows, array $header, string $modulePath, array $settings)
    {
        //need to set default for tier_price_website = settings[site_code],tier_price_customer_group

        if (!empty($settings['product_validation_strategy'])) {
            $productValidationStrategy = $settings['product_validation_strategy'];
        } else {
            $productValidationStrategy =  'validation-skip-errors';
        }

        foreach ($rows as $row) {
            $productsArray[] = array_combine($header, $row);
        }
        //set default group and website if they arent included
        foreach ($productsArray as $productRow) {
            if (empty($productRow['tier_price_website'])) {
                $productRow['tier_price_website'] = self::DEFAULT_WEBSITE;
            }

            if (empty($productRow['tier_price_customer_group'])) {
                $productRow['tier_price_customer_group'] = self::DEFAULT_CUSTOMER_GROUP;
            }
            $updatedProductsArray[]=$productRow;
        }
        $updatedProductsArray = $this->replaceBaseWebsiteCodes($updatedProductsArray, $settings);
        $this->import($updatedProductsArray, $productValidationStrategy);

        return true;
    }

     /**
      * Replace website codes with ids
      *
      * @param array $products
      * @param array $settings
      * @return array
      */
    private function replaceBaseWebsiteCodes($products, $settings)
    {
        $i=0;
        foreach ($products as $product) {
            //product_websites
            if (!empty($product['tier_price_website'])) {
                ///value may be a comma delimited list e.g. notbase,test
                $websiteArray = explode(",", $product['tier_price_website']);
                // phpcs:ignore Magento2.PHP.ReturnValueCheck.ImproperValueTesting
                if (is_int(array_search('base', $websiteArray))) {
                    $websiteArray[array_search('base', $websiteArray)]=$this->stores->replaceBaseWebsiteCode('base');
                    $product['tier_price_website'] = implode(",", $websiteArray);
                }
            }

            $products[$i] = $product;
            $i++;
        }
        return $products;
    }

    /**
     * Call Importer
     *
     * @param array $productsArray
     * @param string $productValidationStrategy
     */
    private function import($productsArray, $productValidationStrategy)
    {
        $importerModel = $this->importer->create();
        $importerModel->setEntityCode('advanced_pricing');
        $importerModel->setValidationStrategy($productValidationStrategy);
        if ($productValidationStrategy == 'validation-stop-on-errors') {
            $importerModel->setAllowedErrorCount(1);
        } else {
            $importerModel->setAllowedErrorCount(100);
        }
        try {
            $importerModel->processImport($productsArray);
        } catch (\Exception $e) {
            $this->helper->logMessage($e->getMessage());
        }

        $this->helper->logMessage($importerModel->getLogTrace());
        $this->helper->logMessage($importerModel->getErrorMessages());

        unset($importerModel);
    }
}
