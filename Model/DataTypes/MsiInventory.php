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

class MsiInventory
{
    /** @var Importer */
    private $importer;

    /** @var Helper */
    protected $helper;

    /** @var State  */
    protected $appState;

    /**
     * MsiInventory constructor
     *
     * @param Helper $helper
     * @param Importer $importer
     */
    public function __construct(
        Helper $helper,
        Importer $importer,
        State $appState
    ) {
        $this->helper = $helper;
        $this->importer = $importer;
        $this->appState = $appState;
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
            $this->appState->emulateAreaCode(
                AppArea::AREA_ADMINHTML,
                [$importerModel, 'processImport'],
                [$productsArray]
            );
            //$importerModel->processImport($productsArray);
        } catch (\Exception $e) {
            $this->helper->logMessage($e->getMessage());
        }

        $this->helper->logMessage($importerModel->getLogTrace());
        $this->helper->logMessage($importerModel->getErrorMessages());

        unset($importerModel);
    }
}
