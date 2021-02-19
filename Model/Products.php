<?php

/**
 * Copyright © Magento. All rights reserved.
 */
namespace MagentoEse\DataInstall\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\State;
use FireGento\FastSimpleImport\Model\ImporterFactory as Importer;
use MagentoEse\DataInstall\Helper\Helper;

class Products
{
    /** @var Helper */
    protected $helper;
    
    const DEFAULT_IMAGE_PATH = '/media/catalog/product';
    //TODO: flexibility for other than default category

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
     * @param Stores $stores
     * @param ProductRepositoryInterface $productRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param State $state
     */
    public function __construct(
        Helper $helper,
        Stores $stores,
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Importer $importer,
        State $state
    ) {
        $this->stores = $stores;
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->importer = $importer;
        $this->state = $state;
        $this->helper = $helper;
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

        if (!empty($settings['product_validation_strategy'])) {
            $productValidationStrategy = $settings['product_validation_strategy'];
        } else {
            $productValidationStrategy =  'validation-skip-errors';
        }

        foreach ($rows as $row) {
            $productsArray[] = array_combine($header, $row);
        }

        /// create array to restrict existing products from other store views
        if($restrictProductsFromViews=='Y'){
            $restrictExistingProducts = $this->restrictExistingProducts($settings['store_view_code']);
            $restrictNewProducts = $this->restrictNewProductsFromOtherStoreViews($productsArray,$settings['store_view_code']);
        }


        $this->helper->printMessage("Importing new products","info");
        $this->import($productsArray,$imgDir,$productValidationStrategy);
        
        /// Restrict products from other stores
        if($restrictProductsFromViews=='Y') {
            $this->helper->printMessage("Restricting products from other store views","info");
            //Need to set area code when updating products
            try{
                $this->state->setAreaCode('adminhtml');
            }
            catch(\Magento\Framework\Exception\LocalizedException $e){
                // left empty
            }
            $this->helper->printMessage("Restricting ".count($restrictExistingProducts)." products from new store view","info");
            $this->updateProductVisitbility($restrictExistingProducts);
            //$this->import($restrictExistingProducts,$imgDir);
            $this->helper->printMessage("Restricting ".count($restrictNewProducts)." new products from existing store views","info");
            //$this->updateProductVisitbility($restrictNewProducts);
            $this->import($restrictNewProducts,$imgDir,$productValidationStrategy);
        }
    }
    
    private function updateProductVisitbility($restrictProducts){
        foreach($restrictProducts as $restrictProduct){
            $product = $this->productRepository->get($restrictProduct['sku']);
            $product->setStoreId($this->stores->getViewId($restrictProduct['store_view_code']));
            $product->setVisibility($restrictProduct['visibility']);
            $this->productRepository->save($product);
        }

    }

    private function import($productsArray,$imgDir,$productValidationStrategy){
        $importerModel = $this->importer->create();
        $importerModel->setImportImagesFileDir($imgDir);
        $importerModel->setValidationStrategy($productValidationStrategy);
        if($productValidationStrategy == 'validation-stop-on-errors'){
            $importerModel->setAllowedErrorCount(1);
        }else{
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

    /**
     * @param array $products
     * @return array
     */
    private function restrictNewProductsFromOtherStoreViews(array $products,$storeViewCode)
    {
        $newProductArray = [];
        $allStoreCodes = $this->stores->getAllViewCodes();
        foreach ($products as $product) {
            if (!empty($product['store_view_code'])) {
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