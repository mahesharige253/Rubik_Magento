<?php
namespace Bat\Customer\Model\Api;

use Bat\Customer\Api\CustomerUpdateInterface;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use Bat\Customer\Helper\Data as CustomerHelper;

/**
 * @class CustomerUpdate
 * Update Customer
 */
class CustomerUpdate implements CustomerUpdateInterface
{
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var CustomerRepositoryInterface
     */
    private CustomerRepositoryInterface $customerRepository;

    /**
     * @var CustomerHelper
     */
    private CustomerHelper $customerHelper;

    /**
     * @param CustomerRepositoryInterface $customerRepository
     * @param LoggerInterface $logger
     * @param CustomerHelper $customerHelper
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        LoggerInterface $logger,
        CustomerHelper $customerHelper
    ) {
        $this->customerRepository = $customerRepository;
        $this->logger = $logger;
        $this->customerHelper = $customerHelper;
    }

    /**
     * Update Customer
     *
     * @param string $batchId
     * @param string $createdAt
     * @param string $countryCode
     * @param string $companyCode
     * @param string $outletCode
     * @param string $sapOutletCode
     * @return array
     */
    public function updateCustomer($batchId, $createdAt, $countryCode, $companyCode, $outletCode, $sapOutletCode)
    {
        $result = [];
        try {
            $this->validateInput($batchId, $createdAt, $countryCode, $companyCode, $outletCode, $sapOutletCode);
            $customer = $this->customerHelper->isOutletIdValidCustomer($outletCode);
            if ($customer) {
                $customer = $this->customerRepository->getById($customer->getId());
                $customer->setCustomAttribute('sap_outlet_code', $sapOutletCode);
                $customer->setCustomAttribute('bat_batch_id', $batchId);
                $customer->setCustomAttribute('bat_created_at', $createdAt);
                $customer->setCustomAttribute('bat_company_code', $companyCode);
                $customer->setCustomAttribute('bat_country_code', $countryCode);
                $this->customerRepository->save($customer);
                $result[] = ['success' => true, 'message'=>'Customer updated successfully'];
            } else {
                throw new LocalizedException(__('Customer not found'));
            }
        } catch (\Exception $e) {
            $result[] = ['success' => false, 'message'=>$e->getMessage()];
        }
        return $result;
    }

    /**
     * Validate customer update input
     *
     * @param string $batchId
     * @param string $createdAt
     * @param string $countryCode
     * @param string $companyCode
     * @param string $outletCode
     * @param string $sapOutletCode
     * @throws LocalizedException
     */
    public function validateInput($batchId, $createdAt, $countryCode, $companyCode, $outletCode, $sapOutletCode)
    {
        if (trim($outletCode) == '') {
            throw new LocalizedException(__('outlet_code is required to update customer'));
        }
        if (trim($sapOutletCode) == '') {
            throw new LocalizedException(__('sap_outlet_code is required to update customer'));
        }
        if (trim($createdAt) == '') {
            throw new LocalizedException(__('created_at is required to update customer'));
        }
        if (trim($companyCode) == '') {
            throw new LocalizedException(__('company_code is required to update customer'));
        }
        if (trim($countryCode) == '') {
            throw new LocalizedException(__('country_code is required to update customer'));
        }
        if (trim($batchId) == '') {
            throw new LocalizedException(__('batch_id is required to update customer'));
        }
    }
}
