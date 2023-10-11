<?php

namespace Bat\RequisitionList\Plugin;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\RequisitionListGraphQl\Model\Resolver\RequisitionList\UpdateItems;
use Bat\GetCartGraphQl\Helper\Data;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Uid as IdEncoder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\RequisitionList\Model\RequisitionList\Items as RequisitionListItems;

class UpdateItemValidate
{
    /**
     * @var IdEncoder
     */
    private $idEncoder;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var RequisitionListItems
     */
    private $requisitionListItems;

    /**
     * @param IdEncoder $idEncoder
     * @param Data $helper
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param RequisitionListItems $requisitionListItems
     */
    public function __construct(
        IdEncoder $idEncoder,
        Data $helper,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequisitionListItems $requisitionListItems,
    ) {
        $this->idEncoder = $idEncoder;
        $this->helper = $helper;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->requisitionListItems = $requisitionListItems;
    }

    /**
     * Before remove product from RL
     *
     * @param UpdateItems $subject
     * @param Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array
     */
    public function beforeResolve(
        UpdateItems $subject,
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ): array {
        $requisitionListItemsId = $args['requisitionListItems'];
        $receivingQty = 0;
        foreach ($requisitionListItemsId as $itemsId) {
            $itemid[] = (int)$this->idEncoder->decode($itemsId['item_id']);
            $receivingQty += $itemsId['quantity'];
        }
        $requisitionListId = (int)$this->idEncoder->decode($args['requisitionListUid']);
        $rlItemsList = $this->getRequisitionListBySourceAndItemsId($itemid, $requisitionListId);
        $existingQty = 0;
        foreach ($rlItemsList as $item) {
            $existingQty += $item->getQty();
        }

        $finalQuantity = $existingQty + $receivingQty;
        if ($finalQuantity < $this->helper->getMinimumCartQty()) {
            throw new GraphQlInputException(
                __('Minimum RL quantity are required:'.$this->helper->getMinimumCartQty())
            );
        }

        if ($this->helper->getMaximumCartQty() < $finalQuantity) {
            throw new GraphQlInputException(
                __('Maximum RL quantity are allowed:'.$this->helper->getMaximumCartQty().' or less than.')
            );
        }
        
        return [$field, $context, $info, $value, $args];
    }

    /**
     * Get requisition list by source id and item ids
     *
     * @param array $itemIds
     * @param int $sourceId
     * @return array
     */
    private function getRequisitionListBySourceAndItemsId(array $itemIds, int $sourceId): array
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('requisition_list_id', $sourceId, 'eq')
            ->addFilter('item_id', $itemIds, 'neq')
            ->create();
        return $this->requisitionListItems->getList($searchCriteria)->getItems();
    }
}
