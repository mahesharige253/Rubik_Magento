<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\RequisitionList\Model\Resolver;

use Exception;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\Resolver\Value;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\RequisitionList\Model\Config as ModuleConfig;
use Bat\RequisitionList\Model\RequisitionListAdminFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Bat\RequisitionList\Model\Resolver\RequisitionList\GetAdminRequisitionList;

/**
 * RequisitionList Resolver
 */
class RequisitionList implements ResolverInterface
{
    /**
     * @var ModuleConfig
     */
    private $moduleConfig;

    /**
     * @var GetAdminRequisitionList
     */
    private $getAdminRequisitionList;

    /**
     * @var ScopeConfigInterface
     */
    private $_scopeConfig;

    /**
     * @var RequisitionListAdminFactory
     */
    private $requisitionListAdminFactory;

    /**
     * @param ModuleConfig $moduleConfig
     * @param GetAdminRequisitionList $getAdminRequisitionList
     * @param ScopeConfigInterface $scopeConfig
     * @param RequisitionListAdminFactory $requisitionListAdminFactory
     */
    public function __construct(
        ModuleConfig $moduleConfig,
        GetAdminRequisitionList $getAdminRequisitionList,
        ScopeConfigInterface $scopeConfig,
        RequisitionListAdminFactory $requisitionListAdminFactory
    ) {
        $this->moduleConfig = $moduleConfig;
        $this->getAdminRequisitionList = $getAdminRequisitionList;
        $this->_scopeConfig = $scopeConfig;
        $this->requisitionListAdminFactory = $requisitionListAdminFactory;
    }

    /**
     * Fetches the data from persistence models and format it according to the GraphQL schema.
     *
     * @param Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return mixed|Value
     * @throws Exception
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameters)
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!$this->moduleConfig->isActive()) {
            throw new GraphQlInputException(__('Requisition List feature is not available.'));
        }
        $collection = $this->requisitionListAdminFactory->create()->getCollection();
        $records = $collection->getData();
        $adminLimit = $this->_scopeConfig->getValue(
            'requisitionlist_bat/requisitionlist/requisitionlist_admin',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
        );
        if (count($records) > 0) {
            $totalcount = count($records);
            $adminRl = [];
            $total = [
                'total_rl_count' => $totalcount,
                'admin_max_limit' => $adminLimit
            ];
            foreach ($records as $data) {
                $requisitionListId = (int) $data['entity_id'];
                $itemsData = $this->getAdminRequisitionList->getadminRequisitionProduct($requisitionListId);
                $arr = [
                    'uid' => $data['entity_id'],
                    'name' => $data['name'],
                    'first_product_name' => ($data['best_seller']) ? '' :$itemsData['name'],
                    'product_count' => $itemsData['product_count'],
                    'bestseller' => $data['best_seller'],
                ];
                $adminRl['items'][] = $arr;
            }

            $adminRl['total'] = $total;
            return $adminRl;
        } else {
            throw new GraphQlNoSuchEntityException(__('There is no Admin Requisition list '));
        }
    }
}
