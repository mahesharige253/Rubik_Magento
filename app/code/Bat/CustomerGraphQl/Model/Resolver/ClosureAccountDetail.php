<?php
namespace Bat\CustomerGraphQl\Model\Resolver;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\CustomerGraphQl\Model\Customer\GetCustomer;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Eav\Model\Config;
use Magento\Customer\Model\Customer;

class ClosureAccountDetail implements ResolverInterface
{
    /**
     * @var GetCustomer
     */
    private $getCustomer;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var Session
     */
    private $eavConfig;

    /**
     * @param GetCustomer $getCustomer
     * @param ScopeConfigInterface $scopeConfig
     * @param Config $eavConfig
     */
    public function __construct(
        GetCustomer $getCustomer,
        ScopeConfigInterface $scopeConfig,
        Config $eavConfig
    ) {
        $this->getCustomer = $getCustomer;
        $this->scopeConfig = $scopeConfig;
        $this->eavConfig = $eavConfig;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        /** @var ContextInterface $context */
        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(__('The current customer isn\'t authorized.'));
        }

        $customer = $this->getCustomer->execute($context);
        $customerId = $customer->getId();
        $accountDisclosureApprovalStatus = '';
        $accountDisclosureApprovalStatusLabel = '';
        $accountDisclosureRejectedReason = '';
        $accountDisclosureRejectedFields = '';
        $statusMessage = '';

        if ($customer->getCustomAttribute('disclosure_approval_status')) {
            $accountDisclosureApprovalStatus = $customer->getCustomAttribute('disclosure_approval_status')->getValue();
            if ($accountDisclosureApprovalStatus !='') {
                $statusLabel = $this->getCustomAttributeOptionLabel(
                    'disclosure_approval_status',
                    $accountDisclosureApprovalStatus
                );
                $accountDisclosureApprovalStatusLabel = $statusLabel->getText();
            }
        }
        if ($customer->getCustomAttribute('disclosure_rejected_fields')) {
            $accountDisclosureRejectedFields = $customer->getCustomAttribute('disclosure_rejected_fields')
            ->getValue();
        }
        if ($customer->getCustomAttribute('disclosure_rejected_reason')) {
            $accountDisclosureRejectedReason = $customer->getCustomAttribute('disclosure_rejected_reason')->getValue();
        }
        if ($accountDisclosureApprovalStatus == 2) {
            $statusMessage = $this->scopeConfig->
            getValue("bat_customer_disclosure/general/account_disclosure_approved_message");
        } elseif ($accountDisclosureApprovalStatus == 3) {
            $statusMessage = $this->scopeConfig->
            getValue("bat_customer_disclosure/general/account_disclosure_rejected_message");
        }
    
        $result = [
            'closure_status' => $accountDisclosureApprovalStatusLabel,
            'status_message' => $statusMessage,
            'rejected_fields' => $accountDisclosureRejectedFields,
            'rejected_reason' => $accountDisclosureRejectedReason
        ];
        return $result;
    }

    /**
     * Get attribute option label
     *
     * @param string $attributeCode
     * @param string $optionValue
     */
    public function getCustomAttributeOptionLabel($attributeCode, $optionValue)
    {
        $attribute = $this->eavConfig->getAttribute(Customer::ENTITY, $attributeCode);

        if ($attribute->usesSource()) {
            $options = $attribute->getSource()->getAllOptions();

            foreach ($options as $option) {
                if ($option['value'] == $optionValue) {
                    return $option['label'];
                }
            }
        }
        return false;
    }
}
