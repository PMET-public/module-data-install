<?php

/**
 * Copyright © Adobe. All rights reserved.
 */
namespace MagentoEse\DataInstall\Model\DataTypes;

use MagentoEse\DataInstall\Helper\Helper;
use Magento\InventoryApi\Api\Data\StockInterfaceFactory;
use Magento\InventoryApi\Api\Data\StockInterface;
use Magento\InventoryApi\Api\StockRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterfaceFactory;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;

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

    /**
     * constructor.
     * @param Helper $helper
     * @param StockInterfaceFactory $stockInterfaceFactory
     * @param StockRepositoryInterface $stockRepositoryInterface
     * @param SearchCriteriaBuilder $searchCriteria
     * @param Stores $stores
     * @param SalesChannelInterfaceFactory $salesChannelInterfaceFactory
     */
    public function __construct(
        Helper $helper, StockInterfaceFactory $stockInterfaceFactory, 
        StockRepositoryInterface $stockRepositoryInterface, SearchCriteriaBuilder $searchCriteria,
        Stores $stores, SalesChannelInterfaceFactory $salesChannelInterfaceFactory
    ) {
        $this->helper = $helper;
        $this->stockInterfaceFactory = $stockInterfaceFactory;
        $this->stockRepository = $stockRepositoryInterface;
        $this->searchCriteria = $searchCriteria;
        $this->stores = $stores;
        $this->salesChannelInterfaceFactory = $salesChannelInterfaceFactory;
     }

    /**
     * install
     *
     * @param  mixed $rows
     * @param  mixed $header
     * @param  mixed $modulePath
     * @param  mixed $settings
     * @return void
     */
    public function install(array $row, array $settings)
    {
        if (empty($row['stock_name'])) {
            $this->helper->printMessage("A row in msi_stock file does not have a value for stock_name. Row is skipped", "warning");
            return true;
        }
        //validate that site_code exists
        $siteCodes = explode(",",preg_replace('/\s+/', '', $row['site_code']));
        $websiteCodes = [];
        foreach($siteCodes as $siteCode){
            if($this->stores->getWebsiteId($siteCode)){
                $websiteCodes[] = $siteCode;
            }elseif($siteCode !=''){
                $this->helper->printMessage("site_code ".$siteCode. " does not exist. Assignment to stock is skipped", "warning");
            }
        }

        $search = $this->searchCriteria->addFilter(StockInterface::NAME, $row['stock_name'], 'eq')->create()->setPageSize(1)->setCurrentPage(1);;

        $stock = $this->stockRepository->getList($search)->getItems();

        if(!$stock){
            $stock = $this->stockInterfaceFactory->create();
        }else{
            $stock = $stock[0];
        }
        
        $stock->setName($row['stock_name']);
        $this->stockRepository->save($stock);
        //set sales channel on stock
        if(!empty($websiteCodes)){
            $stockId = $stock->getStockId();
            //$stock = $this->stockRepository->get($stockId);
            $extensionAttributes = $stock->getExtensionAttributes();
            $salesChannels = [];
            foreach($websiteCodes as $websiteCode){
                $salesChannel = $this->salesChannelInterfaceFactory->create();
                $salesChannel->setCode($websiteCode);
                $salesChannel->setType(SalesChannelInterface::TYPE_WEBSITE);
                $salesChannels[]=$salesChannel;
            }
            $extensionAttributes->setSalesChannels($salesChannels);
            $this->stockRepository->save($stock);
        }
        return true;
    }
}
