<?php

/**
 * Copyright © Adobe. All rights reserved.
 */
namespace MagentoEse\DataInstall\Model\DataTypes;

use FireGento\FastSimpleImport\Model\ImporterFactory as Importer;
use MagentoEse\DataInstall\Helper\Helper;

class MsiInventory
{
    /** @var Importer */
    protected $importer;

    /** @var Helper */
    protected $helper;

    /**
     * MsiInventory constructor.
     * @param Helper $helper
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        Helper $helper,
        Importer $importer
    ) {
        $this->helper = $helper;
        $this->importer = $importer;
    }

    /**
     * install
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
     * @param $productsArray
     * @param $imgDir
     * @param $productValidationStrategy
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
            $this->helper->printMessage($e->getMessage());
        }

        $this->helper->printMessage($importerModel->getLogTrace());
        $this->helper->printMessage($importerModel->getErrorMessages());

        unset($importerModel);
    }
}
