<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\CatalogGraphQl\Model\Resolver\Product;

use Magento\Catalog\Model\ProductRepository;
use Psr\Log\LoggerInterface;

/**
 * @class ProductTag
 * return ProductTag attribute value
 */
class ProductTag implements \Magento\Framework\GraphQl\Query\ResolverInterface
{
    /**
     * @var ProductRepository
     */
    protected $_productRepository;
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param ProductRepository $productRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        ProductRepository $productRepository,
        LoggerInterface $logger
    ) {
        $this->_productRepository = $productRepository;
        $this->logger = $logger;
    }

    /**
     * Resolver for Product Tags
     *
     * @param \Magento\Framework\GraphQl\Config\Element\Field $field
     * @param \Magento\Framework\GraphQl\Query\Resolver\ContextInterface $context
     * @param \Magento\Framework\GraphQl\Schema\Type\ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return false[]
     */
    public function resolve(
        \Magento\Framework\GraphQl\Config\Element\Field $field,
        $context,
        \Magento\Framework\GraphQl\Schema\Type\ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $frequent = '';
        $bestSeller = '';
        $product = $value['model'];
        if (isset($value['frequent'])) {
            $frequent = $value['frequent'];
        }
        if (isset($value['best_seller'])) {
            $bestSeller = $value['best_seller'];
        }
        $data = $product->getProductTag();
        $result = [
            'new' => false,
            'limited' => false,
            'hot' => false,
            'frequent' => false
        ];
        if ($data != '') {
            $data = explode(',', $data);
            foreach ($data as $value) {
                if ($value == 1) {
                    $result['new'] = true;
                }
                if ($value == 2) {
                    $result['limited'] = true;
                }
            }
        }
        if ($bestSeller == $product->getId()) {
            $result['hot'] = true;
        }
        if ($frequent == $product->getId()) {
            $result['frequent'] = true;
        }
        return $result;
    }
}
