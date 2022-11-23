<?php
/**
 * Copyright 2022 Adobe, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace MagentoEse\DataInstall\Model\DataTypes;

use DomainException;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\State;
use FireGento\FastSimpleImport\Model\ImporterFactory as Importer;
use MagentoEse\DataInstall\Helper\Helper;
use Magento\Framework\App\Area as AppArea;
use Magento\Framework\Filesystem\Directory\ReadInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Filesystem;

class Products
{
    //protected const IMPORT_ARRAY_SIZE = 2500;
    protected const DEFAULT_IMAGE_PATH = '/media/catalog/product';
    protected const APP_DEFAULT_IMAGE_PATH = 'var';
    //TODO: flexibility for other than default category

    /** @var Helper */
    protected $helper;

    /** @var Stores */
    protected $stores;

    /** @var ProductRepositoryInterface */
    protected $productRepository;

    /** @var SearchCriteriaBuilder */
    protected $searchCriteriaBuilder;

    /** @var Importer */
    protected $importer;

    /** @var State */
    protected $appState;

    /** @var ReadInterface  */
    protected $directoryRead;

    /** @var Filesystem */
    protected $fileSystem;

    /**
     * Products constructor
     *
     * @param Helper $helper
     * @param Stores $stores
     * @param ProductRepositoryInterface $productRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param Importer $importer
     * @param State $appState
     * @param DirectoryList $directoryList
     * @param Filesystem $fileSystem
     */
    public function __construct(
        Helper $helper,
        Stores $stores,
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Importer $importer,
        State $appState,
        DirectoryList $directoryList,
        Filesystem $fileSystem
    ) {
        $this->stores = $stores;
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->importer = $importer;
        $this->appState = $appState;
        $this->helper = $helper;
        $this->directoryRead = $fileSystem->getDirectoryRead(DirectoryList::ROOT);
    }

    /**
     * Install 
     *
     * @param array $rows 
     * @param array $header 
     * @param string $modulePath 
     * @param array $settings 
     * @param string $behavior 
     * @return void 
     * @throws CouldNotSaveException 
     * @throws DomainException 
     */
    public function install(
        array $rows,
        array $header,
        string $modulePath,
        array $settings,
        $behavior = 'append'
    ) {
        if (!empty($settings['product_image_import_directory'])) {
            $imgDir = $settings['product_image_import_directory'];
        } else {
            $imgDir = $modulePath . self::DEFAULT_IMAGE_PATH;
        }
        //check to see if the image directory exists.  If not, set it to safe default
        //this will catch the case of updating products, but not needing to include image files
        if (!$this->directoryRead->isDirectory($imgDir)) {
            $this->helper->logMessage(
                "The directory or product images ".$imgDir." does not exist. ".
                "This may cause an issue with your product import if you are expecting to include product images",
                "warning"
            );
            $imgDir = self::APP_DEFAULT_IMAGE_PATH;
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
        if ($restrictProductsFromViews=='Y') {
            ///get all products that are not in my view not in my data file
            //restricts from incoming store
            $restrictExistingProducts = $this->restrictExistingProducts($productsArray, $settings['store_view_code']);

            //Restrict new (not updated) products to views that arent in my store
            $restrictNewProducts = $this->restrictNewProductsFromOtherStoreViews(
                $productsArray,
                $settings['store_view_code']
            );
        }
        $productsArray = $this->replaceBaseWebsiteCodes($productsArray, $settings);
        $this->helper->logMessage("Importing products", "info");
        
        $this->import($productsArray, $imgDir, $productValidationStrategy, $behavior);
        
        /// Restrict products from other stores
        if ($restrictProductsFromViews=='Y') {
            $this->helper->logMessage("Restricting products from other store views", "info");

            if (count($restrictExistingProducts) > 0) {
                 $this->helper->logMessage("Restricting ".count($restrictExistingProducts).
                 " products from new store view", "info");
                $this->import($restrictExistingProducts, $imgDir, $productValidationStrategy,$behavior);
            }

            if (count($restrictNewProducts) > 0) {
                $this->helper->logMessage("Restricting ".count($restrictNewProducts).
                " new products from existing store views", "info");
                $this->import($restrictNewProducts, $imgDir, $productValidationStrategy,$behavior);
            }
        }
    }

    /**
     * Restrict products from other stores
     *
     * @param array $restrictProducts
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\StateException
     */
    private function updateProductVisibility($restrictProducts)
    {
        foreach ($restrictProducts as $restrictProduct) {
            $product = $this->productRepository->get($restrictProduct['sku']);
            $product->setStoreId($this->stores->getViewId($restrictProduct['store_view_code']));
            $product->setVisibility($restrictProduct['visibility']);
            $this->productRepository->save($product);
        }
    }

   /**
    * Call importer 
    *
    * @param mixed $productsArray 
    * @param mixed $imgDir 
    * @param mixed $productValidationStrategy 
    * @param string $behavior 
    * @return void 
    * @throws CouldNotSaveException 
    */
    private function import($productsArray, $imgDir, $productValidationStrategy, $behavior)
    {
        $importerModel = $this->importer->create();
        $importerModel->setBehavior($behavior);
        $importerModel->setImportImagesFileDir($imgDir);
        $importerModel->setValidationStrategy($productValidationStrategy);
        if ($productValidationStrategy == 'validation-stop-on-errors') {
            $importerModel->setAllowedErrorCount(1);
        } else {
            $importerModel->setAllowedErrorCount(100);
        }
        try {
            $this->appState->emulateAreaCode(
                AppArea::AREA_ADMINHTML,
                [$importerModel, 'processImport'],
                [$productsArray]
            );
        } catch (\Exception $e) {
            $this->helper->logMessage($e->getMessage());
        }

        $this->helper->logMessage($importerModel->getLogTrace());
        if ($importerModel->getErrorMessages()!="") {
            $this->helper->logMessage($importerModel->getErrorMessages(), "warning");
        }

        unset($importerModel);
    }

    /**
     * Restrict products from current store
     *
     * @param array $products
     * @param string $storeViewCode
     * @return array
     */
    private function restrictExistingProducts(array $products, $storeViewCode)
    {
        $allProductSkus = $this->productDataToSkus($this->getAllProducts());
        $productsToAdd = $this->productDataToSkus($products);
        //$productsToAdd = $this->getUniqueNewProductSkus($products,$allProductSkus);
        $products = array_diff($allProductSkus, $productsToAdd);
        $newProductArray = [];
        foreach ($products as $product) {
            $newProductArray[] = ['sku'=>$product,'store_view_code'=>$storeViewCode,
            'visibility'=>'Not Visible Individually'];
        }
        return $newProductArray;
    }

    /**
     * Restrict new products from existing stores
     *
     * @param array $newProducts
     * @param string $storeViewCode
     * @return array
     */
    private function restrictNewProductsFromOtherStoreViews(array $newProducts, $storeViewCode)
    {

        /////loop over all products, if that sku isn in the products array then flag it
        //get all product skus
        $allProductSkus = $this->productDataToSkus($this->getAllProducts());
        $restrictedProducts = [];
        $allStoreCodes = $this->stores->getViewCodesFromOtherStores($storeViewCode);
        $uniqueNewProductSkus = $this->getUniqueNewProductSkus($newProducts, $allProductSkus);

        //$allStoreCodes = $this->stores->getAllViews();
        foreach ($uniqueNewProductSkus as $product) {
                //add restrictive line for each
            foreach ($allStoreCodes as $storeCode) {
                if ($storeCode != $storeViewCode) {
                    $restrictedProducts[] = ['sku'=>$product,'store_view_code'=>$storeCode,
                    'visibility'=>'Not Visible Individually'];
                }
            }
        }

        return $restrictedProducts;
    }

    /**
     * Get new skus that are unique
     *
     * @param array $newProducts
     * @param array $allProductSkus
     * @return array
     */
    private function getUniqueNewProductSkus(array $newProducts, array $allProductSkus)
    {
        $newSkus = $this->productDataToSkus($newProducts);
        return array_diff($newSkus, $allProductSkus);
    }

    /**
     * Get skus from products
     *
     * @param array $products
     * @return array
     */
    private function productDataToSkus($products)
    {
        $skus = [];
        foreach ($products as $product) {
            $skus[]=$product['sku'];
        }
        return $skus;
    }

    /**
     * Get all products
     *
     * @return ProductInterface[]
     */
    private function getAllProducts()
    {
        $search = $this->searchCriteriaBuilder
        ->addFilter(ProductInterface::SKU, '', 'neq')
        ->create();
        $productCollection = $this->productRepository->getList($search)->getItems();

        return $productCollection;
    }

    /**
     * Get all visible products
     *
     * @return ProductInterface[]
     */
    private function getVisibleProducts()
    {
        $search = $this->searchCriteriaBuilder
        ->addFilter(ProductInterface::VISIBILITY, '4', 'eq')
        ->create();
        $productCollection = $this->productRepository->getList($search)->getItems();

        return $productCollection;
    }

    /**
     * Get visible products skus
     *
     * @return array
     */
    private function getVisibleProductSkus()
    {
        $productSkus = [];
        $productCollection = $this->getVisibleProducts();
        foreach ($productCollection as $product) {
            $productSkus[] = $product->getSku();
        }

        return $productSkus;
    }

    /**
     * Add codes to import file
     *
     * @param array $products
     * @param array $settings
     * @return array
     */
    private function addSettingsToImportFile($products, $settings)
    {
        $i=0;
        foreach ($products as $product) {
            //store_view_code, product_websites
            if (empty($product['store_view_code']) || $product['store_view_code']=='') {
                $product['store_view_code'] = $settings['store_view_code'];
            }
            if (empty($product['product_websites']) || $product['product_websites']=='') {
                $product['product_websites'] = $settings['site_code'];
            }
            $products[$i] = $product;
            $i++;
        }
        return $products;
    }

    /**
     * Replace site codes with ids
     *
     * @param array $products
     * @param array $settings
     * @return array
     */
    private function replaceBaseWebsiteCodes($products, $settings)
    {
        $i=0;
        foreach ($products as $product) {
            //product_websites
            if (!empty($product['product_websites'])) {
                ///value may be a comma delimited list e.g. notbase,test
                $websiteArray = explode(",", $product['product_websites']);
                // phpcs:ignore Magento2.PHP.ReturnValueCheck.ImproperValueTesting
                if (is_int(array_search('base', $websiteArray))) {
                    // phpcs:ignore Magento2.PHP.ReturnValueCheck.ImproperValueTesting
                    $websiteArray[array_search('base', $websiteArray)]=$this->stores->replaceBaseWebsiteCode('base');
                    $product['product_websites'] = implode(",", $websiteArray);
                }
            }

            $products[$i] = $product;
            $i++;
        }
        return $products;
    }
}
