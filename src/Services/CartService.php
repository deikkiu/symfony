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

		$validationResult = $this->validateCart($cart);

		if (!$validationResult['isValid']) {
			$message = $this->generateCartUpdateMessage($validationResult['updatedProducts']);
			throw new \Exception($message);
		}

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

	public function validateCart(Cart $cart): array
	{
		$products = $cart->getProducts();
		$updatedProducts = [];
		$isValid = true;

		if (!empty($products)) {
			foreach ($products as $key => $cartProduct) {
				$product = $this->productRepository->find($cartProduct['id']);

				if (!$product) {
					$this->filterCart($cart, $cartProduct);
					$isValid = false;
					continue;
				}

				if ($cartProduct['quantity'] > $product->getAmount()) {
					$isValid = false;
					$cartProduct['quantity'] = $product->getAmount();
					$updatedProducts[] = $this->createUpdatedProductMessage($product, $cartProduct['quantity']);
				}
			}
		}

		return [
			'isValid' => $isValid,
			'updatedProducts' => $updatedProducts
		];
	}

	private function filterCart(Cart $cart, array $cartProduct): void
	{
		$products = array_filter($cart->getProducts(), fn($p) => $p['id'] !== $cartProduct['id']);
		$cart->setProducts($products);
		$cart->setQuantity($cart->getQuantity() - $cartProduct['quantity']);
		$this->addFlashMessage('notice', "Product with ID: {$cartProduct['id']} is no longer available. We apologize for the inconvenience!");
	}

	private function createUpdatedProductMessage($product, int $requestedQuantity): array
	{
		return [
			'product' => $product->getName(),
			'available' => $product->getAmount(),
			'requested' => $requestedQuantity
		];
	}

	public function generateCartUpdateMessage(array $updatedProducts): string
	{
		if (empty($updatedProducts)) {
			return '';
		}

		$message = "Some items have been modified due to insufficient stock:\n";

		foreach ($updatedProducts as $updatedProduct) {
			$message .= sprintf(
				"Product: %s - Available: %d, Requested: %d\n",
				$updatedProduct['product'],
				$updatedProduct['available'],
				$updatedProduct['requested']
			);
		}

		return $message;
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

	public function clearCart(): void
	{
		$this->stack->getSession()->remove('cart');
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