<?php

namespace Bat\BulkOrder\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Catalog\Model\ProductRepository;
use Bat\GetCartGraphQl\Helper\Data;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Bat\QuoteGraphQl\Helper\Data as PlaceOrderHelper;
use Bat\CustomerBalanceGraphQl\Helper\Data as CustomerBalanceHelper;

class ValidateCSVdata extends AbstractModel
{

    /**
     * @var CollectionFactory
     */
    protected $customerCollectionFactory;

    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    protected $_productRepository;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var StockRegistryInterface
     */
    private $stockRegistry;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var PlaceOrderHelper
     */
    private PlaceOrderHelper $placeOrderHelper;

    /**
     * @var CustomerBalanceHelper
     */
    private CustomerBalanceHelper $customerBalanceHelper;

    /**
     * @param CollectionFactory $customerCollectionFactory
     * @param ProductRepository $productRepository
     * @param Data $helper
     * @param StockRegistryInterface $stockRegistry
     * @param CustomerRepositoryInterface $customerRepository
     * @param PlaceOrderHelper $placeOrderHelper
     * @param CustomerBalanceHelper $customerBalanceHelper
     */
    public function __construct(
        CollectionFactory $customerCollectionFactory,
        ProductRepository $productRepository,
        Data $helper,
        StockRegistryInterface $stockRegistry,
        CustomerRepositoryInterface $customerRepository,
        PlaceOrderHelper $placeOrderHelper,
        CustomerBalanceHelper $customerBalanceHelper
    ) {
        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->_productRepository = $productRepository;
        $this->helper = $helper;
        $this->stockRegistry = $stockRegistry;
        $this->customerRepository = $customerRepository;
        $this->placeOrderHelper =  $placeOrderHelper;
        $this->customerBalanceHelper = $customerBalanceHelper;
    }

    /**
     * Validate Outlet Data
     *
     * @param array $orderDetails
     */
    public function execute($orderDetails)
    {

        $validateData = $validateData['message'] = [];
        $validateData['status'] = 'success';

        foreach ($orderDetails as $orderData) {
            $outletId = $orderData['outlet_id'];
            $is_parent = $orderData['is_parent'];
            
            $validOutlet = $this->isOutletIdValidCustomer($outletId);
            if ($validOutlet != 'success') {
                $validateData['message'][] = $validOutlet;
            } else {
            
                $isParent = $this->isParentOutlet($outletId);
                if ($is_parent && (!$isParent)) {
                    $validateData['message'][] = __('OutLetId '.$outletId.' is not Parent outlet');
                }
            
                $parentOutletId = $orderData['parent_outlet_id'];
            
                $isChild = $this->isParentOutlet($outletId);
                if (!$is_parent && (!$isChild)) {
                    $childOutletData = $this->getChildOutlet($parentOutletId);
                    if (!in_array($outletId, $childOutletData)) {
                        $validateData['message'][] = __('OutLetId '.$outletId.' is not Child outlet');
                    }
                }

                $items = $orderData['items'];
                $quantity = 0;
                foreach ($items as $item) {
                    $skuValid = $this->isSkuExist($item['sku']);
                    if (!$skuValid) {
                        $validateData['message'][] = 'Sku '.
                        $item['sku'].' is not valid given under outlet Id '.$outletId;
                    } else {
                        $isInStock = $this->isSkuInStock($item['sku']);
                        if ($isInStock != 'In Stock') {
                            $validateData['message'][] = 'Sku '.
                            $item['sku'].' is out of stock given under outlet Id '.$outletId;
                        } else {
                            $quantity += $item['quantity'];
                        }
                    }
                
                }
                if ($quantity != 0) {
                    $isValidate = $this->validateQuantity($quantity, $outletId);

                    if ($isValidate != 'success') {
                        $validateData['message'][] = $isValidate;
                    }
                }

                $getOrderFrequencyStatus = $this->getOrderFrequencyData($outletId);

                if ($getOrderFrequencyStatus != '') {
                    $validateData['message'][] = $getOrderFrequencyStatus;
                }

                $getPaymentOverDue = $this->getOverDueData($outletId);

                if ($getPaymentOverDue != '') {
                    $validateData['message'][] = $getPaymentOverDue;
                }
            }
        }

        if (array_key_exists('message',$validateData) && count($validateData['message']) > 0) {
            $validateData['status'] = 'failed';
        }
        return $validateData;
    }

     /**
      * Validate if outletid is already registered or not.
      *
      * @param string $outletId
      * @return string
      */
    public function isOutletIdValidCustomer($outletId)
    {
        $customerStatus = 'success';
        $collection = $this->getCustomer('outlet_id', $outletId);
        if ($collection->getSize() > 0) {
            $customer = $collection->getFirstItem();
            $customerDetatils = $this->customerRepository->getById($customer->getId());
            $approvalStatus = '';
            if (!empty($customerDetatils->getCustomAttribute('approval_status'))) {
                $approvalStatus = $customerDetatils->getCustomAttribute('approval_status')->getValue();
            }

            if ($approvalStatus != 1) {
                $customerStatus = 'The outlet '.$outletId.' is not approved outlet';
            }

            $disapprovalStatus = '';
            if (!empty($customerDetatils->getCustomAttribute('disclosure_approval_status'))) {
                $disapprovalStatus = $customerDetatils->getCustomAttribute('disclosure_approval_status')->getValue();
            }

            if ($disapprovalStatus == 2) {
                $customerStatus = 'The outlet '.$outletId.' account is disclosed';
            }

        } else {
            $customerStatus = 'The outlet '.$outletId.' is not valid';
        }

        return $customerStatus;
    }

    /**
     * Validate if outletid is parent or not.
     *
     * @param string $outletId
     * @return boolean
     */
    public function isParentOutlet($outletId)
    {
        $customer = '';
        $collection = $this->getCustomer('outlet_id', $outletId);
        
        $customer = $collection->getFirstItem();
        $customerDetatils = $this->customerRepository->getById($customer->getId());
        $parentId = '';
        if (!empty($customerDetatils->getCustomAttribute('is_parent'))) {
            $parentId = $customerDetatils->getCustomAttribute('is_parent')->getValue();
        }

        return ($parentId == 1) ? true : false;
    }

    /**
     * Get Parent outlet Id.
     *
     * @param string $outletId
     * @return string
     */
    public function getParentOutlet($outletId)
    {
        $customer = '';
        $collection = $this->getCustomer('outlet_id', $outletId);
        
        $customer = $collection->getFirstItem();
        $parentId = $customer->getParentOutletId();

        return $parentId;
    }

    /**
     * Get Child outlets.
     *
     * @param string $parentOutletId
     * @return array
     */
    public function getChildOutlet($parentOutletId)
    {
        $childOutlet = [];
        $collection = $this->getCustomer('parent_outlet_id', $parentOutletId);

        if ($collection->getSize() > 0) {
            foreach ($collection as $data) {
                    $childOutlet[] = $data->getOutletId();
            }
        }
        return $childOutlet;
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
        return $this->customerCollectionFactory->create()
                   ->addAttributeToFilter($field, $value);
    }

    /**
     * Validate if sku is valid or not.
     *
     * @param string $sku
     * @return boolean
     */
    public function isSkuExist($sku)
    {

        try {
            $productData = $this->_productRepository->get($sku);
            return true;
        } catch (\Exception $e) {
              return false;
        }
    }

    /**
     * Validate if sku is in stock or not.
     *
     * @param string $sku
     * @return boolean
     */
    public function isSkuInStock($sku)
    {
        $productData = $this->_productRepository->get($sku);
        $stockStatus = $this->getStockStatus($productData->getId());
        return ($stockStatus) ? __('In Stock') : __('Out of Stock');
    }

    /**
     * Validate if quantity is valid or not.
     *
     * @param string $quantity
     * @param string $outletId
     * @return string
     */
    public function validateQuantity($quantity, $outletId)
    {
        
        $validateText = 'success';
        if ($quantity < $this->helper->getMinimumCartQty()) {
            $validateText = 'For OutletId : '.$outletId.
            ' Minimum RL quantity are required:'.$this->helper->getMinimumCartQty();
        }
        if ($this->helper->getMaximumCartQty() < $quantity) {
            $validateText = 'For OutletId : '.$outletId.
            ' Maximum RL quantity are allowed:'.$this->helper->getMaximumCartQty().' or less than.';
        }

        return $validateText;
    }

    /**
     * Validate if outlet is valid for placing order.
     *
     * @param string $outletId
     * @return string
     */
    public function getOrderFrequencyData($outletId)
    {
        $orderFrequencyMessage = '';
        $collection = $this->getCustomer('outlet_id', $outletId);
        $customer = $collection->getFirstItem();
        $customerDetatils = $this->customerRepository->getById($customer->getId());
        $orderFrequency = $this->placeOrderHelper->canPlaceOrder($customerDetatils);
        if (!$orderFrequency) {
            $orderFrequencyMessage =  "Order frequency exceeded for the outlet Id ".$outletId;
        }
        return $orderFrequencyMessage;
    }

    /**
     * Validate if outlet is valid for placing order.
     *
     * @param string $outletId
     * @return string
     */
    public function getOverDueData($outletId)
    {

        $collection = $this->getCustomer('outlet_id', $outletId);
        $customer = $collection->getFirstItem();
        $customerDetatils = $this->customerRepository->getById($customer->getId());
        
        $isCreditCustomer = false;
        if (!empty($customerDetatils->getCustomAttribute('is_credit_customer'))) {
            $isCreditCustomer = $customerDetatils->getCustomAttribute('is_credit_customer')->getValue();
        }

        if ($isCreditCustomer) {
            $totalArLimit = $this->getTotalArLimit($customer);
            $creditDue = $this->getCreditCustomerDue($customer->getId(), $totalArLimit);
            if ($creditDue) {
                return 'For Outlet Id :'.$outletId.' '. $this->placeOrderHelper->getOverDueMessage();
            }
        } else {
            if ($this->placeOrderHelper->checkPaymentOverDue($customer->getId())) {
                return 'For Outlet Id :'.$outletId.' '.$this->placeOrderHelper->getOverDueMessage();
            }
        }
        
        return '';
    }

    /**
     * Get Stock status
     *
     * @param int $productId
     * @return bool|int
     * return stock status of a product
     */
    public function getStockStatus($productId)
    {
        $stockItem = $this->stockRegistry->getStockItem($productId);
        $isInStock = $stockItem ? $stockItem->getIsInStock() : false;
        return $isInStock;
    }

    /**
     * Return Total AR Limit
     *
     * @param string $customer
     * @return int
     */
    public function getTotalArLimit($customer)
    {
        $totalARLimit = 0;
        if ($customer->getCustomAttribute('total_ar_limit') !='') {
            $totalARLimit = $customer->getCustomAttribute('total_ar_limit')->getValue();
        }
        return $totalARLimit;
    }

    /**
     * Get Credit Customer Remaining Due
     *
     * @param int $customerId
     * @param float|int $totalArLimit
     * @return string
     */
    public function getCreditCustomerDue($customerId, $totalArLimit)
    {
        $totalDue = 0;
        $order = $this->customerBalanceHelper->getCustomerOrder($customerId);
        foreach ($order as $orderItem) {
            $totalDue = $totalDue + $orderItem['total_due'];
        }
        
        if ($totalDue > $totalArLimit) {
            return true;
        }

        return false;
    }
}
