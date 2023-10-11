<?php

namespace Bat\Sales\Model;

use Bat\Sales\Helper\Data;
use Bat\Sales\Model\ResourceModel\EdaOrdersResource;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Catalog\Model\ProductRepository;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Item;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Bat\Sales\Model\ResourceModel\EdaOrdersResource\CollectionFactory as EdaOrdersCollectionFactory;
use Psr\Log\LoggerInterface;

/**
 * @class SendOrderDetails
 * Create/Update Orders in EDA
 */
class SendOrderDetails
{
    /**
     * @var CustomerRepositoryInterface
     */
    protected CustomerRepositoryInterface $customerRepository;

    /**
     * @var ProductRepository
     */
    protected ProductRepository $productRepository;

    /**
     * @var CollectionFactory
     */
    protected CollectionFactory $orderCollectionFactory;

    /**
     * @var Data
     */
    protected Data $dataHelper;

    /**
     * @var EdaOrdersCollectionFactory
     */
    protected EdaOrdersCollectionFactory $edaOrdersCollectionFactory;

    /**
     * @var EdaOrdersFactory
     */
    private EdaOrdersFactory $edaOrdersFactory;

    /**
     * @var EdaOrdersResource
     */
    private EdaOrdersResource $edaOrdersResource;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param CustomerRepositoryInterface $customerRepository
     * @param ProductRepository $productRepository
     * @param CollectionFactory $orderCollectionFactory
     * @param Data $dataHelper
     * @param EdaOrdersCollectionFactory $edaOrdersCollectionFactory
     * @param EdaOrdersFactory $edaOrdersFactory
     * @param EdaOrdersResource $edaOrdersResource
     * @param LoggerInterface $logger
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        ProductRepository $productRepository,
        CollectionFactory $orderCollectionFactory,
        Data $dataHelper,
        EdaOrdersCollectionFactory $edaOrdersCollectionFactory,
        EdaOrdersFactory $edaOrdersFactory,
        EdaOrdersResource $edaOrdersResource,
        LoggerInterface $logger
    ) {
        $this->customerRepository = $customerRepository;
        $this->productRepository = $productRepository;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->dataHelper = $dataHelper;
        $this->edaOrdersCollectionFactory = $edaOrdersCollectionFactory;
        $this->edaOrdersFactory = $edaOrdersFactory;
        $this->edaOrdersResource = $edaOrdersResource;
        $this->logger = $logger;
    }

    /**
     * Prepare Payload for EDA Order create/update
     *
     * @param OrderInterface $order
     * @param $orderType
     */
    public function formatOrderData($order, $orderType)
    {
        $customerId = $order->getCustomerId();
        $customer = $this->customerRepository->getById($customerId);
        $sapOutletCode = '';
        $batchId = '';
        $countryCode = '';
        if ($customer->getCustomAttribute('sap_outlet_code')) {
            $sapOutletCode = $customer->getCustomAttribute('sap_outlet_code')->getValue();
        }
        if ($customer->getCustomAttribute('bat_batch_id')) {
            $batchId = $customer->getCustomAttribute('bat_batch_id')->getValue();
        }
        if ($customer->getCustomAttribute('bat_country_code')) {
            $countryCode = $customer->getCustomAttribute('bat_country_code')->getValue();
        }
        $createdAt = $this->dataHelper->formatDate($order->getCreatedAt());
        $result = [];
        $orderData = [];
        $result['header']['batchId'] = $batchId;
        $result['header']['transactionType'] = '';
        $result['header']['creationDate'] = $createdAt;
        $result['header']['countryCode'] = $countryCode;
        $orderData['orderNumber'] = $order->getIncrementId();
        $orderData['orderType'] = $orderType;
        $orderData['salesOrg'] = 'KR12';
        $orderData['distributionChannel'] = '01';
        $orderData['soldToCustomer'] = $sapOutletCode;
        $orderData['shipToCustomer'] = $sapOutletCode;
        $orderData['division'] = '01';
        $orderData['deliveryDate'] = $createdAt;
        $orderData['originalOrderNumber'] = $order->getEntityId();
        $orderData['createdDate'] = $createdAt;
        if ($orderType != 'ZOR') {
            $orderData['orderReason'] = '001';
        }
        $orderData['purchaseOrderType'] = '';
        $orderData['lineItems'] = [];
        $orderItems = $order->getAllItems();
        foreach ($orderItems as $item) {
            $itemDetails = $this->getItemDetailsBasedOnOrderType($orderType, $item);
            $productId = $item->getProductId();
            $product = $this->productRepository->getById($productId);
            $lineItemData = [];
            $lineItemData['lineItemId'] = (int)$item->getId();
            $lineItemData['sapProductCode'] = $item->getSku();
            $lineItemData['quantity'] = (float)$itemDetails['quantity'];
            $lineItemData['uom'] = 'CRT';
            $lineItemData['netAmount'] = (float)$itemDetails['netAmount'];
            $lineItemData['tax'] = (float)$itemDetails['tax'];
            $lineItemData['discounts'] = $this->getItemDiscounts($product, $item);
            $orderData['lineItems'][] = $lineItemData;
        }
        $result['orders'][] = $orderData;
        $result['footer']['recordCount'] = 1;
        $result['footer']['lineItemCount'] = count($orderItems);
        return $result;
    }

    /**
     * Return Item Details based on order type
     *
     * @param string $orderType
     * @param Item $item
     */
    public function getItemDetailsBasedOnOrderType($orderType, $item)
    {
        $quantity = 0;
        $netAmount = 0;
        $tax = 0;
        if ($orderType == 'ZRE') {
            $quantity = $item->getQtyOrdered() - $item->getQtyRefunded();
            $netTotalAmount = $item->getRowTotal() - $item->getDiscountAmount();
            $netAmount = $netTotalAmount - ($item->getAmountRefunded() - $item->getDiscountRefunded());
            $tax = $item->getTaxAmount() - $item->getTaxRefunded();
        } elseif ($orderType == 'ZOR') {
            $quantity = $item->getQtyOrdered();
            $netAmount = $item->getRowTotal() - $item->getDiscountAmount();
            $tax = $item->getTaxAmount();
        }
        return ['quantity' => $quantity, 'netAmount' => $netAmount, 'tax' => $tax];
    }

    /**
     * Return Item Discounts
     *
     * @param ProductInterface $product
     * @param Item $item
     */
    public function getItemDiscounts($product, $item)
    {
        /* temporary values */
        $discountResult = [];
        $discountResult[] = $discount = [
            'discountSeqNo' => 0,
            'discountType' => '',
            'discountValue' => 0
        ];
        return $discountResult;
        /* temporary values */
    }

    /**
     * Return order collection for EDA create order
     *
     * @return \Magento\Sales\Model\ResourceModel\Order\Collection
     * @throws LocalizedException
     */
    public function getOrderCollection($orderType, $orderId)
    {
        $orderCollection = $this->orderCollectionFactory->create()
            ->addAttributeToSelect('*')
            ->addFieldToFilter('entity_id', ['in' => $orderId]);
        if ($orderType == 'ZOR') {
            $orderStatus = $this->dataHelper->getOrderStatusRequiredToUpdateEda();
            if ($orderStatus != '') {
                $orderCollection->addFieldToFilter('status', ['eq' => $orderStatus]);
            } else {
                throw new LocalizedException(__('Order status should be configured in admin'));
            }
        }
        return $orderCollection;
    }

    /**
     * Return EDA create/update orders collection
     *
     * @return mixed
     */
    public function getEdaOrderCollection($maxFailuresAllowed)
    {
        return $this->edaOrdersCollectionFactory->create()->addFieldToSelect('*')
            ->addFieldToFilter('failure_attempts', ['lt'=>$maxFailuresAllowed])
            ->addFieldToFilter('order_sent', ['eq'=>0]);
    }

    /**
     * Update order in EDA order update pending table
     *
     * @param string $orderId
     * @param string $orderType
     */
    public function addOrderInEdaOrderUpdate($orderId, $orderType)
    {
        try {
            $edaOrders = $this->edaOrdersCollectionFactory->create()->addFieldToSelect('*')
                ->addFieldToFilter('order_id', ['eq'=>$orderId]);
            if ($edaOrders->count()) {
                foreach ($edaOrders as $edaOrder) {
                    $edaOrder->setOrderSent(0);
                    $edaOrder->setFailureAttempts(0);
                    $edaOrder->setOrderType($orderType);
                    $this->edaOrdersResource->save($edaOrder);
                }
            } else {
                $data = [
                    'order_id' => $orderId,
                    'order_sent' => 0,
                    'order_type' => $orderType,
                    'failure_attempts' => 0
                ];
                $edaOrder = $this->edaOrdersFactory->create();
                $edaOrder->setData($data);
                $this->edaOrdersResource->save($edaOrder);
            }
        } catch (\Exception $e) {
            $this->logger->error(
                'Update order to eda order table failed for orderId : '.$orderId.'-'.$e->getMessage()
            );
        }
    }
}
