<?php
namespace Bat\Sales\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

/**
 * @class AddOrderConsent
 * Add order consent to sales order
 */
class SalesOrderPlaceBefore implements ObserverInterface
{
    /**
     * Set order consent status
     *
     * @param EventObserver $observer
     * @return $this|void
     */
    public function execute(EventObserver $observer)
    {
        $order = $observer->getOrder();
        $order->setData('order_type', __('Sales Order'));
        return $this;
    }
}
