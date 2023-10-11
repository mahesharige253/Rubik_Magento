<?php
namespace Bat\CustomerBalance\Model\Api;

use Bat\CustomerBalance\Api\CustomerCreditUpdateInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\CustomerBalance\Model\BalanceFactory;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use Bat\Customer\Helper\Data as CustomerHelper;

/**
 * @class CustomerCreditUpdate
 * Update Customer credit details
 */
class CustomerCreditUpdate implements CustomerCreditUpdateInterface
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
     * @var BalanceFactory
     */
    private BalanceFactory $_balanceFactory;

    /**
     * @param CustomerRepositoryInterface $customerRepository
     * @param LoggerInterface $logger
     * @param CustomerHelper $customerHelper
     * @param BalanceFactory $balanceFactory
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        LoggerInterface $logger,
        CustomerHelper $customerHelper,
        BalanceFactory $balanceFactory
    ) {
        $this->customerRepository = $customerRepository;
        $this->logger = $logger;
        $this->customerHelper = $customerHelper;
        $this->_balanceFactory = $balanceFactory;
    }

    /**
     * Update Customer credit
     *
     * @param string $sapOutletCode
     * @param string $outletId
     * @param float $creditLimit
     * @param float $availableCreditLimit
     * @param int $creditExposure
     * @param string $overdueFlag
     * @param float $overdueAmount
     * @return array
     */
    public function updateCustomerCredit(
        $sapOutletCode,
        $outletId,
        $creditLimit,
        $availableCreditLimit,
        $creditExposure,
        $overdueFlag,
        $overdueAmount
    ) {
        $result = [];
        try {
            $this->logCreditUpdateRequest('======================================================');
            $this->logCreditUpdateRequest('Request : ');
            $request = [
                'sap_outlet_code' => $sapOutletCode,
                'outlet_id'=>$outletId,
                'credit_limit'=>$creditLimit,
                'available_credit_limit'=>$availableCreditLimit,
                'overdue_flag'=>$overdueFlag,
                'overdue_amount'=>$overdueAmount,
                'credit_exposure'=>$creditExposure
            ];
            $this->logCreditUpdateRequest(json_encode($request));
            $this->logCreditUpdateRequest('Response : ');
            $this->validateInput(
                $sapOutletCode,
                $outletId,
                $creditLimit,
                $availableCreditLimit,
                $creditExposure,
                $overdueFlag,
                $overdueAmount
            );
            $customer = $this->customerHelper->isOutletIdValidCustomer($outletId);
            if ($customer) {
                $customer = $this->customerRepository->getById($customer->getId());
                $isOverDue = 0;
                if ($overdueFlag == 'Y') {
                    $isOverDue = 1;
                }
                $balance = $this->_balanceFactory->create()->setCustomer($customer)
                    ->setWebsiteId($customer->getWebsiteId())
                    ->setAmountDelta($availableCreditLimit)->setUpdatedActionAdditionalInfo('Updated by EDA API');
                $balance = $balance->loadbyCustomer();
                $balance->setOverdueAmount($overdueAmount)
                    ->setOverdueFlag($isOverDue)
                    ->setIsCreditCustomer($creditExposure);
                $balance->save();
                $result[] = ['success' => true, 'message'=>'Customer credit updated successfully'];
            } else {
                throw new LocalizedException(__('Customer not found'));
            }
        } catch (\Exception $e) {
            $result[] = ['success' => false, 'message'=>$e->getMessage()];
        }
        $this->logCreditUpdateRequest(json_encode($result));
        return $result;
    }

    /**
     * Validate customer credit update input
     *
     * @param string $sapOutletCode
     * @param string $outletId
     * @param float $creditLimit
     * @param float $availableCreditLimit
     * @param int $creditExposure
     * @param string $overdueFlag
     * @param float $overdueAmount
     * @throws LocalizedException
     */
    public function validateInput(
        $sapOutletCode,
        $outletId,
        $creditLimit,
        $availableCreditLimit,
        $creditExposure,
        $overdueFlag,
        $overdueAmount
    ) {
        if (trim($outletId) == '') {
            throw new LocalizedException(__('outlet_id is required to update customer'));
        }
        if ($creditLimit == '') {
            throw new LocalizedException(__('credit_limit is required to update customer'));
        }
        $this->checkPositiveNumber('credit_limit', $creditLimit);
        if ($availableCreditLimit == '') {
            throw new LocalizedException(__('available_credit_limit is required to update customer'));
        }
        $this->checkPositiveNumber('available_credit_limit', $availableCreditLimit);
        if ($creditExposure == '') {
            throw new LocalizedException(__('credit_exposure is required to update customer'));
        } elseif ($creditExposure !== 1 && $creditExposure !== 0) {
            throw new LocalizedException(__('Specify 0 or 1 for credit_exposure.'));
        }
        if ($overdueFlag == '') {
            throw new LocalizedException(__('overdue_flag is required to update customer'));
        } elseif ($overdueFlag !== 'Y' && $overdueFlag !== 'N') {
            throw new LocalizedException(__('Specify Y or N for overdue_flag.'));
        }
        if ($overdueAmount == '') {
            throw new LocalizedException(__('overdue_amount is required to update customer'));
        }
        $this->checkPositiveNumber('overdue_amount', $overdueAmount);
    }

    /**
     * Check for positive number
     *
     * @param string $type
     * @param float $value
     * @throws LocalizedException
     */
    public function checkPositiveNumber($type, $value)
    {
        if ($value < 0) {
            throw new LocalizedException(__('%1 should be a positive number', $type));
        }
    }

    /**
     * Credit update Log
     *
     * @param string $message
     * @throws Zend_Log_Exception
     */
    public function logCreditUpdateRequest($message)
    {
        $writer = new \Zend_Log_Writer_Stream(BP .'/var/log/EdaCreditUpdate.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info($message);
    }
}
