<?php
namespace Bat\Customer\Helper;

use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var CollectionFactory
     */
    protected $customerCollectionFactory;

    /**
     * @param CollectionFactory $customerCollectionFactory
     */
    public function __construct(
        CollectionFactory $customerCollectionFactory
    ) {
        $this->customerCollectionFactory = $customerCollectionFactory;
    }

    /**
     * Validate if outletid is already registered or not.
     *
     * @param string $outletId
     * @return array
     */
    public function isOutletIdValidCustomer($outletId)
    {
        $customer = '';
        $collection = $this->getCustomer('outlet_id', $outletId);
        if ($collection->getSize() > 0) {
            $customer = $collection->getFirstItem();
            return $customer;
        } else {
            return $customer;
        }
    }

    /**
     * Validate if outletpin is already registered or not.
     *
     * @param string $outletPin
     * @return array
     */
    public function isOutletPinValidCustomer($outletPin)
    {
        $customer = '';
        $collection = $this->getCustomer('outlet_pin', $outletPin);
        if ($collection->getSize() > 0) {
            $customer = $collection->getFirstItem();
            return $customer;
        } else {
            return $customer;
        }
    }

    /**
     * Getting customer collection.
     *
     * @param string $field
     * @param string $value
     * @return array
     */
    public function getCustomer($field, $value)
    {
        $collection = $this->customerCollectionFactory->create()
                   ->addAttributeToFilter($field, $value);
        return $collection;
    }
}
