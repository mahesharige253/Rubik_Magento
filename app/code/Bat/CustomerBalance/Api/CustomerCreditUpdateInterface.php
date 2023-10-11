<?php
namespace Bat\CustomerBalance\Api;

interface CustomerCreditUpdateInterface
{
    /**
     * Update customer credit details
     *
     * @param string $sapOutletCode
     * @param string $outletId
     * @param float $creditLimit
     * @param float $availableCreditLimit
     * @param int $creditExposure
     * @param string $overdueFlag
     * @param float $overdueAmount
     * @return mixed
     */
    public function updateCustomerCredit(
        $sapOutletCode,
        $outletId,
        $creditLimit,
        $availableCreditLimit,
        $creditExposure,
        $overdueFlag,
        $overdueAmount
    );
}
