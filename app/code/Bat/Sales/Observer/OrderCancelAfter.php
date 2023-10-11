<?php
namespace Bat\Sales\Observer;

use Bat\Sales\Model\SendOrderDetails;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;

/**
 * @class AddOrderConsent
 * Add order consent to sales order and update in eda order update table
 */
class OrderCancelAfter implements ObserverInterface
{
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var SendOrderDetails
     */
    private SendOrderDetails $sendOrderDetails;

    /**
     * @param LoggerInterface $logger
     * @param SendOrderDetails $sendOrderDetails
     */
    public function __construct(
        LoggerInterface $logger,
        SendOrderDetails $sendOrderDetails
    ) {
        $this->logger = $logger;
        $this->sendOrderDetails = $sendOrderDetails;
    }

    /**
     * Set order consent status and update in eda order update table
     *
     * @param EventObserver $observer
     * @return $this|void
     */
    public function execute(EventObserver $observer)
    {
        $order = $observer->getOrder();
        $order->setData('order_type', __('Canceled Order'));
        try {
            $this->sendOrderDetails->addOrderInEdaOrderUpdate($order->getEntityId(), 'ZLOB');
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
        return $this;
    }
}
