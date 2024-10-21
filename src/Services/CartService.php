<?php

namespace App\Services;

use App\Dto\Cart;
use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class CartService
{
	public function __construct(
		protected RequestStack      $stack,
		protected ProductRepository $productRepository
	)
	{
	}

	public function getCart(): Cart
	{
		$cart = $this->getCartFromSession();
		$this->validateCart($cart);

		return $cart;
	}

	public function addProduct(int $id): void
	{
		$cart = $this->getCartFromSession();
		$this->updateCartWithProduct($cart, $id);
		$this->saveCartToSession($cart);
	}

	public function deleteProduct(int $id): void
	{
		$cart = $this->getCartFromSession();
		$this->removeProductFromCart($cart, $id);
		$this->saveCartToSession($cart);
	}

	public function removeFromCart(int $id): void
	{
		$cart = $this->getCartFromSession();
		$products = $cart->getProducts();
		$quantity = $products[$id]['quantity'];

		unset($products[$id]);

		$cart->setProducts($products);
		$cart->setQuantity($cart->getQuantity() - $quantity);

		$this->addFlashMessage('success', "Product was successfully delete!");
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
	}

	private function updateCartWithProduct(Cart $cart, int $id): void
	{
		$products = $cart->getProducts();
		$added = true;

		if (isset($products[$id])) {
			[$products, $added] = $this->setQuantity($products, $id);
		} else {
			$products[$id] = ['id' => $id, 'quantity' => 1];
		}

		$cart->setProducts($products);

		if ($added) {
			$cart->setQuantity($cart->getQuantity() + 1);
		}
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

	private function setQuantity(array $products, int $id): array
	{
		$productAmount = $this->productRepository->find($id)->getAmount();
		$flag = true;

		$productQuantity = $products[$id]['quantity'] + 1;

		if ($productQuantity > $productAmount) {
			$flag = false;
		} else {
			$products[$id]['quantity'] = $productQuantity;
		}

		return [$products, $flag];
	}

	public function getProductQuantityInCart(int $id): int
	{
		$products = $this->getCartFromSession()->getProducts();

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