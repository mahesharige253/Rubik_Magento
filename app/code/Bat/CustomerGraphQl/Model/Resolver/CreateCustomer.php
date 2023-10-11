<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types = 1);

namespace Bat\CustomerGraphQl\Model\Resolver;

use Magento\CustomerGraphQl\Model\Customer\CreateCustomerAccount;
use Magento\CustomerGraphQl\Model\Customer\ExtractCustomerData;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlAlreadyExistsException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Newsletter\Model\Config;
use Magento\Store\Model\ScopeInterface;
use Magento\CustomerGraphQl\Model\Customer\Address\CreateCustomerAddress;
use Bat\Customer\Helper\Data as CustomerData;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\CustomerFactory;
use Bat\CustomerGraphQl\Model\Resolver\DataProvider\LicenseUpload;
use Bat\VirtualBank\Helper\Data as VirtualAccountHelper;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Framework\Api\DataObjectHelper;

/**
 * Create customer account resolver
 */
class CreateCustomer implements ResolverInterface
{
    /**
     * @var Config
     */
    private $newsLetterConfig;

    /**
     * @var ExtractCustomerData
     */
    private $extractCustomerData;

    /**
     * @var CreateCustomerAccount
     */
    private $createCustomerAccount;

    /**
     * @var CreateCustomerAddress
     */
    private $createCustomerAddress;

    /**
     * @var CustomerData
     */
    private $customerHelper;

    /**
     * @var CompanyRepositoryInterface
     */
    private $companyRepository;

    /**
     * @var CompanyInterface
     */
    private $companyInterface;

    /**
     * @var DataObjectHelper
     */
    private $objectHelper;

     /**
      * @var CustomerRepositoryInterface
      */
    protected $_customerRepositoryInterface;

    /**
     * @var CustomerFactory
     */
    protected $_customerFactory;

    /**
     * @var LicenseUpload
     */
    private $licenseUpload;

    /**
     * @var VirtualAccountHelper
     */
    private $virtualAccountHelper;

    /**
     *
     * @param ExtractCustomerData $extractCustomerData
     * @param CreateCustomerAccount $createCustomerAccount
     * @param Config $newsLetterConfig
     * @param CreateCustomerAddress $createCustomerAddress
     * @param CustomerData $customerHelper
     * @param CompanyRepositoryInterface $companyRepository
     * @param CompanyInterface $companyInterface
     * @param DataObjectHelper $objectHelper
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     * @param CustomerFactory $customerFactory
     * @param LicenseUpload $licenseUpload
     * @param VirtualAccountHelper $virtualAccountHelper
     */
    public function __construct(
        ExtractCustomerData $extractCustomerData,
        CreateCustomerAccount $createCustomerAccount,
        Config $newsLetterConfig,
        CreateCustomerAddress $createCustomerAddress,
        CustomerData $customerHelper,
        CompanyRepositoryInterface $companyRepository,
        CompanyInterface $companyInterface,
        DataObjectHelper $objectHelper,
        CustomerRepositoryInterface $customerRepositoryInterface,
        CustomerFactory $customerFactory,
        LicenseUpload $licenseUpload,
        VirtualAccountHelper $virtualAccountHelper
    ) {
        $this->newsLetterConfig = $newsLetterConfig;
        $this->extractCustomerData = $extractCustomerData;
        $this->createCustomerAccount = $createCustomerAccount;
        $this->createCustomerAddress = $createCustomerAddress;
        $this->customerHelper = $customerHelper;
        $this->companyRepository = $companyRepository;
        $this->companyInterface = $companyInterface;
        $this->objectHelper = $objectHelper;
        $this->_customerRepositoryInterface = $customerRepositoryInterface;
        $this->_customerFactory = $customerFactory;
        $this->licenseUpload = $licenseUpload;
        $this->virtualAccountHelper = $virtualAccountHelper;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (empty($args['input']) || !is_array($args['input'])) {
            throw new GraphQlInputException(__('"input" value should be specified'));
        }

        if (isset($args['input']['mobilenumber'])
            && !preg_match("/010 ([0-9]{3}|[0-9]{4}) [0-9]{4}$/", $args['input']['mobilenumber'])) {
            throw new GraphQlInputException(__('Mobile number value is not valid'));
        }
        $outletIdVal = $args['input']['outlet_id'] = $this->getOutletId();

        if (isset($args['input']['mobilenumber']) && !isset($args['input']['email'])) {
             $mobilenumber = str_replace(' ', '', $args['input']['mobilenumber']);
             $args['input']['email'] = $outletIdVal.'@'.$outletIdVal.'.com';
        }

        if (isset($args['input']['name']) && ($args['input']['name'] != '')) {
            $name = $args['input']['name'];
            $args['input']['firstname'] = $name;
        }

        /* check if firstname is not passed in graphql and set value for the same */
        if (!isset($args['input']['name'])) {
             $args['input']['firstname'] = '-';
        }

        /* check if lastname is not passed in graphql and set value for the same */
        if (!isset($args['input']['lastname'])) {
             $args['input']['lastname'] = '-';
        }

        if (!isset($args['input']['paper_forms'])) {
            throw new GraphQlInputException(__('Paper Forms value should be specified'));
        } else {
                $args['input']['bat_paper_forms'] = $args['input']['paper_forms'];
        }
        if (!isset($args['input']['consent_identifier'])) {
            throw new GraphQlInputException(__('Consent Identifier value should be specified'));
        } else {
            $args['input']['consentform'] = $args['input']['consent_identifier'];
        }
        $virtualAccountId = '';
        if (!isset($args['input']['virtual_bank'])) {
            throw new GraphQlInputException(__('Virtual Bank selection is required'));
        } else {
            if ($args['input']['virtual_bank'] == '') {
                throw new GraphQlInputException(__('Virtual Bank selection is required'));
            }
            $virtualAccountNo = $this->virtualAccountHelper->isVirtualAccountNumbersAvailable(
                $args['input']['virtual_bank']
            );
            if ($virtualAccountNo['status']) {
                $args['input']['virtual_account'] = $virtualAccountNo['acc_no'];
                $virtualAccountId = $virtualAccountNo['acc_id'];
            } else {
                throw new GraphQlInputException(__($virtualAccountNo['msg']));
            }
        }
        // Code to create the customer
        try {
            $customer = $this->createCustomerAccount->execute(
                $args['input'],
                $context->getExtensionAttributes()->getStore()
            );
            if ($virtualAccountId) {
                $this->virtualAccountHelper->deleteAccountNo($virtualAccountId);
            }
        } catch (Exception $e) {
            throw new GraphQlInputException(__($e->getMessage()));
        }
        $customerId = (int)$customer->getId();

        /* Check business license file exist or not. Upload business license file */
        if (isset($args['input']['business_license'])) {
            if ((isset($args['input']['business_license'][0]['business_name'])
                && ($args['input']['business_license'][0]['business_name']!= ''))
                && (isset($args['input']['business_license'][0]['business_file'])
                    && ($args['input']['business_license'][0]['business_file'] != ''))) {
                $businessLicenseName = $args['input']['business_license'][0]['business_name'];
                $businessLicenseImage = $args['input']['business_license'][0]['business_file'];
                $businessResponse = $this->licenseUpload->uploadBusinessLicense(
                    $businessLicenseName,
                    $businessLicenseImage,
                    $customerId
                );
                $businessLicenseUpload = 1;
            } else {
                throw new GraphQlInputException(__('Business License value missing'));
            }
        } else {
                throw new GraphQlInputException(__('Business License value should be specified'));
        }

        /* Check tobacco seller license file exist or not. Upload tobacco seller license file */
        if (isset($args['input']['tobacco_seller_license'])) {
            if ((isset($args['input']['tobacco_seller_license'][0]['tobacco_name'])
                && ($args['input']['tobacco_seller_license'][0]['tobacco_name'] != ''))
                && (isset($args['input']['tobacco_seller_license'][0]['tobacco_file'])
                    && ($args['input']['tobacco_seller_license'][0]['tobacco_file'] != ''))) {
                $tobaccoLicenseName = $args['input']['tobacco_seller_license'][0]['tobacco_name'];
                $tobaccoLicenseImage = $args['input']['tobacco_seller_license'][0]['tobacco_file'];
                $tobaccoResponse = $this->licenseUpload->uploadTobaccoSellerLicense(
                    $tobaccoLicenseName,
                    $tobaccoLicenseImage,
                    $customerId
                );
                $tobaccoLicenseUpload = 1;
            } else {
                throw new GraphQlInputException(__('Tobacco Seller License value missing'));
            }
        } else {
                throw new GraphQlInputException(__('Tobacco seller License value should be specified'));
        }

        $addressInput = $args['input']['address'];

        // Code to create the company and assign to customer
        $companyRepo = $this->companyRepository;
        $companyObj = $this->companyInterface;
        $dataObj = $this->objectHelper;
        $street = $addressInput['street'];
        $street[] = $addressInput['city'];

        $company = [
            "company_name" => $args['input']['company_name'],
            "company_email" => $args['input']['email'],
            "street" => $street,
            "city" => "-",
            "country_id" => "KR",
            "postcode" => $addressInput['postcode'],
            "telephone" => $args['input']['mobilenumber'],
            "super_user_id" => $customerId,
            "customer_group_id" => 1
        ];

        $dataObj->populateWithArray(
            $companyObj,
            $company,
            \Magento\Company\Api\Data\CompanyInterface::class
        );

        $companyRepo->save($companyObj);

        // Code to save the address for the customer
        $addressData['firstname'] = $args['input']['name'];
        $addressData['lastname'] = '-';
        $addressData['street'] = $street;
        $addressData['city'] = "-";
        $addressData['postcode'] = $addressInput['postcode'];
        $addressData['telephone'] = $args['input']['mobilenumber'];
        $addressData['country_code'] = 'KR';
        $addressData['default_shipping'] = true;
        $addressData['default_billing'] = true;

        $this->createCustomerAddress->execute($customerId, $addressData);

        $data = $this->extractCustomerData->execute($customer);

        if ($businessLicenseUpload == 1) {
            $filePath = '/business/'.$businessResponse['items'][0]['name'];
            $customerFactory = $this->_customerFactory->create()->load($customerId)->getDataModel();
            $customerFactory->setCustomAttribute('bat_business_license', $filePath);
            $this->_customerRepositoryInterface->save($customerFactory);
        }
        if ($tobaccoLicenseUpload == 1) {
            $tobaccoFilePath = '/tobacco/'.$tobaccoResponse['items'][0]['name'];
            $customerFactory = $this->_customerFactory->create()->load($customerId)->getDataModel();
            $customerFactory->setCustomAttribute('bat_tobacco_seller_license', $tobaccoFilePath);
            $this->_customerRepositoryInterface->save($customerFactory);
        }

        return ['customer' => $data];
    }

    /**
     * Get Outlet Id
     */
    public function getOutletId()
    {

        $key = true;
        while ($key) {
            $outletId = random_int(100000, 999999);
            $isValidOutletId = $this->customerHelper->isOutletIdValidCustomer($outletId);
            if ($isValidOutletId != '') {
                $key = true;
            } else {
                $key = false;
            }
            $uniqueId = $outletId;
        }

        return $uniqueId;
    }
}
