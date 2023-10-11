<?php
namespace Bat\CustomerGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\CustomerGraphQl\Model\Customer\GetCustomer;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;

class ParentOutletData implements ResolverInterface
{
    /**
     * @var GetCustomer
     */
    private $getCustomer;

    /**
     * @var TimezoneInterface
     */
    private $timezoneInterface;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * Construct method
     *
     * @param GetCustomer $getCustomer
     * @param TimezoneInterface $timezoneInterface
     * @param CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        GetCustomer $getCustomer,
        TimezoneInterface $timezoneInterface,
        CustomerRepositoryInterface $customerRepository
    ) {
        $this->getCustomer = $getCustomer;
        $this->timezoneInterface = $timezoneInterface;
        $this->customerRepository = $customerRepository;
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
        $customer = $this->getCustomer->execute($context);
        $customerDetatils = $this->customerRepository->getById($customer->getId());

        $parentId = '';
        if (!empty($customerDetatils->getCustomAttribute('is_parent'))) {
            $parentId = $customerDetatils->getCustomAttribute('is_parent')->getValue();
        }

        if ($parentId == 1) {
            return true;
        } else {
            return false;
        }
    }
}
