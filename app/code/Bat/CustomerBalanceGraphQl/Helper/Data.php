<?php
namespace Bat\CustomerBalanceGraphQl\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;

class Data extends AbstractHelper
{
    /**
     * @var CollectionFactory
     */
    private $orderCollectionFactory;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepositoryInterface;

    /**
     * @param CollectionFactory           $orderCollectionFactory
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     */
    public function __construct(
        CollectionFactory $orderCollectionFactory,
        CustomerRepositoryInterface $customerRepositoryInterface
    ) {
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
    }

    /**
     * Get Customer order
     *
     * @param  int $customerId
     * @return array
     */
    public function getCustomerOrder($customerId)
    {
        $customerOrder = $this->orderCollectionFactory->create()
            ->addFieldToFilter('customer_id', $customerId)
            ->addFieldToFilter('status', ['nin' => ['canceled']]);
        return $customerOrder->getData();
    }

    /**
     * Get Customer Remaining AR limit
     *
     * @param  int $customerId
     * @return float|int
     */
    public function getRemainingArLimit($customerId)
    {
        $remainingArLimit = 0;
        if ($this->isCreditCustomer($customerId)) {
            $totalArLimit = $this->getTotalArLimit($customerId);
            $totalDue = 0;
            $order = $this->getCustomerOrder($customerId);
            foreach ($order as $orderItem) {
                $totalDue = $totalDue + $orderItem['total_due'];
            }
            if ($totalDue == 0) {
                return $totalArLimit;
            } elseif ($totalDue > $totalArLimit) {
                $remainingArLimit = $totalDue - $totalArLimit;
            } else {
                $remainingArLimit = $totalArLimit - $totalDue;
                return $remainingArLimit;
            }
        }
        return $remainingArLimit;
    }

    /**
     * Get Customer Total AR limit
     *
     * @param  int $customerId
     * @return float|int
     */
    public function getTotalArLimit($customerId)
    {
        $customer = $this->customerRepositoryInterface->getById($customerId);
        $customerCustomAttributes = $customer->getCustomAttributes();
        $totalArLimit = 0;
        if (isset($customerCustomAttributes['total_ar_limit'])) {
            $totalArLimit = $customerCustomAttributes['total_ar_limit'];
            if ($totalArLimit->getAttributecode() == "total_ar_limit") {
                if ($totalArLimit->getValue()) {
                    $totalArLimit = $totalArLimit->getValue();
                }
            }
        }
        return $totalArLimit;
    }

    /**
     * Get Is Customer First Order
     *
     * @param int $customerId
     * @param int|null $orderId
     * @return boolean
     */
    public function getIsCustomerFirstOrder($customerId, $orderId = null)
    {
        $customerOrder = $this->orderCollectionFactory->create()
            ->addFieldToFilter('customer_id', $customerId)
            ->addFieldToFilter('status', ['nin' => ['canceled']]);
        if ($orderId != null) {
            $customerOrder->addFieldToFilter('entity_id', ['nin' => [$orderId]]);
        }
        return ($customerOrder->getSize() > 0) ? false : true;
    }

    /**
     * Get is credit customer
     *
     * @param int $customerId
     * @return boolean
     */
    public function isCreditCustomer($customerId)
    {
        $customer = $this->customerRepositoryInterface->getById($customerId);
        $customerCustomAttributes = $customer->getCustomAttributes();
        if (isset($customerCustomAttributes['is_credit_customer'])) {
            $isCreditCustomer = $customerCustomAttributes['is_credit_customer'];
            if ($isCreditCustomer->getAttributecode() == "is_credit_customer"
            && !empty($isCreditCustomer->getValue())) {
                return true;
            }
        }
        return false;
    }
}
