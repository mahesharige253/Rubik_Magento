<?php
namespace Bat\PriceTagsGraphQl\Model;

use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Sales\Api\OrderRepositoryInterface;

class OrderPriceTagList
{
    /**
     * @var CollectionFactory
     */
    protected $productFactory;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @param CollectionFactory $productFactory
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        CollectionFactory $productFactory,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->productFactory = $productFactory;
        $this->orderRepository = $orderRepository;
    }

    /**
     * Get price tag items
     *
     * @param array $data
     * @throws GraphQlInputException
     * @return array
     */
    public function execute($data)
    {
        $order = $this->orderRepository->get($data['orderId']);
        $priceTagItems = [];
        $ids = [];
        foreach ($order->getAllVisibleItems() as $_item) {
            $ids[] = $_item->getProductId();
        }
        $collection = $this->productFactory->create();
        $collection->addAttributeToSelect('pricetag_type');
        $collection->addAttributeToSelect('image');
        $collection->addAttributeToSelect('name');
        $collection->addAttributeToSelect('sku');
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
     
        return $priceTagItems;
    }
}
