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
    ) {
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
        $restrictProducts = $this->restrictExistingProducts($this->stores->getStoreId($settings['store_view_code']));
        if (!empty($restrictProducts)) {
            print_r("Restricting existing products from store\n");
            $importerModel = $this->objectManager->create('FireGento\FastSimpleImport\Model\Importer');
            $importerModel->setImportImagesFileDir($imgDir);
            $importerModel->setValidationStrategy('validation-skip-errors');
            try {
                $importerModel->processImport($restrictProducts);
            } catch (\Exception $e) {
                print_r($e->getMessage());
            }

            print_r($importerModel->getLogTrace());
            print_r($importerModel->getErrorMessages());

            unset($importerModel);
        }

        print_r("Import new products\n");
        $importerModel = $this->objectManager->create('FireGento\FastSimpleImport\Model\Importer');
        $importerModel->setImportImagesFileDir($imgDir);
        $importerModel->setValidationStrategy('validation-skip-errors');
        try {
            $importerModel->processImport($productsArray);
        } catch (\Exception $e) {
            print_r($e->getMessage());
        }

        print_r($importerModel->getLogTrace());
        print_r($importerModel->getErrorMessages());

        unset($importerModel);

        /// create file to restrict new products from other views
        $restrictNewProducts = $this->restrictProductsFromOtherStoreViews($productsArray);
        if (!empty($restrictNewProducts)) {
            print_r("Restrict new products from existing stores\n");
            $importerModel = $this->objectManager->create('FireGento\FastSimpleImport\Model\Importer');
            $importerModel->setImportImagesFileDir($imgDir);
            $importerModel->setValidationStrategy('validation-skip-errors');
            try {
                $importerModel->processImport($restrictNewProducts);
            } catch (\Exception $e) {
                print_r($e->getMessage());
            }

            print_r($importerModel->getLogTrace());
            print_r($importerModel->getErrorMessages());
        }

        unset($productsArray);
        unset($importerModel);
    }

    /**
     * @param array $products
     * @return array
     */
    private function restrictProductsFromOtherStoreViews(array $products)
    {
        $newProductArray = [];
        $allStoreCodes = $this->stores->getAllViewCodes();
        //if count($allStoreCodes) <= 2 it would be default plus a single store so no need to restrict
        if (count($allStoreCodes)>2) {
            foreach ($products as $product) {
                //add restrictive line for each
                foreach ($allStoreCodes as $storeCode) {
                    if ($storeCode != $product['store_view_code']) {
                        $newProductArray[] = ['sku'=>$product['sku'],'store_view_code'=>$storeCode,'visibility'=>'Not Visible Individually'];
                    }
                }
            }
        }

        return $newProductArray;
    }

    /**
     * @param $storeIdToRestrict
     * @return array
     */
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
