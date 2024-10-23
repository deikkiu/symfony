<?php

namespace App\Services;

use App\Dto\Cart;
use App\Dto\ProductDto;
use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class CartService
{
	public function __construct(protected RequestStack $stack, protected ProductRepository $productRepository)
	{
	}

	public function getCart(): Cart
	{
		$cart = $this->getCartFromSession();
		$this->validateCartOrFail($cart);
		return $cart;
	}

	private function getCartFromSession(): Cart
	{
		return $this->stack->getSession()->get('cart', new Cart());
	}

	public function validateCartOrFail(Cart $cart): void
	{
		$validationResult = $this->validateCart($cart);

		if (!$validationResult['isValid']) {
			$message = $this->generateCartUpdateMessage($validationResult['updatedProducts']);
			throw new \Exception($message);
		}
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

		return ['isValid' => $isValid, 'updatedProducts' => $updatedProducts];
	}

	private function filterCart(Cart $cart, array $cartProduct): void
	{
		$products = array_filter($cart->getProducts(), fn($p) => $p['id'] !== $cartProduct['id']);
		$cart->setProducts($products);
		$cart->setQuantity($cart->getQuantity() - $cartProduct['quantity']);
		$this->addFlashMessage('notice', "Product with ID: {$cartProduct['id']} is no longer available. We apologize for the inconvenience!");
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

	private function addFlashMessage(string $type, string $message): void
	{
		$this->stack->getSession()->getFlashBag()->add($type, $message);
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

	public function addProductToCart(int $id): void
	{
		$cart = $this->getCartFromSession();

		$this->updateCartWithProduct($cart, $id);
		$this->saveCartToSession($cart);
	}

	private function updateCartWithProduct(Cart $cart, int $id): void
	{
		$products = $cart->getProducts();

		if (isset($products[$id])) {
			[$products, $added] = $this->setQuantity($products, $id);

			if ($added) {
				$cart->setQuantity($cart->getQuantity() + 1);
			}
		} else {
			$products[$id] = ['id' => $id, 'quantity' => 1];
			$cart->setQuantity($cart->getQuantity() + 1);
		}

		$cart->setProducts($products);
	}

	private function saveCartToSession(Cart $cart): void
	{
		$this->stack->getSession()->set('cart', $cart);
	}

	public function deleteProductFromCart(int $id): void
	{
		$cart = $this->getCartFromSession();
		$this->removeProductFromCart($id);
		$this->saveCartToSession($cart);
	}

	public function removeProductFromCart(int $id): void
	{
		$cart = $this->getCartFromSession();
		$products = $cart->getProducts();

		if (isset($products[$id])) {
			$cart->setQuantity( max($cart->getQuantity() - $products[$id]['quantity'], 0));
			unset($products[$id]);
			$cart->setProducts($products);
		}
	}

	public function getDetailedProducts(Cart $cart): array
	{
		return array_map(fn($cartProduct) => new ProductDTO($this->productRepository->find($cartProduct['id']), $cartProduct['quantity']), $cart->getProducts());
	}

	public function calculateTotalPrice(Cart $cart): float
	{
		return array_reduce($cart->getProducts(), fn($total, $cartProduct) => $total + $this->productRepository->find($cartProduct['id'])->getPrice() * $cartProduct['quantity'], 0);
	}

	public function clearCart(): void
	{
		$this->stack->getSession()->remove('cart');
	}

	public function getProductQuantityInCart(int $id): int
	{
		$products = $this->getCartFromSession()->getProducts();

		return $products[$id]['quantity'] ?? 0;
	}
}
