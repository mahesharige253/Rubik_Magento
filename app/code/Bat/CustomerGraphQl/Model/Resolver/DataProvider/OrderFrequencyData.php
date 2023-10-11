<?php

namespace Bat\CustomerGraphQl\Model\Resolver\DataProvider;

use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Stdlib\DateTime\Timezone\LocalizedDateToUtcConverterInterface;
use Magento\SalesGraphQl\Model\Order\OrderAddress;
use Bat\CustomerGraphQl\Helper\Data;
use Magento\CustomerGraphQl\Model\Customer\GetCustomer;

class OrderFrequencyData
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    private $_session;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    private $_orderFactory;

    /**
     * @var TimezoneInterface
     */
    private $timezoneInterface;

    /**
     * @var LocalizedDateToUtcConverterInterface
     */
    private $utcConverter;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var GetCustomer
     */
    private $getCustomer;

    /**
     * @var
     */
    private $orders;

    /**
     * Construct method
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Customer\Model\Session $session
     * @param TimezoneInterface $timezoneInterface
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderFactory
     * @param LocalizedDateToUtcConverterInterface $utcConverter
     * @param Data $helper
     * @param GetCustomer $getCustomer
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Customer\Model\Session $session,
        TimezoneInterface $timezoneInterface,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderFactory,
        LocalizedDateToUtcConverterInterface $utcConverter,
        Data $helper,
        GetCustomer $getCustomer
    ) {
        $this->_session = $session;
        $this->_orderFactory = $orderFactory;
        $this->timezoneInterface = $timezoneInterface;
        $this->utcConverter = $utcConverter;
        $this->helper = $helper;
        $this->getCustomer = $getCustomer;
    }

    /**
     * Get Weekly Order data

     * @return array
     * @throws NoSuchEntityException
     * @throws GraphQlNoSuchEntityException
     */
    public function getWeekly()
    {
        $customerId = $this->_session->getCustomer()->getId();
        $firstday = date('Y-m-d', strtotime("previous friday"));
        $lastDay = date("Y-m-d", strtotime("this friday"));
        $startDate = date("Y-m-d H:i:s", strtotime($firstday . ' 17:00:00'));
        $endDate = date("Y-m-d H:i:s", strtotime($lastDay . ' 17:00:00'));

        $finalstart = $this->timezoneInterface->convertConfigTimeToUtc($startDate);
        $finalend = $this->timezoneInterface->convertConfigTimeToUtc($endDate);

        $this->orders = $this->_orderFactory->create(
        )->addFieldToFilter(
            'customer_id',
            $customerId
        )->addFieldToFilter('created_at', ['gteq' => $finalstart])->addFieldToFilter(
            'created_at',
            ['lteq' => $finalend]
        )->setOrder(
            'created_at',
            'desc'
        );
        return $this->orders;
    }

    /**
     * Get Monthly Order data

     * @return array
     * @throws NoSuchEntityException
     * @throws GraphQlNoSuchEntityException
     */
    public function getMonthly()
    {

        $customerId = $this->_session->getCustomer()->getId();
        $currentDate = $this->timezoneInterface->date()->format('Y-m-d');
        $firstDayMonth = date('Y-m-d', strtotime('last day of previous month'));
        $lastDayMonth = date("Y-m-t", strtotime($currentDate));
        $startDate = date("Y-m-d H:i:s", strtotime($firstDayMonth . ' 17:00:00'));
        $endDate = date("Y-m-d H:i:s", strtotime($lastDayMonth . ' 17:00:00'));
        $finalstart = $this->timezoneInterface->convertConfigTimeToUtc($startDate);
        $finalend = $this->timezoneInterface->convertConfigTimeToUtc($endDate);

        $now = new \DateTime();
        $this->orders = $this->_orderFactory->create(
        )->addFieldToFilter(
            'customer_id',
            $customerId
        )->addFieldToFilter('created_at', ['gteq' => $finalstart])->addFieldToFilter(
            'created_at',
            ['lteq' => $finalend]
        )->setOrder(
            'created_at',
            'desc'
        );
        return $this->orders;
    }

    /**
     * Get Bi-Weekly Order data

     * @return array
     * @throws NoSuchEntityException
     * @throws GraphQlNoSuchEntityException
     */
    public function getBiWeekly()
    {

        $customerId = $this->_session->getCustomer()->getId();
        $currentDate = $this->timezoneInterface->date()->format('Y-m-d');
        $firstDay = date("Y-m-d", strtotime("previous friday"));
        $next_week = strtotime('next week');
        $lastDay = date("Y-m-d", strtotime('friday', $next_week));
        $startDate = date("Y-m-d H:i:s", strtotime($firstDay . ' 17:00:00'));
        $endDate = date("Y-m-d H:i:s", strtotime($lastDay . ' 17:00:00'));
        $finalstart = $this->timezoneInterface->convertConfigTimeToUtc($startDate);
        $finalend = $this->timezoneInterface->convertConfigTimeToUtc($endDate);

        $now = new \DateTime();
        $this->orders = $this->_orderFactory->create(
        )->addFieldToFilter(
            'customer_id',
            $customerId
        )->addFieldToFilter('created_at', ['gteq' => $finalstart])->addFieldToFilter(
            'created_at',
            ['lteq' => $finalend]
        )->setOrder(
            'created_at',
            'desc'
        );
        return $this->orders;
    }

    /**
     * Get order Frequency function

     * @param string $customer
     */
    public function getOrderFrequency($customer)
    {
        $cname = $customer->getFirstName() . " " . $customer->getLastName();
        $cid = $customer->getId();
        $err1 = 'Your can\'t create a new order because your order frequency has exceeded.';
        $err = 'For additional order contact Customer Care';
        $error = $err1 . $err;
        $orderFrequency = $customer->getCustomAttribute('bat_order_frequency')->getValue();
        if ($orderFrequency == 0) {
            $orderPlaced = count($this->getWeekly());
            $totalOrder = $this->helper->getFrequencyWeekly();
            if ($orderPlaced >= $totalOrder) {
                return [
                    'message' => $error,
                    'success' => false
                ];
            } else {
                $status = "You can place the order";
                return [
                    'cust_id' => $cid,
                    'cust_name' => $cname,
                    'order_placed' => $orderPlaced,
                    'order_frequency' => $orderFrequency,
                    'total_order' => $totalOrder,
                    'message' => $status,
                    'success' => true
                ];
            }
        } elseif ($orderFrequency == 1) {
            $orderPlaced = count($this->getBiWeekly());
            $totalOrder = $this->helper->getFrequencyBiWeekly();
            if ($orderPlaced >= $totalOrder) {
                return [
                    'message' => $error,
                    'success' => false
                ];
            } else {
                $status = "You can place the order";
                return [
                    'cust_id' => $cid,
                    'cust_name' => $cname,
                    'order_placed' => $orderPlaced,
                    'order_frequency' => $orderFrequency,
                    'total_order' => $totalOrder,
                    'message' => $status,
                    'success' => true
                ];
            }
        } elseif ($orderFrequency == 2) {
            $orderPlaced = count($this->getMonthly());
            $totalOrder = $this->helper->getFrequencyMonthly();
            if ($orderPlaced >= $totalOrder) {
                return [
                    'message' => $error,
                    'success' => false
                ];
            } else {
                $status = "You can place the order";
                return [
                    'cust_id' => $cid,
                    'cust_name' => $cname,
                    'order_placed' => $orderPlaced,
                    'order_frequency' => $orderFrequency,
                    'total_order' => $totalOrder,
                    'message' => $status,
                    'success' => true
                ];
            }
        }
    }
}
