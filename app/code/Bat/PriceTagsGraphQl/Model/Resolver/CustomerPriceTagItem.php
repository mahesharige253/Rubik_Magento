<?php
namespace Bat\PriceTagsGraphQl\Model\Resolver;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Quote\Model\QuoteFactory;
use \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductFactory;

class CustomerPriceTagItem implements ResolverInterface
{
    /**
     * @var QuoteFactory
     */
    protected $quoteFactory;

    /**
     * @var ProductFactory
     */
    protected $productFactory;

    /**
     * @param QuoteFactory $quoteFactory
     * @param ProductFactory $productFactory
     */
    public function __construct(
        QuoteFactory $quoteFactory,
        ProductFactory $productFactory
    ) {
        $this->quoteFactory = $quoteFactory;
        $this->productFactory = $productFactory;
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
        $customerId = $context->getUserId();
           return $this->getCartPriceTagItems($customerId);
    }

        /**
         * Get cart price tag items
         *
         * @param int $customerId
         * @return array
         */
    public function getCartPriceTagItems($customerId)
    {
        $items = $this->getCartItems($customerId);
        $priceTagItems = [];
        $ids = [];
        foreach ($items as $_item) {
            $ids[] = $_item->getProductId();
        }
        if (!empty($ids)) {
            $collection = $this->productFactory->create();
            $collection->addAttributeToSelect('pricetag_type');
            $collection->addAttributeToSelect('image');
            $collection->addAttributeToSelect('name');
            $collection->addAttributeToSelect('sku');
            $collection->addAttributeToSelect('status');
            $collection->addFieldToFilter('status', 1);
            $collection->addFieldToFilter('pricetag_type', ['eq' => 1]);
            $collection->addFieldToFilter('entity_id', [$ids]);

            foreach ($collection as $product) {
                $attribute = $product->getResource()->getAttribute('image');
                $imageUrl = $attribute->getFrontend()->getUrl($product);
                $imageEncodeUrl = base64_encode($imageUrl);
                $priceTagItems[] = [
                                  'priceTagImage' => $imageEncodeUrl,
                                  'priceTagName' => $product->getName(),
                                  'priceTagSku' => $product->getSku()
                                ];
            }
        }
        
        return $priceTagItems;
    }

    /**
     * Get cart items
     *
     * @param int $customerId
     * @return array
     */
    public function getCartItems($customerId)
    {
        $quote = $this->quoteFactory->create()->loadByCustomer($customerId);
        return $items = $quote->getAllItems();
    }
}
