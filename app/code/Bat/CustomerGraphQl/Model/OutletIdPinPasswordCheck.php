<?php
namespace Bat\CustomerGraphQl\Model;

use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Bat\Customer\Helper\Data;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Framework\Encryption\EncryptorInterface;

class OutletIdPinPasswordCheck
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepositoryInterface;

    /**
     * @var CustomerRegistry
     */
    protected $customerRegistry;

    /**
     * @var EncryptorInterface
     */
    protected $encryptor;

    /**
     * @param Data $helper
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     * @param CustomerRegistry $customerRegistry
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        Data $helper,
        CustomerRepositoryInterface $customerRepositoryInterface,
        CustomerRegistry $customerRegistry,
        EncryptorInterface $encryptor
    ) {
        $this->helper = $helper;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->customerRegistry = $customerRegistry;
        $this->encryptor = $encryptor;
    }

    /**
     * Data validation and update
     *
     * @param array $data
     * @throws GraphQlInputException
     */
    public function execute($data)
    {
        try {
            $this->vaildateData($data);
            $response = $this->helper->isOutletIdValidCustomer($data['outletId']);
            if (!empty($response)) {
                $customerId = $response->getId(); // here assign your customer id
                $customer = $this->customerRepositoryInterface->getById($customerId);
                $customerSecure = $this->customerRegistry->retrieveSecureData($customerId);
                $customerSecure->setRpToken(null);
                $customerSecure->setRpTokenCreatedAt(null);
                $customerSecure->setPasswordHash($this->encryptor->getHash($data['password'], true));
                $customer->setCustomAttribute('outlet_pin', base64_encode($data['pin']));
                $this->customerRepositoryInterface->save($customer);

            } else {
                throw new GraphQlInputException(__('Not found outlet Id. Enter valid Outlet Id'));
            }
        } catch (LocalizedException $e) {
            throw new GraphQlInputException(__($e->getMessage()));
        }
        return ['success' => true, 'message' => __('Successfully updated your password and pin.')];
    }

    /**
     * Handle bad request.
     *
     * @param array $data
     * @throws LocalizedException
     */
    private function vaildateData($data)
    {
        if (!isset($data['outletId'])) {
            throw new GraphQlInputException(__('Outlet Id value is required'));
        }
        if (!isset($data['password'])) {
            throw new GraphQlInputException(__('Password value is required'));
        } elseif (isset($data['password']) && ($data['password'] == '')) {
            throw new GraphQlInputException(__('Password value is required'));
        }
        if (!isset($data['pin'])) {
            throw new GraphQlInputException(__('Pin value required'));
        } elseif (strlen(trim($data['pin'])) != 6) {
            throw new GraphQlInputException(__('Only 6-digit pin required'));
        }
    }
}
