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

    /** @var Stores */
    protected $stores;

    /**
     * Products constructor.
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager, Stores $stores)
    {
        $this->objectManager=$objectManager;
        $this->stores = $stores;
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

        unset($this->importerModel);

        ///add rows to file to restrict products from other views
        //$productsArray = $this->restrictProductsFromOtherStoreViews($productsArray);
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

        unset($productsArray);
        unset($this->importerModel);


    }

    private function restrictProductsFromOtherStoreViews($products)
    {
        $newProductArray = [];
        $allStoreCodes = $this->stores->getAllViewCodes();
        foreach ($products as $product) {
            //write out original line
            //$newProductArray[]=$product;
            //add restrictive line for each
            foreach ($allStoreCodes as $storeCode) {
                if ($storeCode != $product['store_view_code']) {
                    $newProductArray[] = ['sku'=>$product['sku'],'store_view_code'=>$storeCode,'visibility'=>'Not Visible Individually'];
                }
            }
        }
        return $newProductArray;
    }
}
