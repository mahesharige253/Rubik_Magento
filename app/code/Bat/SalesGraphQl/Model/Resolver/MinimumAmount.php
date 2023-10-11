<?php
declare(strict_types=1);

namespace Bat\SalesGraphQl\Model\Resolver;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Bat\CustomerBalanceGraphQl\Helper\Data;

class MinimumAmount implements ResolverInterface
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * @param Data $helper
     */
    public function __construct(
        Data $helper
    ) {
        $this->helper = $helper;
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
        $customerId = $context->getUserId();
        if (empty($customerId)) {
            throw new GraphQlAuthorizationException(__('Please specify a valid customer'));
        }
        
        $minimumAmount = 0;
        $customerOverPayment = 0;
        $remainingLimit = 0;
        if ($this->helper->isCreditCustomer($customerId)) {
            $order = $value['model'];
            $customerOverPayment = $order->getCustomerBalanceAmount();
            $subTotal = $order->getBaseSubtotal();
            $remainingLimit = $this->helper->getRemainingArLimit($customerId);
            $minimumAmount = $subTotal - ($customerOverPayment + $remainingLimit);
            if ($minimumAmount <= 0) {
                $minimumAmount = 0;
            }
            return $minimumAmount;
        } else {
            return $minimumAmount;
        }
    }
}
