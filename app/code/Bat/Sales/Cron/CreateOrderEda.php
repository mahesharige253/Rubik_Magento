<?php

namespace Bat\Sales\Cron;

use Bat\Sales\Helper\Data;
use Bat\Sales\Model\EdaOrdersFactory;
use Bat\Sales\Model\ResourceModel\EdaOrdersResource;
use Bat\Sales\Model\SendOrderDetails;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\OrderStatusHistoryRepositoryInterface;

/**
 * @class CreateOrderEda
 * Cron to create orders in EDA
 */
class CreateOrderEda
{
    /**
     * @var int
     */
    private $maxFailuresAllowed;

    /**
     * @var string
     */
    private $apiEndPoint;

    /**
     * @var boolean
     */
    private $logEnabled;

    /**
     * @var SendOrderDetails
     */
    private SendOrderDetails $sendOrderDetails;

    /**
     * @var Data
     */
    private $dataHelper;

    /**
     * @var EdaOrdersFactory
     */
    private EdaOrdersFactory $edaOrdersFactory;

    /**
     * @var EdaOrdersResource
     */
    private EdaOrdersResource $edaOrdersResource;

    /**
     * @var OrderRepositoryInterface
     */
    private OrderRepositoryInterface $orderRepository;

    /**
     * @var OrderStatusHistoryRepositoryInterface
     */
    private OrderStatusHistoryRepositoryInterface $orderStatusRepository;

    /**
     * @param SendOrderDetails $sendOrderDetails
     * @param Data $dataHelper
     * @param EdaOrdersFactory $edaOrdersFactory
     * @param EdaOrdersResource $edaOrdersResource
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderStatusHistoryRepositoryInterface $orderStatusRepository
     */
    public function __construct(
        SendOrderDetails $sendOrderDetails,
        Data $dataHelper,
        EdaOrdersFactory $edaOrdersFactory,
        EdaOrdersResource $edaOrdersResource,
        OrderRepositoryInterface $orderRepository,
        OrderStatusHistoryRepositoryInterface $orderStatusRepository
    ) {
        $this->sendOrderDetails = $sendOrderDetails;
        $this->dataHelper = $dataHelper;
        $this->edaOrdersFactory = $edaOrdersFactory;
        $this->edaOrdersResource = $edaOrdersResource;
        $this->orderRepository = $orderRepository;
        $this->orderStatusRepository = $orderStatusRepository;
    }

    /**
     * Create Orders in EDA
     */
    public function execute()
    {
        $this->logEnabled = $this->dataHelper->getEdaCreateOrderLogStatus();
        try {
            $this->maxFailuresAllowed = $this->dataHelper->getEdaCreateOrderMaxFailuresAllowed();
            $edaOrderCollection = $this->sendOrderDetails->getEdaOrderCollection($this->maxFailuresAllowed);
            if ($edaOrderCollection->count()) {
                $this->apiEndPoint = $this->dataHelper->getEdaCreateUpdateOrderEndpoint();
                foreach ($edaOrderCollection as $edaOrder) {
                    $result = $this->processOrder($edaOrder);
                    if ($result['success'] && $result['eligible']) {
                        $edaOrder->setOrderSent(1);
                        $order = $this->orderRepository->get($edaOrder['order_id']);
                        $comment = $order->addStatusHistoryComment(
                            'Updated order to EDA for type : '.$edaOrder['order_type']
                        );
                        $this->orderStatusRepository->save($comment);
                    } elseif ($result['eligible']) {
                        $failureAttempts = $edaOrder['failure_attempts'] + 1;
                        $edaOrder->setFailureAttempts($failureAttempts);
                    }
                    $this->edaOrdersResource->save($edaOrder);
                }
            } else {
                if ($this->logEnabled) {
                    $this->dataHelper->logEdaOrderUpdateRequest(
                        '=============================================='
                    );
                    $this->dataHelper->logEdaOrderUpdateRequest('No orders to update');
                }
            }
        } catch (\Exception $e) {
            if ($this->logEnabled) {
                $this->dataHelper->logEdaOrderUpdateRequest($e->getMessage());
            }
        }
    }

    /**
     * Process order data to EDA
     *
     * @param $edaOrder
     * @return bool|false[]
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function processOrder($edaOrder)
    {
        $result = ['success'=>false,'eligible'=>false];
        $orderId = $edaOrder['order_id'];
        $orderType = $edaOrder['order_type'];
        $orderCollection = $this->sendOrderDetails->getOrderCollection($orderType, $orderId);
        if ($orderCollection->count()) {
            $result['eligible'] = true;
            foreach ($orderCollection as $order) {
                $orderData = json_encode($this->sendOrderDetails->formatOrderData($order, $orderType));
                $status = $this->dataHelper->postOrderDataToEda($orderData, $this->apiEndPoint);
                $statusDecoded = json_decode($status, true);
                if (isset($statusDecoded['success']) && $statusDecoded['success']) {
                    $result['success'] = true;
                }
                if ($this->logEnabled) {
                    $this->dataHelper->logEdaOrderUpdateRequest(
                        '==============================================',
                    );
                    $this->dataHelper->logEdaOrderUpdateRequest('Request : OrderId - '.$orderId);
                    $this->dataHelper->logEdaOrderUpdateRequest($orderData);
                    $statusLog = ($result['success']) ? 'success' : 'failure';
                    $this->dataHelper->logEdaOrderUpdateRequest('Response : '.$statusLog);
                    $this->dataHelper->logEdaOrderUpdateRequest($status);
                }
            }
        }
        return $result;
    }
}
