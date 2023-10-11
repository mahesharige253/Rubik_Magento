<?php
namespace Bat\OrderProducts\Helper;

use Magento\Framework\App\Helper\Context;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    
    /**
     * Server key config path
     */
    public const PAYMENT_DEADLINE_IN_DAYS= 'payment_deadline/general/payment_deadline';
    
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
     * Get Payment deadline days
     *
     * @return int
     */
    public function getPaymentDeadline()
    {
        return $this->getConfig(self::PAYMENT_DEADLINE_IN_DAYS);
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
