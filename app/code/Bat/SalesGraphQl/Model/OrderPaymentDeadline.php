<?php

namespace Bat\SalesGraphQl\Model;

use Magento\Sales\Model\OrderFactory;
use Magento\Framework\Model\AbstractModel;

class OrderPaymentDeadline extends AbstractModel
{
    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * @param OrderFactory $orderFactory
     */
    public function __construct(
        OrderFactory $orderFactory
    ) {
        $this->orderFactory = $orderFactory;
    }

    /**
     * Order Payment Deadline
     *
     * @param type $orderId
     * @return type
     */
    public function getPaymentDeadline($orderId)
    {
        $order = $this->orderFactory->create()->load($orderId);
        $orderCreatedAtDate = $order->getCreatedAt();
        $orderCreatedDate = date("Y-m-d", strtotime($orderCreatedAtDate));
        $orderCreatedTime = date("H:i:s", strtotime($orderCreatedAtDate));
        $orderCreatedDayNumber = $day = date('w', strtotime($orderCreatedAtDate));

        $beforeElevenPm = strtotime('23:00:00');
        $deadLineDate = '';
        $weekendDays = 0;
        if ($orderCreatedDayNumber == 6 || $orderCreatedDayNumber == 0) {// 0 means Sunday and 6 means Saturday
            $weekendDays = 2;
        }

        if ($beforeElevenPm >= strtotime($orderCreatedTime)) {
            $noOfDays = $weekendDays + 1;
            $addedDay = " +".$noOfDays. " days";

            $deadlineDate = date('Y/m/d, 11a', strtotime($orderCreatedAtDate. $addedDay));

        } else {
            $noOfDays = $weekendDays + 2;
            $addedDay = " +".$noOfDays. " days";
            $deadlineDate = date('Y/m/d, 11a', strtotime($orderCreatedAtDate. $addedDay));
        }
        return  $deadlineDate;
    }
}
