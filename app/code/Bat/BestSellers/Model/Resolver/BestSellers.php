<?php

namespace Bat\BestSellers\Model\Resolver;

use Magento\CustomerGraphQl\Model\Customer\GetCustomer;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Sales\Model\ResourceModel\Report\Bestsellers\CollectionFactory as BestSellersCollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Catalog\Helper\Image as ImageHelper;

/**
 * Bset seller resolver, used for GraphQL request processing.
 */
class BestSellers implements ResolverInterface
{
    /**
     * @var CollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var BestSellersCollectionFactory
     */
    protected $bestSellersCollectionFactory;

    /**
     * @var ImageHelper
     */
    protected $imageHelper;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var ProductFactory
     */
    protected $productFactory;

    /**
     * @var GetSalableQuantityDataBySku
     */
    protected $getSalableQuantityDataBySku;

    /**
     * @var GetCustomer
     */
    protected $getCustomer;

    /**
     * @param CollectionFactory $productCollectionFactory
     * @param BestSellersCollectionFactory $bestSellersCollectionFactory
     * @param ImageHelper $imageHelper
     * @param ScopeConfigInterface $scopeConfig
     * @param ProductFactory $productFactory
     * @param GetSalableQuantityDataBySku $getSalableQuantityDataBySku
     * @param GetCustomer $getCustomer
     */
    public function __construct(
        CollectionFactory $productCollectionFactory,
        BestSellersCollectionFactory $bestSellersCollectionFactory,
        ImageHelper $imageHelper,
        ScopeConfigInterface $scopeConfig,
        ProductFactory $productFactory,
        GetSalableQuantityDataBySku $getSalableQuantityDataBySku,
        GetCustomer $getCustomer
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->bestSellersCollectionFactory = $bestSellersCollectionFactory;
        $this->imageHelper = $imageHelper;
        $this->scopeConfig = $scopeConfig;
        $this->productFactory = $productFactory;
        $this->getSalableQuantityDataBySku = $getSalableQuantityDataBySku;
        $this->getCustomer = $getCustomer;
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
        $productIds = [];

        $areaCode = $args['areaCode'];

        $enabledStatus = $this->scopeConfig->getValue("best_sellers/general/best_seller_carousel");
        $guestAllow = $this->scopeConfig->getValue("best_sellers/general/best_seller_guestallow");
        $frequentlyOrderedProductId = '';
        if ((!$guestAllow) && (false === $context->getExtensionAttributes()->getIsCustomer())) {
            throw new GraphQlAuthorizationException(__('The current customer isn\'t authorized.'));
        } else {
            $customer = $this->getCustomer->execute($context);
            $customAttributes = $customer->getCustomAttributes();
            if (isset($customAttributes['bat_frequently_ordered'])) {
                $frequentlyOrderedProductId = $customAttributes['bat_frequently_ordered']->getValue();
            }
        }

        $data = [];
        $productData = [];
        if ($enabledStatus) {

            if (($areaCode == '') || (!is_numeric($areaCode))) {
                throw new GraphQlInputException(__('Area code is not valid'));
            }

            $limitCount = $this->scopeConfig->getValue("best_sellers/general/best_seller_carousel_limit");
            //To get the best seller products data
            $bestSellers = $this->bestSellersCollectionFactory->create()
                ->setPeriod('month')
                ->setPageSize($limitCount);
            if (count($bestSellers) > 0) {
                foreach ($bestSellers as $product) {
                    $productIds[] = $product->getProductId();
                }
                // To get the product data based on areacode
                $collection = $this->productCollectionFactory->create()->addIdFilter($productIds);
                $collection->addMinimalPrice()
                    ->addFinalPrice()
                    ->addTaxPercents()
                    ->addAttributeToSelect('*');
                $areaCodeCollectionData = $collection->addAttributeToFilter(
                    'bat_product_area_code',
                    ['eq'=> $areaCode]
                );
                $productCount = count($areaCodeCollectionData);

                if ($productCount > 0) {
                    $areaCodeCollectionData = $areaCodeCollectionData;
                } else {
                    $basiccollection = $this->productCollectionFactory->create()->addIdFilter($productIds);
                    $basiccollection->addMinimalPrice()
                        ->addFinalPrice()
                        ->addTaxPercents()
                        ->addAttributeToSelect('*');
                    $areaCodeCollectionData = $basiccollection;
                }

                $productArray = [];
                foreach ($areaCodeCollectionData->getItems() as $product) {
                    $productId = $product->getId();
                    $productArray[$productId] = $product->getData();
                    if ($productId == $frequentlyOrderedProductId) {
                        $productArray[$productId]['frequent'] = $frequentlyOrderedProductId;
                    }
                    $productArray[$productId]['best_seller'] = $productId;
                    $productArray[$productId]['model'] = $product;
                }

                // Code to check the product data if area code product is less than the bestseller limit data
                if (($productCount < $limitCount) && ($productCount != 0)) {
                    $nonAreaCount = $limitCount - $productCount;
                    $nonAteaCollection = $this->productCollectionFactory->create()->addIdFilter($productIds);
                    $nonAteaCollection->addMinimalPrice()
                        ->addFinalPrice()
                        ->addTaxPercents()
                        ->addAttributeToSelect('*');
                    $nonAreaproductCollection = $nonAteaCollection->addAttributeToFilter(
                        'bat_product_area_code',
                        ['neq'=> $areaCode]
                    )->setPageSize($nonAreaCount);

                    foreach ($nonAreaproductCollection->getItems() as $product) {
                        $productId = $product->getId();
                        if ($productId == $frequentlyOrderedProductId) {
                            $productArray[$productId]['frequent'] = $frequentlyOrderedProductId;
                        }
                        $productArray[$productId] = $product->getData();
                        $productArray[$productId]['best_seller'] = $productId;
                        $productArray[$productId]['model'] = $product;
                    }
                }

                $data['items'] = $productArray;

            } else {
                //Code to get area code product data if No sales happened
                $collection = $this->productCollectionFactory->create();
                $collection->addMinimalPrice()
                    ->addFinalPrice()
                    ->addTaxPercents()
                    ->addAttributeToSelect('*');
                $areaCodeCollectionData = $collection->addAttributeToFilter(
                    'bat_product_area_code',
                    ['eq'=> $areaCode]
                );
                if (count($areaCodeCollectionData) > 0) {
                    $productArray = [];
                    foreach ($areaCodeCollectionData->getItems() as $product) {
                        $productId = $product->getId();
                        if ($productId == $frequentlyOrderedProductId) {
                            $productArray[$productId]['frequent'] = $frequentlyOrderedProductId;
                        }
                        $productArray[$productId] = $product->getData();
                        $productArray[$productId]['best_seller'] = $productId;
                        $productArray[$productId]['model'] = $product;
                    }

                    $data['items'] = $productArray;

                } else {
                    throw new GraphQlNoSuchEntityException(__(
                        'Best Sellers Carousel have no products data for given areacode'
                    ));
                }
            }
        } else {
            throw new GraphQlNoSuchEntityException(__('Best Sellers Carousel is Disabled in admin'));
        }

        return $data;
    }
}
