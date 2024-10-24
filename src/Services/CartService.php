<?php

namespace App\Services;

use App\Dto\CartDto;
use App\Dto\CartProductDto;
use App\Dto\ProductDto;
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

	public function getCart(): CartDto
	{
		$cart = $this->getCartFromSession();
		$this->validateCartOrFail($cart);
		return $cart;
	}

	public function addProductToCart(int $id): void
	{
		$cart = $this->getCartFromSession();

		$this->updateCartWithProduct($cart, $id);
		$this->saveCartToSession($cart);
	}

	public function validateCartOrFail(CartDto $cart): void
	{
		$result = $this->validateCart($cart);

		if (!$result['isValid']) {
			$message = $this->generateCartUpdateMessage($result['updatedProducts']);
			throw new \Exception($message);
		}
	}

	public function validateCart(CartDto $cart): array
	{
		$cartProducts = $cart->getProducts();
		$updatedProducts = [];
		$isValid = true;

		if (!empty($cartProducts)) {
			foreach ($cartProducts as $cartProduct) {
				$product = $this->productRepository->find($cartProduct->getId());

				if (!$product) {
					$this->filterCart($cart, $cartProduct);
					$isValid = false;
					continue;
				}

				// @TODO: product amount = 0
				if ($product->getAmount() === 0) {
					$cart->setQuantity(max($cart->getQuantity() - $cartProduct->getQuantity(), 0));
					$cartProduct->setQuantity(0);
					continue;
				}

				if ($cartProduct->getQuantity() > $product->getAmount()) {
					$isValid = false;
					$cartProduct->setQuantity($product->getAmount());
					$updatedProducts[] = $this->createUpdatedProductMessage($product, $cartProduct->getQuantity());
				}
			}
		}

		return ['isValid' => $isValid, 'updatedProducts' => $updatedProducts];
	}

	private function filterCart(CartDto $cart, CartProductDto $cartProduct): void
	{
		$cartProducts = array_filter($cart->getProducts(), fn($cp) => $cp->getId() !== $cartProduct->getId());
		$cart->setProducts($cartProducts);
		$cart->setQuantity(max($cart->getQuantity() - $cartProduct->getQuantity(), 0));
		$this->addFlashMessage('notice', "Product with ID: {$cartProduct->getId()} is no longer available. We apologize for the inconvenience!");
	}

	private function setQuantity(CartProductDto $cartProduct): bool
	{
		$flag = true;

		$productAmount = $this->productRepository->find($cartProduct->getId())->getAmount();
		$productQuantity = $cartProduct->getQuantity() + 1;

		$productQuantity > $productAmount
			? $flag = false
			: $cartProduct->setQuantity($productQuantity);;

		return $flag;
	}

	private function updateCartWithProduct(CartDto $cart, int $id): void
	{
		$cartProducts = $cart->getProducts();
		$cartProduct = $cartProducts[$id] ?? null;

		if ($cartProduct) {
			$added = $this->setQuantity($cartProduct);

			if ($added) {
				$cart->setQuantity($cart->getQuantity() + 1);
			}
		} else {
			$cartProducts[$id] = new CartProductDto($id, 1);
			$cart->setQuantity($cart->getQuantity() + 1);
		}

		$cart->setProducts($cartProducts);
	}

	public function deleteProductFromCart(int $id): void
	{
		$cart = $this->getCartFromSession();
		$cartProduct = $cart->getProducts()[$id] ?? null;

		if ($cartProduct) {
			if ($cartProduct->getQuantity() - 1 > 0) {
				$cartProduct->setQuantity($cartProduct->getQuantity() - 1);
			} else {
				$this->removeProductFromCart($id);
			}

			$cart->setQuantity($cart->getQuantity() - 1);
		}

		$this->saveCartToSession($cart);
	}

	public function removeProductFromCart(int $id): void
	{
		$cart = $this->getCartFromSession();
		$cartProducts = $cart->getProducts();
		$cartProduct = $cartProducts[$id] ?? null;

		if ($cartProduct) {
			$cart->setQuantity(max($cart->getQuantity() - $cartProduct->getQuantity(), 0));
			unset($cartProducts[$id]);
		}

		$cart->setProducts($cartProducts);
	}

	private function createUpdatedProductMessage($product, int $requestedQuantity): array
	{
		return ['product' => $product->getName(), 'available' => $product->getAmount(), 'requested' => $requestedQuantity];
	}

	public function generateCartUpdateMessage(array $updatedProducts): string
	{
		if (empty($updatedProducts)) {
			return '';
		}

		$message = "Some items have been modified due to insufficient stock:\n";

		foreach ($updatedProducts as $updatedProduct) {
			$message .= sprintf("Product: %s - Available: %d, Requested: %d\n", $updatedProduct['product'], $updatedProduct['available'], $updatedProduct['requested']);
		}

		return $message;
	}

	public function getDetailedProducts(CartDto $cart): array
	{
		return array_map(function ($cartProduct) {
			$product = $this->productRepository->find($cartProduct->getId());
			return new ProductDto($product, $cartProduct->getQuantity());
		}, $cart->getProducts());
	}

	public function clearCart(): void
	{
		$cart = $this->getCartFromSession();
		$cartProducts = $cart->getProducts();

		foreach ($cartProducts as $cartProduct) {
			if ($cartProduct->getQuantity() !== 0) {
				$cart->setQuantity(max($cart->getQuantity() - $cartProduct->getQuantity(), 0));
				unset($cartProducts[$cartProduct->getId()]);
			}
		}

		$cart->setProducts($cartProducts);
	}

	public function calculateTotalPrice(CartDto $cart): float
	{
		return array_reduce($cart->getProducts(), function ($total, $cartProduct) {
			$productPrice = $this->productRepository->find($cartProduct->getId())->getPrice();
			return $total + ($productPrice * $cartProduct->getQuantity());
		}, 0);
	}

	public function getProductQuantityInCart(int $id): int
	{
		$cartProduct = $this->getCartFromSession()->getProducts()[$id] ?? null;

		return $cartProduct ? $cartProduct->getQuantity() : 0;
	}

	private function getCartFromSession(): CartDto
	{
		return $this->stack->getSession()->get('cart', new CartDto());
	}

	private function saveCartToSession(CartDto $cart): void
	{
		$this->stack->getSession()->set('cart', $cart);
	}

	private function addFlashMessage(string $type, string $message): void
	{
		$this->stack->getSession()->getFlashBag()->add($type, $message);
	}
}
