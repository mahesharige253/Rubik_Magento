<?php
namespace Bat\PriceTagsGraphQl\Plugin\Quote;
 
class ConvertToOrderItem
{

    /**
     * Around convert
     *
     * @param \Magento\Quote\Model\Quote\Item\ToOrderItem $subject
     * @param callable $proceed
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @param array $additional
     * @return \Magento\Sales\Model\Order\Item
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundConvert(
        \Magento\Quote\Model\Quote\Item\ToOrderItem $subject,
        \Closure $proceed,
        \Magento\Quote\Model\Quote\Item\AbstractItem $item,
        $additional = []
    ) {
              $orderItem = $proceed($item, $additional);
              $orderItem->setIsPriceTag($item->getIsPriceTag());
              return $orderItem;
    }
}
