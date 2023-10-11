<?php
declare(strict_types=1);

namespace Bat\SalesGraphQl\Model\Resolver;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Bat\VirtualBank\Helper\Data;

class VirtualBankAccount implements ResolverInterface
{
    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var Data
     */
    protected $virtualBankData;

    /**
     * @param CustomerRepositoryInterface $customerRepository
     * @param Data $virtualBankData
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        Data $virtualBankData
    ) {
        $this->customerRepository = $customerRepository;
        $this->virtualBankData = $virtualBankData;
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
        $customer = $this->customerRepository->getById($customerId);
        $customerName = $customer->getFirstName().' '.$customer->getLastName();
        $virtualAccountNumber =  $customer->getCustomAttribute('virtual_account')->getValue();
        
        $virtualBank =  $customer->getCustomAttribute('virtual_bank')->getValue();
        $bankName = $this->virtualBankData->getVirtualBankName($virtualBank);

        return ['bank_name' => $bankName,
                'account_number' => $virtualAccountNumber,
                'account_holder_name' => $customerName];
    }
}
