<?php

namespace Bat\CustomerGraphQl\Model\Resolver\DataProvider;

class GetNextOrderDate
{
    /**
     * Get Next Order Date

     * @param string $customer
     */
    public function getNextOrderDate($customer)
    {
        $orderFrequency = $customer->getCustomAttribute('bat_order_frequency')->getValue();
        if ($orderFrequency == 0) {
            return date("Y-m-d", strtotime("next monday"));
        } elseif ($orderFrequency == 1) {
            return date("Y-m-d", strtotime("second monday"));
        } elseif ($orderFrequency == 2) {
            return date("Y-m-d", strtotime("first day of next month"));
        }
    }
}
