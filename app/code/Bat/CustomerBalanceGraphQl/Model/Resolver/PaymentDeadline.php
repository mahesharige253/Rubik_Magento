<?php
declare(strict_types=1);

namespace Bat\CustomerBalanceGraphQl\Model\Resolver;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Bat\OrderProducts\Helper\Data;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\CustomerGraphQl\Model\Customer\GetCustomer;

class PaymentDeadline implements ResolverInterface
{

    /**
     * @var Config
     */
    private $_eavConfig;

    /**
     * @var GetCustomer
     */
    private $getCustomer;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var DateTime
     */
    private $date;

    /**
     * @var TimezoneInterface
     */
    private $timezoneInterface;

    /**
     * Construct method
     *
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param GetCustomer $getCustomer
     * @param Data $helper
     * @param TimezoneInterface $timezoneInterface
     * @param DateTime $date
     */
    public function __construct(
        \Magento\Eav\Model\Config $eavConfig,
        GetCustomer $getCustomer,
        Data $helper,
        TimezoneInterface $timezoneInterface,
        DateTime $date
    ) {
        $this->_eavConfig = $eavConfig;
        $this->getCustomer = $getCustomer;
        $this->helper = $helper;
        $this->timezoneInterface = $timezoneInterface;
        $this->date = $date;
    }
    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(
                __(
                    'The current customer isn\'t authorized.try agin with authorization token'
                )
            );
        }
        $orderdate = $this->timezoneInterface->date()->format('Y-m-d');
        $deadline = $this->helper->getPaymentDeadline();
        $nextdate = $this->date->date('Y-m-d', strtotime($orderdate . " +" . $deadline . "days"));
        $paymentdeadline = date("Y-m-d h:i:s", strtotime($nextdate . ' 23:00:00'));
        return $paymentdeadline;
    }
}
