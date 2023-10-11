<?php

namespace Bat\CustomerGraphQl\Model;

use Magento\Integration\Model\Oauth\TokenFactory;
use Bat\Customer\Helper\Data;
use Magento\Customer\Model\AccountManagement;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Customer\Api\CustomerRepositoryInterface;

class ValidatePinPassword
{
    /**
     * @var TokenFactory
     */
    protected $tokenModelFactory;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var AccountManagement
     */
    protected $accountmanagement;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var CustomerRepositoryInterface;
     */
    protected $customerRepository;

    /**
     * @param TokenFactory $tokenModelFactory
     * @param Data $helper
     * @param AccountManagement $accountmanagement
     * @param ScopeConfigInterface $scopeConfig
     * @param CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        TokenFactory $tokenModelFactory,
        Data $helper,
        AccountManagement $accountmanagement,
        ScopeConfigInterface $scopeConfig,
        CustomerRepositoryInterface $customerRepository
    ) {
        $this->tokenModelFactory = $tokenModelFactory;
        $this->helper = $helper;
        $this->accountmanagement = $accountmanagement;
        $this->scopeConfig = $scopeConfig;
        $this->customerRepository = $customerRepository;
    }

    /**
     * Data validation and genrate token
     *
     * @param array $data
     * @throws GraphQlInputException
     */
    public function loginCustomerWithPinPassword($data)
    {
        $genrateToken = '';
        $mobileNumber = '';
        $customerToken = $this->tokenModelFactory->create();
        $response = $this->helper->isOutletIdValidCustomer($data['outletId']);
        if (!empty($response)) {
            $mobileNumber = $response->getMobilenumber();
        }
        $maxAttemptsValue = $this->scopeConfig->getValue(
            'customer/password/lockout_failures',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
        );
        if (!empty($response)) {
            $customer = $this->customerRepository->getById($response->getId());
            $extensionAttributes = $customer->getExtensionAttributes();
            if ($extensionAttributes->getCompanyAttributes()->getStatus() == 0) {
                throw new GraphQlNoSuchEntityException(
                    __(
                        'Your account is locked, You have reached maximum number of login attempts'
                    )
                );
            }
            $attempts = $response->getFailuresNum();
            if ($response->getLockExpires()) {
                $val = $response->getLockExpires();
                $lockExpires = new \DateTime($val);
            } else {
                $lockExpires = '';
            }
        }
        if (!empty($response)) {
            if (isset($data['pin'])) {
                if ($response['outlet_pin'] == base64_encode($data['pin'])) {
                    if (empty($lockExpires) || $lockExpires < new \DateTime()) {
                        $genrateToken = $customerToken->createCustomerToken($response->getId())->getToken();
                    } else {
                        throw new GraphQlNoSuchEntityException(
                            __(
                                'Your Account is locked, You have reached maximum number of login attempts'
                            )
                        );
                    }
                } else {
                    try {
                        $this->accountmanagement->authenticate($response->getEmail(), base64_encode($data['pin']));
                    } catch (\Exception $e) {
                        ++$attempts;
                        if ($attempts >= $maxAttemptsValue) {
                                $this->checkLockedStatus($maxAttemptsValue, $attempts, $lockExpires);
                        }
                        $remainingattempts = $maxAttemptsValue - $attempts;
                        throw new GraphQlNoSuchEntityException(
                            __(
                                'Invalid login Credentials,You have remaining attempts of '
                                . $remainingattempts . ' out of ' . $maxAttemptsValue
                            )
                        );
                    }
                }
            } else {
                if (isset($data['password'])) {
                    try {
                        $this->accountmanagement->authenticate($response->getEmail(), $data['password']);
                        $genrateToken = $customerToken->createCustomerToken($response->getId())->getToken();
                    } catch (\Exception $e) {
                        ++$attempts;
                        if ($attempts >= $maxAttemptsValue) {
                                $this->checkLockedStatus($maxAttemptsValue, $attempts, $lockExpires);
                        }
                        $remainingattempts = $maxAttemptsValue - $attempts;
                        throw new GraphQlNoSuchEntityException(
                            __(
                                'Invalid login Credentials,You have remaining attempts of '
                                . $remainingattempts . ' out of ' . $maxAttemptsValue
                            )
                        );
                    }
                } else {
                    throw new GraphQlInputException(__('Invalid login details'));
                }
            }
        } else {
            throw new GraphQlInputException(__('Invalid login details'));
        }
        $mobileNumber = ($genrateToken) ? $mobileNumber : '';
        return ['token' => $genrateToken, 'mobilenumber' => $mobileNumber];
    }

    /**
     * Check locked status
     *
     * @param string $maxAttemptsValue
     * @param string $attempts
     * @param object $lockExpires
     * @throws GraphQlInputException
     */
    public function checkLockedStatus($maxAttemptsValue, $attempts, $lockExpires = null)
    {
        if (empty($lockExpires)) {
            throw new GraphQlNoSuchEntityException(
                __(
                    'Your Account is locked, You have reached maximum number of login attempts'
                )
            );
        }
        if ($lockExpires > new \DateTime()) {
            throw new GraphQlNoSuchEntityException(
                __(
                    'Your Account is locked, You have reached maximum number of login attempts'
                )
            );
        } else {
            $attempts = $attempts - $maxAttemptsValue;
            $remainingattempts = $maxAttemptsValue - $attempts;
            throw new GraphQlNoSuchEntityException(
                __(
                    'Invalid login Credentials,You have remaining attempts of '
                    . $remainingattempts . ' out of ' . $maxAttemptsValue
                )
            );
        }
    }
}
