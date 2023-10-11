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
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\CustomerBalance\Model\BalanceFactory;
use Bat\CustomerBalanceGraphQl\Helper\Data;
use Magento\CustomerBalance\Api\BalanceManagementInterface;

class CreditCustomer implements ResolverInterface
{

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepositoryInterface;

    /**
     * @var BalanceFactory
     */
    private $balanceFactory;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var BalanceManagementInterface
     */
    private $balanceManagement;

    /**
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     * @param BalanceFactory              $balanceFactory
     * @param Data                        $helper
     * @param BalanceManagementInterface  $balanceManagement
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepositoryInterface,
        BalanceFactory $balanceFactory,
        Data $helper,
        BalanceManagementInterface $balanceManagement
    ) {
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->balanceFactory = $balanceFactory;
        $this->helper = $helper;
        $this->balanceManagement = $balanceManagement;
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
        $customerId = $context->getUserId();
        $totalArLimit = $this->helper->getTotalArLimit($customerId);
        $quote = $value['model'];
        $quoteId = $quote->getId();
        $overpaymentvalue = $this->balanceFactory->create()
            ->setCustomerId($customerId)
            ->setWebsiteId($store->getWebsiteId())
            ->loadByCustomer()
            ->getAmount();
        $remainingAr = $this->helper->getRemainingArLimit($customerId);
        $customer = $this->customerRepositoryInterface->getById($customerId);
        $customerCustomAttributes = $customer->getCustomAttributes();
        if ((count($quote->getAllItems()) > 0) && isset($customerCustomAttributes['is_credit_customer'])) {
            $isCreditCustomer = $customerCustomAttributes['is_credit_customer'];
            if ($isCreditCustomer->getAttributecode() == "is_credit_customer"
            && !empty($isCreditCustomer->getValue())) {
                    $minimumPayment = 0;
                if ($remainingAr) {
                    if (($remainingAr < $totalArLimit) && $remainingAr > $quote->getSubtotal()) {
                        return ['remaining_ar' => $remainingAr,
                            'overpayment' => $overpaymentvalue,
                            'minimum_payment' => $minimumPayment];
                    }
                    if (($remainingAr < $totalArLimit) && $remainingAr < $quote->getSubtotal()) {
                        $minimumPayment = $quote->getSubtotal() - $remainingAr;
                        return ['remaining_ar' => $remainingAr,
                            'overpayment' => $overpaymentvalue,
                            'minimum_payment' => $minimumPayment];
                    }
                    if (($remainingAr == $totalArLimit) && $remainingAr >= $quote->getSubtotal()) {
                        $this->balanceManagement->apply($quoteId);
                        return ['remaining_ar' => $remainingAr,
                            'overpayment' => $overpaymentvalue,
                            'minimum_payment' => $minimumPayment];
                    } elseif (($remainingAr == $totalArLimit) && $remainingAr < $quote->getSubtotal()) {
                        $this->balanceManagement->apply($quoteId);
                        if ($overpaymentvalue < $remainingAr) {
                            $totalRemaining = $remainingAr + $overpaymentvalue;
                            $minimumPayment = $quote->getSubtotal() - $totalRemaining;
                        }
                        return ['remaining_ar' => $remainingAr,
                            'overpayment' => $overpaymentvalue,
                            'minimum_payment' => $minimumPayment];
                    }
                }
                if (($remainingAr == 0) && $overpaymentvalue == 0) {
                    $minimumPayment = $quote->getSubtotal();
                    return ['remaining_ar' => $remainingAr,
                        'overpayment' => $overpaymentvalue,
                        'minimum_payment' => $minimumPayment];
                }
                    
            }
        }
        return ['remaining_ar' => 0,
               'overpayment' => 0,
               'minimum_payment' => 0];
    }
}
