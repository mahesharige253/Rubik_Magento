<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\RequisitionList\Model\Resolver\RequisitionList;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Bat\RequisitionList\Model\ResourceModel\RequisitionListItemAdmin\Collection;

class GetAdminRequisitionListItems
{

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**

     * @var Collection
     */
    private $collection;

     /**

      * @param ProductRepositoryInterface $productRepository
      * @param CollectionFactory $collection
      */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        Collection $collection
    ) {
        $this->productRepository = $productRepository;
        $this->collection = $collection;
    }

    /**
     * Get Admin Rl Product Function

     * @param array $requisitionListIds
     * Getting AdminRL Items Data
     */
    public function getadminRequisitionListData(array $requisitionListIds): array
    {
        $requisitionListItems = $this->collection->
            addFieldToFilter('requisition_list_id', ['in' => $requisitionListIds]);
        if (!$requisitionListItems->count()) {
            return [];
        }
        $result = [];
        $data = $requisitionListItems->getData();
        foreach ($data as $requisitionListItem) {
            // $productArray = [];
            $requesitionItem = [];
            $product = $this->productRepository->getById($requisitionListItem['product_id']);
            $quantity = $requisitionListItem['qty'];
            $productData = $product->getData();
            $price = $product->getPrice();
            $subtotal = ($price * $quantity);
            $productId = $product->getId();
            $requesitionItem['subtotal'] = $subtotal;
            $requesitionItem['quantity'] = $quantity;
            $productData['model'] = $product;
            $requesitionItem['adminitemsdata'] =  $productData;
            $result[] = $requesitionItem;
        }
        return $result;
    }
}
