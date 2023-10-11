<?php
declare(strict_types=1);

namespace Bat\GetCartGraphQl\Model\Cart;

use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\QuoteGraphQl\Model\Cart\AddSimpleProductToCart;
use Magento\QuoteGraphQl\Model\Cart\AddProductsToCart as NewAddProductsToCart;
use Magento\QuoteGraphQl\Model\Cart\GetCartProducts;
use Bat\GetCartGraphQl\Helper\Data;
use Magento\Checkout\Model\Cart;

/**
 * Adding products to cart using GraphQL
 */
class AddProductsToCart extends NewAddProductsToCart
{
    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var AddSimpleProductToCart
     */
    private $addProductToCart;

    /**
     * @var GetCartProducts
     */
    private $getCartProducts;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var Cart
     */
    protected $cart;

    /**
     * @param CartRepositoryInterface $cartRepository
     * @param AddSimpleProductToCart $addProductToCart
     * @param GetCartProducts $getCartProducts
     * @param Data $helper
     * @param Cart $cart
     */
    public function __construct(
        CartRepositoryInterface $cartRepository,
        AddSimpleProductToCart $addProductToCart,
        GetCartProducts $getCartProducts,
        Data $helper,
        Cart $cart
    ) {
        parent::__construct($cartRepository, $addProductToCart);
        $this->cartRepository = $cartRepository;
        $this->addProductToCart = $addProductToCart;
        $this->getCartProducts = $getCartProducts;
        $this->helper = $helper;
        $this->cart = $cart;
    }

    /**
     * Add products to cart
     *
     * @param Quote $cart
     * @param array $cartItems
     * @throws GraphQlInputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException
     */
    public function execute(Quote $cart, array $cartItems): void
    {
        // get array of all items what can be display directly
        $itemsVisible = $this->cart->getQuote()->getAllVisibleItems();
        $qty = 0;
        foreach ($itemsVisible as $item) {
            $qty = $qty + $item->getQty();
        }
        $requestQty = 0;
        foreach ($cartItems as $cartItem) {
            $requestQty = $requestQty + $cartItem['data']['quantity'];
        }
        $totalQty = $qty + $requestQty;
        if ($totalQty < $this->helper->getMinimumCartQty()) {
            throw new GraphQlInputException(
                __('Minimum cart cartons are required:'.$this->helper->getMinimumCartQty())
            );
        }
        if ($this->helper->getMaximumCartQty() < $totalQty) {
            throw new GraphQlInputException(
                __('Maximum cart cartons are allowed:'.$this->helper->getMaximumCartQty().' or less than.')
            );
        }
        foreach ($cartItems as $cartItemData) {
            $this->addProductToCart->execute($cart, $cartItemData);
        }
        $this->cartRepository->save($cart);
    }
}
