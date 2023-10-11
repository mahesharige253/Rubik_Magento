<?php

namespace Bat\BulkOrder\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;
use Magento\Quote\Api\GuestCartManagementInterface;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\DataObject\Factory;
use Magento\Quote\Model\QuoteManagement;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Quote\Model\QuoteIdToMaskedQuoteIdInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;

class CreateBulkCart extends AbstractModel
{

    /**
     * @var GuestCartManagementInterface
     */
    private $guestCart;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var MaskedQuoteIdToQuoteIdInterface
     */
    private $maskedQuoteIdToQuoteId;

    /**
     * @var FormKey 
     */
    private $formKey;

    /**
     * @var ProductRepository 
     */
    private $productRepository;

    /**
     * @var Factory 
     */
    private $dataObjectFactory;

    /**
     * @var QuoteManagement 
     */
    private $quoteManagement;

    /**
     * @var CollectionFactory
     */
    protected $customerCollectionFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var QuoteFactory
     */
    protected $quoteFactory;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var QuoteIdToMaskedQuoteIdInterface
     */
    private $quoteIdToMaskedQuoteId;

    /**
     * @var QuoteIdMaskFactory
     */
    protected $quoteIdMaskFactory;

    /**
     * Construct method
     * 
     * @param GuestCartManagementInterface $guestCart
     * @param CartRepositoryInterface $cartRepository
     * @param MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId
     * @param FormKey $formKey
     * @param ProductRepository $productRepository
     * @param Factory $dataObjectFactory
     * @param QuoteManagement $quoteManagement
     * @param CollectionFactory $customerCollectionFactory
     * @param StoreManagerInterface $storeManager
     * @param QuoteFactory $quoteFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param QuoteIdToMaskedQuoteIdInterface $quoteIdToMaskedQuoteId
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     */
    public function __construct(
        GuestCartManagementInterface $guestCart,
        CartRepositoryInterface $cartRepository,
        MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId,
        FormKey $formKey,
        ProductRepository $productRepository,
        Factory $dataObjectFactory,
        QuoteManagement $quoteManagement,
        CollectionFactory $customerCollectionFactory,
        StoreManagerInterface $storeManager,
        QuoteFactory $quoteFactory,
        CustomerRepositoryInterface $customerRepository,
        QuoteIdToMaskedQuoteIdInterface $quoteIdToMaskedQuoteId,
        QuoteIdMaskFactory $quoteIdMaskFactory
    ) {
        $this->guestCart = $guestCart;
        $this->cartRepository = $cartRepository;
        $this->maskedQuoteIdToQuoteId = $maskedQuoteIdToQuoteId;
        $this->formKey = $formKey;
        $this->productRepository = $productRepository;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->quoteManagement = $quoteManagement;
        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->storeManager = $storeManager;
        $this->quoteFactory = $quoteFactory;
        $this->customerRepository = $customerRepository;
        $this->quoteIdToMaskedQuoteId = $quoteIdToMaskedQuoteId;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
    }

     /**
     * Create customer quote.
     *
     * @param array $orderItems
     * @return array
     */
    public function createCart($orderItems) {
        $storeId = $this->storeManager->getStore()->getStoreId();
        $cartData = [];
        foreach($orderItems as $item) {
            $customerData = $this->getCustomer('outlet_id',$item['outlet_id']);
            $customer = $customerData->getFirstItem();
            $quoteData = $this->getCustomerQuote($customer->getId(),$storeId);
            $customerQuote = $this->quoteManagement->createEmptyCartForCustomer($customer->getId());
            $this->addProductToCart($customerQuote,$item['items']); 
            $cartData[$item['outlet_id']] = $this->createMaskId($customerQuote);
        }
        return $cartData;
    }

     /**
     * Creating empty cart.
     *
     * @return String
     */
    public function createEmptyCart() {
        return $this->guestCart->createEmptyCart();
    }

    /**
     * Adding products to cart.
     *
     * @param string $cartId
     * @param array $productItems
     * @return array
     */
    public function addProductToCart($cartId,$productItems) {
        $cart = $this->cartRepository->get($cartId);
        foreach($productItems as $product) {
            $productData = $this->productRepository->get($product['sku']);
            $params = [
                'form_key' => $this->formKey->getFormKey(),
                'product' => $productData->getId(),
                'qty'   => $product['quantity']
            ];
            $cart->addProduct(
                $productData,
                $this->dataObjectFactory->create($params)
            );
            $this->cartRepository->save($cart);
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
        return $this->customerCollectionFactory->create()
                   ->addAttributeToFilter($field, $value);
    }

    /**
     * Check customer active quote and make inactive.
     *
     * @param string $customerId
     * @param string $storeId
     * @return Int|String
     */
    public function getCustomerQuote($customerId, $storeId) {
        try {
            $quoteData = $this->quoteManagement->getCartForCustomer($customerId);
            $quoteData->setIsActive(0);
            $quoteData->save();
            return $quoteData->Id();
            
        } catch(\Exception $e) {
            return '';
        }
    }  

    /**
     * Check customer active quote and make inactive.
     *
     * @param Int $quoteId
     * @return String
     */
    public function createMaskId($quoteId) {
        $quoteIdMask = $this->quoteIdMaskFactory->create();
        $quoteIdMask->setQuoteId($quoteId)->save();
        return $quoteIdMask->getMaskedId();
    }

}