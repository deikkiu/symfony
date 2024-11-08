<?php

namespace App\Service;

use App\Dto\CartDto;
use App\Dto\CartItemDto;
use App\Dto\ProductDto;
use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class CartService
{
	private bool $isValid = true;
	private array $messages = [];

	public function __construct(
		private readonly RequestStack      $stack,
		private readonly ProductRepository $productRepository
	)
	{
	}

	public function getCart(): CartDto
	{
		$cart = $this->getCartFromSession();

		$this->validateCart($cart);
		$this->updateCartQuantity();

		return $cart;
	}

	private function getCartFromSession(): CartDto
	{
		return $this->stack->getSession()->get('cart', new CartDto());
	}

	private function validateCart(CartDto $cart): void
	{
		$cartList = $cart->getList();

		if (empty($cartList)) return;

		foreach ($cartList as $cartItem) {
			$product = $this->productRepository->find($cartItem->getId());

			if (!$product || $product->isDraft()) {
				$this->removeItemFromCart($cartItem->getId());

				$this->isValid = false;
				$this->messages['notice'] = "Product with id:{$cartItem->getId()} was delete!";

				continue;
			}

			if ($product->getAmount() < 1) {
				if ($cartItem->isInStock()) {
					$cartItem->setInStock(false);

					$this->isValid = false;
					$this->messages['notice'] = "Product with id:{$cartItem->getId()} is out of stock!";
				}

				continue;
			}

			if (!$cartItem->isInStock()) {
				$cartItem->setInStock(true);

				if ($cartItem->getQuantity() > $product->getAmount()) {
					$cartItem->setQuantity($product->getAmount());
				}

				continue;
			}

			if ($cartItem->getQuantity() > $product->getAmount()) {
				$cartItem->setQuantity($product->getAmount());

				$this->isValid = false;
				$this->messages['notice'] = "Product with id:{$cartItem->getId()} quantity is less than in stock. And we changed the quantity.";
			}
		}
	}

	public function removeItemFromCart(int $id): void
	{
		$cart = $this->getCartFromSession();
		$cartList = $cart->getList();

		if ($cartList[$id] ?? null) {
			unset($cartList[$id]);
		}

		$cart->setList($cartList);
		$this->updateCartQuantity();
	}

	private function setQuantity(CartItemDto $cartItem): void
	{
		$productAmount = $this->productRepository->find($cartItem->getId())->getAmount();
		$productQuantity = $cartItem->getQuantity() + 1;

		if ($productQuantity < $productAmount) {
			$cartItem->setQuantity($productQuantity);
		}
	}

	public function addItemToCart(int $id): void
	{
		$cart = $this->getCartFromSession();

		$this->updateCartWithItem($cart, $id);
		$this->saveCartToSession($cart);
	}

	private function updateCartWithItem(CartDto $cart, int $id): void
	{
		$cartList = $cart->getList();
		$cartItem = $cartList[$id] ?? null;

		if ($cartItem && $cartItem->isInStock()) {
			$this->setQuantity($cartItem);
		} else {
			$cartList[$id] = new CartItemDto($id, 1);
		}

		$cart->setList($cartList);
		$this->updateCartQuantity();
		$this->saveCartToSession($cart);
	}

	public function deleteItemFromCart(int $id): void
	{
		$cart = $this->getCartFromSession();
		$cartItem = $cart->getList()[$id] ?? null;

		if (!$cartItem) return;

		if ($cartItem->getQuantity() - 1 > 0) {
			$cartItem->setQuantity($cartItem->getQuantity() - 1);
		} else {
			$this->removeItemFromCart($id);
		}

		$this->updateCartQuantity();
		$this->saveCartToSession($cart);
	}

	public function getDetailedProducts(CartDto $cart): array
	{
		$productList = [];
		$productListIsNotStock = [];

		foreach ($cart->getList() as $cartItem) {
			$product = $this->productRepository->find($cartItem->getId());
			$productDto = new ProductDto($product, $cartItem->getQuantity(), $cartItem->isInStock());

			$cartItem->isInStock()
				? $productList[] = $productDto
				: $productListIsNotStock[] = $productDto;
		}

		return [$productList, $productListIsNotStock];
	}

	public function clearCart(): void
	{
		$cart = $this->getCartFromSession();
		$cartList = $cart->getList();

		foreach ($cartList as $cartItem) {
			if ($cartItem->isInStock()) {
				unset($cartList[$cartItem->getId()]);
			}
		}

		$cart->setList($cartList);
		$this->updateCartQuantity();
	}

	public function calculateTotalPrice(CartDto $cart): int
	{
		return array_reduce($cart->getList(), function ($total, $cartItem) {
			if ($cartItem->isInStock()) {
				$productPrice = $this->productRepository->find($cartItem->getId())->getPrice();
				return $total + ($productPrice * $cartItem->getQuantity());
			}

			return $total;
		}, 0);
	}

	public function getItemQuantityInCart(int $id): int
	{
		$cartItem = $this->getCartFromSession()->getList()[$id] ?? null;
		return $cartItem ? $cartItem->getQuantity() : 0;
	}

	public function cartIsValid(): bool
	{
		return $this->isValid;
	}

	public function getMessages(): array
	{
		return $this->messages;
	}

	private function updateCartQuantity(): void
	{
		$cart = $this->getCartFromSession();

		$quantity = array_reduce($cart->getList(), function ($quantity, $cartItem) {
			if ($cartItem->isInStock()) {
				$quantity += $cartItem->getQuantity();
			}

			return $quantity;
		}, 0);

		$cart->setQuantity($quantity);
	}

	private function saveCartToSession(CartDto $cart): void
	{
		$this->stack->getSession()->set('cart', $cart);
	}
}