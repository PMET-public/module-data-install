<?php

/**
 * Copyright © Adobe. All rights reserved.
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

    /**
     * Products constructor.
     * @param Helper $helper
     * @param ProductRepositoryInterface $productRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param State $appState
     */
    public function __construct(
        Helper $helper,
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        State $appState
    ) {
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->appState = $appState;
        $this->helper = $helper;
    }

    /**
     * @param array $rows
     * @param array $settings
     */
    public function install(array $row, array $settings)
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
                $this->helper->printMessage("Product with sku ".$row['sku'].
                " not found in in gift_cards.csv", "warning");
            }
        } else {
            $this->helper->printMessage("sku column required in gift_cards.csv", "warning");
        }
        return true;
    }
}
