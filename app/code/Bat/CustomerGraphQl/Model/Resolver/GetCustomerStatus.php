<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\CustomerGraphQl\Model\Resolver;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\Collection as AttributeCollection;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Bat\Customer\Helper\Data;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;

/**
 * Customer Group Code field resolver
 */
class GetCustomerStatus implements ResolverInterface
{
    /**
     * @var CustomerFactory
     */
    private $customerFactory;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var AttributeCollection
     */
    private $attributeCollection;

    /**
     * @var ScopeConfigInterface
     */
    private $_scopeConfig;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @param CustomerFactory $customerFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param AttributeCollection $attributeCollection
     * @param ScopeConfigInterface $scopeConfig
     * @param Data $helper
     */
    public function __construct(
        CustomerFactory $customerFactory,
        CustomerRepositoryInterface $customerRepository,
        AttributeCollection $attributeCollection,
        ScopeConfigInterface $scopeConfig,
        Data $helper
    ) {
        $this->customerFactory = $customerFactory;
        $this->customerRepository = $customerRepository;
        $this->attributeCollection = $attributeCollection;
        $this->_scopeConfig = $scopeConfig;
        $this->helper = $helper;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (isset($args['mobilenumber']) && !preg_match("/010 ([0-9]{3}|[0-9]{4}) [0-9]{4}$/", $args['mobilenumber'])) {
            throw new GraphQlInputException(__('Mobile number value is not valid'));
        }
        $mobileNumber = $args['mobilenumber'];
        $customers = $this->helper->getCustomer("mobilenumber", $mobileNumber);

        $data = [];
        if ($customers->getSize() > 0) {
            $customer = $customers->getFirstItem();
            $customerId = $customer->getId();
            $customerDetatils = $this->customerRepository->getById($customerId);

            if (empty($customerDetatils->getCustomAttribute('approval_status'))
                            || $customerDetatils->getCustomAttribute('approval_status')->getValue() == 0
                            || $customerDetatils->getCustomAttribute('approval_status')->getValue() == 3) {
                $data['heading'] = $this->getCustomerUnderReviewHeading();
                $data['message'] = $this->getCustomerUnderReviewMessage();
            } elseif ($customerDetatils->getCustomAttribute('approval_status')->getValue() == 1) {
                $data['heading'] = $this->getCustomerApprovedHeading();
                $data['message'] = $this->getCustomerApprovedMessage();
            } elseif ($customerDetatils->getCustomAttribute('approval_status')->getValue() == 2) {
                $data['heading'] = $this->getCustomerRejectedHeading();
                $data['message'] = $this->getCustomerRejectedMessage();
                $data['rejected_fields'] = (!empty($customerDetatils->getCustomAttribute('rejected_fields'))) ?
                    $customerDetatils->getCustomAttribute('rejected_fields')->getValue() : '';
            }
        } else {
            $data['heading'] = $this->getCustomerNotFoundHeading();
            $data['message'] = $this->getCustomerNotFoundMessage();
        }

        $data['call_center_number'] = $this->getCustomerCallCenterNumber();

        return $data;
    }

    /**
     * GetCustomerNotFoundHeading
     */
    public function getCustomerNotFoundHeading()
    {
        $data = $this->_scopeConfig->getValue("customer_approval_status/general/customer_application_notfound_heading");
        return $data;
    }

    /**
     * GetCustomerNotFoundMessage
     */
    public function getCustomerNotFoundMessage()
    {
        $data = $this->_scopeConfig->getValue("customer_approval_status/general/customer_application_notfound_message");
        return $data;
    }

    /**
     * GetCustomerApprovedHeading
     */
    public function getCustomerApprovedHeading()
    {
        $data = $this->_scopeConfig->getValue("customer_approval_status/general/customer_application_approved_heading");
        return $data;
    }

    /**
     * GetCustomerApprovedMessage
     */
    public function getCustomerApprovedMessage()
    {
        $data = $this->_scopeConfig->getValue("customer_approval_status/general/customer_application_approved_message");
        return $data;
    }

    /**
     * GetCustomerRejectedHeading
     */
    public function getCustomerRejectedHeading()
    {
        $data = $this->_scopeConfig->getValue("customer_approval_status/general/customer_application_rejected_heading");
        return $data;
    }

    /**
     * GetCustomerRejectedMessage
     */
    public function getCustomerRejectedMessage()
    {
        $data = $this->_scopeConfig->getValue("customer_approval_status/general/customer_application_rejected_message");
        return $data;
    }

    /**
     * GetCustomerUnderReviewHeading
     */
    public function getCustomerUnderReviewHeading()
    {
        $data = $this->_scopeConfig->getValue("customer_approval_status/general/customer_under_review_heading");
        return $data;
    }

    /**
     * GetCustomerUnderReviewMessage
     */
    public function getCustomerUnderReviewMessage()
    {
        $data = $this->_scopeConfig->getValue("customer_approval_status/general/customer_under_review_message");
        return $data;
    }

    /**
     * GetCustomerCallCenterNumber
     */
    public function getCustomerCallCenterNumber()
    {
        $data = $this->_scopeConfig->getValue("customer_approval_status/general/customer_callcenter_number");
        return $data;
    }
}
