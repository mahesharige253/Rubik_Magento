<?php
namespace Bat\CatalogRestApi\Plugin;

use Magento\Catalog\Model\Product\Price\BasePriceStorage;
use Magento\Catalog\Model\Product\Action as ProductAction;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;

class UpdateBasePriceStoragePlugin
{
    /**
     * UpdateBasePriceStoragePlugin constructor
     *
     * @param CollectionFactory $collection
     * @param ProductAction $action
     */
    public function __construct(
        CollectionFactory $collection,
        ProductAction $action
    ) {
        $this->productCollection = $collection;
        $this->productAction = $action;
    }

    /**
     * Update custom attribute values
     *
     * @param BasePriceStorage $subject
     * @param object $result
     * @param array|BasePriceInterface[] $prices
     */
    public function afterUpdate(BasePriceStorage $subject, $result, array $prices)
    {
        
        foreach ($prices as $price) {
            $attributesArray = [];
            if ($price->getExtensionAttributes()->getBatchId()) {
                $attributesArray['batch_id'] = $price->getExtensionAttributes()->getBatchId();
            }
            if ($price->getExtensionAttributes()->getCompanyCode()) {
                $attributesArray['company_code'] = $price->getExtensionAttributes()->getCompanyCode();
            }
            if ($price->getExtensionAttributes()->getCreatedAt()) {
                 $attributesArray['created_at'] = $price->getExtensionAttributes()->getCreatedAt();
            }
            if ($price->getExtensionAttributes()->getCountryCode()) {
                $attributesArray['country_code'] = $price->getExtensionAttributes()->getCountryCode();
            }
            if ($price->getExtensionAttributes()->getIdocReferenceNumber()) {
                $attributesArray['idoc_reference_number'] =
                    $price->getExtensionAttributes()->getIdocReferenceNumber();
            }
            if ($price->getExtensionAttributes()->getUom()) {
                $attributesArray['uom'] = $price->getExtensionAttributes()->getUom();
            }
            if ($price->getExtensionAttributes()->getPriceGroupNumber()) {
                $attributesArray['price_group_number'] = $price->getExtensionAttributes()->getPriceGroupNumber();
            }
            if ($price->getExtensionAttributes()->getCustomerGroupCode()) {
                $attributesArray['customer_group_code'] = $price->getExtensionAttributes()->getCustomerGroupCode();
            }
            if ($price->getExtensionAttributes()->getCustomerGroupId()) {
                $attributesArray['customer_group_id'] = $price->getExtensionAttributes()->getCustomerGroupId();
            }
            if ($price->getExtensionAttributes()->getCurrencyCode()) {
                $attributesArray['currency_code'] = $price->getExtensionAttributes()->getCurrencyCode();
            }
            if ($price->getExtensionAttributes()->getEffectiveDate()) {
                $attributesArray['effective_date'] = $price->getExtensionAttributes()->getEffectiveDate();
            }
            if ($price->getExtensionAttributes()->getConditionSequence()) {
                $attributesArray['condition_sequence'] = $price->getExtensionAttributes()->getConditionSequence();
            }
            if ($price->getExtensionAttributes()->getCustomerGroup()) {
                $attributesArray['customer_group'] = $price->getExtensionAttributes()->getCustomerGroup();
            }
            if ($price->getExtensionAttributes()->getConditionName()) {
                $attributesArray['condition_name'] = $price->getExtensionAttributes()->getConditionName();
            }
            if ($price->getExtensionAttributes()->getConditionValue()) {
                $attributesArray['condition_value'] = $price->getExtensionAttributes()->getConditionValue();
            }
            if ($price->getExtensionAttributes()->getTotalPriceItem()) {
                $attributesArray['total_price_item'] = $price->getExtensionAttributes()->getTotalPriceItem();
            }
                  
              $collection = $this->productCollection->create()->addFieldToFilter('sku', $price->getSku());
            foreach ($collection as $item) {
                $this->productAction->updateAttributes([$item->getEntityId()], $attributesArray, 0);
            }
               
        }
          
        return $result;
    }
}
