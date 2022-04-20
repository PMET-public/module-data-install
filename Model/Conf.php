<?php
/** Copyright © Adobe  All rights reserved */
namespace MagentoEse\DataInstall\Model;

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
     protected $orderInstall;

     /** @var Datatypes\CustomerSegments */
    protected $customerSegmentsInstall;

    /** @var Datatypes\CatalogRules */
    protected $catalogRulesInstall;

    /** @var Datatypes\CartRules */
    protected $cartRulesInstall;

    /** @var Datatypes\Companies */
    protected $companiesInstall;

    /** @var Datatypes\CompanyRoles */
    protected $companyRolesInstall;

    /** @var Datatypes\CompanyUserRoles */
    protected $companyUserRolesInstall;

    /** @var Datatypes\RequisitionLists */
    protected $requisitionListsInstall;

    /** @var Datatypes\SharedCatalogs */
    protected $sharedCatalogsInstall;

    /** @var Datatypes\SharedCatalogCategories */
    protected $sharedCatalogCategoriesInstall;

    /** @var Datatypes\Teams */
    protected $companyTeamsInstall;

    /** @var Datatypes\ApprovalRules */
    protected $approvalRulesInstall;

    /** @var string[]  */
    const ALL_FILES = ['stores.csv',
    'config_default.json',
    'config_default.csv',
    'config_vertical.json',
    'config_vertical.csv',
    'config_secret.json',
    'config_secret.csv',
    'config.json',
    'config.csv',
    'admin_roles.csv',
    'admin_users.csv',
    'customer_groups.csv',
    'customer_attributes.csv',
    'reward_exchange_rate.csv',
    'customers.csv',
    'customer_addresses.csv',
    'product_attributes.csv',
    'blocks.csv',
    'categories.csv',
    'customer_segments.csv',
    'products.csv',
    'gift_cards.csv',
    'msi_source.csv',
    'msi_stock.csv',
    'msi_inventory.csv',
    'upsells.csv',
    'blocks.csv',
    'dynamic_blocks.csv',
    'widgets.csv',
    'catalog_rules.csv',
    'pages.csv',
    'templates.csv',
    'reviews.csv',
    'b2b_companies.csv',
    'b2b_shared_catalogs.csv',
    'b2b_shared_catalog_categories.csv',
    'b2b_requisition_lists.csv',
    'b2b_approval_rules.csv',
    'cart_rules.csv',
    'advanced_pricing.csv',
    'orders.csv'];

    /** @var string[]  */
    const STORE_FILES = ['stores.csv'];

    /** @var string[]  */
    const STAGE1 = ['config_default.json','config_default.csv','config_vertical.json',
    'config_vertical.csv','config_secret.json','config_secret.csv','config.json','config.csv',
    'admin_roles.csv','admin_users.csv','customer_groups.csv','customer_attributes.csv','reward_exchange_rate.csv',
    'customers.csv','customer_addresses.csv','product_attributes.csv',
    'customer_segments.csv','blocks.csv','categories.csv'];

    /** @var string[]  */
    const STAGE2 = ['products.csv','msi_source.csv','msi_stock.csv','msi_inventory.csv','upsells.csv','blocks.csv',
    'dynamic_blocks.csv','widgets.csv','catalog_rules.csv',
    'pages.csv','templates.csv','reviews.csv','b2b_companies.csv','b2b_shared_catalogs.csv',
    'b2b_shared_catalog_categories.csv','b2b_requisition_lists.csv','cart_rules.csv',
    'advanced_pricing.csv','orders.csv'];

    /** @var string[]  */
    const B2B_REQUIRED_FILES = ['b2b_customers.csv','b2b_companies.csv','b2b_company_roles.csv',
    'b2b_sales_reps.csv','b2b_teams.csv'];
    /** @var string[]  */
    const SETTINGS = ['site_code'=>'base', 'store_code'=>'main_website_store','store_view_code'=>'default',
        'root_category' => 'Default Category', 'root_category_id' => '2'];

    /**
     * Conf constructor.
     * @param DataTypes\AdminUsers $adminUsers
     * @param DataTypes\AdminRoles $adminRoles
     * @param DataTypes\AdvancedPricing $advancedPricing
     * @param DataTypes\ApprovalRules $approvalRules
     * @param DataTypes\Blocks $blocks
     * @param DataTypes\CatalogRules $catalogRules
     * @param DataTypes\CartRules $cartRules
     * @param DataTypes\Categories $categories
     * @param Datatypes\Companies $companies
     * @param DataTypes\CompanyRoles $companyRoles
     * @param DataTypes\CompanyUserRoles $companyUserRoles
     * @param DataTypes\Configuration $configuration
     * @param DataTypes\CustomerGroups $customerGroups
     * @param DataTypes\CustomerAttributes $customerAttributes
     * @param DataTypes\RewardExchangeRate $rewardExchangeRate
     * @param DataTypes\Customers $customers
     * @param DataTypes\CustomerAddresses $customerAddresses
     * @param DataTypes\CustomerSegments $customerSegments
     * @param DataTypes\DynamicBlocks $dynamicBlocks
     * @param DataTypes\Widgets $widgets
     * @param DataTypes\MsiStock $msiStock
     * @param DataTypes\MsiSource $msiSource
     * @param DataTypes\MsiInventory $msiInventory
     * @param DataTypes\Orders $orders
     * @param DataTypes\Pages $pages
     * @param DataTypes\ProductAttributes $productAttributes
     * @param DataTypes\Products $products
     * @param DataTypes\GiftCards $giftCards
     * @param DataTypes\RequisitionLists $requisitionLists
     * @param DataTypes\SharedCatalogs $sharedCatalogs
     * @param DataTypes\SharedCatalogCategories $sharedCatalogCategories
     * @param DataTypes\Stores $stores
     * @param DataTypes\Reviews $reviews
     * @param DataTypes\Teams $teams
     * @param DataTypes\Templates $templates
     * @param DataTypes\Upsells $upsells
     */
    public function __construct(
        DataTypes\AdminUsers $adminUsers,
        DataTypes\AdminRoles $adminRoles,
        DataTypes\AdvancedPricing $advancedPricing,
        DataTypes\ApprovalRules $approvalRules,
        DataTypes\Blocks $blocks,
        DataTypes\CatalogRules $catalogRules,
        DataTypes\CartRules $cartRules,
        DataTypes\Categories $categories,
        Datatypes\Companies $companies,
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
    }

    /**
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
            ['customer_addresses.csv'=>['process'=>'file','class'=>$this->customerAddressesInstall,
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
            ['products.csv'=>['process'=>'file','class'=>$this->productInstall,
            'label'=>'Loading Products']],
            ['gift_cards.csv'=>['process'=>'rows','class'=>$this->giftCardsInstall,
            'label'=>'Updating Gift Cards']],
            ['gift_cards.json'=>['process'=>'graphqlrows','class'=>$this->giftCardsInstall,
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
            ['widgets.csv'=>['process'=>'rows','class'=>$this->widgetInstall,
            'label'=>'Loading Wigets']],
            ['widgets.json'=>['process'=>'graphqlrows','class'=>$this->widgetInstall,
            'label'=>'Loading Wigets']],
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
            ['b2b_shared_catalogs.csv'=>['process'=>'rows','class'=>$this->sharedCatalogsInstall,
            'label'=>'Loading B2B Shared Catalogs']],
            ['b2b_shared_catalog_categories.csv'=>['process'=>'file','class'=>$this->sharedCatalogCategoriesInstall,
            'label'=>'Loading B2B Shared Catalog Categories']],
            ['b2b_requisition_lists.csv'=>['process'=>'rows','class'=>$this->requisitionListsInstall,
            'label'=>'Loading B2B Requisiton Lists']],
            ['b2b_approval_rules.csv'=>['process'=>'rows','class'=>$this->approvalRulesInstall,
            'label'=>'Loading B2B Approval Rules']],
            ['cart_rules.csv'=>['process'=>'rows','class'=>$this->cartRulesInstall,
            'label'=>'Loading Cart Rules']],
            ['cart_rules.json'=>['process'=>'graphqlrows','class'=>$this->cartRulesInstall,
            'label'=>'Loading Cart Rules']],
            ['advanced_pricing.csv'=>['process'=>'file','class'=>$this->advancedPricingInstall,
            'label'=>'Loading Advanced Pricing']],
            ['orders.csv'=>['process'=>'rows','class'=>$this->ordersInstall,
            'label'=>'Loading Orders']]
        ];
    }
}
