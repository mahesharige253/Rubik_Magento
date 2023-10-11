<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\SalesGraphQl\Model;

use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Sales\Model\OrderRepository as OrderRepositoryModel;

/**
 * Orders data resolver
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class OrderCollectionByCustomer
{

    /**
     * @var CollectionFactory
     */
    protected $orderCollection;

    /**
     * @var OrderRepositoryModel
     */
    protected $orderRepositoryModel;

    /**
     * Contructor
     *
     * @param CollectionFactory $orderCollection
     * @param OrderRepositoryModel $orderRepositoryModel
     */
    public function __construct(
        CollectionFactory $orderCollection,
        OrderRepositoryModel $orderRepositoryModel
    ) {
        $this->orderCollection = $orderCollection;
        $this->orderRepositoryModel = $orderRepositoryModel;
    }
   
    /**
     * Get order collection by customer id
     *
     * @param String $customerId
     * @param Array $arguments
     */
    public function getOrderCollectionByCustomerId($customerId, $arguments)
    {
        $response = [];
        $resultItmes = [];
        $firstItemTitle = '';
        $collection = $this->orderCollection->create()
         ->addFieldToSelect('*')
         ->addAttributeToFilter('customer_id', $customerId);

        $filterStatus =[];
        if (isset($arguments['filter']['status']) && $arguments['filter']['status'] !='') {
            $filterStatus = explode(',', $arguments['filter']['status']);
            $collection->addFieldToFilter('status', ['in' => $filterStatus]);
        }
        if (isset($arguments['filter']['date_from']) && ($arguments['filter']['date_from'] !='')
            && isset($arguments['filter']['date_to']) && ($arguments['filter']['date_to'] !='')) {
            $collection->addFieldToFilter('created_at', ['gteq' => $arguments['filter']['date_from']]);
            $collection->addFieldToFilter('created_at', ['lteq' => $arguments['filter']['date_to']." 23:59:59"]);
        }
         $collection->setPageSize($arguments['pageSize']);
         $collection->setCurPage($arguments['currentPage']);
        if (isset($arguments['filter']['sort'])) {
            $collection->setOrder(
                'created_at',
                $arguments['filter']['sort']
            );
        }
        $collection->load();

        $collectionTotal = $this->orderCollection->create()
         ->addFieldToSelect('*')
         ->addAttributeToFilter('customer_id', $customerId);
        if (isset($arguments['filter']['status']) && $arguments['filter']['status'] !='') {
            $collectionTotal->addFieldToFilter('status', ['in' => $arguments['filter']['status']]);
        }
        if (isset($arguments['filter']['date_from']) && ($arguments['filter']['date_from'] !='')
            && isset($arguments['filter']['date_to']) && ($arguments['filter']['date_to'] !='')) {
            $collectionTotal->addFieldToFilter('created_at', ['gteq' => $arguments['filter']['date_from']]);
            $collectionTotal->addFieldToFilter(
                'created_at',
                ['lteq' => $arguments['filter']['date_to']." 23:59:59"]
            );
        }
        $collectionTotal->load();
        $totalOrders = count($collectionTotal);

        foreach ($collection as $key => $collectionData) {
            $result = [];
            $orderData = $this->orderRepositoryModel->get($collectionData['entity_id']);
            $orderItems = $orderData->getAllItems();
            $result['order_id'] = $collectionData['entity_id'];

            $createdDate = date("Y/m/d", strtotime($collectionData['created_at']));
            $result['created_at'] = $createdDate;
            $result['grand_total'] = $collectionData['grand_total'];
            $result['status'] = $collectionData['status'];
            foreach ($orderItems as $item) {
                $firstItemTitle = $item->getName();
                break;
            }
            $itemsCount = 0;
            if (count($orderItems) > 1) {
                $itemsCount = count($orderItems) - 1;
                $firstItemTitle = $firstItemTitle.' and '.$itemsCount.' more';
            }
            $result =  [
                  "id" => $collectionData['entity_id'],
                  "increment_id" => $collectionData['increment_id'],
                  "order_date" => $createdDate,
                  "status" => $collectionData['status'],
                  "item_name" => $firstItemTitle,
                  "grand_total" => $collectionData['grand_total'],
                  "order_type" => $collectionData['order_type']
               ];

            $resultItmes[] = $result;
        }
        $response['items'] = $resultItmes;
        $response['total_orders'] = $totalOrders;
        return $response;
    }
}
