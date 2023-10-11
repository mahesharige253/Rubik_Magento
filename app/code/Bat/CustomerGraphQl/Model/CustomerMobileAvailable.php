<?php
namespace Bat\CustomerGraphQl\Model;

use Bat\Customer\Helper\Data;
use Magento\Framework\App\Config\ScopeConfigInterface;

class CustomerMobileAvailable
{
    public const XML_PATH_MOBILE_NUMBER_AVAILABLE_MESSAGE = "bat_customer/registration/mobile_number_available_message";

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @param Data $helper
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Data $helper,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->helper = $helper;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Validate if mobile number is already registered or not.
     *
     * @param string $mobileNumber
     * @return array
     */
    public function isMobileAvailable($mobileNumber)
    {
        $customers = $this->helper->getCustomer("mobilenumber", $mobileNumber);
        $message = ($customers->getSize() > 0) ? $this->getMessage() : '';

        return [
            'is_mobile_available' => true,
            'message' => $message
        ];
    }

    /**
     * Get store config message for mobile number already associated to another outlet.
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_MOBILE_NUMBER_AVAILABLE_MESSAGE);
    }
}
