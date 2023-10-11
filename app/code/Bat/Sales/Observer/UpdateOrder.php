<?php
namespace Bat\Sales\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Bat\Sales\Model\EdaOrdersFactory;
use Bat\Sales\Model\ResourceModel\EdaOrdersResource;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Psr\Log\LoggerInterface;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Framework\DB\Transaction;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order;

/**
 * @class UpdateOrder
 * Save order placed details to eda pending orders table
 */
class UpdateOrder implements ObserverInterface
{
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var EdaOrdersFactory
     */
    private EdaOrdersFactory $edaOrdersFactory;

    /**
     * @var EdaOrdersResource
     */
    private EdaOrdersResource $edaOrdersResource;

    /**
     * @var InvoiceService
     */
    private InvoiceService $invoiceService;

    /**
     * @var Transaction
     */
    private Transaction $transaction;

    /**
     * @var InvoiceSender
     */
    private InvoiceSender $invoiceSender;

    /**
     * @param LoggerInterface $logger
     * @param EdaOrdersFactory $edaOrdersFactory
     * @param EdaOrdersResource $edaOrdersResource
     * @param InvoiceService $invoiceService
     * @param InvoiceSender $invoiceSender
     * @param Transaction $transaction
     */
    public function __construct(
        LoggerInterface $logger,
        EdaOrdersFactory $edaOrdersFactory,
        EdaOrdersResource $edaOrdersResource,
        InvoiceService $invoiceService,
        InvoiceSender $invoiceSender,
        Transaction $transaction
    ) {
        $this->logger = $logger;
        $this->edaOrdersFactory = $edaOrdersFactory;
        $this->edaOrdersResource = $edaOrdersResource;
        $this->invoiceService = $invoiceService;
        $this->transaction = $transaction;
        $this->invoiceSender = $invoiceSender;
    }

    /**
     * Save order placed details to eda pending orders table
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        /** @var OrderInterface $order */
        $order = $observer->getEvent()->getOrder();
        try {
            if ($order->getGrandTotal() == 0) {
                $this->createInvoice($order);
            }
            $edaPendingOrder = $this->edaOrdersFactory->create();
            $edaPendingOrder->setData(['order_id' => $order->getId(),'order_type' => 'ZOR']);
            $this->edaOrdersResource->save($edaPendingOrder);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    /**
     * Generate Invoice
     *
     * @param OrderInterface $order
     */
    public function createInvoice($order)
    {
        try {
            if ($order->canInvoice()) {
                $invoice = $this->invoiceService->prepareInvoice($order);
                $invoice->register();
                $invoice->save();
                $transactionSave = $this->transaction->addObject($invoice)->addObject($invoice->getOrder());
                $transactionSave->save();
                $this->invoiceSender->send($invoice);
                $order->addCommentToStatusHistory(
                    __('Notified customer about invoice generated')
                )->setIsCustomerNotified(true)->save();
                $orderState = Order::STATE_PROCESSING;
                $order->setState($orderState)->setStatus($orderState);
                $order->save();

            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
