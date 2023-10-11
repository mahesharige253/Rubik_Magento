<?php
declare(strict_types=1);

namespace Bat\OrderProducts\Model\Resolver;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterfaceFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\CustomerGraphQl\Model\Customer\GetCustomer;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Sales\Model\Order;
use Bat\OrderProducts\Helper\Data;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Bat\CustomerBalanceGraphQl\Helper\Data as Datas;

/**
 * Sales Order field resolver, used for GraphQL request processing
 */
class OrderDetails implements ResolverInterface
{

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var GetCustomer
     */
    private $getCustomer;

    /**
     * @var ProductRepositoryInterfaceFactory
     */
    private $productRepositoryFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var CompanyManagementInterface
     */
    private $companyRepository;

    /**
     * @var Order
     */
    private $order;

    /**
     * @var DateTime
     */
    private $date;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var \Magento\Eav\Model\Config $eavConfig
     */
    protected $_eavConfig;

    /**
     * @var ProductRepositoryInterfaceFactory
     */
    protected $_productRepositoryFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var Datas
     */
    protected $datas;

    /**
     * Construct method
     *
     * @param OrderRepositoryInterface $orderRepository
     * @param ProductRepositoryInterfaceFactory $productRepositoryFactory
     * @param StoreManagerInterface $storeManager
     * @param GetCustomer $getCustomer
     * @param CompanyManagementInterface $companyRepository
     * @param Order $order
     * @param DateTime $date
     * @param ScopeConfigInterface $scopeConfig
     * @param Data $helper
     * @param Config $eavConfig
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        ProductRepositoryInterfaceFactory $productRepositoryFactory,
        StoreManagerInterface $storeManager,
        GetCustomer $getCustomer,
        CompanyManagementInterface $companyRepository,
        Order $order,
        DateTime $date,
        ScopeConfigInterface $scopeConfig,
        Data $helper,
        Datas $datas,
        \Magento\Eav\Model\Config $eavConfig
    ) {
        $this->orderRepository = $orderRepository;
        $this->_productRepositoryFactory = $productRepositoryFactory;
        $this->_storeManager = $storeManager;
        $this->getCustomer = $getCustomer;
        $this->companyRepository = $companyRepository;
        $this->order = $order;
        $this->date = $date;
        $this->scopeConfig = $scopeConfig;
        $this->helper = $helper;
        $this->_eavConfig = $eavConfig;
        $this->datas = $datas;
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
        if (!isset($args['order_id'])) {
            throw new GraphQlInputException(
                __(
                    'orderId value must be specified'
                )
            );
        }
        $customer = $this->getCustomer->execute($context);
        $firstname = $customer->getFirstname();
        $cname = $firstname;
        $cid = $customer->getId();
        $bankCode = $customer->getCustomAttribute('virtual_bank')->getValue();
        $bankName = $this->getAttributeLabelByValue('virtual_bank', 'customer', $bankCode);
        $accountno = $customer->getCustomAttribute('virtual_account')->getValue();
        $company = $this->companyRepository->getByCustomerId($cid);
        $companyname = $company->getCompanyName();
        $orderId = $args['order_id'];
        $firstOrder = $this->datas->getIsCustomerFirstOrder($cid,$orderId);
        $order = $this->orderRepository->get($orderId);
        $total = $order->getGrandTotal();
        $status = $order->getStatusLabel();
        $orderdate = $order->getCreatedAt();
        $date = date('Y/m/d', strtotime($orderdate));
        $orderIncrementId = $order->getIncrementId();
        $deadline = $this->helper->getPaymentDeadline();
        $nextdate = $this->date->date('Y-m-d', strtotime($orderdate . " +" . $deadline . "days"));
        $paymentdeadline = date("Y-m-d h:i:s", strtotime($nextdate . ' 23:00:00'));
        $order = $this->order->load($orderId); // pass orderId
        $shippingaddress = $order->getShippingAddress();
        $street1 = $shippingaddress->getStreetLine(1);
        $street2 = $shippingaddress->getStreetLine(2);
        $city = $shippingaddress->getCity();
        $region = $shippingaddress->getRegion();
        $postal = $shippingaddress->getPostcode();
        $phone = $shippingaddress->getTelephone();
        $arr = [
            'payment_deadline' => $paymentdeadline,
            'message' => 'Once we receive your payment, we will send you a message and arrange shipping',
            'order_id' => $orderIncrementId,
            'order_amount' => $total,
            'order_status' => $status,
            'order_date' => $date,
            'outlet_name' => $cname,
            'outlet_owner_name' => $companyname,
            'is_first_order' => $firstOrder,
            'address' => [
                'street' => [
                    'street1' => $street1,
                    'street2' => $street2
                ],
                'city' => $city,
                'region' => $region,
                'postal' => $postal
            ],
            'phone_number' => $phone,
            'account_number' => $accountno,
            'account_holder' => $cname,
            'bank_details' => [
                'bank_code' => $bankCode,
                'bank_name' => $bankName
            ]
        ];
        return $arr;
    }
     /**
      * @inheritdoc
      */
    public function getAttributeLabelByValue($attributeCode, $entityType, $value)
    {
        try {
            $entityType = $this->_eavConfig->getEntityType($entityType);
            $attribute  = $this->_eavConfig->getAttribute($entityType, $attributeCode);
            $options    = $attribute->getSource()->getAllOptions();
            foreach ($options as $option) {
                if ($option['value'] == $value) {
                    return $option['label'];
                }
            }
        } catch (\Exception $e) {
            return null;
        }
    
        return null;
    }
}
