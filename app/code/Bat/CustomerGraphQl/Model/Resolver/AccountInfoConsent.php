<?php
namespace Bat\CustomerGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\CustomerGraphQl\Model\Customer\GetCustomer;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\ResourceModel\CustomerFactory;

class AccountInfoConsent implements ResolverInterface
{

    /**
     * @var GetCustomer
     */
    private $getCustomer;

    /**
     * @var Customer
     */
    private $customer;

    /**
     * @var CustomerFactory
     */
    private $customerFactory;

    /**
     * Construct method
     *
     * @param GetCustomer $getCustomer
     * @param Customer $customer
     * @param CustomerFactory $customerFactory
     */
    public function __construct(
        GetCustomer $getCustomer,
        Customer $customer,
        CustomerFactory $customerFactory
    ) {
        $this->getCustomer = $getCustomer;
        $this->customer = $customer;
        $this->customerFactory = $customerFactory;
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
        $customerId = $customer->getId();
        if (!isset($args['input']['consent_identifier'])) {
            throw new GraphQlInputException(__('Consent Identifier value should be specified'));
        } else {
            $customer = $this->customer->load($customerId);
            $customerData = $customer->getDataModel();
            $customerData->setCustomAttribute('marketingconsent', $args['input']['consent_identifier']);
            $customer->updateData($customerData);
            $customerResource = $this->customerFactory->create();
            $customerResource->saveAttribute($customer, 'marketingconsent');
            $success = true;
            $message = __('Your account has been successfully updated.');
        }
        $result = ['success' => $success, 'message' => $message];
        return $result;
    }
}
