<?php
/**
 * Copyright 2022 Adobe, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace MagentoEse\DataInstall\Model\DataTypes;

use Exception;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\State;
use MagentoEse\DataInstall\Helper\Helper;
use Magento\Framework\App\Area as AppArea;

class GiftCards
{
    /** @var Helper */
    protected $helper;

    /** @var ProductRepositoryInterface */
    protected $productRepository;

    /** @var SearchCriteriaBuilder */
    protected $searchCriteriaBuilder;

    /** @var State */
    protected $appState;

    /** @var Products */
    protected $productImporter;

    /**
     * Products constructor
     *
     * @param Helper $helper
     * @param ProductRepositoryInterface $productRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param State $appState
     */
    public function __construct(
        Helper $helper,
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        State $appState,
        Products $productImporter
    ) {
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->appState = $appState;
        $this->helper = $helper;
        $this->productImporter = $productImporter;
    }

    /**
     * Install
     *
     * @param array $row
     * @param array $settings
     * @return bool
     */
    public function install(array $rows, array $header, string $modulePath, array $settings)
    {
        foreach ($rows as $row) {
            $productsArray[] = array_combine($header, $row);
        }
        
        //pass to product importer
        //running twice as append mode adds duplicate amounts, so giftcard is deleted and then added 
        $this->productImporter->install($rows, $header, $modulePath, $settings, 'delete');
        $this->productImporter->install($rows, $header, $modulePath, $settings);
        
        //update gift cards
        foreach ($productsArray as $row) {
            $this->updateGiftCard($row);
        }
        return true;
    }

    protected function updateGiftCard(array $row)
    {
        if (!empty($row['sku'])) {
            try {
                 $product = $this->productRepository->get($row['sku']);
                $this->appState->emulateAreaCode(
                    AppArea::AREA_ADMINHTML,
                    [$this->productRepository, 'save'],
                    [$product]
                );
            } catch (Exception $e) {
                $this->helper->logMessage("Giftcard with sku ".$row['sku'].
                " cannot be updated. ".$e->getMessage(), "warning");
            }
        } else {
            $this->helper->logMessage("sku column required in gift_cards file", "warning");
        }
    }
}
