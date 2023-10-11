<?php
namespace Bat\QuoteGraphQl\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

/**
 * @class AddOrderConsent
 * Add order consent to sales order
 */
class AddOrderConsent implements ObserverInterface
{
    /**
     * Set order consent status
     *
     * @param EventObserver $observer
     * @return $this|void
     */
    public function execute(EventObserver $observer)
    {
        $quote = $observer->getQuote();
        $order = $observer->getOrder();
        $order->setData('order_consent', $quote->getOrderConsent());
        return $this;
    }
}
