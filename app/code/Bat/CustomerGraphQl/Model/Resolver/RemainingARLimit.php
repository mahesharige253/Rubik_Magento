<?php
namespace Bat\CustomerGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\CustomerGraphQl\Model\Customer\GetCustomer;
use Magento\CustomerBalance\Model\BalanceFactory;
use Magento\Store\Model\StoreManagerInterface;

class RemainingARLimit implements ResolverInterface
{

    /**
     * @var GetCustomer
     */
    private $getCustomer;

    /**
     * @var BalanceFactory
     */
    private $_balanceFactory;

    /**
     * @var StoreManagerInterface
     */
    private $_storeManager;

    /**
     * Construct method
     *
     * @param GetCustomer $getCustomer
     * @param BalanceFactory $balanceFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        GetCustomer $getCustomer,
        BalanceFactory $balanceFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->getCustomer = $getCustomer;
        $this->_balanceFactory = $balanceFactory;
        $this->_storeManager = $storeManager;
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
        $remainingAR = 0;
        $totalARLimit = 0;

        $customer = $this->getCustomer->execute($context);
        $customerId = $customer->getId();
        if ($customer->getCustomAttribute('total_ar_limit') !='') {
            $totalARLimit = $customer->getCustomAttribute('total_ar_limit')->getValue();
        }
        $websiteId = $this->_storeManager->getStore($customer->getStoreId())->getWebsiteId();

        $balanceModel = $this->_balanceFactory->create()->setCustomerId(
            $customer->getId()
        )->setWebsiteId(
            $websiteId
        )->loadByCustomer();
        $remainingAR = $balanceModel->getAmount();

        $result = ['customer_id' => $customerId, 'total_ar_limit' => $totalARLimit, 'remaining_ar' => $remainingAR];
        return $result;
    }
}
