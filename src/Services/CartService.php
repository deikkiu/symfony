<?php

namespace App\Services;

use App\Dto\Cart;
use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class CartService
{
	public function __construct(
		protected RequestStack        $stack,
		protected ProductRepository   $productRepository
	)
	{
	}

	public function getCart(): Cart
	{
		$cart = $this->getCartFromSession();
		$this->validateCart($cart);
		return $cart;
	}

	public function addProduct(int $id, int $quantity): void
	{
		$cart = $this->getCartFromSession();
		$this->updateCartWithProduct($cart, $id, $quantity);
		$this->saveCartToSession($cart);
		$this->addFlashMessage('success', 'Product added to cart!');
	}

	public function deleteProduct(int $id): void
	{
		$cart = $this->getCartFromSession();
		$this->removeProductFromCart($cart, $id);
		$this->saveCartToSession($cart);
		$this->addFlashMessage('success', 'Product deleted from cart!');
	}

	private function validateCart(Cart $cart): void
	{
		$products = $cart->getProducts();

		if (!empty($products)) {
			foreach ($products as $cartProduct) {
				$product = $this->productRepository->find($cartProduct['id']);

				if (!$product) {
					$products = array_filter($cart->getProducts(), fn($p) => $p['id'] !== $cartProduct['id']);
					$cart->setProducts($products);
					$cart->setQuantity($cart->getQuantity() - $cartProduct['quantity']);
					$this->addFlashMessage('notice', "The store has run out of product with id: {$cartProduct['id']}. We apologize for the inconvenience!");
				}
			}
		}

		$this->countTotalPrice($cart);
	}

	private function updateCartWithProduct(Cart $cart, int $id, int $quantity): void
	{
		$products = $cart->getProducts();
		[$products, $addedQuantity] = $this->setQuantity($products, $id, $quantity);

		$cart->setProducts($products);
		$cart->setQuantity($cart->getQuantity() + $addedQuantity);
	}

	private function removeProductFromCart(Cart $cart, int $id): void
	{
		$products = $cart->getProducts();

		if (isset($products[$id])) {
			if ($products[$id]['quantity'] > 1) {
				$products[$id]['quantity'] -= 1;
			} else {
				unset($products[$id]);
			}

			$cart->setProducts($products);
			$cart->setQuantity($cart->getQuantity() - 1);
		}
	}

	private function countTotalPrice(Cart $cart): void
	{
		$totalPrice = 0;

		foreach ($cart->getProducts() as $cartProduct) {
			$product = $this->productRepository->find($cartProduct['id']);
			$totalPrice += $product->getPrice() * $cartProduct['quantity'];
		}

		$cart->setTotalPrice($totalPrice);
	}

	private function setQuantity(array $products, int $id, int $quantity): array
	{
		$productAmount = $this->productRepository->find($id)->getAmount();
		$addedQuantity = 0;

		if (isset($products[$id])) {
			$productQuantity = min($products[$id]['quantity'] + $quantity, $productAmount);
			$products[$id]['quantity'] = $productQuantity;
			$addedQuantity = $productQuantity;
		} else {
			$products[$id] = ['id' => $id, 'quantity' => min($quantity, $productAmount)];
			$addedQuantity = $products[$id]['quantity'];
		}

		return [$products, $addedQuantity];
	}

	public function getProductQuantityInCart(int $id): int
	{
		$cart = $this->getCartFromSession();
		$products = $cart->getProducts();

		return $products[$id]['quantity'] ?? 0;
	}

	private function getCartFromSession(): Cart
	{
		return $this->stack->getSession()->get('cart', new Cart());
	}

	private function saveCartToSession(Cart $cart): void
	{
		$this->stack->getSession()->set('cart', $cart);
	}

	private function addFlashMessage(string $type, string $message): void
	{
		$this->stack->getSession()->getFlashBag()->add($type, $message);
	}
}

