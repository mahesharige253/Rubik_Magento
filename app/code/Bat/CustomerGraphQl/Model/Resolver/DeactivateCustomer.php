<?php
namespace Bat\CustomerGraphQl\Model\Resolver;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Bat\CustomerGraphQl\Model\Resolver\DataProvider\BankCardUpload;
use Magento\Customer\Model\CustomerFactory;
use Magento\CustomerGraphQl\Model\Customer\GetCustomer;
use Magento\Customer\Api\CustomerRepositoryInterface;

class DeactivateCustomer implements ResolverInterface
{
    /**
     * @var BankCardUpload
     */
    private $bankCardUpload;

    /**
     * @var GetCustomer
     */
    private $getCustomer;

    /**
     * @var CustomerFactory
     */
    protected $_customerFactory;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $_customerRepositoryInterface;

    /**
     * @param BankCardUpload $bankCardUpload
     * @param GetCustomer $getCustomer
     * @param CustomerFactory $customerFactory
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     */
    public function __construct(
        BankCardUpload $bankCardUpload,
        GetCustomer $getCustomer,
        CustomerFactory $customerFactory,
        CustomerRepositoryInterface $customerRepositoryInterface
    ) {
        $this->bankCardUpload = $bankCardUpload;
        $this->getCustomer = $getCustomer;
        $this->_customerFactory = $customerFactory;
        $this->_customerRepositoryInterface = $customerRepositoryInterface;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        /** @var ContextInterface $context */
        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(__('The current customer isn\'t authorized.'));
        }

        if (empty($args['input']) || !is_array($args['input'])) {
            throw new GraphQlInputException(__('"input" value should be specified'));
        }
        
        if (!isset($args['input']['account_closing_date'])) {
            throw new GraphQlInputException(__('Account Closing Date should be specified'));
        } elseif (isset($args['input']['account_closing_date']) && ($args['input']['account_closing_date'] == '')) {
            throw new GraphQlInputException(__('Account Closing Date should be specified'));
        }

        if (!isset($args['input']['consent_form'])) {
            throw new GraphQlInputException(__('Consent form should be specified'));
        } elseif (isset($args['input']['consent_form']) && ($args['input']['consent_form'] != 1)) {
            throw new GraphQlInputException(__('Please select the required consent'));
        }

        if (!isset($args['input']['returning_stock'])) {
            throw new GraphQlInputException(__('Return to Stock value should be specified'));
        }

        $startDate = date('Y-m-d', strtotime("+7 day", time()));
        $endDate = date('Y-m-d', strtotime("+20 day", time()));

        $closingDate = $args['input']['account_closing_date'];

        if (!( $closingDate >= $startDate ) || !( $closingDate <= $endDate )) {
            throw new GraphQlInputException(__('Closing date should be in between next 7 - 20 days only'));
        }

        $customer = $this->getCustomer->execute($context);
        $customerId = $customer->getId();
        $isBankCardFileUpload = 0;

        /* Upload Bank Account Card file */
        if (isset($args['input']['bank_account_card'])) {
            if ((isset($args['input']['bank_account_card'][0]['card_name'])
                && ($args['input']['bank_account_card'][0]['card_name']!= ''))
                && (isset($args['input']['bank_account_card'][0]['card_file'])
                    && ($args['input']['bank_account_card'][0]['card_file'] != ''))) {
                $bankAccountCardName = $args['input']['bank_account_card'][0]['card_name'];
                $bankAccountCardFile = $args['input']['bank_account_card'][0]['card_file'];
                $bankCardResponse = $this->bankCardUpload->uploadBankAccountCard(
                    $bankAccountCardName,
                    $bankAccountCardFile,
                    $customerId
                );
                $isBankCardFileUpload = 1;
            } else {
                throw new GraphQlInputException(__('Bank account card value missing'));
            }
        } else {
                throw new GraphQlInputException(__('Bank account card value should be specified'));
        }

        if ($isBankCardFileUpload == 1) {
            $filePath = '/bankCard/'.$bankCardResponse['items'][0]['name'];
            $customerFactory = $this->_customerFactory->create()->load($customerId)->getDataModel();
            $customerFactory->setCustomAttribute('bank_account_card', $filePath);
            $customerFactory->setCustomAttribute('account_closing_date', $args['input']['account_closing_date']);
            $customerFactory->setCustomAttribute('returning_stock', $args['input']['returning_stock']);
            $customerFactory->setCustomAttribute('disclosure_consent_form_selected', $args['input']['consent_form']);
            $customerFactory->setCustomAttribute('disclosure_approval_status', 1);
            $this->_customerRepositoryInterface->save($customerFactory);
        } else {
            throw new GraphQlInputException(__('Bank card file could\'t upload.'));
        }
        return ['success' => true , 'message' => __('Your Account Will Be Deactivated')];
    }
}
