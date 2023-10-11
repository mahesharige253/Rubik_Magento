<?php
declare(strict_types=1);

namespace Bat\CustomerBalanceGraphQl\Model\Resolver;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\CustomerBalance\Model\BalanceFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Sales\Model\OrderFactory;

class NonCreditCustomer implements ResolverInterface
{
    /**
     * @var BalanceFactory
     */
    private $balanceFactory;

    /**
     * @var CustomerRepositoryInterface
     */
     private $customerRepositoryInterface;

    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @param BalanceFactory              $balanceFactory
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     * @param OrderFactory                $orderFactory
     */
    public function __construct(
        BalanceFactory $balanceFactory,
        CustomerRepositoryInterface $customerRepositoryInterface,
        OrderFactory $orderFactory
    ) {
        $this->balanceFactory = $balanceFactory;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->orderFactory = $orderFactory;
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
        $quote = $value['model'];
        $store = $context->getExtensionAttributes()->getStore();
        $customerId = $context->getUserId();
        if (empty($customerId)) {
            throw new GraphQlAuthorizationException(__('Please specify a valid customer'));
        }
        $customerBalance = $this->getStoreCreditBalance(
            $customerId,
            (int)$store->getWebsiteId(),
            (int)$store->getId()
        );
        $customer = $this->customerRepositoryInterface->getById($customerId);
        $customerCustomAttributes = $customer->getCustomAttributes();
        if (count($quote->getAllItems()) > 0) {
            if (isset($customerCustomAttributes['is_credit_customer'])) {
                $isCreditCustomer = $customerCustomAttributes['is_credit_customer'];
                if ($isCreditCustomer->getAttributecode() == "is_credit_customer") {
                    if (!$isCreditCustomer->getValue()) {
                        if ($this->getOverDue($customerId)) {
                            throw new GraphQlNoSuchEntityException(
                                __(
                                    'Overdue payment is pending'
                                )
                            );
                        }
                        if ($customerBalance) {
                            $quote->setUseCustomerBalance(true);
                            $quote->setCustomerBalanceInstance($customerBalance);
                            $quote->collectTotals();
                            $quote->save();
                            return ['overpayment' => $customerBalance];
                        }
                    }
                }
            }
        }
        return ['overpayment' => 0];
    }

    /**
     * Return store credit balance for customer
     *
     * @param  int $customerId
     * @param  int $websiteId
     * @return float
     * @throws LocalizedException
     */
    private function getStoreCreditBalance($customerId, int $websiteId): float
    {
        $baseBalance = $this->balanceFactory->create()
            ->setCustomerId($customerId)
            ->setWebsiteId($websiteId)
            ->loadByCustomer()
            ->getAmount();
        return $baseBalance;
    }

    /**
     * Get Over Due payment
     *
     * @param  int $customerId
     * @return float
     */
    public function getOverDue($customerId)
    {
        $totalDue = 0;
        $order = $this->orderFactory->create()
            ->getCollection()
            ->addFieldToFilter('customer_id', $customerId)
            ->setOrder('created_at', 'DESC')->getFirstItem();
        if ($order->getStatus() != 'canceled') {
            if ($order->getStatus() == 'pending' || $order->getTotalDue() > 0) {
                $totalDue = $order->getTotalDue();
            }
        }
        return $totalDue;
    }
}
