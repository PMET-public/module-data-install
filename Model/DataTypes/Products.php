<?php

/**
 * Copyright © Magento. All rights reserved.
 */
namespace MagentoEse\DataInstall\Model\DataTypes;

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
            ///get all products that are in views that are not in my store
            $restrictExistingProducts = $this->restrictExistingProducts($productsArray,$settings['store_view_code']);

            //Restrict new (not updated) products to views that arent in my store
            $restrictNewProducts = $this->restrictNewProductsFromOtherStoreViews($productsArray,$settings['store_view_code']);
        }


        $this->helper->printMessage("Importing new products","info");
        $this->import($productsArray,$imgDir,$productValidationStrategy);

        /// Restrict products from other stores
        if($restrictProductsFromViews=='Y') {
            $this->helper->printMessage("Restricting products from other store views","info");
            //Need to set area code when updating products
            // try{
            //     $this->state->setAreaCode('adminhtml');
            // }
            // catch(\Magento\Framework\Exception\LocalizedException $e){
            //     // left empty
            // }
            $this->helper->printMessage("Restricting ".count($restrictExistingProducts)." products from new store view","info");
            //$this->updateProductVisibility($restrictExistingProducts);
            $this->import($restrictExistingProducts,$imgDir,$productValidationStrategy);
            $this->helper->printMessage("Restricting ".count($restrictNewProducts)." new products from existing store views","info");
            //$this->updateProductVisibility($restrictNewProducts);
            $this->import($restrictNewProducts,$imgDir,$productValidationStrategy);
        }

    }
    
    private function updateProductVisibility($restrictProducts){
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
    private function restrictExistingProducts(array $products,$storeViewCode)
    {
        //get all product skus
        $allProductSkus = $this->getVisibleProductSkus();
        $newProductArray = [];
        //$allStoreCodes = $this->stores->getViewCodesFromOtherStores($storeViewCode);
        //$allStoreCodes = $this->stores->getAllViews();
        foreach ($products as $product) {
            $r= array_search($product['sku'],$allProductSkus);
            $sku = $product['sku'];
            //only restrict if product doesnt exist
            if(array_search($product['sku'],$allProductSkus)!==false){
                // if (!empty($product['store_view_code'])) {
                // $storeViewCode = $product['store_view_code'];
                // }
                //add restrictive line for each
                //foreach ($allStoreCodes as $storeCode) {
                //    if ($storeCode != $storeViewCode) {
                        $newProductArray[] = ['sku'=>$product['sku'],'store_view_code'=>$storeViewCode,'visibility'=>'Not Visible Individually'];
                //    }
               // }
            }
            
        }

        return $newProductArray;
    }

    private function restrictNewProductsFromOtherStoreViews(array $newProducts,$storeViewCode)
    {
        
        /////loop over all products, if that sku isn in the products array then flag it
        //get all product skus
        $allProductSkus = $this->getVisibleProductSkus();
        $restrictedProducts = [];
        $allStoreCodes = $this->stores->getViewCodesFromOtherStores($storeViewCode);
        $uniqueNewProductSkus = $this->getUniqueNewProductSkus($newProducts,$allProductSkus);

        //$allStoreCodes = $this->stores->getAllViews();
        foreach ($newProducts as $product) {
            $r= array_search($product['sku'],$allProductSkus);
            $sku= $product['sku'];
            //only restrict if product doesnt exist
            if(array_search($product['sku'],$allProductSkus)===false){
                if (!empty($product['store_view_code'])) {
                $storeViewCode = $product['store_view_code'];
                }
                //add restrictive line for each
                foreach ($allStoreCodes as $storeCode) {
                    if ($storeCode != $storeViewCode) {
                        $restrictedProducts[] = ['sku'=>$product['sku'],'store_view_code'=>$storeCode,'visibility'=>'Not Visible Individually'];
                    }
                }
            }
            
        }

        return $restrictedProducts;
    }

    private function getUniqueNewProductSkus(array $newProducts, array $allProductSkus){
        $newSkus = $this->productDataToSkus($newProducts);
        return array_diff($newProducts,$allProductSkus);
    }

    private function productDataToSkus($products){
        $productData = [];
        foreach ($products as $product) {
            $productData[]=$product['sku'];
        }
    }

    /**
     * @param $storeViewCodeToRestrict
     * @return array
     */
    // private function restrictExistingProducts($storeViewCodeToRestrict)
    // {
    //     $newProductArray = [];
    //     $productCollection = $this->getVisibleProducts();
    //     foreach ($productCollection as $product) {
    //         $newProductArray[] = ['sku'=>$product->getSku(),'store_view_code'=>$storeViewCodeToRestrict,'visibility'=>'Not Visible Individually'];
    //     }

    //     return $newProductArray;
    // }

    private function getVisibleProducts(){
        $search = $this->searchCriteriaBuilder
        //->addFilter(ProductInterface::SKU, '', 'neq')->create();
        ->addFilter(ProductInterface::VISIBILITY, '4', 'eq')
        ->create();
        $productCollection = $this->productRepository->getList($search)->getItems();

        return $productCollection;
    }

    private function getVisibleProductSkus()
    {
        $productSkus = [];
        $productCollection = $this->getVisibleProducts();
        foreach ($productCollection as $product) {
            $productSkus[] = $product->getSku();
        }

        return $productSkus;
    }
}