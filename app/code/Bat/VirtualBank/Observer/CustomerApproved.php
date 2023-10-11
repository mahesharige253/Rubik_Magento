<?php

namespace Bat\VirtualBank\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Psr\Log\LoggerInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\CustomerFactory;

/**
 * @class CustomerApproved
 * Send Customer details to swift+ and sap if approved
 */
class CustomerApproved implements ObserverInterface
{
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;
    /**
     * @var CustomerRepositoryInterface
     */
    private CustomerRepositoryInterface $customerRepositoryInterface;
    /**
     * @var CustomerFactory
     */
    private CustomerFactory $customerFactory;
    /**
     * @var ManagerInterface
     */
    private ManagerInterface $messageManager;

    /**
     * @param LoggerInterface $logger
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     * @param CustomerFactory $customerFactory
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        LoggerInterface $logger,
        CustomerRepositoryInterface $customerRepositoryInterface,
        CustomerFactory $customerFactory,
        ManagerInterface $messageManager
    ) {
        $this->logger = $logger;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->customerFactory = $customerFactory;
        $this->messageManager = $messageManager;
    }

    /**
     * Send details to integration on customer status approval
     *
     * @param Observer $observer
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Exception
     */
    public function execute(Observer $observer)
    {
        $approvalStatusPrepareSave = '';
        $approvalStatusBeforeSave = '';
        $customerPrepareSave = $observer->getCustomer();
        $customerAttributesPrepareSave = $customerPrepareSave->getCustomAttributes();
        $approvalStatusPrepareSave = $this->getCustomerApprovalStatus($customerAttributesPrepareSave);
        if ($approvalStatusPrepareSave == '1') {
            $customer = $this->customerRepositoryInterface->getById($customerPrepareSave->getId());
            $customerAttributes = $customer->getCustomAttributes();
            $approvalStatusBeforeSave = $this->getCustomerApprovalStatus($customerAttributes);
            if ($approvalStatusBeforeSave != '1') {
                $vbaInfo = $this->checkVbaAssignedToCustomer($customerAttributes);
                if (!empty($vbaInfo)) {
                    /**customer approved - send detail to swift+, sap **/
                    $status = $this->sendCustomerDetailsToIntegrations($customerPrepareSave);
                    /**customer approved - send detail to swift+, sap **/
                } else {
                    $msg = 'VBA should be assigned for customer Approval';
                    $this->messageManager->addErrorMessage($msg);
                    throw new LocalizedException(__($msg));
                }
            }
        }
    }

    /**
     * Return customer approval status
     *
     * @param array $customerAttributes
     * @return string
     * return customer approval status
     */
    public function getCustomerApprovalStatus($customerAttributes)
    {
        $approvalStatus = '';
        if (isset($customerAttributes['approval_status'])) {
            $approvalStatus = $customerAttributes['approval_status']->getvalue();
        }
        return $approvalStatus;
    }

    /**
     * Check Vba Assigned to customer
     *
     * @param array $customerAttributes
     * @return array
     * Check VBA assigned to customer
     */
    public function checkVbaAssignedToCustomer($customerAttributes)
    {
        $vbaInfo = [];
        if (isset($customerAttributes['virtual_bank']) && isset($customerAttributes['virtual_account'])) {
            $vbaInfo['virtual_bank'] = $customerAttributes['virtual_bank']->getvalue();
            $vbaInfo['virtual_account'] = $customerAttributes['virtual_account']->getvalue();
        }
        return $vbaInfo;
    }

    /**
     * Send customer details to swift+, sap
     *
     * @param Customer $customer
     * @return array
     * @throws \Exception
     */
    public function sendCustomerDetailsToIntegrations($customer)
    {
        $status = ['success'=>true,'msg'=>'Customer details sent successfully'];
        //customer approved - send detail to swift+, sap
        if ($status['success']) {
            $this->messageManager->addSuccessMessage($status['msg']);
        } else {
            $status['msg'] = 'Customer details not sent';
            $this->messageManager->addErrorMessage($status['msg']);
            throw new LocalizedException(__($status['msg']));
        }
        return $status;
    }
}
