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

/**
 * Sales Order field resolver, used for GraphQL request processing
 */
class OrderProducts implements ResolverInterface
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var ProductRepositoryInterfaceFactory
     */
    private $_productRepositoryFactory;

    /**
     * @var StoreManagerInterface
     */
    private $_storeManager;

    /**
     * @var GetCustomer
     */
    private $getCustomer;

    /**
     * Construct method
     *
     * @param OrderRepositoryInterface $orderRepository
     * @param ProductRepositoryInterfaceFactory $productRepositoryFactory
     * @param StoreManagerInterface $storeManager
     * @param GetCustomer $getCustomer
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        ProductRepositoryInterfaceFactory $productRepositoryFactory,
        StoreManagerInterface $storeManager,
        GetCustomer $getCustomer,
    ) {
        $this->orderRepository = $orderRepository;
        $this->_productRepositoryFactory = $productRepositoryFactory;
        $this->_storeManager = $storeManager;
        $this->getCustomer = $getCustomer;
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
        $orderId = $args['order_id'];
        $order = $this->orderRepository->get($orderId);
        foreach ($order->getAllVisibleItems() as $_item) {
            $itemsData[] = $_item->getData();
        }
        $itemArray = [];
        $result = [];
        $imgUrl = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
        $prodPath = 'catalog/product';
        $count = count($itemsData);

        $defaultTextAttributeVal = '';
        $attributeLabel = '';
        $selectedAttributeVal = '';
        foreach ($itemsData as $item) {
            $productData = $this->_productRepositoryFactory->create()->getById($item['product_id']);
            $attributeCode = $productData->getBatDefaultAttribute();

            $attribute = $productData->getResource()->getAttribute($attributeCode);
            if ($attribute) {
                if (
                    in_array(
                        $productData->getResource()->getAttribute($attributeCode)->getFrontendInput(),
                        ['select']
                    )
                ) {
                    $selectedAttributeVal = $productData->getAttributeText($attributeCode);
                    $attributeLabel = $productData->getResource()->getAttribute($attributeCode)->getFrontendLabel();
                } else {
                    $selectedAttributeVal = $productData->getData($attributeCode);
                }
            }
            if ($selectedAttributeVal != '') {
                $defaultTextAttributeVal = $attributeLabel . ': ' . $selectedAttributeVal;
            }
            $arr = [];
            $arr['sku'] = $item['sku'];
            $arr['title'] = $item['name'];
            $arr['price'] = $item['price'];
            $arr['quantity'] = $item['qty_ordered'];
            $arr['subtotal'] = $item['row_total'];
            $arr['image'] = base64_encode(
                $imgUrl . $prodPath . $productData->getData('image')
            );
            $arr['default_attribute'] = $defaultTextAttributeVal;
            $arr['is_price_tag'] = ($item['is_price_tag']) ? true : false;

            $itemArray[] = $arr;

        }
        $result['product_count'] = $count;
        $result['items'] = $itemArray;
        return $result;
    }
}