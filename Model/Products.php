<?php

/**
 * Copyright © Magento. All rights reserved.
 */
namespace MagentoEse\DataInstall\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\App\State;
use FireGento\FastSimpleImport\Model\ImporterFactory as Importer;

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

    /** @var Importer */
    protected $importer;

    /** @var State */
    protected $state;

    /**
     * Products constructor.
     * @param ObjectManagerInterface $objectManager
     * @param Stores $stores
     * @param ProductRepositoryInterface $productRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param State $state
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        Stores $stores,
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Importer $importer,
        State $state
    ) {
        $this->objectManager=$objectManager;
        $this->stores = $stores;
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->importer = $importer;
        $this->state = $state;
    }

    /**
     * @param array $rows
     * @param array $header
     * @param string $modulePath
     * @param array $settings
     */
    public function install(array $rows, array $header, string $modulePath, array $settings)
    {
        if (!empty($settings['product_image_import_directory'])) {
            $imgDir = $settings['product_image_import_directory'];
        } else {
            $imgDir = $modulePath . self::DEFAULT_IMAGE_PATH;
        }

        if (!empty($settings['restrict_products_from_views'])) {
            $restrictProductsFromViews = $settings['restrict_products_from_views'];
        } else {
            $restrictProductsFromViews =  'N';
        }

        foreach ($rows as $row) {
            $productsArray[] = array_combine($header, $row);
        }

        /// create array to restrict existing products from other store views
        if($restrictProductsFromViews=='Y'){
            $restrictExistingProducts = $this->restrictExistingProducts($settings['store_view_code']);
            $restrictNewProducts = $this->restrictNewProductsFromOtherStoreViews($productsArray,$settings['store_view_code']);
        }


        print_r("Import new products\n");
        $this->import($productsArray,$imgDir);
        
        /// Restrict products from other stores
        if($restrictProductsFromViews=='Y') {
            print_r("Restricting products from other store views\n");
            //Need to set area code when updating products
            try{
                $this->state->setAreaCode('adminhtml');
            }
            catch(\Magento\Framework\Exception\LocalizedException $e){
                // left empty
            }
            print_r("Restricting ".count($restrictExistingProducts)." products from new store view\n");
            //$this->updateProductVisitbility($restrictExistingProducts);
            $this->import($restrictExistingProducts,$imgDir);
            print_r("Restricting ".count($restrictNewProducts)." new products from existing store views\n");
            //$this->updateProductVisitbility($restrictNewProducts);
            $this->import($restrictNewProducts,$imgDir);
        }
        //$t=$r;
    }
    
    private function updateProductVisitbility($restrictProducts){
        foreach($restrictProducts as $restrictProduct){
            $product = $this->productRepository->get($restrictProduct['sku']);
            $product->setStoreId($this->stores->getViewId($restrictProduct['store_view_code']));
            $product->setVisibility($restrictProduct['visibility']);
            $this->productRepository->save($product);
        }

    }

    private function import($productsArray,$imgDir){
        $importerModel = $this->importer->create();
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
    }

    /**
     * @param array $products
     * @return array
     */
    private function restrictNewProductsFromOtherStoreViews(array $products,$storeViewCode)
    {
        $newProductArray = [];
        $allStoreCodes = $this->stores->getAllViewCodes();
        foreach ($products as $product) {
            if(!empty($product['store_view_code'])){
                $storeViewCode = $product['store_view_code'];
            }
            //add restrictive line for each
            foreach ($allStoreCodes as $storeCode) {
                 if ($storeCode != $storeViewCode) {
                    $newProductArray[] = ['sku'=>$product['sku'],'store_view_code'=>$storeCode,'visibility'=>'Not Visible Individually'];
                }
            }
        }

        return $newProductArray;
    }

    /**
     * @param $storeViewCodeToRestrict
     * @return array
     */
    private function restrictExistingProducts($storeViewCodeToRestrict)
    {
        $newProductArray = [];
        $search = $this->searchCriteriaBuilder
            //->addFilter(ProductInterface::SKU, '', 'neq')->create();
            ->addFilter(ProductInterface::VISIBILITY, '4', 'eq')->create();
        $productCollection = $this->productRepository->getList($search)->getItems();
        foreach ($productCollection as $product) {
            $newProductArray[] = ['sku'=>$product->getSku(),'store_view_code'=>$storeViewCodeToRestrict,'visibility'=>'Not Visible Individually'];
        }

        return $newProductArray;
    }
}