<?php

/**
 * Copyright © Magento. All rights reserved.
 */
namespace MagentoEse\DataInstall\Model\DataTypes;

use Magento\Framework\ObjectManagerInterface;
use MagentoEse\DataInstall\Helper\Helper;

class MsiInventory
{
    /** @var ObjectManagerInterface  */
    protected $objectManager;

    /** @var Helper */
    protected $helper;
    
    /**
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        Helper $helper,
        ObjectManagerInterface $objectManager
    ) {
        $this->helper = $helper;
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
            $this->helper->printMessage($e->getMessage());
        }

        $this->helper->printMessage($importerModel->getLogTrace());
        $this->helper->printMessage($importerModel->getErrorMessages());

        unset($importerModel);
    }
}
