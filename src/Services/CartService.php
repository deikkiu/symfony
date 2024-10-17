<?php

namespace App\Services;

use App\Dto\Cart;
use App\Dto\ProductDto;
use App\Entity\Product;
use Symfony\Component\HttpFoundation\RequestStack;

class CartService
{
	public function __construct(
		protected RequestStack $stack
	)
	{
	}

	public function addProduct(Product $product): void
	{
		$productDto = $this->createProductDto($product);

		$cart = $this->stack->getSession()->get('cart');

		if (!$cart) {
			$cart = new Cart();
			$cart->setProducts([$productDto]);
		} else {
			$productFound = false;
			$products = $cart->getProducts();

			foreach ($cart->getProducts() as $cartProduct) {
				if ($cartProduct->getId() === $product->getId()) {
					$cartProduct->setQuantity($cartProduct->getQuantity() + 1);
					$productFound = true;
					break;
				}
			}

			if (!$productFound) {
				$products[] = $productDto;
				$cart->setProducts($products);
			}
		}

		$cart->setQuantity($cart->getQuantity() + 1);

		$this->stack->getSession()->set('cart', $cart);
		$this->stack->getSession()->getFlashBag()->add('success', 'Product added to cart!');
	}

	public function deleteProduct(Product $product): void
	{
		$cart = $this->stack->getSession()->get('cart');
		$products = $cart->getProducts();

		foreach ($products as $cartProduct) {
			if ($cartProduct->getId() === $product->getId()) {
				if ($cartProduct->getQuantity() > 1) {
					$cartProduct->setQuantity($cartProduct->getQuantity() - 1);
				} else {
					$filteredProducts = array_filter($cart->getProducts(), function ($cartProduct) use ($product) {
						return $cartProduct->getId() !== $product->getId();
					});

					$cart->setProducts($filteredProducts);
				}

				break;
			}
		}

		$cart->setQuantity($cart->getQuantity() - 1);

		$this->stack->getSession()->set('cart', $cart);
		$this->stack->getSession()->getFlashBag()->add('success', 'Product deleted from cart!');
	}

	private function createProductDto(Product $product): ProductDto
	{
		return new ProductDto(
			$product->getId(),
			$product->getName(),
			$product->getSlug(),
			$product->getCategory()->getName(),
			$product->getPrice(),
			$product->getAmount(),
			1,
			$product->getImagePath(),
			$product->getColors()->toArray()
		);
	}
}

