<?php

/**
 * Copyright © Magento. All rights reserved.
 */
namespace MagentoEse\DataInstall\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
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

    /** @var ProductRepositoryInterface */
    protected $productRepository;

    /** @var SearchCriteriaBuilder */
    protected $searchCriteriaBuilder;

    /**
     * Products constructor.
     * @param ObjectManagerInterface $objectManager
     * @param Stores $stores
     * @param ProductRepositoryInterface $productRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        Stores $stores,
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    )
    {
        $this->objectManager=$objectManager;
        $this->stores = $stores;
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @param array $rows
     * @param array $header
     * @param string $modulePath
     * @param array $settings
     */
    public function install(array $rows, array $header, string $modulePath, array $settings)
    {
        if (!empty($configuration['product_image_import_directory'])) {
            $imgDir = $configuration['product_image_import_directory'];
        } else {
            $imgDir = $modulePath . self::DEFAULT_IMAGE_PATH;
        }
        foreach ($rows as $row) {
            $productsArray[] = array_combine($header, $row);
        }

        /// create file to restrict existing products from the store view
        print_r("Restricting existing products from store\n");
        //$restrictProducts = $this->restrictExistingProducts($this->stores->getStoreId($settings['store_view_code']));
        $this->importerModel = $this->objectManager->create('FireGento\FastSimpleImport\Model\Importer');
        $this->importerModel->setImportImagesFileDir($imgDir);
        $this->importerModel->setValidationStrategy('validation-skip-errors');
        try {
            $this->importerModel->processImport($this->restrictExistingProducts($this->stores->getStoreId($settings['store_view_code'])));
        } catch (\Exception $e) {
            print_r($e->getMessage());
        }
        print_r($this->importerModel->getLogTrace());
        print_r($this->importerModel->getErrorMessages());

        unset($this->importerModel);


        print_r("Import new products\n");
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

        /// create file to restrict new products from other views
        //$productsArray = $this->restrictProductsFromOtherStoreViews($productsArray);
        print_r("Restrict new products from existing stores\n");
        $this->importerModel = $this->objectManager->create('FireGento\FastSimpleImport\Model\Importer');
        $this->importerModel->setImportImagesFileDir($imgDir);
        $this->importerModel->setValidationStrategy('validation-skip-errors');
        try {
            $this->importerModel->processImport($this->restrictProductsFromOtherStoreViews($productsArray));
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

    private function restrictExistingProducts($storeIdToRestrict)
    {
        $newProductArray = [];
        $search = $this->searchCriteriaBuilder
            ->addFilter(ProductInterface::SKU, '', 'neq')->create();
        $productCollection = $this->productRepository->getList($search)->getItems();
        foreach ($productCollection as $product) {
            $newProductArray[] = ['sku'=>$product->getSku(),'store_view_code'=>$storeIdToRestrict,'visibility'=>'Not Visible Individually'];
        }
        return $newProductArray;
    }
}
