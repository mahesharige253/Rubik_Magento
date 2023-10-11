<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\NewProduct\Model\DataProvider;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Model\ResourceModel\Report\Bestsellers\CollectionFactory as BestSellersCollectionFactory;
use Psr\Log\LoggerInterface;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Bat\NewProduct\Model\ResourceModel\NewProductResource\CollectionFactory as NewProductsCollectionFactory;
use Magento\Customer\Api\Data\CustomerInterface;

/**
 * @class NewProductListDataProvider
 * New Products data provider
 */
class NewProductListDataProvider
{

    /**
     * @var CollectionFactory
     */
    private CollectionFactory $productCollectionFactory;
    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;
    /**
     * @var NewProductsCollectionFactory
     */
    private NewProductsCollectionFactory $newProductsCollectionFactory;
    /**
     * @var BestSellersCollectionFactory
     */
    private BestSellersCollectionFactory $_bestSellersCollectionFactory;

    /**
     * @param CollectionFactory $productCollectionFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface $logger
     * @param NewProductsCollectionFactory $newProductsCollectionFactory
     * @param BestSellersCollectionFactory $bestSellersCollectionFactory
     */
    public function __construct(
        CollectionFactory $productCollectionFactory,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        NewProductsCollectionFactory $newProductsCollectionFactory,
        BestSellersCollectionFactory $bestSellersCollectionFactory
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        $this->newProductsCollectionFactory = $newProductsCollectionFactory;
        $this->_bestSellersCollectionFactory = $bestSellersCollectionFactory;
    }

    /**
     * Return New Products List
     *
     * @param CustomerInterface $customer
     * @param String $areaCode
     * @return array
     * @throws GraphQlNoSuchEntityException
     */
    public function getNewProductList($customer, $areaCode)
    {
        $result = [];
        $carouselEnabled = $this->scopeConfig->getValue(
            'product_carousel/general/new_products_carousel'
        );
        if ($carouselEnabled) {
            $customAttributes = $customer->getCustomAttributes();
            $frequentlyOrderedProductId = $this->getFrequentlyOrdered($customAttributes);
            $newProductIds = [];
            $newProductCollectionData = $this->newProductsCollectionFactory->create();
            foreach ($newProductCollectionData as $newProduct) {
                $newProductIds[] = $newProduct->getProductId();
            }
            if (!empty($newProductIds)) {
                $newProductList = $this->getNewProductCollection($newProductIds);
                if ($newProductList->count()) {
                    $carouselTitle = $this->scopeConfig->getValue(
                        'product_carousel/general/new_products_carousel_title'
                    );
                    $result['title'] = $carouselTitle;
                    $productArray = [];
                    $bestSellerReferenceId = [];
                    foreach ($newProductList->getItems() as $product) {
                        $productData = $product->getData();
                        $productId = $product->getId();
                        if ($areaCode != '') {
                            $bestSellerReferenceId = $this->checkMatchingAreaCode(
                                $areaCode,
                                $bestSellerReferenceId,
                                $productData,
                                $productId
                            );
                        }
                        $productArray[$productId] = $productData;
                        if ($productId == $frequentlyOrderedProductId) {
                            $productArray[$productId]['frequent'] = $frequentlyOrderedProductId;
                        }
                        $productArray[$productId]['model'] = $product;
                    }
                    $productArray = $this->getBestSellers($bestSellerReferenceId, $productArray);
                    $result['items'] = $productArray;
                }
            }
        } else {
            throw new GraphQlNoSuchEntityException(__('New Product Carousel disabled'));
        }
        if (empty($result)) {
            throw new GraphQlNoSuchEntityException(__('No New Products'));
        }
        return $result;
    }

    /**
     * Return new product collection
     *
     * @param array $newProductIds
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    public function getNewProductCollection($newProductIds)
    {
        $productCount = $this->scopeConfig->getValue(
            'product_carousel/general/new_products_carousel_limit'
        );
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect('*')
            ->addAttributeToFilter('status', ['eq'=>1])
            ->addIdFilter($newProductIds)
            ->addMinimalPrice()
            ->addFinalPrice()
            ->addTaxPercents()
            ->setOrder('created_at', 'desc')
            ->setPageSize($productCount);
        return $collection;
    }

    /**
     * Return best sellers
     *
     * @param array $bestSellerReferenceId
     * @param array $productArray
     * @return array
     */
    public function getBestSellers($bestSellerReferenceId, $productArray)
    {
        if (!empty($bestSellerReferenceId)) {
            $bestSellers = $this->_bestSellersCollectionFactory->create()->setPeriod('month');
            $bestSellers->addFieldToFilter('product_id', ['in'=>$bestSellerReferenceId]);
            if ($bestSellers->count()) {
                foreach ($bestSellers as $bestSeller) {
                    $bestSellerProductId = $bestSeller->getProductId();
                    $productArray[$bestSellerProductId]['best_seller'] = $bestSellerProductId;
                }
            }
        }
        return $productArray;
    }

    /**
     * Return frequently ordered product id
     *
     * @param array $customAttributes
     * @return mixed|string
     */
    public function getFrequentlyOrdered($customAttributes)
    {
        $frequentlyOrderedProductId = '';
        if (isset($customAttributes['bat_frequently_ordered'])) {
            $frequentlyOrderedProductId = $customAttributes['bat_frequently_ordered']->getValue();
        }
        return $frequentlyOrderedProductId;
    }

    /**
     * Check if area code matches on the request
     *
     * @param string $areaCode
     * @param array $bestSellerReferenceId
     * @param array $productData
     * @param Int $productId
     * @return mixed
     */
    public function checkMatchingAreaCode($areaCode, $bestSellerReferenceId, $productData, $productId)
    {
        if (isset($productData['bat_product_area_code'])) {
            if ($areaCode == $productData['bat_product_area_code']) {
                $bestSellerReferenceId[] = $productId;
            }
        }
        return $bestSellerReferenceId;
    }
}
