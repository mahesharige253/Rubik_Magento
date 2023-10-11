<?php

namespace Bat\ShipmentUpdate\Model\Api;

use Bat\ShipmentUpdate\Api\OrderDeliveryUpdateInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Sales\Model\Order;

/**
 * @class DeliveryUpdate
 * Update delivery status
 */
class DeliveryUpdate implements OrderDeliveryUpdateInterface
{
    /**
     * @var Order
     */
    private Order $order;

    /**
     * @var OrderRepositoryInterface
     */
    private OrderRepositoryInterface $orderRepository;

    /**
     * @var EventManager
     */
    private EventManager $eventManager;

    /**
     * @param Order $order
     * @param OrderRepositoryInterface $orderRepository
     * @param EventManager $eventManager
     */
    public function __construct(
        Order $order,
        OrderRepositoryInterface $orderRepository,
        EventManager $eventManager
    ) {
        $this->order = $order;
        $this->orderRepository = $orderRepository;
        $this->eventManager = $eventManager;
    }

    /**
     * @param mixed $data
     * @return array[]
     */
    public function deliveryUpdate($data)
    {
        $result = ['success' => false, 'message' => ''];
        try {
            $this->addLog("====================================================");
            $this->addLog("Request : ");
            $this->addLog(json_encode($data));
            $this->addLog("Response : ");
            $this->validateInput($data);
            $orderData = $this->order->loadByIncrementId($data['order_increment_id']);
            if ($orderData->getId()) {
                $order = $this->orderRepository->get($orderData->getId());
                $order->setMessageId($data['message_id']);
                $order->setMessageDate($data['message_date']);
                if (isset($data['carrier_code']) && $data['carrier_code'] != '') {
                    $order->setCarrierCode($data['carrier_code']);
                }
                $order->setCarrierName($data['carrier_name']);
                $order->setAwbNumber($data['awb_number']);
                $order->setTrackingUrl($data['trackin_url']);
                $order->setAction($data['action']);
                $order->setActionDate($data['action_date']);
                $order->setActionTime($data['action_time']);
                $order->setActionLocal($data['action_local']);
                $order->setCountryCode($data['country_code']);
                $order->save();
                $result['success'] = true;
                $result['message'] = 'Delivery status updated successfully';
            } else {
                $result['message'] = 'Order not found';
            }
        } catch (\Exception $e) {
            $result['message'] = $e->getMessage();
        }
        $this->addLog(json_encode($result));
        return [$result];
    }

    /**
     * Delivery update Log
     *
     * @param string $message
     * @throws Zend_Log_Exception
     */
    public function addlog($message)
    {
        $writer = new \Zend_Log_Writer_Stream(BP .'/var/log/EdaDeliveryUpdate.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info($message);
    }

    /**
     * Validate Input
     *
     * @param $data
     * @throws LocalizedException
     */
    public function validateInput($data)
    {
        if (!isset($data['message_id']) || (trim($data['message_id']) == '')) {
            throw new LocalizedException(__('message_id is required and value should be specified'));
        }
        if (!isset($data['message_date']) || (trim($data['message_date']) == '')) {
            throw new LocalizedException(__('message_date is required and value should be specified'));
        }
        if (!isset($data['order_increment_id']) || (trim($data['order_increment_id']) == '')) {
            throw new LocalizedException(__('order_increment_id is required and value should be specified'));
        }
        if (!isset($data['carrier_name']) || (trim($data['carrier_name']) == '')) {
            throw new LocalizedException(__('carrier_name is required and value should be specified'));
        }
        if (!isset($data['awb_number']) || (trim($data['awb_number']) == '')) {
            throw new LocalizedException(__('awb_number is required and value should be specified'));
        }
        if (!isset($data['action']) || (trim($data['action']) == '')) {
            throw new LocalizedException(__('action is required and value should be specified'));
        }
        if (!isset($data['action_local']) || (trim($data['action_local']) == '')) {
            throw new LocalizedException(__('action_local is required and value should be specified'));
        }
        if (!isset($data['action_time']) || (trim($data['action_time']) == '')) {
            throw new LocalizedException(__('action_time is required and value should be specified'));
        }
        if (!isset($data['action_date']) || (trim($data['action_date']) == '')) {
            throw new LocalizedException(__('action_date is required and value should be specified'));
        }
        if (!isset($data['trackin_url']) || (trim($data['trackin_url']) == '')) {
            throw new LocalizedException(__('trackin_url is required and value should be specified'));
        }
        if (!isset($data['country_code']) || (trim($data['country_code']) == '')) {
            throw new LocalizedException(__('country_code is required and value should be specified'));
        }
    }
}
