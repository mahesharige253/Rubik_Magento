<?php
namespace Bat\PriceTagsGraphQl\Model;

use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Quote\Model\QuoteFactory;
use \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Bat\CustomerBalanceGraphQl\Helper\Data;

class PriceTagList
{

    /**
     * @var QuoteFactory
     */
    protected $quoteFactory;

    /**
     * @var CollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @param QuoteFactory $quoteFactory
     * @param CollectionFactory $productCollectionFactory
     * @param StoreManagerInterface $storeManager
     * @param Data $helper
     */
    public function __construct(
        QuoteFactory $quoteFactory,
        CollectionFactory $productCollectionFactory,
        StoreManagerInterface $storeManager,
        Data $helper
    ) {
        $this->quoteFactory = $quoteFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->storeManager = $storeManager;
        $this->helper = $helper;
    }

    /**
     * Get price tag items
     *
     * @param array $customerId
     * @throws GraphQlInputException
     */
    public function execute($customerId)
    {
        try {
            if (!empty($customerId)) {
                $priceTagProduct = $this->getPriceTagItems();
                $priceTagItems = [];
                $imageEncodeUrl = '';
                if ($this->helper->getIsCustomerFirstOrder($customerId) == false) {
                    foreach ($priceTagProduct as $item) {
                        if (!empty($item->getImage())) {
                            $imagePath = $item->getImage();
                            $imageUrl = $this->getMediaUrl() .$imagePath;
                            $imageEncodeUrl = base64_encode($imageUrl);
                        }
                        $priceTagItems[] = [
                                            'priceTagImage' => $imageEncodeUrl,
                                            'priceTagName' => $item->getName(),
                                            'priceTagSku' => $item->getSku()
                                            ];
                    }
                }
           
            } else {
                throw new GraphQlInputException(__('Not found customerId'));
            }
        } catch (LocalizedException $e) {
            throw new GraphQlInputException(__($e->getMessage()));
        }
        return $priceTagItems;
    }

    /**
     * Get Price Tag items
     *
     * @param int $customerId
     * @return array
     */
    public function getPriceTagItems()
    {
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect('pricetag_type');
        $collection->addAttributeToSelect('image');
        $collection->addAttributeToSelect('name');
        $collection->addAttributeToSelect('sku');
        $collection->addAttributeToSelect('status');
        $collection->addFieldToFilter('status', 1);
        $collection->addFieldToFilter('pricetag_type', ['eq' => 1]);
        return $collection;
    }

    /**
     * Get Media Url
     *
     * @return string
     */
    public function getMediaUrl()
    {
        $prodPath = 'catalog/product';
        return $this->storeManager->getStore()
        ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA).$prodPath;
    }
}
