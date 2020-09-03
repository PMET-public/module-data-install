<?php

/**
 * Copyright © Magento. All rights reserved.
 */
namespace MagentoEse\DataInstall\Model;

use Magento\Framework\ObjectManagerInterface;

class Products
{
    const DEFAULT_IMAGE_PATH = '/media/catalog/product';
    //TODO: flexibility for other than default category
    //TODO: Check on using Export as import file

    /** @var ObjectManagerInterface  */
    protected $objectManager;

    /**
     * Products constructor.
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager=$objectManager;
    }

    /**
     * @param array $rows
     * @param array $header
     * @param string $modulePath
     * @param array $configuration
     */
    public function install(array $rows, array $header, string $modulePath, array $configuration)
    {
        if (!empty($configuration['product_image_import_directory'])) {
            $imgDir = $configuration['product_image_import_directory'];
        } else {
            $imgDir = $modulePath . self::DEFAULT_IMAGE_PATH;
        }
        foreach ($rows as $row) {
            $productsArray[] = array_combine($header, $row);
        }
        $this->importerModel = $this->objectManager->create('FireGento\FastSimpleImport\Model\Importer');
        $this->importerModel->setImportImagesFileDir($imgDir);
        $this->importerModel->setValidationStrategy('validation-skip-errors');
        try {
            $this->importerModel->processImport($productsArray);
        } catch (\Exception $e) {
            print_r($e->getMessage());
        }

        print_r($this->importerModel->getLogTrace());
        print_r($this->importerModel->getErrorMessages());
        unset($_productsArray);
        unset($this->importerModel);
    }
}
