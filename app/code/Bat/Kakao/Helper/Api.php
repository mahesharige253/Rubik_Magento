<?php

namespace Bat\Kakao\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\HTTP\ClientFactory;
use Bat\Kakao\Logger\Logger;

class Api extends AbstractHelper
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var EncryptorInterface
     */
    protected $encryptor;

    /**
     * @var ClientFactory
     */
    protected $clientFactory;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var DataObject
     */
    protected $config;

    /**
     * @var DataObject
     */
    protected $templateConfig;

    /**
     * @param EncryptorInterface $encryptor
     * @param ClientFactory $clientFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param Logger $logger
     */
    public function __construct(
        EncryptorInterface $encryptor,
        ClientFactory $clientFactory,
        ScopeConfigInterface $scopeConfig,
        Logger $logger
    ) {
        $this->encryptor = $encryptor;
        $this->clientFactory = $clientFactory;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        $this->config = new \Magento\Framework\DataObject();
        $this->templateConfig = new \Magento\Framework\DataObject();
    }

    /**
     * Prepare facade configurations
     *
     * @return DataObject
     */
    public function getConfig()
    {
        $this->config->addData(
            $this->scopeConfig->getValue('kakao/setting', ScopeInterface::SCOPE_STORE)
        );
        return $this->config;
    }

    /**
     * Prepare facade configurations
     *
     * @return DataObject
     */
    public function getTemplateConfig()
    {
        return $this->scopeConfig->getValue('kakao/template', ScopeInterface::SCOPE_STORE);
    }

    /**
     * Check is cron active or not.
     *
     * @return boolean
     */
    public function isActive()
    {
        $isCronEnable = $this->getConfig()->getIsActive();
        if (!$isCronEnable || $isCronEnable == 0) {
            $this->addLog("Kakao module is disabled.");
            return false;
        }
        return true;
    }

    /**
     * Get password from salesforce configuration.
     *
     * @return type
     */
    private function getPassword()
    {
        //return $this->encryptor->decrypt($this->getConfig()->getXApiKey());
        return $this->getConfig()->getApiKey();
    }

    /**
     * Check  configuration.
     *
     * @return type
     */
    public function isCredentialsAvailable()
    {
        if (!$this->getConfig()->getApiUrl() || !$this->getPassword()) {
            $this->addLog("API URL or API-KEY is not added.");
            return false;
        }
        return true;
    }

    /**
     * Check if cron is enabled and credentials are added
     *
     * @return type
     */
    public function isEnabled()
    {
        return ($this->isActive() && $this->isCredentialsAvailable()) ? true : false;
    }

    /**
     * Send request to facade
     *
     * @param type $endPoint
     * @param type $method
     * @param type $requestBody
     * @param type $header
     */
    public function send($endPoint, $method = 'post', $requestBody = '', $header = [])
    {
        /** REMOVE CODE AFTER CORRECT CREDENTIALS - START */
        $message = __("SMS sent successfully.");
        return [
            'code' => 200,
            'message' => (string)$message
        ];
        /** REMOVE CODE AFTER CORRECT CREDENTIALS - END */

        //$method = strtolower($method);
        //$apiUrl = trim($this->getConfig()->getApiUrl()) . $endPoint;

        /** @var \Magento\Framework\HTTP\ClientFactory $client */
        /*$client = $this->clientFactory->create();
        $headers = [
            "Content-Type" => "application/json",
            "Expect" => ""
        ];

        if (!empty($header)) {
            foreach ($header as $key => $value) {
                $headers[$key] = $value;
            }
        }
        $client->setHeaders($headers);

        if ($method == 'post') {
            $client->post($apiUrl, $requestBody);
        } elseif ($method == 'delete') {
            $client->setOption(CURLOPT_CUSTOMREQUEST, $method);
            $client->setOption(CURLOPT_RETURNTRANSFER, true);
            $client->post($apiUrl, $requestBody);
        } else {
            $client->get($apiUrl);
        }

        //$response = $client->getBody();
        $this->addLog("Request Body:");
        $this->addLog($requestBody);
        $this->addLog("Code:");
        $this->addLog($client->getCode());
        $this->addLog("Message:");
        $this->addLog($client->getMessage());
        $this->addLog("Result:");
        $this->addLog($client->getResult());
        $this->addLog("-------------------------");

        $message = __($client->getMessage());
        return [
            'code' => $client->getCode(),
            'message' => (string)$message
        ];*/
    }

    /**
     * Prepare headers
     *
     * @param type $headers
     * @return string
     */
    public function getHeaders($headers)
    {
        $data = [
            "Content-Type" => "application/json",
            "Expect" => ""
        ];

        if (!empty($headers)) {
            foreach ($headers as $key => $value) {
                $data[$key] = $value;
            }
        }

        return $data;
    }

    /**
     * Add data to log file.
     *
     * @param type $logdata
     * @return type
     */
    public function addLog($logdata)
    {
        if (!$this->getConfig()->getlogActive()) {
            return;
        }
        if (is_array($logdata)) {
            $this->logger->info('', $logdata);
        } else {
            $this->logger->info($logdata);
        }
    }
}
