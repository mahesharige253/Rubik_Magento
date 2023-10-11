<?php
namespace Bat\Sales\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\HTTP\Client\Curl;

/**
 * @class Data
 * Helper Class for Eda Create/Update orders
 */
class Data extends AbstractHelper
{
    /**
     * @var CollectionFactory
     */
    private CollectionFactory $orderCollectionFactory;

    /**
     * @var CustomerRepositoryInterface
     */
    private CustomerRepositoryInterface $customerRepositoryInterface;

    /**
     * @var Curl
     */
    private Curl $curl;

    /**
     * @var TimezoneInterface
     */
    private TimezoneInterface $date;

    /**
     * @param CollectionFactory $orderCollectionFactory
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     * @param ScopeConfigInterface $scopeConfig
     * @param Curl $curl
     * @param TimezoneInterface $date
     */
    public function __construct(
        CollectionFactory $orderCollectionFactory,
        CustomerRepositoryInterface $customerRepositoryInterface,
        ScopeConfigInterface $scopeConfig,
        Curl $curl,
        TimezoneInterface $date
    ) {
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->scopeConfig = $scopeConfig;
        $this->curl = $curl;
        $this->date =  $date;
    }

    /**
     * Return API EndPoint for creating and updating orders in EDA
     *
     * @return mixed
     */
    public function getEdaCreateUpdateOrderEndpoint()
    {
        return $this->scopeConfig->getValue(
            'bat_integrations/bat_order/eda_order_endpoint'
        );
    }

    /**
     * Return API Authorization Key for creating and updating orders in EDA
     *
     * @return mixed
     */
    public function getApiSubscriptionKey()
    {
        return $this->scopeConfig->getValue(
            'bat_integrations/bat_subscription/eda_subscription_key'
        );
    }

    /**
     * Return Maximum failure attempts allowed
     *
     * @return mixed
     */
    public function getEdaCreateOrderMaxFailuresAllowed()
    {
        return $this->scopeConfig->getValue(
            'bat_integrations/bat_order/eda_order_max_failures_allowed'
        );
    }

    /**
     * Return logs enabled status for Order create/Update API calls between M2 and EDA
     *
     * @return mixed
     */
    public function getEdaCreateOrderLogStatus()
    {
        return $this->scopeConfig->getValue(
            'bat_integrations/bat_order/eda_order_log'
        );
    }

    /**
     * Return order status required for sending order data to EDA
     *
     * @return mixed
     */
    public function getOrderStatusRequiredToUpdateEda()
    {
        return $this->scopeConfig->getValue(
            'bat_integrations/bat_order/order_status_required_to_update_eda'
        );
    }

    /**
     * Return Auth Token Generation End Point
     *
     * @return mixed
     */
    public function getAuthTokenGenerationEndpoint()
    {
        return $this->scopeConfig->getValue(
            'bat_integrations/bat_oauth/eda_generate_auth_token_endpoint'
        );
    }

    /**
     * Return Auth Token Generation Authorization Username
     *
     * @return mixed
     */
    public function getAuthGenerationAuthorizationUsername()
    {
        return $this->scopeConfig->getValue(
            'bat_integrations/bat_oauth/eda_generate_auth_token_username'
        );
    }

    /**
     * Return Auth Token Generation Authorization Password
     *
     * @return mixed
     */
    public function getAuthGenerationAuthorizationPassword()
    {
        return $this->scopeConfig->getValue(
            'bat_integrations/bat_oauth/eda_generate_auth_token_password'
        );
    }

    /**
     * Create Order In EDA Logs
     *
     * @param string $message
     * @throws Zend_Log_Exception
     */
    public function logEdaOrderUpdateRequest($message)
    {
        $writer = new \Zend_Log_Writer_Stream(BP .'/var/log/EdaOrder.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info($message);
    }

    /**
     * Post Data to EDA
     *
     * @param $orderData
     * @param $endPoint
     * @return string|void
     */
    public function postOrderDataToEda($orderData, $endPoint)
    {
        $response = [];
        try {
            $authorization = $this->getAuthToken();
            $this->curl->addHeader("Content-Type", "application/json");
            $this->curl->addHeader("Event-Source", "M2B2B");
            $this->curl->addHeader("Ocp-Apim-Subscription-Key", $authorization);
            $this->curl->setOption(CURLOPT_TIMEOUT, 600);
            $this->curl->post($endPoint, $orderData);
            $response = $this->curl->getBody();
        } catch (\Exception $e) {
            $response['success'] = false;
            $response['message'] = $e->getMessage();
            $response = json_encode($response);
        }
        return $response;
    }

    /**
     * @throws LocalizedException
     */
    public function getAuthToken()
    {
        /*  temporary authorization subscription key */
        $generatedToken = '';
        return $this->getApiSubscriptionKey();
        /*  temporary authorization subscription key */

        /* uncomment after auth implementation
        $authTokenEndpoint = $this->getAuthTokenGenerationEndpoint();
        $authorizationUsername = $this->getAuthGenerationAuthorizationUsername();
        $authorizationPassword = $this->getAuthGenerationAuthorizationPassword();
        if ($authTokenEndpoint == '') {
            throw new LocalizedException(__('Authorization API end point not set '));
        }
        if ($authorizationUsername == '') {
            throw new LocalizedException(__('Authorization Username not set '));
        }
        if ($authorizationPassword == '') {
            throw new LocalizedException(__('Authorization Password not set '));
        }
        $generatedToken = $this->generateToken($authTokenEndpoint, $authorizationUsername, $authorizationPassword);
        */
    }

    /**
     * Generate Auth Token for EDA
     *
     * @return false
     */
    public function generateToken($authTokenEndpoint, $authorizationUsername, $authorizationPassword)
    {
        /** Add Auth Token API Call here */
        return false;
    }

    /**
     * Format Date to Ymd
     *
     * @param $date
     * @return string
     */
    public function formatDate($date)
    {
        return $this->date->date($date)->format('Ymd');
    }
}
