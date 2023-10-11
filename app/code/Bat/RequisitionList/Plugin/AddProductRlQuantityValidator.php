<?php

namespace Bat\RequisitionList\Plugin;

use Magento\RequisitionList\Api\Data\RequisitionListInterface;
use Magento\RequisitionListGraphQl\Model\RequisitionList\Item\AddItemsToRequisitionList;
use Magento\RequisitionList\Model\RequisitionListRepository;
use Bat\GetCartGraphQl\Helper\Data;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;

class AddProductRlQuantityValidator
{

    /**
     * @var RequisitionListRepository
     */
    private $repository;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @param RequisitionListRepository $repository
     * @param Data $helper
     */
    public function __construct(
        RequisitionListRepository $repository,
        Data $helper
    ) {
        $this->repository = $repository;
        $this->helper = $helper;
    }

    /**
     * Before Add product to RL
     *
     * @param AddItemsToRequisitionList $subject
     * @param RequisitionListInterface $requisitionList
     * @param array $items
     * @return array
     */
    public function beforeExecute(
        AddItemsToRequisitionList $subject,
        RequisitionListInterface $requisitionList,
        array $items
    ): array {
        $requisitionListId = (int)$requisitionList->getId();
        $totalReceivedQty = 0;
        foreach ($items as $item) {
            $totalReceivedQty += $item->getQuantity();
        }

        $ListData = $this->repository->get($requisitionListId);
        $existingQty = 0;
        if ($ListData->getItems()) {
            foreach ($ListData->getItems() as $item) {
                $existingQty += (int)$item->getQty();
            }
        }

        $totalAvailQty = $totalReceivedQty + $existingQty;

        if ($totalAvailQty < $this->helper->getMinimumCartQty()) {
            throw new GraphQlInputException(
                __('Minimum RL quantity are required:'.$this->helper->getMinimumCartQty())
            );
        }
        if ($this->helper->getMaximumCartQty() < $totalAvailQty) {
            throw new GraphQlInputException(
                __('Maximum RL quantity are allowed:'.$this->helper->getMaximumCartQty().' or less than.')
            );
        }
        
        return [$requisitionList, $items];
    }
}
