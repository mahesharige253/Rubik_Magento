<?php

namespace Bat\Sales\Model\Api;

use Bat\Sales\Api\OrderConfirmationInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;

/**
 * @class ConfirmOrder
 * Order Confirmation update
 */
class ConfirmOrder implements OrderConfirmationInterface
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
     * @param Order $order
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        Order $order,
        OrderRepositoryInterface $orderRepository,
    ) {
        $this->order = $order;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @param mixed $data
     * @return array[]
     */
    public function confirmOrder($data)
    {
        $result = ['success' => false, 'message' => ''];
        try {
            $this->addLog("====================================================");
            $this->addLog("Request : ");
            $this->addLog(json_encode($data));
            $this->addLog("Response : ");
            $this->validateInput($data);
            $orderData = $this->order->loadByIncrementId($data['increment_id']);
            if ($orderData->getId()) {
                $order = $this->orderRepository->get($orderData->getId());
                $order->setBatchId($data['batch_id']);
                $order->setUpdatedDate($data['updated_date']);
                $order->setCountryCode($data['country_code']);
                $order->setSapCountryCode($data['sap_country_code']);
                $order->setSapOrderNumber($data['sap_order_number']);
                $order->setSapCreditStatus($data['sap_credit_status']);
                $order->setSapOrderStatus($data['sap_order_status']);
                $order->save();
                $result['success'] = true;
                $result['message'] = 'Order confirmation status updated successfully';
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
     * Eda order confirmation log
     *
     * @param string $message
     * @throws Zend_Log_Exception
     */
    public function addlog($message)
    {
        $writer = new \Zend_Log_Writer_Stream(BP .'/var/log/EdaOrderConfirmation.log');
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
        if (!isset($data['increment_id']) || (trim($data['increment_id']) == '')) {
            throw new LocalizedException(__('increment_id is required and value should be specified'));
        }
        if (!isset($data['batch_id']) || (trim($data['batch_id']) == '')) {
            throw new LocalizedException(__('batch_id is required and value should be specified'));
        }
        if (!isset($data['updated_date']) || (trim($data['updated_date']) == '')) {
            throw new LocalizedException(__('updated_date is required and value should be specified'));
        }
        if (!isset($data['country_code']) || (trim($data['country_code']) == '')) {
            throw new LocalizedException(__('country_code is required and value should be specified'));
        }
        if (!isset($data['sap_country_code']) || (trim($data['sap_country_code']) == '')) {
            throw new LocalizedException(__('sap_country_code is required and value should be specified'));
        }
        if (!isset($data['sap_order_number']) || (trim($data['sap_order_number']) == '')) {
            throw new LocalizedException(__('sap_order_number is required and value should be specified'));
        }
        if (!isset($data['sap_credit_status']) || (trim($data['sap_credit_status']) == '')) {
            throw new LocalizedException(__('sap_credit_status is required and value should be specified'));
        }
        if (!isset($data['sap_order_status']) || (trim($data['sap_order_status']) == '')) {
            throw new LocalizedException(__('sap_order_status is required and value should be specified'));
        }
    }
}
