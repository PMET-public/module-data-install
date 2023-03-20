<?php
///function deprecated
//phpcs:disable
/**
 * Copyright 2022 Adobe, Inc. All rights reserved.
 * See LICENSE for license details.
*/
namespace MagentoEse\DataInstall\Model\DataTypes;

use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Backend\Model\Session\QuoteFactory as SessionQuoteFactory;
use Magento\Sales\Model\AdminOrder\CreateFactory as CreateOrderFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Shipping\Controller\Adminhtml\Order\ShipmentLoaderFactory;
use Magento\Sales\Controller\Adminhtml\Order\CreditmemoLoaderFactory;
use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\Registry;
use MagentoEse\DataInstall\Helper\Helper;
use Magento\Framework\ObjectManagerInterface;

class Orders
{

    /** @var Helper */
    protected $helper;

    /** @var CustomerRepository */
    protected $customerRepository;

    /** @var SessionQuoteFactory */
    protected $sessionQuoteFactory;

    /** @var CreateOrderFactory */
    protected $createOrderFactory;

    /** @var ProductRepositoryInterface */
    protected $productRepository;

    /** @var InvoiceService */
    //protected $invoiceManagement;

    /** @var ShipmentLoaderFactory */
    protected $shipmentLoaderFactory;

    /** @var CreditmemoLoaderFactory */
    protected $creditmemoLoaderFactory;

    /** @var TransactionFactory */
    protected $transactionFactory;

    /** @var Registry */
    protected $coreRegistry;

    /** @var ObjectManagerInterface  */
    protected $objectManager;

    /** @var OrderFactory  */
    protected $orderFactory;

    public function __construct(
        Helper $helper,
        CustomerRepositoryInterface $customerRepository,
        SessionQuoteFactory $sessionQuoteFactory,
        CreateOrderFactory $createOrderFactory,
        ProductRepositoryInterface $productRepositoryInterface,
        OrderFactory $orderFactory,
        //InvoiceService $invoiceService,
        ShipmentLoaderFactory $shipmentLoaderFactory,
        CreditmemoLoaderFactory $creditmemoLoaderFactory,
        TransactionFactory $transactionFactory,
        Registry $coreRegistry,
        ObjectManagerInterface $objectManager
    ) {
        $this->helper = $helper;
        $this->customerRepository = $customerRepository;
        $this->sessionQuoteFactory = $sessionQuoteFactory;
        $this->createOrderFactory = $createOrderFactory;
        $this->productRepository = $productRepositoryInterface;
        $this->orderFactory = $orderFactory;
        //$this->invoiceManagement = $invoiceService;
        $this->shipmentLoaderFactory = $shipmentLoaderFactory;
        $this->creditmemoLoaderFactory = $creditmemoLoaderFactory;
        $this->transactionFactory = $transactionFactory;
        $this->coreRegistry = $coreRegistry;
        $this->objectManager = $objectManager;
    }

    /**
     * @param array $row
     * @return bool
     * @throws LocalizedException
     */
    public function install(array $row, array $settings)
    {
        //check if user exists
        if (empty($row['customer_email'])) {
            $this->helper->logMessage("customer_email value is required in orders file", "warning");
            return true;
        }
        /** @var CustomerInterface $customer */
        $customer = $this->customerRepository->get($row['customer_email'], 1);
        if (!$customer->getId()) {
            $this->helper->logMessage("customer_email ".$row['customer_email'].
            " was not found in orders file. Row is skipped", "warning");
            return true;
        }
        $this->createOrder($row, $customer);
        //check if products exist in the quantity requested.
        //check if payment method exists
        //check if shipping method exists
        return true;
    }
    public function createOrder($row, $customer)
    {
        $this->currentSession = $this->sessionQuoteFactory->create();
        $this->currentSession->setCustomerId($customer->getId());
        $orderCreateModel = $this->processQuote($row, $customer);
        $orderCreateModel->getQuote()->setCustomer($customer);
        //TBD $orderCreateModel->setShippingMethod();
        $order = $orderCreateModel->createOrder();
        $orderItem = $this->getOrderItemForTransaction($order);
        $this->invoiceOrder($orderItem);
        $this->shipOrder($orderItem);
        if (!empty($row['refund']) && $row['refund'] === "yes") {
            $this->refundOrder($orderItem);
        }
        $registryItems = [
            'rule_data',
            'currently_saved_addresses',
            'current_invoice',
            'current_shipment',
        ];
        $this->unsetRegistryData($registryItems);
        $this->currentSession->unsQuoteId();
        $this->currentSession->unsStoreId();
        $this->currentSession->unsCustomerId();
    }

    /**
     * @param array $data
     * @return \Magento\Sales\Model\AdminOrder\Create
     */
    protected function processQuote($row, $customer)
    {
        /** @var \Magento\Sales\Model\AdminOrder\Create $orderCreateModel */

        // $orderCreateModel = $this->appState->emulateAreaCode(
        //     AppArea::AREA_ADMINHTML,
        //     [$this->createOrderFactory, 'create'],
        //     [['quoteSession' => $this->currentSession]]
        // );
        $orderCreateModel = $this->createOrderFactory->create(
            ['quoteSession' => $this->currentSession]
        );
        $orderCreateModel->importPostData($row)->initRuleData();
        //TBD $orderCreateModel->setBillingAddress($this->getBillingAddress($customer));
        //$orderCreateModel->setShippingAddress([$this->getShippingAddress($customer)]);

        //tbd:$orderCreateModel->importPostData($data['order'])->initRuleData();
        $orderCreateModel->getBillingAddress();
        //TODO: REPLACE THIS
        $orderCreateModel->setShippingAsBilling(1);
        $orderCreateModel->addProducts($this->convertProductArray($row['products']));
        $orderCreateModel->getQuote()->getShippingAddress()->unsetData('cached_items_all');
        $orderCreateModel->getQuote()->getShippingAddress()->setShippingMethod($row['shipping_method']);
        $orderCreateModel->getQuote()->setTotalsCollectedFlag(false);
        $orderCreateModel->collectShippingRates();
        $orderCreateModel->getQuote()->getPayment()->addData(
            ['method'=>$row['payment']]
        )->setQuote($orderCreateModel->getQuote());
        return $orderCreateModel;
    }

    protected function getShippingAddress($customer)
    {
        /** @var CustomerInterface $customer */
        $addressId = $customer->getDefaultShipping();
        $addresses = $customer->getAddresses();
        foreach ($addresses as $address) {
            /** @var AddressInterface $address */
            if ($address->getId() == $customer->getDefaultShipping()) {
                return $address;
            }
        }
    }

    protected function getBillingAddress($customer)
    {
         /** @var CustomerInterface $customer */
        $addressId = $customer->getDefaultBilling();
        $addresses = $customer->getAddresses();
        foreach ($addresses as $address) {
            /** @var AddressInterface $address */
            if ($address->getId() == $customer->getDefaultBilling()) {
                return $address;
            }
        }
    }
    protected function convertProductArray($products)
    {
        $productArray = [];
        $products = explode(';', $products); //"sku=ORT4030054913,qty=5"
        foreach ($products as $product) {
            $productInfo = explode(',', $product);
            foreach ($productInfo as $productItem) {
                $b = explode('=', $productItem);
                $item[$b[0]] = $b[1];
            }
            $t = $this->productRepository->get($item['sku']);
            $productArray[$this->productRepository->get($item['sku'])->getId()] = ['qty'=>$item['qty']];
        }
        return $productArray;
    }

    protected function getOrderItemForTransaction(\Magento\Sales\Model\Order $order)
    {
        $order->getItemByQuoteItemId($order->getQuoteId());
        foreach ($order->getItemsCollection() as $item) {
            if (!$item->isDeleted() && !$item->getParentItemId()) {
                return $item;
            }
        }
        return null;
    }

    /**
     * @param \Magento\Sales\Model\Order\Item $orderItem
     * @return void
     */
    protected function invoiceOrder(\Magento\Sales\Model\Order\Item $orderItem)
    {
        $invoiceData = [$orderItem->getId() => $orderItem->getQtyToInvoice()];
        $invoice = $this->createInvoice($orderItem->getOrderId(), $invoiceData);
        if ($invoice) {
            $invoice->register();
            $invoice->getOrder()->setIsInProcess(true);
            $invoiceTransaction = $this->transactionFactory->create()
                ->addObject($invoice)
                ->addObject($invoice->getOrder());
            $invoiceTransaction->save();
        }
    }

    /**
     * @param int $orderId
     * @param array $invoiceData
     * @return bool | \Magento\Sales\Model\Order\Invoice
     */
    protected function createInvoice($orderId, $invoiceData)
    {
        $order = $this->orderFactory->create()->load($orderId);
        if (!$order) {
            return false;
        }
        // phpcs:ignore Magento2.PHP.LiteralNamespaces.LiteralClassUsage
        $invoiceManagement = $this->objectManager->create('Magento\Sales\Model\Service\InvoiceService');
        $invoice = $this->invoiceManagement->prepareInvoice($order, $invoiceData);
        return $invoice;
    }

    /**
     * @param \Magento\Sales\Model\Order\Item $orderItem
     * @return void
     */
    protected function shipOrder(\Magento\Sales\Model\Order\Item $orderItem)
    {
        $shipmentLoader = $this->shipmentLoaderFactory->create();
        $shipmentData = [$orderItem->getId() => $orderItem->getQtyToShip()];
        $shipmentLoader->setOrderId($orderItem->getOrderId());
        $shipmentLoader->setShipment($shipmentData);
        $shipment = $shipmentLoader->load();
        if ($shipment) {
            $shipment->register();
            $shipment->getOrder()->setIsInProcess(true);
            $shipmentTransaction = $this->transactionFactory->create()
                ->addObject($shipment)
                ->addObject($shipment->getOrder());
            $shipmentTransaction->save();
        }
    }

    /**
     * @param \Magento\Sales\Model\Order\Item $orderItem
     * @return void
     */
    protected function refundOrder(\Magento\Sales\Model\Order\Item $orderItem)
    {
        $creditmemoLoader = $this->creditmemoLoaderFactory->create();
        $creditmemoLoader->setOrderId($orderItem->getOrderId());
        $creditmemoLoader->setCreditmemo($this->getCreditmemoData($orderItem));
        $creditmemo = $creditmemoLoader->load();
        if ($creditmemo && $creditmemo->isValidGrandTotal()) {
            $creditmemo->setOfflineRequested(true);
            $this->creditmemoManagement->refund($creditmemo, true);
        }
    }

    /**
     * @param \Magento\Sales\Model\Order\Item $orderItem
     * @return array
     */
    public function getCreditmemoData(\Magento\Sales\Model\Order\Item $orderItem)
    {
        $data = [$orderItem->getId() => $orderItem->getQtyToRefund()];

        return $data;
    }

    /**
     * Unset registry item
     * @param array|string $unsetData
     * @return void
     */
    protected function unsetRegistryData($unsetData)
    {
        if (is_array($unsetData)) {
            foreach ($unsetData as $item) {
                $this->coreRegistry->unregister($item);
            }
        } else {
            $this->coreRegistry->unregister($unsetData);
        }
    }
}
