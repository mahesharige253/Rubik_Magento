<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\CatalogGraphQl\Model\Resolver\DataProvider;

use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Bat\NewProduct\Model\ResourceModel\NewProductResource\CollectionFactory as NewProductsCollectionFactory;
use Magento\Sales\Model\ResourceModel\Report\Bestsellers\CollectionFactory as BestSellersCollectionFactory;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\CatalogInventory\Model\Stock\Status as StockStatus;

/**
 * Products field resolver, used for GraphQL request processing.
 */
class ProductItems
{
    /**
     * @var CollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var NewProductsCollectionFactory
     */
    private NewProductsCollectionFactory $newProductsCollectionFactory;

    /**
     * @var BestSellersCollectionFactory
     */
    protected $bestSellersCollectionFactory;

    /**
     * @var CategoryFactory
     */
    protected $categoryFactory;

    /**
     * @param CollectionFactory $productCollectionFactory
     * @param NewProductsCollectionFactory $newProductsCollectionFactory
     * @param BestSellersCollectionFactory $bestSellersCollectionFactory
     * @param CategoryFactory $categoryFactory
     */
    public function __construct(
        CollectionFactory $productCollectionFactory,
        NewProductsCollectionFactory $newProductsCollectionFactory,
        BestSellersCollectionFactory $bestSellersCollectionFactory,
        CategoryFactory $categoryFactory
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->newProductsCollectionFactory = $newProductsCollectionFactory;
        $this->bestSellersCollectionFactory = $bestSellersCollectionFactory;
        $this->categoryFactory = $categoryFactory;
    }

    /**
     * @inheritdoc
     */
    public function getProductData($categoryId, $pageSize, $currentPage, $frequentlyOrderedProductId)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/plp.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        
        $newProductsIds = [];
        $bestSellerProductIds = [];
        //Get New products id
        $newProductCollectionData = $this->newProductsCollectionFactory->create();
        foreach ($newProductCollectionData as $newProduct) {
            $catIdsArray = $newProduct->getCategoryId();
            $catArray = explode(',', $catIdsArray);
            if (in_array($categoryId, $catArray)) {
                $newProductsIds[] = $newProduct->getProductId();
            }
        }

        //To get the best seller products data
        $bestSellers = $this->bestSellersCollectionFactory->create()
            ->setPeriod('month');
        if (count($bestSellers) > 0) {
            foreach ($bestSellers as $bestSellerProduct) {
                $bestSellerProductIds[] = $bestSellerProduct->getProductId();
            }
        }
        $newProducts = [];
        $collectionNewProduct = $this->productCollectionFactory->create();
        $collectionNewProduct->addAttributeToSelect('*');
        $collectionNewProduct->addCategoriesFilter(['in' => $categoryId]);
        $collectionNewProduct->addAttributeToFilter('entity_id', ['in' => $newProductsIds]);
        $collectionNewProduct->addAttributeToFilter('status', Status::STATUS_ENABLED);
        $collectionNewProduct->addAttributeToFilter('visibility', ['neq' => Visibility::VISIBILITY_NOT_VISIBLE]);

        $newProducts = $collectionNewProduct->getData();
        $logger->info('New Products: '.count($newProducts));

        // Get Best Seller Products
        $collectionBestSellerProduct = $this->productCollectionFactory->create();
        $collectionBestSellerProduct->addAttributeToSelect('*');
        $collectionBestSellerProduct->addCategoriesFilter(['in' => $categoryId]);
        $collectionBestSellerProduct->addAttributeToFilter('entity_id', ['in' => $bestSellerProductIds]);
        $collectionBestSellerProduct->addAttributeToFilter('entity_id', ['nin' => $newProductsIds]);
        $collectionBestSellerProduct->addAttributeToFilter('status', Status::STATUS_ENABLED);
        $collectionBestSellerProduct->addAttributeToFilter('visibility', ['neq' => Visibility::VISIBILITY_NOT_VISIBLE]);

        $bestSellerProducts = $collectionBestSellerProduct->getData();
        $logger->info('Best Seller Products: '.count($bestSellerProducts));

        $newAndBestSellerdata = array_merge($newProducts, $bestSellerProducts);
        $logger->info('BestSeller and New Products: '.count($newAndBestSellerdata));

        $newAndBestSellerProductIds = array_merge($newProductsIds, $bestSellerProductIds);
        $logger->info('BestSeller and New Products IDs: '.count($newAndBestSellerProductIds));

        // Get Other products
        $category = $this->categoryFactory->create()->load($categoryId);
        $collectionOtherProduct = $this->productCollectionFactory->create();
        $collectionOtherProduct->addCategoryFilter($category);
        if (!empty($newAndBestSellerProductIds)) {
            $collectionOtherProduct->addAttributeToFilter('entity_id', ['nin' => $newAndBestSellerProductIds]);
        }
        $collectionOtherProduct->addAttributeToSort('position');

        $collectionOtherProduct->addAttributeToFilter('status', Status::STATUS_ENABLED);
        $collectionOtherProduct->addAttributeToFilter('visibility', ['neq' => Visibility::VISIBILITY_NOT_VISIBLE]);

        $otherProducts = $collectionOtherProduct->getData();
        $logger->info('Other Products: '.count($otherProducts));
        $data = array_merge($newAndBestSellerdata, $otherProducts);
        $offset = ($currentPage - 1) * $pageSize;
        $dataItems = array_slice($data, $offset, $pageSize);
        foreach ($dataItems as $key => $value) {
            $productTag = [];
            if (in_array($value['entity_id'], $bestSellerProductIds)) {
                $productTag[] = 3;
            }
            if ($value['entity_id'] == $frequentlyOrderedProductId) {
                $productTag[] = 4;
            }
            if (!empty($productTag)) {
                $dataItems[$key]['product_tag'] = implode(',', $productTag);
            }
        }
        $logger->info('Data Items: '.count($dataItems));
        return $dataItems;
    }
}
