<?php
namespace Bat\CustomerGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\CustomerGraphQl\Model\Customer\GetCustomer;
use Magento\Sales\Model\OrderFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\CustomerBalance\Model\BalanceFactory;
use Bat\CustomerBalanceGraphQl\Helper\Data;
use Bat\CustomerGraphQl\Model\Resolver\DataProvider\GetNextOrderDate;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactoryInterface;

class PaymentOverdue implements ResolverInterface
{

    /**
     * @var GetCustomer
     */
    private $getCustomer;

    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @var ScopeConfigInterface
     */
    private $_scopeConfig;

    /**
     * @var BalanceFactory
     */
    private $balanceFactory;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var CollectionFactoryInterface
     */
    private $orderCollectionFactory;

    /**
     * @var GetNextOrderDate
     */
    private $getNextOrderDate;

    /**
     * Construct method
     *
     * @param GetCustomer $getCustomer
     * @param OrderFactory $orderFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param BalanceFactory $balanceFactory
     * @param Data $helper
     * @param CollectionFactoryInterface $orderCollectionFactory
     * @param GetNextOrderDate $getNextOrderDate
     */
    public function __construct(
        GetCustomer $getCustomer,
        OrderFactory $orderFactory,
        ScopeConfigInterface $scopeConfig,
        BalanceFactory $balanceFactory,
        Data $helper,
        CollectionFactoryInterface $orderCollectionFactory,
        GetNextOrderDate $getNextOrderDate
    ) {
        $this->getCustomer = $getCustomer;
        $this->orderFactory = $orderFactory;
        $this->_scopeConfig = $scopeConfig;
        $this->balanceFactory = $balanceFactory;
        $this->helper = $helper;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->getNextOrderDate = $getNextOrderDate;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(
                __(
                    'The current customer isn\'t authorized.try agin with authorization token'
                )
            );
        }
        $store = $context->getExtensionAttributes()->getStore();
        $customer = $this->getCustomer->execute($context);
        $customerType = 0;
        $message = __('No overdue');
        $status = true;
        if ($customer->getCustomAttribute('is_credit_customer') != '') {
            $customerType = $customer->getCustomAttribute('is_credit_customer')->getValue();
        }
        $customerId = $customer->getId();
        $nextOrderdate = $this->getNextOrderDate->getNextOrderDate($customer);
        $dueAmount = 0;
        $order = $this->orderFactory->create()
            ->getCollection()
            ->addFieldToFilter('customer_id', $customerId)
            ->setOrder('created_at', 'DESC')->getFirstItem();
        if($order->getStatus() != 'canceled' && !empty($order->getData())) {
            if ($order->getStatus() == 'pending' || $order->getTotalDue() > 0) {
                /* Overdue Message */
                $message = $this->_scopeConfig->getValue("bat_customer/registration/payment_overdue_message");
                $dueAmount = number_format($order->getGrandTotal(), 0, '.', '');
                $status = false;
            }
        }

        $overpaymentvalue = $this->balanceFactory->create()
            ->setCustomerId($customerId)
            ->setWebsiteId($store->getWebsiteId())
            ->loadByCustomer()
            ->getAmount();

        $minimumAmount = 0;
        if ($customerType == 1) {
            $remainingLimit = $this->getTotalRemainingArLimit($customerId);
            $orders = $this->getOrderCollectionByCustomerId($customerId);
            $subTotal = 0;
            foreach ($orders as $order) {
                $orderSubtotal = $order->getGrandTotal();
                $subTotal = $orderSubtotal + $subTotal;
            }
            $totalValue = $overpaymentvalue + $remainingLimit;
            if ($remainingLimit == 0 && $overpaymentvalue == 0) {
                $minimumAmount = $subTotal;
            }
            if ($subTotal != 0 && $subTotal > $totalValue) {
                $minimumAmount = $subTotal - $totalValue;
            } elseif ($remainingLimit < $subTotal) {
                $minimumAmount = $subTotal - $remainingLimit;
            }
        }
        $result = [
            'customer_id' => $customerId,
            'status' => $status,
            'message' => $message,
            'due_amount' => $dueAmount,
            'total_overpayment' => $overpaymentvalue,
            'minimum_payment' => $minimumAmount,
            'next_order_date' => $nextOrderdate
        ];
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getTotalRemainingArLimit($customerId)
    {
        $totalArLimit = $this->helper->getTotalArLimit($customerId);
        $totalDue = 0;
        $order = $this->helper->getCustomerOrder($customerId);
        foreach ($order as $orderItem) {
            $totalDue = $totalDue + $orderItem['total_due'];
        }
        if ($totalDue == 0) {
            return $totalArLimit;
        } else {
            $remainingArLimit = $totalArLimit - $totalDue;
            return $remainingArLimit;
        }
    }

    /**
     * Getting Order Collection Function

     * @param string $customerId
     * Getting all the Processing and Pending orders for customer
     */
    public function getOrderCollectionByCustomerId($customerId)
    {
        $orderCollection = $this->orderCollectionFactory->create();
        $orderCollection->addFieldToSelect('*');
        $orderCollection->addAttributeToFilter('customer_id', $customerId);
        $orderCollection->addAttributeToFilter('status', ['in' => ['processing', 'pending']]);

        return $orderCollection;
    }
}
