<?php

/**
 * Copyright © Adobe. All rights reserved.
 */
namespace MagentoEse\DataInstall\Model\DataTypes;

use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Validation\ValidationException;
use MagentoEse\DataInstall\Helper\Helper;
use Magento\InventoryApi\Api\Data\StockInterfaceFactory;
use Magento\InventoryApi\Api\Data\StockInterface;
use Magento\InventoryApi\Api\StockRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterfaceFactory;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventoryApi\Api\Data\StockSourceLinkInterfaceFactory;
use Magento\InventoryApi\Api\Data\StockSourceLinkInterface;
use Magento\InventoryApi\Api\StockSourceLinksSaveInterface;
use Magento\InventoryApi\Api\StockSourceLinksDeleteInterface;
use Magento\InventoryApi\Api\GetStockSourceLinksInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\InventoryApi\Api\Data\SourceInterfaceFactory;
use Magento\Framework\Exception\NoSuchEntityException;

class MsiStock
{
    /** @var Helper */
    protected $helper;

    /** @var StockInterfaceFactory */
    protected $stockInterfaceFactory;

    /** @var StockRepositoryInterface */
    protected $stockRepository;

    /** @var SearchCriteriaBuilder */
    protected $searchCriteria;

    /** @var Stores */
    protected $stores;

    /** @var SalesChannelInterfaceFactory */
    protected $salesChannelInterfaceFactory;

    /** @var StockSourceLinkInterfaceFactory */
    protected $stockSourceLinkInterfaceFactory;

    /** @var StockSourceLinksSaveInterface */
    protected $stockSourceLinksSaveInterface;

    /** @var StockSourceLinksDeleteInterface */
    protected $stockSourceLinksDeleteInterface;

    /** @var GetStockSourceLinksInterface */
    protected $getStockSourceLinksInterface;

    /** @var SourceRepositoryInterface */
    protected $sourceRepository;

    /** @var SourceInterfaceFactory */
    protected $sourceInterfaceFactory;

    /**
     * constructor.
     * @param Helper $helper
     * @param StockInterfaceFactory $stockInterfaceFactory
     * @param StockRepositoryInterface $stockRepositoryInterface
     * @param SearchCriteriaBuilder $searchCriteria
     * @param Stores $stores
     * @param SalesChannelInterfaceFactory $salesChannelInterfaceFactory
     * @param StockSourceLinkInterfaceFactory $stockSourceLinkInterfaceFactory
     * @param StockSourceLinksSaveInterface $stockSourceLinksSaveInterface
     * @param StockSourceLinksDeleteInterface $stockSourceLinksDeleteInterface
     * @param GetStockSourceLinksInterface $getStockSourceLinksInterface
     * @param SourceRepositoryInterface $sourceRepository
     * @param SourceInterfaceFactory $sourceInterfaceFactory
     */
    public function __construct(
        Helper $helper,
        StockInterfaceFactory $stockInterfaceFactory,
        StockRepositoryInterface $stockRepositoryInterface,
        SearchCriteriaBuilder $searchCriteria,
        Stores $stores,
        SalesChannelInterfaceFactory $salesChannelInterfaceFactory,
        StockSourceLinkInterfaceFactory $stockSourceLinkInterfaceFactory,
        StockSourceLinksSaveInterface $stockSourceLinksSaveInterface,
        SourceRepositoryInterface $sourceRepository,
        SourceInterfaceFactory $sourceInterfaceFactory,
        StockSourceLinksDeleteInterface $stockSourceLinksDeleteInterface,
        GetStockSourceLinksInterface $getStockSourceLinksInterface
    ) {
        $this->helper = $helper;
        $this->stockInterfaceFactory = $stockInterfaceFactory;
        $this->stockRepository = $stockRepositoryInterface;
        $this->searchCriteria = $searchCriteria;
        $this->stores = $stores;
        $this->salesChannelInterfaceFactory = $salesChannelInterfaceFactory;
        $this->stockSourceLinkInterfaceFactory = $stockSourceLinkInterfaceFactory;
        $this->stockSourceLinksSaveInterface = $stockSourceLinksSaveInterface;
        $this->sourceRepository = $sourceRepository;
        $this->sourceInterfaceFactory = $sourceInterfaceFactory;
        $this->stockSourceLinksDeleteInterface = $stockSourceLinksDeleteInterface;
        $this->getStockSourceLinksInterface = $getStockSourceLinksInterface;
    }

    /**
     * @param array $row
     * @param array $settings
     * @return bool
     * @throws CouldNotSaveException
     * @throws ValidationException
     */
    public function install(array $row, array $settings)
    {
        if (empty($row['stock_name'])) {
            $this->helper->logMessage(
                "A row in msi_stock file does not have a value for stock_name. Row is skipped",
                "warning"
            );
            return true;
        }
        //validate that site_code exists
        $siteCodes = explode(",", preg_replace('/\s+/', '', $row['site_code']));
        $websiteCodes = [];
        foreach ($siteCodes as $siteCode) {
            if ($this->stores->getWebsiteId($siteCode)) {
                $websiteCodes[] = $siteCode;
            } elseif ($siteCode !='') {
                $this->helper->logMessage(
                    "site_code ".$siteCode. " does not exist. Assignment to stock is skipped",
                    "warning"
                );
            }
        }

        $search = $this->searchCriteria->addFilter(StockInterface::NAME, $row['stock_name'], 'eq')
        ->create()->setPageSize(1)->setCurrentPage(1);
        $stock = $this->stockRepository->getList($search)->getItems();

        if (!$stock) {
            $stock = $this->stockInterfaceFactory->create();
        } else {
            $stock = $stock[0];
        }

        $stock->setName($row['stock_name']);
        $this->stockRepository->save($stock);
        //set sales channel on stock
        if (!empty($websiteCodes)) {
            $stockId = $stock->getStockId();
            //$stock = $this->stockRepository->get($stockId);
            $extensionAttributes = $stock->getExtensionAttributes();
            $salesChannels = [];
            foreach ($websiteCodes as $websiteCode) {
                $salesChannel = $this->salesChannelInterfaceFactory->create();
                $salesChannel->setCode($websiteCode);
                $salesChannel->setType(SalesChannelInterface::TYPE_WEBSITE);
                $salesChannels[]=$salesChannel;
            }
            $extensionAttributes->setSalesChannels($salesChannels);
            $this->stockRepository->save($stock);
        }
        if (!empty($row['source_code'])) {
            $this->setStockSource(explode(",", trim($row['source_code'])), $stock->getStockId());
        }
        return true;
    }

    /**
     * @param $sourceCodes
     * @param $stockId
     * @throws CouldNotSaveException
     * @throws ValidationException
     * @throws CouldNotDeleteException
     */
    private function setStockSource($sourceCodes, $stockId)
    {
        //get current links assigned to stock
        $search = $this->searchCriteria->addFilter(StockSourceLinkInterface::STOCK_ID, $stockId, 'eq')->create();
        $stockLinks = $this->getStockSourceLinksInterface->execute($search)->getItems();
        //delete current links
        if (!empty($stockLinks)) {
            $this->stockSourceLinksDeleteInterface->execute($stockLinks);
        }

        //assign source to stock
         $sourceLinks=[];
         $priority = 1;
        foreach ($sourceCodes as $sourceCode) {
            try {
                $source = $this->sourceRepository->get($sourceCode);
            } catch (NoSuchEntityException $e) {
                $this->helper->logMessage(
                    "Msi source ".$sourceCode." is not available to assign to stock",
                    "warning"
                );
                return;
            }
            $sourceLink = $this->stockSourceLinkInterfaceFactory->create();
            $sourceLink->setSourceCode($sourceCode);
            $sourceLink->setStockId($stockId);
            $sourceLink->setPriority($priority);
            $sourceLinks[]=$sourceLink;
            $priority ++;
        }
         $this->stockSourceLinksSaveInterface->execute($sourceLinks);
    }
}
