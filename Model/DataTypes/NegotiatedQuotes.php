<?php
/**
 * Copyright 2023 Adobe, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace MagentoEse\DataInstall\Model\DataTypes;

use Exception;
use Magento\Catalog\Model\ProductRepository;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Api\ExtensibleDataObjectConverter;
use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\State;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\NegotiableQuote\Api\Data\CommentInterfaceFactory;
use Magento\NegotiableQuote\Api\Data\HistoryInterface;
use Magento\NegotiableQuote\Api\Data\HistoryInterfaceFactory;
use Magento\NegotiableQuote\Api\Data\ItemNoteInterface;
use Magento\NegotiableQuote\Api\Data\ItemNoteInterfaceFactory;
use \Magento\NegotiableQuote\Model\CommentAttachmentFactory;
use Magento\NegotiableQuote\Model\HistoryRepositoryInterface;
use Magento\NegotiableQuote\Model\ResourceModel\ItemNote as ItemNoteResource;
use Magento\NegotiableQuote\Model\ResourceModel\Comment as CommentResource;
use Magento\NegotiableQuote\Api\NegotiableQuoteItemManagementInterface;
use Magento\NegotiableQuote\Model\Expiration;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\NegotiableQuote\Model\NegotiableQuoteConverter;
use Magento\NegotiableQuote\Model\Quote\History;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteFactory;
use MagentoEse\DataInstall\Helper\Helper;
use MagentoEse\DataInstall\Model\Conf;
use \Magento\Store\Model\StoreManagerInterface;
use MagentoEse\DataInstall\Model\NegotiableQuote\Creator;

class NegotiatedQuotes
{
    /** @var Helper */
    protected Helper $helper;
    protected array $creators = [];
    protected string $websiteId = '';
    protected string $quoteName = '';
    protected QuoteFactory $quoteFactory;
    protected StoreManagerInterface $storeManager;
    protected CartRepositoryInterface $quoteRepository;
    protected ProductRepository $productRepository;
    protected History $quoteHistory;
    protected NegotiableQuoteConverter $negotiableQuoteConverter;
    protected ScopeConfigInterface $storeConfig;
    protected ScopeConfigInterface $scopeConfig;
    protected Expiration $expiration;
    protected NegotiableQuoteItemManagementInterface $quoteItemManagement;
    protected ItemNoteResource $itemNoteResource;
    protected CommentInterfaceFactory $commentFactory;
    protected Escaper $escaper;
    protected CommentResource $commentResource;
    protected CommentAttachmentFactory $commentAttachmentFactory;
    protected HistoryInterfaceFactory $historyFactory;
    protected HistoryRepositoryInterface $historyRepository;
    protected SerializerInterface $serializer;
    private State $appState;
    private ItemNoteInterfaceFactory $itemNoteFactory;
    private CartManagementInterface $cartManagementInterface;
    private Creator $creatorModel;
    private ExtensibleDataObjectConverter $dataObjectConverter;

    /**
     * @param ExtensibleDataObjectConverter $dataObjectConverter
     * @param State $appState
     * @param SerializerInterface $serializer
     * @param HistoryRepositoryInterface $historyRepository
     * @param HistoryInterfaceFactory $historyFactory
     * @param CommentAttachmentFactory $commentAttachmentFactory
     * @param CommentResource $commentResource
     * @param Escaper $escaper
     * @param CommentInterfaceFactory $commentFactory
     * @param ItemNoteResource $itemNoteResource
     * @param ItemNoteInterfaceFactory $itemNoteFactory
     * @param NegotiableQuoteItemManagementInterface $quoteItemManagement
     * @param Helper $helper
     * @param Expiration $expiration
     * @param ScopeConfigInterface $scopeConfig
     * @param NegotiableQuoteConverter $negotiableQuoteConverter
     * @param History $quoteHistory
     * @param ProductRepository $productRepository
     * @param CartRepositoryInterface $quoteRepository
     * @param StoreManagerInterface $storeManager
     * @param Creator $creatorModel
     * @param CartManagementInterface $cartManagementInterface
     * @param QuoteFactory $quoteFactory
     */
    public function __construct(
        ExtensibleDataObjectConverter          $dataObjectConverter,
        State                                  $appState,
        SerializerInterface                    $serializer,
        HistoryRepositoryInterface             $historyRepository,
        HistoryInterfaceFactory                $historyFactory,
        CommentAttachmentFactory               $commentAttachmentFactory,
        CommentResource                        $commentResource,
        Escaper                                $escaper,
        CommentInterfaceFactory                $commentFactory,
        ItemNoteResource                       $itemNoteResource,
        ItemNoteInterfaceFactory               $itemNoteFactory,
        NegotiableQuoteItemManagementInterface $quoteItemManagement,
        Helper                                 $helper,
        Expiration                             $expiration,
        ScopeConfigInterface                   $scopeConfig,
        NegotiableQuoteConverter               $negotiableQuoteConverter,
        History                                $quoteHistory,
        ProductRepository                      $productRepository,
        CartRepositoryInterface                $quoteRepository,
        StoreManagerInterface                  $storeManager,
        Creator                                $creatorModel,
        CartManagementInterface                $cartManagementInterface,
        QuoteFactory                           $quoteFactory
    ) {
        $this->helper = $helper;
        $this->quoteFactory = $quoteFactory;
        $this->storeManager = $storeManager;
        $this->quoteRepository = $quoteRepository;
        $this->productRepository = $productRepository;
        $this->negotiableQuoteConverter = $negotiableQuoteConverter;
        $this->quoteHistory = $quoteHistory;
        $this->scopeConfig = $scopeConfig;
        $this->expiration = $expiration;
        $this->quoteItemManagement = $quoteItemManagement;
        $this->itemNoteResource = $itemNoteResource;
        $this->commentFactory = $commentFactory;
        $this->escaper = $escaper;
        $this->commentResource = $commentResource;
        $this->historyFactory = $historyFactory;
        $this->historyRepository = $historyRepository;
        $this->serializer = $serializer;
        $this->commentAttachmentFactory = $commentAttachmentFactory;
        $this->appState = $appState;
        $this->itemNoteFactory = $itemNoteFactory;
        $this->cartManagementInterface = $cartManagementInterface;
        $this->creatorModel = $creatorModel;
        $this->dataObjectConverter = $dataObjectConverter;
    }

    public function installJson(string $json, array $settings)
    {
        try {
            //convert to array of objects. Remove the parent query name node
            $fileData = json_decode($json, true);
        } catch (Exception $e) {
            $this->helper->logMessage("The JSON in your b2b file is invalid", "error");
            return true;
        }

        $inputData = $fileData['data']['negotiableQuotesExport']['items'];
        foreach ($inputData as $quote) {
            $this->install($quote, $settings);
        }
    }

    public function install(array $row, array $settings)
    {
        if (empty($row['site_code'])) {
            $row['site_code'] = $settings['site_code'];
        }
        if (empty($row['store'])) {
            $row['store'] = $settings['store_view_code'];
        }

        if (!$this->validateData($row)) {
            //exceptions encountered
            return true;
        }

        $this->quoteName = $row['name'];

        if (!isset($row['product'])) {
            $row['product'] = [];
        }

        //first create quote, then negotiated quote (NQ), then set NQ items price and then set price on NQ
        $quote = $this->createQuote($row);
        if (empty($quote)) {
            //exceptions encountered
            return true;
        }

        $quoteId = $quote->getId();
        $quote = $this->getQuote($quoteId);
        if (empty($quote)) {
            //exceptions encountered
            return true;
        }

        if (!$this->createNegotiableQuote($quote, $row)) {
            //exceptions encountered
            return true;
        }

        $productsIndexed = $this->setNegotiableQuoteItems($quote, $row['product']);

        //update notes id map
        $productsIndexed = $this->saveItemNotes($productsIndexed);

        $commentsIdsMap = [];
        if (!empty($row['comments'])) {
            $commentsIdsMap = $this->saveComments($row['comments'], $quoteId);
        }

        if (!empty($row['history'])) {
            //save all histories with current quote and then only update the final price and status
            $this->saveHistories($row['history'], $quote, $productsIndexed, $commentsIdsMap);
        }

        $this->retrieveNegotiableQuote($quote)
            ->setNegotiatedPriceType($row['negotiated_price_type'])
            ->setNegotiatedPriceValue($row['negotiated_price_value']);

        try {
            $this->saveQuote($quote);
        } catch (Exception $e) {
            $this->helper->logMessage("Unable to save Negotiable Quote Prices ". $this->quoteName .
                ", row skipped. {$e->getMessage()}", "warning");
            return false;
        }

        try {
            //make sure to call quote save
            $this->updatePriceQuote($quoteId);
        } catch (NoSuchEntityException $e) {
            $this->helper->logMessage("Unable to recalculate Negotiable Quote Price ". $this->quoteName .
                " is invalid, row skipped. {$e->getMessage()}", "warning");
            return true;
        }

        $this->setNegotiableQuoteSnapshot($quoteId, $row);

        if ($row['status'] === NegotiableQuoteInterface::STATUS_ORDERED) {
            try {
                $this->appState->emulateAreaCode(
                    Area::AREA_ADMINHTML,
                    [$this->cartManagementInterface, 'placeOrder'],
                    [$quoteId]
                );
                $orderId = $this->cartManagementInterface->placeOrder($quoteId);
            } catch (CouldNotSaveException|Exception $e) {
                $this->helper->logMessage("order data for ". $this->quoteName .
                    " is invalid, row skipped", "warning");
                return true;
            }
        }

        return true;
    }

    private function validateData($row): bool
    {
        if (empty($row['name'])) {
            $this->helper->logMessage("Missing Negotiated Quote Name, row skipped", "warning");
            return false;
        }
        if (empty($row['status'])) {
            $this->helper->logMessage("Missing Negotiated Quote Status, row skipped", "warning");
            return false;
        }

        if (empty($row['creator_type_id'])) {
            $this->helper->logMessage("Missing Negotiated Quote Creator Info, row skipped", "warning");
            return false;
        }

        return true;
    }

    /**
     * @param $row
     * @return bool|Quote
     */
    private function createQuote($row): bool|Quote
    {
        try {
            $store = $this->storeManager->getStore($row['store']);
        } catch (NoSuchEntityException $e) {
            $this->helper->logMessage("Store ".$row['store'].
                " is not found, row skipped", "warning");
            return false;
        }

        $storeId = $store->getId();
        $this->websiteId = $store->getWebsiteId();

        $this->helper->logMessage("Customer store".$storeId.
            " is {$row['store']} ");

        try {
            $customer = $this->creatorModel->getCustomer($row['email'], $this->websiteId);
        } catch (NoSuchEntityException $e) {
            $this->helper->logMessage("Customer ".$row['email'].
                " is missing, row skipped", "warning");
            return false;
        } catch (LocalizedException $e) {
            $this->helper->logMessage("Customer ".$row['email'].
                " is invalid {$e->getMessage()}, row skipped", "warning");
            return false;
        }

        $quote = $this->quoteFactory->create();
        $quote->setStore($store);
        $quote->setIsActive(false);
        $quote->setCustomer($customer);
        $quote->setCustomerIsGuest(0);

        if (!empty($row['shipping_addresses'])) {
            foreach ($row['shipping_addresses'] as $address) {
                $address['country_id'] = $address['country']['code'];
                $address['region_id'] = $address['region']['region_id'];
                $address['region'] = $address['region']['label'];
                $quote->getShippingAddress()->addData($address);

                if (!empty($address['shipping_method'])) {
                    $quote->getShippingAddress()->setShippingMethod($address['shipping_method']);
                    $quote->getShippingAddress()->setCollectShippingRates(true);
                    $quote->getShippingAddress()->collectShippingRates();

                }
            }
        }

        if (!empty($row['billing_address'])) {
            $row['billing_address']['country_id'] = $row['billing_address']['country']['code'];
            $row['billing_address']['region_id'] = $row['billing_address']['region']['region_id'];
            $row['billing_address']['region'] = $row['billing_address']['region']['label'];
            $quote->getBillingAddress()->addData($row['billing_address']);
        }

        //keep dates as in export and extend expiry to current + 30days
        if (!empty($row['updated_at'])) $quote->setUpdatedAt($row['updated_at']);
        if (!empty($row['created_at'])) $quote->setCreatedAt($row['created_at']);

        if (!$this->addProductsToCart($row, $quote, $storeId))
            return false;

        if (!$this->setPaymentMethod($row, $quote))
            return false;

        $quote->collectTotals();
        $this->quoteRepository->save($quote);
        return $quote;
    }

    /**
     * @param Quote|CartInterface $quote
     * @return void
     * @throws Exception
     */
    private function saveQuote(CartInterface $quote): void
    {
        $this->appState->emulateAreaCode(
            Area::AREA_ADMINHTML,
            [$this->quoteRepository, 'save'],
            [$quote]
        );
    }

    private function getQuote($quoteId): bool|CartInterface
    {
        try {
            return $this->quoteRepository->get($quoteId);
        } catch (NoSuchEntityException $e) {
            $this->helper->logMessage("Invalid Quote ". $this->quoteName .
                ", row skipped. {$e->getMessage()}", "warning");
            return false;
        }
    }

    /**
     * @param CartInterface $quote
     * @return NegotiableQuoteInterface|null
     */
    private function retrieveNegotiableQuote(CartInterface $quote): ?NegotiableQuoteInterface
    {
        return ($quote->getExtensionAttributes()
            && $quote->getExtensionAttributes()->getNegotiableQuote())
            ? $quote->getExtensionAttributes()->getNegotiableQuote()
            : null;
    }

    private function setNegotiableQuoteSnapshot($quoteId, $row): void
    {
        $quote = $this->getQuote($quoteId);
        $this->setNegotiableQuoteStatus($quote, $row['status']);
        $this->retrieveNegotiableQuote($quote)
            ->setSnapshot(
                json_encode($this->replaceNegotiableQuoteSnapshot($quote, ($row['snapshot'] ?? '') ))
            );

        //replace if any extra addition form the exported snapshot in order to keep the view restriction


        $this->saveQuote($quote);
    }


    /**
     * @param $quote
     * @param $exportedSnapshotData
     * @return array
     */
    private function replaceNegotiableQuoteSnapshot($quote, $exportedSnapshotData)
    {
        $currentSnapshotData = $this->negotiableQuoteConverter->quoteToArray($quote);
        $exportedSnapshotData = json_decode($exportedSnapshotData, true);
        $exportedSnapshotData = !is_null($exportedSnapshotData) ? $exportedSnapshotData : [];
        return $this->replaceSnapshotDiff($exportedSnapshotData, $currentSnapshotData);
    }

    private function getItemFromArray($items, $search) {
        foreach ($items as $item) {
            if ($item['name'] == $search['name'] && $item['sku'] == $search['sku']){
                return $item;
            }
        }

        return [];
    }

    private function replaceSnapshotDiff($snapshot, $currentSnapshotData)
    {
        $sets = ["quote", "negotiable_quote", "shipping_address", "billing_address"];
        $indexArr = [
            "entity_id", "store_id", "customer_id", "customer_tax_class_id",
            "customer_group_id", "quote_id", "address_id", "creator_id",
            "customer_address_id", "applied_rule_ids", "orig_order_id",
        ];
        foreach ($sets as $key) {
            foreach ($indexArr as $index) {
                if (isset($snapshot[$key][$index]))
                    $snapshot[$key][$index] = $currentSnapshotData[$key][$index] ?? '';
            }
        }

        if (!empty($snapshot['items']) && !empty($currentSnapshotData['items'])) {
            //$currentSnapshotDataItems = array_column($currentSnapshotData['items'], NULL,'name');
            $currentSnapshotDataItems = $currentSnapshotData['items'];

            $indexArr = [
                "quote_id", "item_id", "product_id", "parent_item_id", "store_id"
            ];
            foreach ($snapshot['items'] as &$item) {
                $sku = $item['sku'];
                $name = $item['name'];

                $itemMatched = $this->getItemFromArray($currentSnapshotDataItems, $item);
                if (!empty($itemMatched)) {
                    foreach ($indexArr as $index => $value) {
                        if (isset($item[$index]))
                            $item[$index] = $itemMatched[$index] ?? '';
                    }

                    if (isset($item["negotiable_quote_item"]["quote_item_id"])) {
                        $item["negotiable_quote_item"]["quote_item_id"] = $itemMatched["negotiable_quote_item"]["quote_item_id"] ?? '';
                }

                if (isset($item["options"])) {
                        /*
                         * customers cannot add product but can update qty, add note and remove product.
                         * while admin can add,update,remove,change configurable options product and add note.
                         */
                        //only handling the qty for both admin and customer case
                    $options = $item["options"];
                        $item["options"] = $itemMatched["options"] ?? [];
                    foreach ($options as $k => $option) {
                            if (isset($option['code']) && $option['code'] == 'info_buyRequest' && isset($option['value'])) {
                                $val = json_decode($option['value'], true);
                            $valNew = json_decode($item["options"][$k]['value'], true);
                            $valNew['qty'] = $val['qty'] ?? '';
                                if (!empty($val['custom_price']))
                                    $valNew['custom_price'] = $val['custom_price'];

                            $valNew['original_qty'] = $val['original_qty'] ?? '';

                            $item["options"][$k]['value'] = json_encode($valNew);
                        }
                    }
                }
            }
        }
        }

        return $snapshot;
    }

    function arrayDiffRecursiveCurrent($currentSnapshotData, $exportedSnapshotData) {
        $diff = [];

        foreach ($currentSnapshotData as $key => $value) {
            if (!array_key_exists($key, $exportedSnapshotData)) {
                $diff[$key] = null;
            }
        }

        return $diff;
    }

    function arrayDiffRecursiveExport($currentSnapshotData, $exportedSnapshotData) {
        $diff = [];

        foreach ($exportedSnapshotData as $key => $value) {
            if (is_array($value)) {
                if (!isset($currentSnapshotData[$key]) || !is_array($currentSnapshotData[$key])) {
                    $diff[$key] = $value;
                } else {
                    $nestedDiff = $this->arrayDiffRecursiveExport($currentSnapshotData[$key], $value);
                    if (!empty($nestedDiff)) {
                        $diff[$key] = $nestedDiff;
                    }
                }
            } elseif (!array_key_exists($key, $currentSnapshotData)) {
                //arises two cases where we had to replace ids and where we have to keep the non-ids value
                $diff[$key] = $value;
            } elseif ( $currentSnapshotData[$key] !== $value) {
                //arises two cases where we had to replace ids and where we have to keep the non-ids|null value
                $diff[$key] = $value;
            } else {
                $diff[$key] = $value;
            }
        }

        return $diff;
    }

    private function setPaymentMethod($row, $quote): bool
    {
        if (!empty($row['selected_payment_method']['code'])) {
            $paymentMethod = $row['selected_payment_method'];
            $paymentMethod['method'] = $paymentMethod['code'];
            unset($paymentMethod['code']);

            $paymentMethod['additional_method'] = isset($paymentMethod['additional_method']) ? json_decode($paymentMethod['additional_method'], 1) : [];

            $quotePayment = $quote->getPayment();
            $quotePayment->setMethod($paymentMethod['method'])->setQuote($quote);
            try {
                $quotePayment->importData($paymentMethod);
            } catch (LocalizedException $e) {
                $this->helper->logMessage("Payment method is invalid for ".$this->quoteName.
                    ". {$e->getMessage()}, row skipped", "warning");
                return false;
            }
        }

        return true;
    }

    private function addProductsToCart($row, Quote $quote, $storeId): bool
    {
        foreach ($row['product'] as $item) {
            $dataToSend['qty'] = $item['qty'];

            $sku = !empty($item['parent_sku']) ? $item['parent_sku'] : $item['sku'];

            try {
                $product = $this->productRepository->get($sku, false, $storeId, true);
                $dataToSend['product'] = $product->getId();
            } catch (NoSuchEntityException $e) {
                $this->helper->logMessage("Product data ".$sku.
                    " is invalid {$e->getMessage()}, row skipped", "warning");
                return false;
            }

            if ($item['type'] == Configurable::TYPE_CODE) {
                $childProductSku = $item['sku'];

                if (/*!empty($item['super_attribute']) ||*/ $childProductSku) {
                    $options = [];

                    try {
                        $childItem = $this->productRepository->get(
                            $childProductSku, false, $storeId, true
                        );
                    } catch (NoSuchEntityException $e) {
                        $this->helper->logMessage("Child Product data ".$childProductSku.
                            " is invalid {$e->getMessage()}, row skipped", "warning");
                        return false;
                    }

                    // Get the selected options for this child product
                    $proAttributes = $product->getTypeInstance()->getConfigurableAttributesAsArray($product);
                    foreach ($proAttributes as $attribute) {
                        $attrCode = $attribute['attribute_code'];
                        if ($childItem->getData($attrCode)) {
                            $attrValue = $childItem->getData($attrCode);
                            $optionValue = $childItem->getAttributeText($attrCode);
                            $options[$attribute['attribute_id']] = $attrValue;
                        }
                    }

                    $item['super_attribute'] = $options;
                }

                if (!is_array($item['super_attribute'])) {
                    $item['super_attribute'] = json_decode($item['super_attribute'], true);
                }

                $dataToSend['super_attribute'] = $item['super_attribute'];
            }

            try {
                $dataReq = new \Magento\Framework\DataObject($dataToSend);
                $this->appState->emulateAreaCode(
                    Area::AREA_ADMINHTML,
                    [$quote, 'addProduct'],
                    [$product, $dataReq]
                );
            } catch (LocalizedException|Exception $e) {
                $this->helper->logMessage("Cannot add item ".$sku.
                    " to cart. {$e->getMessage()}, row skipped", "warning");
                return false;
            }
        }

        return true;
    }

    /**
     * @param CartInterface $quote
     * @param $row
     * @return bool
     */
    private function createNegotiableQuote(CartInterface $quote, $row): bool
    {
        $creator = $this->getCreator($row['creator'], $row['creator_type_id']);
        if (empty($creator)) {
            $this->helper->logMessage("Missing Negotiated Quote Creator, row skipped", "warning");
            return false;
        }

        $negotiableQuote = $quote->getExtensionAttributes()->getNegotiableQuote();
        $negotiableQuote->setQuoteId($quote->getId())
            ->setIsRegularQuote(true)
            ->setAppliedRuleIds($quote->getAppliedRuleIds())
            ->setStatus($this->getQuoteInitialStatus($row['creator_type_id']))
            ->setQuoteName($this->quoteName)
            ->setCreatorId($creator)
            ->setCreatorType($row['creator_type_id']);

        if (!empty($row['proposed_shipping_amount'])) {
            $negotiableQuote->setShippingPrice($row['proposed_shipping_amount']);
            $quote->getShippingAddress()->setCollectShippingRates(true);

            try {
                $this->appState->emulateAreaCode(
                    Area::AREA_ADMINHTML,
                    [$quote->getShippingAddress(), 'collectShippingRates'],
                    [$quote]
                );
            } catch (Exception $e) {
                $this->helper->logMessage("Unable to save shipping rates for Quote ". $this->quoteName .
                    ", row skipped. {$e->getMessage()}", "warning");
                return false;
            }
        }

        $formattedExpirationDate = $this->getExpirationDate();
        if (!empty($row['expiration_period']) &&
            $formattedExpirationDate !== Expiration::DATE_QUOTE_NEVER_EXPIRES &&
            strtotime($row['expiration_period']) > strtotime($formattedExpirationDate)
        ) {
            $formattedExpirationDate = $row['expiration_period'];
        }
        $negotiableQuote->setExpirationPeriod(
            $formattedExpirationDate
        );

        try {
            $this->saveQuote($quote);
        } catch (Exception $e) {
            $this->helper->logMessage("Unable to save Negotiable Quote ". $this->quoteName .
                ", row skipped. {$e->getMessage()}", "warning");
            return false;
        }

        return true;
    }

    private function getExpirationDate(): string
    {
        $expirationDate = $this->expiration->retrieveDefaultExpirationDate();
        return $expirationDate === null
            ? Expiration::DATE_QUOTE_NEVER_EXPIRES
            : $expirationDate->format('Y-m-d');
    }

    /**
     * @param Quote|CartInterface $quote
     * @param $products
     * @return array
     */
    private function setNegotiableQuoteItems(CartInterface $quote, $products): array
    {
        foreach ($quote->getAllVisibleItems() as $quoteItem) {
            /** @var Item $quoteItem */
            $sku = $quoteItem->getSku();

            if (!empty($productsIndexed[$sku])) {
                $productsIndexed[$sku] = array_merge(
                    $productsIndexed[$sku],
                    [
                        'item_id' => $quoteItem->getItemId(),
                        'product_id' => $quoteItem->getProduct()->getId(),
                        'parent_item_id' => $quoteItem->getParentItemId(),
                        'price' => $quoteItem->getBasePrice(),
                        'taxAmount' => $quoteItem->getBaseTaxAmount(),
                        'discountAmount' => $this->getBaseTotalDiscountAmount($quoteItem)
                    ]
                );

                if ($quoteItem->getBuyRequest()) {
                    $productsIndexed[$sku]["options"] = $this->retrieveProductOptions($quoteItem->getBuyRequest());
                }

                $quoteItemExtension = $quoteItem->getExtensionAttributes();
                $negotiableItem = $quoteItemExtension->getNegotiableQuoteItem();
                $quoteItemExtension->setNegotiableQuoteItem($negotiableItem);
                $negotiableItemExtension = $negotiableItem->getExtensionAttributes();
                $negotiableItemExtension->setNegotiatedPriceType(
                    (int)$productsIndexed[$sku]['negotiated_price_type']
                );
                $negotiableItemExtension->setNegotiatedPriceValue(
                    (float)$productsIndexed[$sku]['negotiated_price_value']
                );
                $negotiableItem->setExtensionAttributes($negotiableItemExtension);
                $quoteItem->setExtensionAttributes($quoteItemExtension);
            }
        }

        return $productsIndexed;
    }

    public function retrieveProductOptions($request): array
    {
        $options = [];
        $optionsNames = [
            'super_attribute',
            'options',
            'bundle_option',
            'custom_giftcard_amount',
            'giftcard_amount',
            'giftcard_message',
            'giftcard_recipient_email',
            'giftcard_recipient_name',
            'giftcard_sender_email',
            'giftcard_sender_name'
        ];
        foreach ($optionsNames as $option) {
            if ($request->hasData($option) && $request->getData($option)) {
                $options[$option] = $request->getData($option);
            }
        }
        return $options;
    }

    private function getQuoteInitialStatus($creatorType): string
    {
        switch ($creatorType) {
            case ItemNoteInterface::CREATOR_TYPE_BUYER:
                return NegotiableQuoteInterface::STATUS_CREATED;

            case ItemNoteInterface::CREATOR_TYPE_SELLER:
            default:
                return NegotiableQuoteInterface::STATUS_DRAFT_BY_ADMIN;
        }
    }

    private function getCreator($creator, $creatorType = ItemNoteInterface::CREATOR_TYPE_BUYER): bool|int|null
    {
        if (empty($this->creators[$creatorType][$creator])) {

            $this->creators[$creatorType][$creator] = $this->creatorModel->retrieveCreatorByUsername(
                $creatorType,
                $creator
            );
        }

        return $this->creators[$creatorType][$creator];
    }

    /**
     * Calculate base total discount for quote item.
     *
     * @param CartItemInterface $quoteItem
     * @return int
     */
    private function getBaseTotalDiscountAmount(CartItemInterface $quoteItem): int
    {
        $totalDiscountAmount = 0;
        $children = $quoteItem->getChildren();
        if (!empty($children) && $quoteItem->isChildrenCalculated()) {
            foreach ($children as $child) {
                $totalDiscountAmount += $child->getBaseDiscountAmount();
            }
        } else {
            $totalDiscountAmount = $quoteItem->getBaseDiscountAmount();
        }
        return $totalDiscountAmount;
    }

    /**
     * @throws NoSuchEntityException
     * @throws Exception
     */
    private function updatePriceQuote($quoteId): void
    {
        $this->appState->emulateAreaCode(
            Area::AREA_ADMINHTML,
            [$this->quoteItemManagement, 'recalculateOriginalPriceTax'],
            [$quoteId, true, true, true, false]
        );
    }

    /**
     * @param $productsIndexed
     * @return array
     */
    private function saveItemNotes($productsIndexed): array
    {
        foreach ($productsIndexed as $sku => $prod) {
            try {
                $productsIndexed[$sku]['note_id_map'] = $this->saveItemNote($prod);
            } catch (AlreadyExistsException $e) {
                $this->helper->logMessage("Cart note for ". $this->quoteName .
                    " is invalid {$e->getMessage()}, row skipped", "warning");
            }
        }

        return $productsIndexed;
    }

    private function saveComments($comments, $quoteId): array
    {
        $commentIdsMap = [];
        foreach ($comments as $comment) {
            try {
                $commentId = $this->saveComment($comment, $quoteId);
                if (isset($comment['uid']))
                    $commentIdsMap[$comment['uid']] = $commentId;
            } catch (AlreadyExistsException|Exception $e) {
                $this->helper->logMessage("Comment data for ". $this->quoteName.
                    " is invalid {$e->getMessage()}, row skipped", "warning");
            }
        }

        return $commentIdsMap;
    }

    /**
     * @param $histories
     * @param CartInterface $quote
     * @param $productsIndexed
     * @param array $commentsIdsMap
     * @return void
     */
    private function saveHistories($histories, CartInterface $quote, $productsIndexed, array $commentsIdsMap = []): void
    {
        $previousHistorySnapshot = [];
        foreach ($histories as $history) {
            try {
                $previousHistory = $this->saveHistory(
                    $history,
                    $quote,
                    $productsIndexed,
                    $commentsIdsMap,
                    $previousHistorySnapshot
                );

                if ($previousHistory)
                    $previousHistorySnapshot = $this->serializer->unserialize($previousHistory->getSnapshotData());
            } catch (CouldNotSaveException $e) {
                $this->helper->logMessage("History data for ". $this->quoteName .
                    " is invalid {$e->getMessage()}, row skipped", "warning");
            }
        }
    }

    /**
     * @throws AlreadyExistsException
     */
    private function saveItemNote($product): array
    {
        $productNoteIdMap = [];
        if (!empty($product['note']) && !empty($product['item_id'])) {
            foreach ($product['note'] as $note) {
                $itemNote = $this->itemNoteFactory->create();
                $itemNote->setCreatorId($this->getCreator($note['creator'], $note['creator_type_id']));
                $itemNote->setNegotiableQuoteItemId((int)$product['item_id']);
                $itemNote->setCreatorType((int)$note['creator_type_id']);
                $itemNote->setNote($note['note']);
                $itemNote->setCreatedAt($note['created_at']);
                $this->itemNoteResource->save($itemNote);

                if (!empty($note['note_uid'])) {
                    $productNoteIdMap[$note['note_uid']] = $itemNote->getNoteId();
                }
            }
        }

        return $productNoteIdMap;
    }

    /**
     * @throws AlreadyExistsException
     * @throws Exception
     */
    protected function saveComment($commentData, $quoteId): mixed
    {
        $comment = $this->commentFactory->create();
        $commentText = $this->escaper->escapeHtml($commentData['text']);
        $comment->setCreatorId($this->getCreator($commentData['creator'], $commentData['creator_type_id']))
            ->setParentId($quoteId)
            ->setCreatorType($commentData['creator_type_id'])
            ->setIsDecline($commentData['is_decline'])
            ->setIsDraft($commentData['is_draft'])
            ->setComment($commentText);
        $comment->setCreatedAt($commentData['created_at']);
        $this->commentResource->save($comment);
        $commentId = $comment->getId();

        if (!empty($commentData['files'])) {
            foreach ($commentData['files'] as $file) {
                if (isset($file['file_name']) && isset($file['file_path']) && isset($file['file_type'])) {
                    $attachment = $this->commentAttachmentFactory->create();
                    $attachment->setCommentId($commentId)
                        ->setFileName($file['file_name'])
                        ->setFilePath($file['file_path'])
                        ->setFileType($file['file_type'])
                        ->save();
                }
            }
        }

        return $commentId;
    }

    /**
     * @param $history
     * @param Quote $quote
     * @param array $productIndexed
     * @param array $commentsIdsMap
     * @param array $previousHistorySnapshot
     * @return HistoryInterface|null
     * @throws CouldNotSaveException
     */
    protected function saveHistory(
        $history,
        Quote $quote,
        array $productIndexed,
        array $commentsIdsMap = [],
        array $previousHistorySnapshot = []
    ): ?\Magento\NegotiableQuote\Api\Data\HistoryInterface
    {
        if (isset($history['creator']) && isset($history['status']) && isset($history['log_data'])) {
            //set negotiable status as per history
            $logData = (!empty($history['log_data'])) ? $this->serializer->unserialize($history['log_data']) : [];
            if (!empty($logData['status']['new_value'])) {
                $this->setNegotiableQuoteStatus($quote, $logData['status']['new_value']);
            }

            $isSeller = $history['is_seller'] ?? false;
            $authorId = $this->getCreator(
                $history['creator'],
                ($isSeller ? ItemNoteInterface::CREATOR_TYPE_SELLER : ItemNoteInterface::CREATOR_TYPE_BUYER)
            );

            $historyLog = $this->historyFactory->create();
            $historyLog->setQuoteId($quote->getId())
                ->setIsSeller($isSeller)
                ->setAuthorId($authorId)
                ->setIsDraft($history['is_draft'])
                ->setStatus($history['status']);

            $logDataNew = $history['log_data'];
            $snapshotNew = $history['snapshot_data'];
            $snapshot = [];

            if (!empty($history['created_at'])) $historyLog->setCreatedAt($history['created_at']);

            if (!empty($history['snapshot_data'])) {
                //as snapshots contains identifiers, need to replace them with current ones.
                $snapshot = $this->serializer->unserialize($history['snapshot_data']);
                $snapshot = $this->replaceSnapshotData($snapshot, $quote, $productIndexed, $commentsIdsMap);

                $snapshotNew = $this->serializer->serialize($snapshot);
            }

            if (!empty($logData)) {
                $logData = $this->replaceLogData(
                    $logData,
                    $quote,
                    $productIndexed,
                    $commentsIdsMap,
                    $snapshot,
                    $previousHistorySnapshot
                );

                $logDataNew = $this->serializer->serialize($logData);
            }

            $historyLog->setSnapshotData($snapshotNew);
            $historyLog->setLogData($logDataNew);
            $this->historyRepository->save($historyLog);
            return $historyLog;
        }

        return null;
    }

    /**
     * @param $log
     * @param $quote
     * @param $productIndexed
     * @param array $commentsIdsMap
     * @param array $snapshot
     * @param array $previousHistorySnapshot
     * @return array
     */
    private function replaceLogData(
        $log,
        $quote,
        $productIndexed,
        array $commentsIdsMap = [],
        array $snapshot = [],
        array $previousHistorySnapshot = []
    ): array
    {
        //need to find a logic to get old address, for now utilizing current add
        if (isset($log['address']['old_value'])) {
            $log['address']['old_value'] = $this->collectAddressData($quote);
        }

        if (isset($log['address']['new_value'])) {
            $log['address']['new_value'] = $this->collectAddressData($quote);
        }

        if (isset($log['comment']) && array_key_exists($log['comment'], $commentsIdsMap)) {
            $log['comment'] = $commentsIdsMap[$log['comment']];
        }

        if (isset($log['item_notes'])) {
            $log = $this->processItemNotesDiffInCart($log, $snapshot, $previousHistorySnapshot);
        }

        if (isset($log['added_to_cart'])) {
            foreach ($log['added_to_cart'] as $sku => $itemData) {
                try {
                    $prod = $this->productRepository->get($sku);
                    $itemData['product_id'] = $prod->getId();


                    //options addition has error in current b2b version. workaround added
                if (array_key_exists($sku, $productIndexed)) {
                    $prod = $productIndexed[$sku];
                    if (isset($itemData['options']) && isset($prod['options']))
                        $itemData['options'] = $this->getOptionsArray($prod['options']);
                    }

                    $log['added_to_cart'][$sku] = $itemData;
                } catch (NoSuchEntityException $e) {
                    $this->helper->logMessage("Added to cart Log History data for ". $this->quoteName .
                        " is invalid {$e->getMessage()}, row skipped", "warning");
                }
            }
        }

        if (isset($log['custom_log'])) {
            foreach ($log['custom_log'] as $key => $itemData) {
                if (isset($itemData['product_id']) && isset($itemData['product_sku'])) {
                    try {
                        $prod = $this->productRepository->get($itemData['product_sku']);
                        $itemData['product_id'] = $prod->getId();
                        $log['custom_log'][$key] = $itemData;
                    } catch (NoSuchEntityException $e) {
                        $this->helper->logMessage("Custom Log History data for ". $this->quoteName .
                            " is invalid {$e->getMessage()}, row skipped", "warning");
                    }
                }
            }
        }

        //need to check with multi options
        if (isset($log['removed_from_cart'])) {
            foreach ($log['removed_from_cart'] as $key => $itemData) {
                try {
                    $prod = $this->productRepository->get($itemData['sku']);
                    $itemData['product_id'] = $prod->getId();
                    //for key as item id, no requirement to add and delete item from cart, as key is not used
                    $log['removed_from_cart'][$key] = $itemData;
                } catch (NoSuchEntityException $e) {
                    $this->helper->logMessage("Removed from cart Log History data for ". $this->quoteName .
                        " is invalid {$e->getMessage()}, row skipped", "warning");
                }
            }
        }

        if (isset($log['updated_in_cart'])) {
            foreach ($log['updated_in_cart'] as $sku => $itemData) {
                try {
                    if (isset($itemData['product_sku'])) {
                        $productModel = $this->productRepository->get($itemData['product_sku']);
                        unset($itemData['product_sku']);
                        $itemData['product_id'] = $productModel->getId();
                        if (isset($itemData['options_changed'])) {
                            $optChange = $itemData['options_changed'];
                            $productAttributes = $productModel->getTypeInstance()
                                ->getConfigurableAttributesAsArray($productModel);

                            $attIndexed = array_column($productAttributes, null, 'attribute_code');
                            foreach ($optChange as $attrCode => $optionVals) {
                                $attribute = $attIndexed[$attrCode];
                                $attrOptions = $attribute['options'];
                                foreach ($attrOptions as $attrOption) {
                                    if ($attrOption['label'] === $optionVals['old_value']) {
                                        $optionVals['old_value'] = $attrOption['value'];
                                    }
                                    if ($attrOption['label'] === $optionVals['new_value']) {
                                        $optionVals['new_value'] = $attrOption['value'];
                                    }
                                }
                                unset($itemData['options_changed'][$attrCode]);
                                $itemData['options_changed'][$attribute['attribute_id']] = $optionVals;
                            }
                        }
                    }
                    $log['updated_in_cart'][$sku] = $itemData;
                } catch (NoSuchEntityException $e) {
                    $this->helper->logMessage("Updated in cart Log History data for ". $this->quoteName .
                        " is invalid {$e->getMessage()}, row skipped", "warning");
                }
            }
        }

        return $log;
    }

    private function processItemNotesDiffInCart($log, $currentSnapshot, $oldSnapshot): array
    {
        $log['item_notes'] = [];
        foreach ($currentSnapshot['cart'] as $cartItemId => $product) {
            if (empty($product['notes'])) {
                continue;
            }

            if (isset($oldSnapshot['cart'][$cartItemId]['notes'])
                && is_array($oldSnapshot['cart'][$cartItemId]['notes'])
            ) {
                if (count($oldSnapshot['cart'][$cartItemId]['notes']) === count($product['notes'])) {
                    continue;
                }
                if (count($product['notes']) > count($oldSnapshot['cart'][$cartItemId]['notes'])) {
                    $log['item_notes'][$cartItemId]['notes'] = array_pop($product['notes']);
                }
            } else {
                $log['item_notes'][$cartItemId]['notes'] = array_pop($product['notes']);
            }
        }

        return $log;
    }

    /**
     * @param $snapshot
     * @param $quote
     * @return array
     */
    private function replaceSnapshotData($snapshot, $quote, $productIndexed, $commentsIdsMap = []): array
    {
        if (isset($snapshot['address'])) {
            $snapshot['address'] = $this->collectAddressData($quote);
        }

        if (isset($snapshot['expiration_date']) && $snapshot['expiration_date'] != Expiration::DATE_QUOTE_NEVER_EXPIRES) {
            $defaultExpirationDate = $this->retrieveNegotiableQuote($quote)->getExpirationPeriod();

            if ($defaultExpirationDate != Expiration::DATE_QUOTE_NEVER_EXPIRES && strtotime($defaultExpirationDate) > strtotime($snapshot['expiration_date']))
            $snapshot['expiration_date'] = $this->retrieveNegotiableQuote($quote)->getExpirationPeriod();
        }

        if (isset($snapshot['comments'])) {
            $ids = [];
            foreach ($snapshot['comments'] as $oldId) {
                if (array_key_exists($oldId, $commentsIdsMap))
                    $ids[] = $commentsIdsMap[$oldId];
            }
            $snapshot['comments'] = $ids;
        }

        if (isset($snapshot['cart'])) {
            $snapshot['cart'] = $this->setCartSnapshotData($snapshot['cart'], $productIndexed);
        }

        return $snapshot;
    }

    private function setCartSnapshotData($snapshotCart, $productIndexed): array
    {
        $newItemData = [];
        foreach ($snapshotCart as $itemData) {
            if (array_key_exists($itemData['sku'], $productIndexed) && !empty($prod['product_id'])) {
                $prod = $productIndexed[$itemData['sku']];
                $itemData['product_id'] = $prod['product_id'];

                if (isset($itemData['options']) && isset($itemData['product_sku'])) {
                    $productModel = $this->productRepository->get($itemData['product_sku']);
                    unset($itemData['product_sku']);
                    $itemData['product_id'] = $productModel->getId();

                    $productAttributes = $productModel->getTypeInstance()
                        ->getConfigurableAttributesAsArray($productModel);

                    $attIndexed = array_column($productAttributes, null, 'attribute_code');
                    foreach ($itemData['options'] as $k => $opts) {
                        $attribute = $attIndexed[$opts['option']];
                        $attOptionsIndexed = array_column($attribute['options'], 'value', 'label');
                        $opts[$k] = [
                            'option' => $attribute['attribute_id'],
                            'value' => $attOptionsIndexed[$opts['value']]
                        ];
                    }
                }
                //$itemData['options'] = $this->getOptionsArray($prod['options']);

                if (!empty($itemData['notes'])) {
                    $notesIdsMap = !empty($prod['note_id_map']) ? $prod['note_id_map'] : [];
                    $ids = [];
                    foreach ($itemData['notes'] as $oldId) {
                        if (array_key_exists($oldId, $notesIdsMap))
                            $ids[] = $notesIdsMap[$oldId];
                    }
                    $itemData['notes'] = $ids;
                }

                $newItemData[$prod["item_id"]] = $itemData;
            }
        }

        return $newItemData;
    }

    private function getOptionsArray(array $options): array
    {
        $optionsArray = [];
        if (isset($options['super_attribute'])) {
            $optionsArray = $this->mergeOptionsToArray($options['super_attribute'], $optionsArray);
        }
        if (isset($options['bundle_option'])) {
            $optionsArray = $this->mergeOptionsToArray($options['bundle_option'], $optionsArray);
        }
        if (isset($options['options'])) {
            $optionsArray = $this->mergeOptionsToArray($options['options'], $optionsArray);
        }

        return $optionsArray;
    }

    /**
     * Add options to array.
     *
     * @param array $options
     * @param array $optionsArray
     * @return array
     */
    private function mergeOptionsToArray(array $options, array $optionsArray): array
    {
        foreach ($options as $option => $value) {
            $optionsArray[] = [
                'option' => $option,
                'value' => $value
            ];
        }
        return $optionsArray;
    }

    private function setNegotiableQuoteStatus($quote, $status): void
    {
        $negotiableQuote = $quote->getExtensionAttributes()->getNegotiableQuote();

        if ($status != $negotiableQuote->getStatus()) {
            $negotiableQuote->setStatus($status);
            $this->saveQuote($quote);
        }
    }

    private function collectAddressData(CartInterface $quote): array
    {
        $shippingAddressArray = [];
        /** @var \Magento\Quote\Api\Data\AddressInterface $shippingAddress */
        $shippingAddress = $quote->getShippingAddress();
        if ($shippingAddress->getPostcode()) {
            $shippingAddressArray['id'] = $shippingAddress->getCustomerAddressId();
            $shippingAddressArray['array'] = $this->getAddressArray($shippingAddress);
        }
        return $shippingAddressArray;
    }

    /**
     * Get customer address html.
     *
     * @param \Magento\Quote\Api\Data\AddressInterface $address
     * @return array
     */
    private function getAddressArray(\Magento\Quote\Api\Data\AddressInterface $address): array
    {
        $flatAddressArray = $this->dataObjectConverter->toFlatArray(
            $address,
            [],
            \Magento\Quote\Api\Data\AddressInterface::class
        );
        $street = $address->getStreet();
        if (!empty($street) && is_array($street)) {
            // Unset flat street data
            $streetKeys = array_keys($street);
            foreach ($streetKeys as $key) {
                if (is_array($flatAddressArray)) {
                    unset($flatAddressArray[$key]);
                }
            }
            //Restore street as an array
            $flatAddressArray['street'] = $street;
        }
        return $flatAddressArray;
    }

}
