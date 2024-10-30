<?php

namespace App\Service;

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

	public function getCart(): array
	{
		$cart = $this->getCartFromSession();
		[$isValid, $messages] = $this->validateCart($cart);
		return [$cart, $isValid, $messages];
	}

	public function addProductToCart(int $id): void
	{
		$cart = $this->getCartFromSession();

		$this->updateCartWithProduct($cart, $id);
		$this->saveCartToSession($cart);
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

	private function validateCart(CartDto $cart): array
	{
		$isValid = true;
		$messages = [];

		$cartProducts = $cart->getProducts();

		if (empty($cartProducts)) return [$isValid, $messages];

		foreach ($cartProducts as $cartProduct) {
			$product = $this->productRepository->find($cartProduct->getId());

			if (!$product || $product->isDraft()) {
				$this->removeProductFromCart($cartProduct->getId());

				$isValid = false;
				$messages['notice'] = "Product with id:{$cartProduct->getId()} was delete!";

				continue;
			}

			if ($product->getAmount() < 1) {
				if ($cartProduct->isInStock()) {
					$cartProduct->setInStock(false);
					$cart->setQuantity(max($cart->getQuantity() - $cartProduct->getQuantity(), 0));

					$isValid = false;
					$messages['notice'] = "Product with id:{$cartProduct->getId()} is out of stock!";
				}

				continue;
			}

			if (!$cartProduct->isInStock()) {
				$cartProduct->setInStock(true);

				if ($cartProduct->getQuantity() > $product->getAmount()) {
					$cart->setQuantity(max($cart->getQuantity() + $product->getAmount(), 0));
					$cartProduct->setQuantity($product->getAmount());
				} else {
					$cart->setQuantity(max($cart->getQuantity() + $cartProduct->getQuantity(), 0));
				}

				continue;
			}

			if ($cartProduct->getQuantity() > $product->getAmount()) {
				$cart->setQuantity(max($cart->getQuantity() - ($cartProduct->getQuantity() - $product->getAmount()), 0));
				$cartProduct->setQuantity($product->getAmount());

				$isValid = false;
				$messages['notice'] = "Product with id:{$cartProduct->getId()} quantity is less than in stock. And we changed the quantity.";
			}
		}

		return [$isValid, $messages];
	}

	private function updateCartWithProduct(CartDto $cart, int $id): void
	{
		$cartProducts = $cart->getProducts();
		$cartProduct = $cartProducts[$id] ?? null;

		if ($cartProduct && $cartProduct->isInStock()) {
			$added = $this->setQuantity($cartProduct);

			if ($added) {
				$cart->setQuantity($cart->getQuantity() + 1);
			}
		} else {
			$cartProducts[$id] = new CartProductDto($id, 1);
			$cart->setQuantity($cart->getQuantity() + 1);
		}

		$cart->setProducts($cartProducts);
		$this->saveCartToSession($cart);
	}

	private function setQuantity(CartProductDto $cartProduct): bool
	{
		$flag = true;

		$productAmount = $this->productRepository->find($cartProduct->getId())->getAmount();
		$productQuantity = $cartProduct->getQuantity() + 1;

		$productQuantity > $productAmount
			? $flag = false
			: $cartProduct->setQuantity($productQuantity);

		return $flag;
	}

	public function getDetailedProducts(CartDto $cart): array
	{
		$cartProducts = [];
		$cartProductsIsNotStock = [];

		foreach ($cart->getProducts() as $cartProduct) {
			$product = $this->productRepository->find($cartProduct->getId());
			$productDto = new ProductDto($product, $cartProduct->getQuantity(), $cartProduct->isInStock());

			$cartProduct->isInStock()
				? $cartProducts[] = $productDto
				: $cartProductsIsNotStock[] = $productDto;
		}

		return [$cartProducts, $cartProductsIsNotStock];
	}

	public function clearCart(): void
	{
		$cart = $this->getCartFromSession();
		$cartProducts = $cart->getProducts();

		foreach ($cartProducts as $cartProduct) {
			if ($cartProduct->isInStock()) {
				$cart->setQuantity(max($cart->getQuantity() - $cartProduct->getQuantity(), 0));
				unset($cartProducts[$cartProduct->getId()]);
			}
		}

		$cart->setProducts($cartProducts);
	}

	public function calculateTotalPrice(CartDto $cart): int
	{
		return array_reduce($cart->getProducts(), function ($total, $cartProduct) {
			if ($cartProduct->isInStock()) {
				$productPrice = $this->productRepository->find($cartProduct->getId())->getPrice();
				return $total + ($productPrice * $cartProduct->getQuantity());
			}

			return $total;
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
}

