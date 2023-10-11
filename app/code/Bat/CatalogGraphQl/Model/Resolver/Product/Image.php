<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\CatalogGraphQl\Model\Resolver\Product;

use Magento\Catalog\Model\ProductRepository;
use Magento\Store\Model\StoreManagerInterface;

class Image implements \Magento\Framework\GraphQl\Query\ResolverInterface
{
    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    protected $_productRepository;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * Construct method
     *
     * @param ProductRepository $productRepository
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ProductRepository $productRepository,
        StoreManagerInterface $storeManager
    ) {
        $this->_productRepository = $productRepository;
        $this->_storeManager = $storeManager;
    }

    /**
     * Resolve method
     *
     * @param Field $field
     * @param Context $context
     * @param ResolveInfo $info
     * @param Array $value
     * @param Array $args
     */
    public function resolve(
        \Magento\Framework\GraphQl\Config\Element\Field $field,
        $context,
        \Magento\Framework\GraphQl\Schema\Type\ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $defaultAttributeVal = '';
        $attributeVal = '';
        $encodeUrl = '';
        $product = $value['model'];
        if (!empty($product->getImage())) {
            $imagePath = $product->getImage();
            $imageUrl = $this->getMediaUrl() .$imagePath;
            $encodeUrl = base64_encode($imageUrl);
        }
        return $encodeUrl;
    }

    /**
     * Get Media Url
     */
    public function getMediaUrl()
    {
        $prodPath = 'catalog/product';
        return $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA).$prodPath;
    }
}
