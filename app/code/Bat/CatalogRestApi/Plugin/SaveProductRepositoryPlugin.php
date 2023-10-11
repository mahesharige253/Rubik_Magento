<?php
namespace Bat\CatalogRestApi\Plugin;

use Magento\Catalog\Model\ProductRepository;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Framework\App\State;

class SaveProductRepositoryPlugin
{
    
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var ProductFactory
     */
    protected $productFactory;

    /**
     * @var State
     */
    protected $state;

    /**
     * RestApi constructor
     *
     * @param Request $request
     * @param ProductFactory $productFactory
     * @param State $state
     */
    public function __construct(
        Request $request,
        ProductFactory $productFactory,
        State $state
    ) {
        $this->request = $request;
        $this->productFactory = $productFactory;
        $this->state = $state;
    }
    
    /**
     * RestApi constructor
     *
     * @param ProductRepository $subject
     * @param object $product
     * @param boolen|null $requestInfo
     * @return array
     */
    public function beforeSave(
        ProductRepository $subject,
        $product,
        $requestInfo = null
    ) {
         
        if ($this->state->getAreaCode() == 'webapi_rest') {
            if (($this->request->getRequestUri() == '/rest/all/V1/products') ||
                $this->request->getRequestUri() == '/rest/all/V1/products/') {
                $productFactory = $this->productFactory->create();
                $requestData = $this->request->getBodyParams();
                if (isset($requestData['product']['attribute_set_id'])) {
                    $product->setAttributeSetId($requestData['product']['attribute_set_id']);
                } else {
                    $product->setAttributeSetId($productFactory->getDefaultAttributeSetId());
                }
                if (isset($requestData['product']['price'])) {
                    $product->setPrice($requestData['product']['price']);
                } else {
                    $product->setPrice(0);
                }
            }
        }
        return [$product, $requestInfo];
    }
}
