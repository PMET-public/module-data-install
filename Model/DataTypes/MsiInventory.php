<?php
/**
 * Copyright 2022 Adobe, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace MagentoEse\DataInstall\Model\DataTypes;

use MagentoEse\DataInstall\Model\Import\Importer\ImporterFactory as Importer;
use MagentoEse\DataInstall\Helper\Helper;

class MsiInventory
{
    /** @var Importer */
    protected $importer;

    /** @var Helper */
    protected $helper;

    /**
     * MsiInventory constructor
     *
     * @param Helper $helper
     * @param Importer $importer
     */
    public function __construct(
        Helper $helper,
        Importer $importer
    ) {
        $this->helper = $helper;
        $this->importer = $importer;
    }

    /**
     * Install
     *
     * @param  mixed $rows
     * @param  mixed $header
     * @param  mixed $modulePath
     * @param  mixed $settings
     * @return void
     */
    public function install(array $rows, array $header, string $modulePath, array $settings)
    {
        foreach ($rows as $row) {
            $productsArray[] = array_combine($header, $row);
        }
        if (!empty($settings['product_validation_strategy'])) {
            $productValidationStrategy = $settings['product_validation_strategy'];
        } else {
            $productValidationStrategy =  'validation-skip-errors';
        }
        $this->import($productsArray, $productValidationStrategy);
        return true;
    }

    /**
     * Import inventory
     *
     * @param array $productsArray
     * @param string $productValidationStrategy
     */
    private function import($productsArray, $productValidationStrategy)
    {
        $importerModel = $this->importer->create();
        $importerModel->setEntityCode('stock_sources');
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
