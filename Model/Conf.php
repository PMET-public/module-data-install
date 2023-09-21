<?php
/**
 * Copyright 2022 Adobe, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace MagentoEse\DataInstall\Model;

use MagentoEse\DataInstall\Model\DataTypes\AdminRoles;
use MagentoEse\DataInstall\Model\DataTypes\AdminUsers;
use MagentoEse\DataInstall\Model\DataTypes\AdvancedPricing;
use MagentoEse\DataInstall\Model\DataTypes\ApprovalRules;
use MagentoEse\DataInstall\Model\DataTypes\Blocks;
use MagentoEse\DataInstall\Model\DataTypes\CartRules;
use MagentoEse\DataInstall\Model\DataTypes\CatalogRules;
use MagentoEse\DataInstall\Model\DataTypes\Categories;
use MagentoEse\DataInstall\Model\DataTypes\Companies;
use MagentoEse\DataInstall\Model\DataTypes\CompanyRoles;
use MagentoEse\DataInstall\Model\DataTypes\CompanyUserRoles;
use MagentoEse\DataInstall\Model\DataTypes\Configuration;
use MagentoEse\DataInstall\Model\DataTypes\CustomerAddresses;
use MagentoEse\DataInstall\Model\DataTypes\CustomerAttributes;
use MagentoEse\DataInstall\Model\DataTypes\CustomerGroups;
use MagentoEse\DataInstall\Model\DataTypes\Customers;
use MagentoEse\DataInstall\Model\DataTypes\CustomerSegments;
use MagentoEse\DataInstall\Model\DataTypes\DynamicBlocks;
use MagentoEse\DataInstall\Model\DataTypes\GiftCards;
use MagentoEse\DataInstall\Model\DataTypes\MsiInventory;
use MagentoEse\DataInstall\Model\DataTypes\MsiSource;
use MagentoEse\DataInstall\Model\DataTypes\MsiStock;
use MagentoEse\DataInstall\Model\DataTypes\NegotiatedQuotes;
use MagentoEse\DataInstall\Model\DataTypes\Orders;
use MagentoEse\DataInstall\Model\DataTypes\Pages;
use MagentoEse\DataInstall\Model\DataTypes\ProductAttributes;
use MagentoEse\DataInstall\Model\DataTypes\Products;
use MagentoEse\DataInstall\Model\DataTypes\RequisitionLists;
use MagentoEse\DataInstall\Model\DataTypes\Reviews;
use MagentoEse\DataInstall\Model\DataTypes\RewardExchangeRate;
use MagentoEse\DataInstall\Model\DataTypes\SharedCatalogCategories;
use MagentoEse\DataInstall\Model\DataTypes\SharedCatalogs;
use MagentoEse\DataInstall\Model\DataTypes\Stores;
use MagentoEse\DataInstall\Model\DataTypes\Teams;
use MagentoEse\DataInstall\Model\DataTypes\Templates;
use MagentoEse\DataInstall\Model\DataTypes\Upsells;
use MagentoEse\DataInstall\Model\DataTypes\Widgets;

class Conf
{
     /** @var DataTypes\Stores  */
     protected $storeInstall;

     /** @var DataTypes\ProductAttributes  */
     protected $productAttributesInstall;

     /** @var DataTypes\Categories  */
     protected $categoryInstall;

     /** @var DataTypes\Products  */
     protected $productInstall;

     /** @var DataTypes\GiftCards  */
     protected $giftCardsInstall;

     /** @var DataTypes\DirectoryList  */
     protected $directoryList;

     /** @var DataTypes\Pages  */
     protected $pageInstall;

     /** @var DataTypes\Blocks  */
     protected $blockInstall;

     /** @var DataTypes\DynamicBlocks  */
     protected $dynamicBlockInstall;

     /** @var DataTypes\Widgets  */
     protected $widgetInstall;

     /** @var DataTypes\Configuration  */
     protected $configurationInstall;

     /** @var DataTypes\CustomerGroups  */
     protected $customerGroupInstall;

     /** @var DataTypes\CustomerAttributes  */
     protected $customerAttributeInstall;

      /** @var DataTypes\RewardExchangeRate  */
      protected $rewardExchangeRateInstall;

     /** @var DataTypes\Customers  */
     protected $customerInstall;

     /** @var DataTypes\CustomerAddresses  */
     protected $customerAddressesInstall;

     /** @var DataTypes\Reviews  */
     protected $reviewsInstall;

     /** @var Validate */
     protected $validate;

     /** @var DataTypes\Templates  */
     protected $templatesInstall;

     /** @var DataTypes\Upsells */
     protected $upsellsInstall;

     /** @var CopyMedia */
     protected $copyMedia;

     /** @var DataTypes\MsiStock */
     protected $msiStockInstall;

     /** @var DataTypes\MsiSource */
     protected $msiSourceInstall;

     /** @var DataTypes\MsiInventory */
     protected $msiInventoryInstall;

     /** @var DataTypes\AdminUsers  */
     protected $adminUsersInstall;

     /** @var DataTypes\AdminRoles  */
     protected $adminRolesInstall;

     /** @var DriverInterface */
     protected $driverInterface;

     /** @var DataTypes\AdvancedPricing */
     protected $advancedPricingInstall;

     /** @var DataTypes\Orders */
     protected $ordersInstall;

     /** @var DataTypes\CustomerSegments */
    protected $customerSegmentsInstall;

    /** @var DataTypes\CatalogRules */
    protected $catalogRulesInstall;

    /** @var DataTypes\CartRules */
    protected $cartRulesInstall;

    /** @var DataTypes\Companies */
    protected $companiesInstall;

    /** @var DataTypes\CompanyRoles */
    protected $companyRolesInstall;

    /** @var DataTypes\CompanyUserRoles */
    protected $companyUserRolesInstall;

    /** @var DataTypes\RequisitionLists */
    protected $requisitionListsInstall;

    /** @var DataTypes\SharedCatalogs */
    protected $sharedCatalogsInstall;

    /** @var DataTypes\SharedCatalogCategories */
    protected $sharedCatalogCategoriesInstall;

    /** @var DataTypes\Teams */
    protected $companyTeamsInstall;

    /** @var DataTypes\ApprovalRules */
    protected $approvalRulesInstall;

    /** @var array]  */
    public const STAGE2_FILES = ['msi_inventory.csv','stock_sources.csv','stock_sources.json',
    'gift_cards.csv','gift_cards.json'];

    /** @var array]  */
    public const B2B_REQUIRED_FILES = ['b2b_customers.csv','b2b_companies.csv','b2b_company_roles.csv',
    'b2b_sales_reps.csv','b2b_teams.csv'];

    /** @var array  */
    public const B2B_COMPANY_COLUMNS = ['site_code','legal_name','company_name','company_email','street','city',
    'country_id','region','postcode','telephone','credit_export','reseller_id','vat_tax_id','company_admin','address'];

    /** @var array  */
    public const B2B_NEGOTIATED_COLUMNS = ['store','customer_email','currency','status','quote_name',
        'negotiated_price_type','negotiated_price_value','expiration_period','creator_type','creator_id',
        'base_negotiated_total_price'];

    /** @var array  */
    public const SETTINGS = ['site_code'=>'base', 'store_code'=>'main_website_store','store_view_code'=>'default',
        'root_category' => 'Default Category', 'root_category_id' => '2'];

    private NegotiatedQuotes $negotiatedQuotesInstall;

    /**
     * Conf constructor
     *
     * @param NegotiatedQuotes $negotiatedQuotes
     * @param AdminUsers $adminUsers
     * @param AdminRoles $adminRoles
     * @param AdvancedPricing $advancedPricing
     * @param ApprovalRules $approvalRules
     * @param Blocks $blocks
     * @param CatalogRules $catalogRules
     * @param CartRules $cartRules
     * @param Categories $categories
     * @param Companies $companies
     * @param CompanyRoles $companyRoles
     * @param CompanyUserRoles $companyUserRoles
     * @param Configuration $configuration
     * @param CustomerGroups $customerGroups
     * @param CustomerAttributes $customerAttributes
     * @param RewardExchangeRate $rewardExchangeRate
     * @param Customers $customers
     * @param CustomerAddresses $customerAddresses
     * @param CustomerSegments $customerSegments
     * @param DynamicBlocks $dynamicBlocks
     * @param Widgets $widgets
     * @param MsiStock $msiStock
     * @param MsiSource $msiSource
     * @param MsiInventory $msiInventory
     * @param Orders $orders
     * @param Pages $pages
     * @param ProductAttributes $productAttributes
     * @param Products $products
     * @param GiftCards $giftCards
     * @param RequisitionLists $requisitionLists
     * @param SharedCatalogs $sharedCatalogs
     * @param SharedCatalogCategories $sharedCatalogCategories
     * @param Stores $stores
     * @param Reviews $reviews
     * @param Teams $teams
     * @param Templates $templates
     * @param Upsells $upsells
     */
    public function __construct(
        DataTypes\NegotiatedQuotes $negotiatedQuotes,
        DataTypes\AdminUsers $adminUsers,
        DataTypes\AdminRoles $adminRoles,
        DataTypes\AdvancedPricing $advancedPricing,
        DataTypes\ApprovalRules $approvalRules,
        DataTypes\Blocks $blocks,
        DataTypes\CatalogRules $catalogRules,
        DataTypes\CartRules $cartRules,
        DataTypes\Categories $categories,
        DataTypes\Companies $companies,
        DataTypes\CompanyRoles $companyRoles,
        DataTypes\CompanyUserRoles $companyUserRoles,
        DataTypes\Configuration $configuration,
        DataTypes\CustomerGroups $customerGroups,
        DataTypes\CustomerAttributes $customerAttributes,
        DataTypes\RewardExchangeRate $rewardExchangeRate,
        DataTypes\Customers $customers,
        DataTypes\CustomerAddresses $customerAddresses,
        DataTypes\CustomerSegments $customerSegments,
        DataTypes\DynamicBlocks $dynamicBlocks,
        DataTypes\Widgets $widgets,
        DataTypes\MsiStock $msiStock,
        DataTypes\MsiSource $msiSource,
        DataTypes\MsiInventory $msiInventory,
        DataTypes\Orders $orders,
        DataTypes\Pages $pages,
        DataTypes\ProductAttributes $productAttributes,
        DataTypes\Products $products,
        DataTypes\GiftCards $giftCards,
        DataTypes\RequisitionLists $requisitionLists,
        DataTypes\SharedCatalogs $sharedCatalogs,
        DataTypes\SharedCatalogCategories $sharedCatalogCategories,
        DataTypes\Stores $stores,
        DataTypes\Reviews $reviews,
        DataTypes\Teams $teams,
        DataTypes\Templates $templates,
        DataTypes\Upsells $upsells
    ) {
        $this->storeInstall = $stores;
        $this->productAttributesInstall = $productAttributes;
        $this->categoryInstall = $categories;
        $this->productInstall = $products;
        $this->giftCardsInstall = $giftCards;
        $this->pageInstall = $pages;
        $this->blockInstall = $blocks;
        $this->dynamicBlockInstall = $dynamicBlocks;
        $this->widgetInstall = $widgets;
        $this->configurationInstall = $configuration;
        $this->customerGroupInstall = $customerGroups;
        $this->customerAttributeInstall = $customerAttributes;
        $this->rewardExchangeRateInstall = $rewardExchangeRate;
        $this->customerInstall = $customers;
        $this->customerAddressesInstall = $customerAddresses;
        $this->reviewsInstall = $reviews;
        $this->templatesInstall = $templates;
        $this->upsellsInstall = $upsells;
        $this->msiStockInstall = $msiStock;
        $this->msiSourceInstall = $msiSource;
        $this->msiInventoryInstall = $msiInventory;
        $this->adminUsersInstall = $adminUsers;
        $this->adminRolesInstall = $adminRoles;
        $this->advancedPricingInstall = $advancedPricing;
        $this->approvalRulesInstall = $approvalRules;
        $this->ordersInstall = $orders;
        $this->customerSegmentsInstall = $customerSegments;
        $this->catalogRulesInstall = $catalogRules;
        $this->cartRulesInstall = $cartRules;
        $this->companiesInstall = $companies;
        $this->companyRolesInstall = $companyRoles;
        $this->companyUserRolesInstall = $companyUserRoles;
        $this->requisitionListsInstall = $requisitionLists;
        $this->sharedCatalogsInstall = $sharedCatalogs;
        $this->sharedCatalogCategoriesInstall = $sharedCatalogCategories;
        $this->companyTeamsInstall = $teams;
        $this->negotiatedQuotesInstall = $negotiatedQuotes;
    }

    /**
     * Get configuration for process class
     *
     * @return \array[][]
     */
    public function getProcessConfiguration()
    {
        return[
            ['stores.csv'=>['process'=>'rows','class'=>$this->storeInstall,
            'label'=>'Loading Sites, Stores and Views']],
            ['stores.json'=>['process'=>'graphqlrows','class'=>$this->storeInstall,
            'label'=>'Loading Sites, Stores and Views']],
            ['config_default.json'=>['process'=>'json','class'=>$this->configurationInstall,
            'label'=>'Loading config_default.json']],
            ['config_default.csv'=>['process'=>'rows','class'=>$this->configurationInstall,
            'label'=>'Loading config_default.csv']],
            ['config_vertical.json'=>['process'=>'json','class'=>$this->configurationInstall,
            'label'=>'Loading config_vertical.json']],
            ['config_vertical.csv'=>['process'=>'rows','class'=>$this->configurationInstall,
            'label'=>'Loading config_vertical.csv']],
            ['config_secret.json'=>['process'=>'json','class'=>$this->configurationInstall,
            'label'=>'Loading config_secret.json']],
            ['config_secret.csv'=>['process'=>'rows','class'=>$this->configurationInstall,
            'label'=>'Loading config_secret.csv']],
            ['config.json'=>['process'=>'json','class'=>$this->configurationInstall,
            'label'=>'Loading config.json']],
            ['config.csv'=>['process'=>'rows','class'=>$this->configurationInstall,
            'label'=>'Loading config.csv']],
            ['store_configurations.json'=>['process'=>'graphqlrows','class'=>$this->configurationInstall,
            'label'=>'Loading store_configurations.json']],
            ['admin_roles.csv'=>['process'=>'file','class'=>$this->adminRolesInstall,
            'label'=>'Loading Admin Roles']],
            ['admin_roles.json'=>['process'=>'graphqlfile','class'=>$this->adminRolesInstall,
            'label'=>'Loading Admin Roles']],
            ['admin_users.csv'=>['process'=>'rows','class'=>$this->adminUsersInstall,
            'label'=>'Loading Admin Users']],
            ['admin_users.json'=>['process'=>'graphqlrows','class'=>$this->adminUsersInstall,
            'label'=>'Loading Admin Users']],
            ['customer_groups.csv'=>['process'=>'rows','class'=>$this->customerGroupInstall,
            'label'=>'Loading Customer Groups']],
            ['customer_groups.json'=>['process'=>'graphqlrows','class'=>$this->customerGroupInstall,
            'label'=>'Loading Customer Groups']],
            ['customer_attributes.csv'=>['process'=>'rows','class'=>$this->customerAttributeInstall,
            'label'=>'Loading Customer Attributes']],
            ['customer_attributes.json'=>['process'=>'graphqlrows','class'=>$this->customerAttributeInstall,
            'label'=>'Loading Customer Attributes']],
            ['reward_exchange_rate.csv'=>['process'=>'rows','class'=>$this->rewardExchangeRateInstall,
            'label'=>'Loading Rewards Exchange Rate']],
            ['reward_exchange_rate.json'=>['process'=>'graphqlrows','class'=>$this->rewardExchangeRateInstall,
            'label'=>'Loading Rewards Exchange Rate']],
            ['customers.csv'=>['process'=>'file','class'=>$this->customerInstall,
            'label'=>'Loading Customers']],
            ['customers.json'=>['process'=>'graphqlexport','class'=>$this->customerInstall,
            'label'=>'Loading Customers']],
            ['customer_addresses.csv'=>['process'=>'file','class'=>$this->customerAddressesInstall,
            'label'=>'Loading Customer Addresses']],
            ['customer_addresses.json'=>['process'=>'graphqlexport','class'=>$this->customerAddressesInstall,
            'label'=>'Loading Customer Addresses']],
            ['product_attributes.csv'=>['process'=>'rows','class'=>$this->productAttributesInstall,
            'label'=>'Loading Product Attributes']],
            ['product_attributes.json'=>['process'=>'graphqlrows','class'=>$this->productAttributesInstall,
            'label'=>'Loading Product Attributes']],
            ['blocks.csv'=>['process'=>'rows','class'=>$this->blockInstall,
            'label'=>'Loading Blocks']],
            ['blocks.json'=>['process'=>'graphqlrows','class'=>$this->blockInstall,
            'label'=>'Loading Blocks']],
            ['categories.csv'=>['process'=>'rows','class'=>$this->categoryInstall,
            'label'=>'Loading Categories']],
            ['categories.json'=>['process'=>'graphqlrows','class'=>$this->categoryInstall,
            'label'=>'Loading Categories']],
            ['customer_segments.csv'=>['process'=>'rows','class'=>$this->customerSegmentsInstall,
            'label'=>'Loading Customer Segments']],
            ['customer_segments.json'=>['process'=>'graphqlrows','class'=>$this->customerSegmentsInstall,
            'label'=>'Loading Customer Segments']],
            ['msi_source.csv'=>['process'=>'rows','class'=>$this->msiSourceInstall,
            'label'=>'Loading MSI Source']],
            ['msi_source.json'=>['process'=>'graphqlrows','class'=>$this->msiSourceInstall,
            'label'=>'Loading MSI Source']],
            ['msi_stock.csv'=>['process'=>'rows','class'=>$this->msiStockInstall,
            'label'=>'Loading MSI Stock']],
            ['msi_stock.json'=>['process'=>'graphqlrows','class'=>$this->msiStockInstall,
            'label'=>'Loading MSI Stock']],
            ['products.json'=>['process'=>'graphqlexport','class'=>$this->productInstall,
            'label'=>'Loading Products']],
            ['products.csv'=>['process'=>'file','class'=>$this->productInstall,
            'label'=>'Loading Products']],
            ['gift_cards.csv'=>['process'=>'file','class'=>$this->giftCardsInstall,
            'label'=>'Updating Gift Cards']],
            ['gift_cards.json'=>['process'=>'graphqlexport','class'=>$this->giftCardsInstall,
            'label'=>'Updating Gift Cards']],
            ['msi_inventory.csv'=>['process'=>'file','class'=>$this->msiInventoryInstall,
            'label'=>'Loading MSI Inventory']],
            ['upsells.csv'=>['process'=>'rows','class'=>$this->upsellsInstall,
            'label'=>'Loading Upsells']],
            ['upsells.json'=>['process'=>'graphqlrows','class'=>$this->upsellsInstall,
            'label'=>'Loading Upsells']],
            ['blocks.csv'=>['process'=>'rows','class'=>$this->blockInstall,
            'label'=>'Loading Blocks']],
            ['blocks.json'=>['process'=>'graphqlrows','class'=>$this->blockInstall,
            'label'=>'Loading Blocks']],
            ['dynamic_blocks.csv'=>['process'=>'rows','class'=>$this->dynamicBlockInstall,
            'label'=>'Loading Dynamic blocks']],
            ['dynamic_blocks.json'=>['process'=>'graphqlrows','class'=>$this->dynamicBlockInstall,
            'label'=>'Loading Dynamic blocks']],
            ['dynamic_blocks.csv'=>['process'=>'rows','class'=>$this->dynamicBlockInstall,
            'label'=>'Loading Dynamic blocks']],
            ['dynamic_blocks.json'=>['process'=>'graphqlrows','class'=>$this->dynamicBlockInstall,
            'label'=>'Loading Dynamic blocks']],
            ['widgets.csv'=>['process'=>'rows','class'=>$this->widgetInstall,
            'label'=>'Loading Widgets']],
            ['widgets.json'=>['process'=>'graphqlrows','class'=>$this->widgetInstall,
            'label'=>'Loading Widgets']],
            ['catalog_rules.csv'=>['process'=>'rows','class'=>$this->catalogRulesInstall,
            'label'=>'Loading Catalog Rules']],
            ['catalog_rules.json'=>['process'=>'graphqlrows','class'=>$this->catalogRulesInstall,
            'label'=>'Loading Catalog Rules']],
            ['pages.csv'=>['process'=>'rows','class'=>$this->pageInstall,
            'label'=>'Loading Pages']],
            ['pages.json'=>['process'=>'graphqlrows','class'=>$this->pageInstall,
            'label'=>'Loading Pages']],
            ['templates.csv'=>['process'=>'rows','class'=>$this->templatesInstall,
            'label'=>'Loading Templates']],
            ['templates.json'=>['process'=>'graphqlrows','class'=>$this->templatesInstall,
            'label'=>'Loading Templates']],
            ['reviews.csv'=>['process'=>'rows','class'=>$this->reviewsInstall,
            'label'=>'Loading Reviews']],
            ['reviews.json'=>['process'=>'graphqlrows','class'=>$this->reviewsInstall,
            'label'=>'Loading Reviews']],
            ['b2b_companies.csv'=>['process'=>'b2b',
            'class'=>['customerInstall'=>$this->customerInstall,'adminUsersInstall'=>$this->adminUsersInstall,
            'companiesInstall'=>$this->companiesInstall,'companyRolesInstall'=>$this->companyRolesInstall,
            'companyUserRolesInstall'=>$this->companyUserRolesInstall,
            'companyTeamsInstall'=>$this->companyTeamsInstall],
            'label'=>'Loading B2B Data']],
            ['b2b_companies.json'=>['process'=>'b2bgraphql',
            'class'=>['customerInstall'=>$this->customerInstall,'adminUsersInstall'=>$this->adminUsersInstall,
            'companiesInstall'=>$this->companiesInstall,'companyRolesInstall'=>$this->companyRolesInstall,
            'companyUserRolesInstall'=>$this->companyUserRolesInstall,
            'requisitionListsInstall'=>$this->requisitionListsInstall,
            'companyTeamsInstall'=>$this->companyTeamsInstall,'sharedCatalogInstall'=>$this->sharedCatalogsInstall,
            'sharedCatalogCategoriesInstall'=>$this->sharedCatalogCategoriesInstall,
            'approvalRulesInstall'=>$this->approvalRulesInstall],
            'label'=>'Loading B2B Data']],
            ['b2b_shared_catalogs.csv'=>['process'=>'rows','class'=>$this->sharedCatalogsInstall,
            'label'=>'Loading B2B Shared Catalogs']],
            ['b2b_shared_catalog_categories.csv'=>['process'=>'file','class'=>$this->sharedCatalogCategoriesInstall,
            'label'=>'Loading B2B Shared Catalog Categories']],
            ['b2b_requisition_lists.csv'=>['process'=>'rows','class'=>$this->requisitionListsInstall,
            'label'=>'Loading B2B Requisiton Lists']],
            ['b2b_approval_rules.csv'=>['process'=>'rows','class'=>$this->approvalRulesInstall,
            'label'=>'Loading B2B Approval Rules']],
            ['b2b_negotiated_quotes.json'=>['process'=>'json','class'=>$this->negotiatedQuotesInstall,
            'label'=>'Loading B2B Quotes']],
            ['cart_rules.csv'=>['process'=>'rows','class'=>$this->cartRulesInstall,
            'label'=>'Loading Cart Rules']],
            ['cart_rules.json'=>['process'=>'graphqlrows','class'=>$this->cartRulesInstall,
            'label'=>'Loading Cart Rules']],
            ['advanced_pricing.csv'=>['process'=>'file','class'=>$this->advancedPricingInstall,
            'label'=>'Loading Advanced Pricing']],
            ['advanced_pricing.json'=>['process'=>'graphqlexport','class'=>$this->advancedPricingInstall,
            'label'=>'Loading Advanced Pricing']],
            ['orders.csv'=>['process'=>'rows','class'=>$this->ordersInstall,
            'label'=>'Loading Orders']],
            ['stock_sources.csv'=>['process'=>'file','class'=>$this->msiInventoryInstall,
            'label'=>'Loading MSI Inventory']],
            ['stock_sources.json'=>['process'=>'graphqlexport','class'=>$this->msiInventoryInstall,
            'label'=>'Loading MSI Inventory']],
        ];
    }
}
