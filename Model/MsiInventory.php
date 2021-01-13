<?php

/**
 * Copyright © Magento. All rights reserved.
 */
namespace MagentoEse\DataInstall\Model;

use Magento\Framework\ObjectManagerInterface;

class MsiInventory
{
    /** @var ObjectManagerInterface  */
    protected $objectManager;

    /**
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        ObjectManagerInterface $objectManager
    ) {
        $this->objectManager=$objectManager;
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
        $importerModel = $this->objectManager->create('FireGento\FastSimpleImport\Model\Importer');
        $importerModel->setEntityCode('stock_sources');
        $importerModel->setValidationStrategy('validation-skip-errors');
        try {
            $importerModel->processImport($productsArray);
        } catch (\Exception $e) {
            print_r($e->getMessage());
        }

        print_r($importerModel->getLogTrace());
        print_r($importerModel->getErrorMessages());

        unset($importerModel);
    }
}
