<?php

namespace Bat\ShipmentUpdate\Model\Api;

use Bat\ShipmentUpdate\Api\OrderShipmentUpdateInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Event\ManagerInterface as EventManager;

class ShipmentUpdate implements OrderShipmentUpdateInterface
{
    /**
     * @var EventManager
     */
    private $eventManager;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param OrderRepositoryInterface $orderRepository
     * @param EventManager $eventManager
     */
    public function __construct(
        \Magento\Sales\Model\Order $order,
        OrderRepositoryInterface $orderRepository,
        EventManager $eventManager
    ) {
        $this->order = $order;
        $this->orderRepository = $orderRepository;
        $this->eventManager = $eventManager;
    }
    /**
     * @inheritdoc
     */
    public function shipmentUpdate($entity)
    {
        $this->addLog("======================Request Data===============================");
        $this->addLog(json_encode($entity));
        $messageId = $entity['message_id'];
        $messageDate = $entity['message_date'];
        $orderId = $entity['order_increment_id'];
        $courierId = $entity['carrier_code'];
        $courierName = $entity['carrier_name'];
        $shipmentInvoice = $entity['awb_number'];
        $trackUrl = $entity['tracking_url'];
        $wmsOutDate = $entity['ship_date'];

        $data = [];

        if ((trim($messageId) == '')) {
            $data['status'] = false;
            $data['message'] = 'The Message ID should not be empty';

            $return['response'] = $data;
            return $return;
        }

        if ((trim($messageDate) == '')) {
            $data['status'] = false;
            $data['message'] = 'The Message Date should not be empty';

            $return['response'] = $data;
            return $return;
        }

        if ((trim($orderId) == '')) {
            $data['status'] = false;
            $data['message'] = 'The order ID should not be empty';

            $return['response'] = $data;
            return $return;
        }

        if ((trim($courierId) == '')) {
            $data['status'] = false;
            $data['message'] = 'The Carrier Code should not be empty';

            $return['response'] = $data;
            return $return;
        }

        if ((trim($courierName) == '')) {
            $data['status'] = false;
            $data['message'] = 'The Carrier Name should not be empty';

            $return['response'] = $data;
            return $return;
        }

        if ((trim($shipmentInvoice) == '')) {
            $data['status'] = false;
            $data['message'] = 'The Awb Number should not be empty';

            $return['response'] = $data;
            return $return;
        }

        if ((trim($trackUrl) == '')) {
            $data['status'] = false;
            $data['message'] = 'The Track Url should not be empty';

            $return['response'] = $data;
            return $return;
        }

        if ((trim($wmsOutDate) == '')) {
            $data['status'] = false;
            $data['message'] = 'The Ship Date should not be empty';

            $return['response'] = $data;
            return $return;
        }

        try {

            $orderData = $this->order->loadByIncrementId($orderId);
            if ($orderData->getId()) {
                $order = $this->orderRepository->get($orderData->getId());
                $order->setMessageId($entity['message_id']);
                $order->setMessageDate($entity['message_date']);
                $order->setCarrierCode($entity['carrier_code']);
                $order->setCarrierName($entity['carrier_name']);
                $order->setAwbNumber($entity['awb_number']);
                $order->setTrackingUrl($entity['tracking_url']);
                $order->setShippingStatusCode($entity['shipping_status_code']);
                $order->setShippingStatusMessage($entity['shipping_status_message']);
                $order->setShipDate($entity['ship_date']);
                $order->save();
            }
            
            $Updatedorder = $this->orderRepository->get($orderData->getId());

            $data['status'] = true;
            $data['message_id'] = $Updatedorder->getMessageId();
            $data['message_date'] = $Updatedorder->getMessageDate();
            $data['order_id'] = $Updatedorder->getId();
            $data['courier_id'] = $Updatedorder->getCarrierCode();
            $data['courier_name'] = $Updatedorder->getCarrierName();
            $data['shipment_invoice'] = $Updatedorder->getAwbNumber();
            $data['track_url'] = $Updatedorder->getTrackingUrl();
            $data['status_cd'] = $Updatedorder->getShippingStatusCode();
            $data['status_text'] = $Updatedorder->getShippingStatusMessage();
            $data['wms_out_date'] = $Updatedorder->getShipDate();

        } catch (Exception $e) {
            $this->addLog($e->getMessage());
        }

        $this->addLog("==================================Response========================");
        $this->addLog(json_encode($data));
        $return['response'] = $data;

        return $return;
    }

    /**
     * Add Log Function
     *
     * @param mixed $logData
     * @param string $filename
     */
    public function addLog($logData, $filename = "order_shipment_update.log")
    {
        if ($this->canWriteLog($filename)) {
            $this->logger->info($logData);
        }
    }

     /**
      * Write logfile
      *
      * @param string $filename
      */
    protected function canWriteLog($filename)
    {
        $logEnable = 1;
        if ($logEnable) {
            $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/'.$filename);
            $logger = new \Zend_Log();
            $logger->addWriter($writer);
            $this->logger = $logger;
        }

        return $logEnable;
    }
}
