<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\RequisitionList\Model\Resolver\RequisitionList;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Bat\RequisitionList\Model\ResourceModel\RequisitionListItemAdmin\CollectionFactory;

class GetAdminRequisitionList
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**

     * @var CollectionFactory
     */
    private $collection;

    /**

     * @param ProductRepositoryInterface $productRepository
     * @param CollectionFactory $collection
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        CollectionFactory $collection
    ) {
        $this->productRepository = $productRepository;
        $this->collection = $collection;
    }

    /**
     * Get Admin Rl Product Function

     * @param int $requisitionListId
     * Getting AdminRL Product first name and total product count
     */
    public function getadminRequisitionProduct(int $requisitionListId): array
    {
        $requisitionListItems = $this->collection->create()
            ->addFieldToFilter('requisition_list_id', $requisitionListId);
        if (!$requisitionListItems->count()) {
            return [
                'product_count' => 0
            ];
        }
        $firstList = $requisitionListItems->getFirstItem();
        $product = $this->productRepository->getById($firstList->getProductId());
        return [
            'name' => $product->getName(),
            'product_count' => $requisitionListItems->count()
        ];
    }
}
