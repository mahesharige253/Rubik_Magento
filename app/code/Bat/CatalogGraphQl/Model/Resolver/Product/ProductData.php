<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\CatalogGraphQl\Model\Resolver\Product;

use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku;
use Magento\Catalog\Model\CategoryFactory;
use Psr\Log\LoggerInterface;

/**
 * Products field resolver, used for GraphQL request processing.
 */
class ProductData implements ResolverInterface
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku
     */
    protected $getSalableQuantityDataBySku;

    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    protected $_categoryFactory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param ProductRepositoryInterface $productRepository
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param GetSalableQuantityDataBySku $getSalableQtyDataBySku
     * @param CategoryFactory $categoryFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        GetSalableQuantityDataBySku $getSalableQtyDataBySku,
        CategoryFactory $categoryFactory,
        LoggerInterface $logger
    ) {
        $this->productRepository = $productRepository;
        $this->_storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->getSalableQuantityDataBySku = $getSalableQtyDataBySku;
        $this->_categoryFactory = $categoryFactory;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        \Magento\Framework\GraphQl\Config\Element\Field $field,
        $context,
        \Magento\Framework\GraphQl\Schema\Type\ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        try {
            $arr = [];
            $areaCode = $value['area_code'];
            foreach ($value['items'] as $key => $dataVal) {

                $imageEncodeUrl = '';
                $saleableQty = '';

                $productTags = [];
                if (isset($dataVal['product_tag'])) {
                    $productTags = explode(',', $dataVal['product_tag']);
                }
                $productData = $this->productRepository->get($dataVal['sku']);

                if (!empty($productData->getImage())) {
                    $imagePath = $productData->getImage();
                    $imageUrl = $this->getMediaUrl() .$imagePath;
                    $imageEncodeUrl = base64_encode($imageUrl);
                }

                $suffix = $this->scopeConfig->getValue('catalog/seo/product_url_suffix', ScopeInterface::SCOPE_STORE);
                $getUrlKey = '/product/'.$productData->getUrlKey().$suffix;

                $stockStatusArr = $productData->getQuantityAndStockStatus();
                $stockStatus = $stockStatusArr['is_in_stock'];
                if ($stockStatus) {
                    $isInStock = __('In Stock');
                } else {
                    $isInStock = __('Out of Stock');
                }

                $salableQtyData = $this->getSalableQuantityDataBySku->execute($productData->getSku());
                if (isset($salableQtyData[0])) {
                    $saleableQty = $salableQtyData[0]['qty'];
                }

                $categoryData = [];
                $categoryVal = '';
                $suffix = $this->scopeConfig->getValue('catalog/seo/product_url_suffix', ScopeInterface::SCOPE_STORE);
                $catIds = $productData->getCategoryIds();
                foreach ($catIds as $catId) {
                    $category = $this->_categoryFactory->create()->load($catId);
                    $categoryData[] = ['url' => '/category/'.$category->getUrlPath().$suffix,
                     'label'=> $category->getName()];
                }
                $categoryVal = $categoryData;

                $productTag = $productData->getProductTag();
                if ($productTag != '' && $productTag != 0) {
                    $productTag = explode(',', $productTag);
                    $productTags = $this->mergeArrays($productTags, $productTag);
                }
                sort($productTags);
                if ($areaCode != '') {
                    $batAreaCode = $productData->getBatProductAreaCode();
                    if ($areaCode != $batAreaCode) {
                        if (($key = array_search(3, $productTags)) !== false) {
                            unset($productTags[$key]);
                        }
                    }
                }

                $defaultTextAttributeVal = '';
                $attributeLabel = '';
                $selectedAttributeVal = '';
                $attributeCode = $productData->getBatDefaultAttribute();

                $attribute = $productData->getResource()->getAttribute($attributeCode);
                if ($attribute) {
                    if (in_array(
                        $productData->getResource()->getAttribute($attributeCode)->getFrontendInput(),
                        ['select']
                    )) {
                        $selectedAttributeVal = $productData->getAttributeText($attributeCode);
                        $attributeLabel = $productData->getResource()->getAttribute($attributeCode)->getFrontendLabel();
                    } else {
                        $selectedAttributeVal = $productData->getData($attributeCode);
                    }
                }
                if ($selectedAttributeVal != '') {
                    $defaultTextAttributeVal =  $attributeLabel.': '.$selectedAttributeVal;
                }
                $arrData = [
                'name' => $productData->getName(),
                'sku' => $dataVal['sku'],
                'image' => $imageEncodeUrl,
                'product_url' => $getUrlKey,
                'stock_status' => $isInStock,
                'product_tags' => $this->getProductTags($productTags),
                'default_attribute' => $defaultTextAttributeVal,
                'price' => (int)$productData->getPrice(),
                'quantity' => $saleableQty,
                'category' => $categoryVal
                ];
                $arr[] = $arrData;
            }

            $value['items'] = $arr;
            return $value['items'];

        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            throw new GraphQlNoSuchEntityException(
                __('Something wrong: '.$e->getMessage()),
                $e
            );
        }
    }

     /**
      * Get Media Url
      */
    public function getMediaUrl()
    {
        $prodPath = 'catalog/product';
        return $this->_storeManager->getStore()
        ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA).$prodPath;
    }

    /**
     * Get Product Tags
     *
     * @param string $productTags
     * @return array
     */
    public function getProductTags($productTags)
    {
        $productTagsResult = [
            'new' => false,
            'limited' => false,
            'hot' => false,
            'frequent' => false
        ];
        foreach ($productTags as $productTag) {
            if ($productTag == 1) {
                $productTagsResult['new'] = true;
            }
            if ($productTag == 2) {
                $productTagsResult['limited'] = true;
            }
            if ($productTag == 3) {
                $productTagsResult['hot'] = true;
            }
            if ($productTag == 4) {
                $productTagsResult['frequent'] = true;
            }
        }
        return $productTagsResult;
    }

    /**
     * Merge arrays
     *
     * @param array $productTags
     * @param array $productTag
     * @return array
     */
    public function mergeArrays($productTags, $productTag)
    {
        return array_unique(array_merge($productTag, $productTags));
    }
}
