<?php
namespace Bat\CustomerGraphQl\Helper;

use Magento\Framework\App\Helper\Context;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * Server key config path
     */
    public const ORDER_FREQUENCY_WEEKLY = 'order_frequency/general/order_frequency_weekly';

    public const ORDER_FREQUENCY_BIWEEKLY = 'order_frequency/general/order_frequency_biweekly';

    public const ORDER_FREQUENCY_MONTHLY = 'order_frequency/general/order_frequency_monthly';

    /**
     * @var getScopeConfig
     */
    protected $scopeConfig;

    /**
     * Data Construct
     *
     * @param Context $context
     */
    public function __construct(
        Context $context
    ) {
        $this->scopeConfig = $context->getScopeConfig();
        parent::__construct($context);
    }

    /**
     * Get Order Frequency Weekly
     *
     * @return int
     */
    public function getFrequencyWeekly()
    {
        return $this->getConfig(self::ORDER_FREQUENCY_WEEKLY);
    }

    /**
     * Get Order Frequency BiWeekly
     *
     * @return int
     */
    public function getFrequencyBiWeekly()
    {
        return $this->getConfig(self::ORDER_FREQUENCY_BIWEEKLY);
    }

    /**
     * Get Order Frequency Monthly
     *
     * @return int
     */
    public function getFrequencyMonthly()
    {
        return $this->getConfig(self::ORDER_FREQUENCY_MONTHLY);
    }

    /**
     * Get Config path
     *
     * @param string $path
     * @return string|int
     */
    public function getConfig($path)
    {
        return $this->scopeConfig->getValue(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
}
