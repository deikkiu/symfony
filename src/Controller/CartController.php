<?php

namespace App\Controller;

use App\Dto\CartDto;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

class CartController extends AbstractController
{
	public function show(RequestStack $stack): Response
	{
		$cart = $stack->getSession()->get('cart');

		if (!$cart) {
			return $this->render('cart/index.html.twig');
		}

		$products = $cart->getProducts();
		$quantity = $cart->getQuantity();

		return $this->render('cart/index.html.twig', [
			'products' => $products,
			'quantity' => $quantity
		]);
	}

	public function add(Request $request, RequestStack $stack, ProductRepository $productRepository): Response
	{
		$productId = $request->get('id');
		$product = $productRepository->find($productId);

		if (!$product) {
			throw $this->createNotFoundException("Product for this id: {$productId} not found!");
		}

		$cart = $stack->getSession()->get('cart');

		if (!$cart) {
			$cart = new CartDto([$product], 1);
		} else {
			$products = $cart->getProducts();
			$products[] = $product;
			$cart->setProducts($products);
		}

		$cart->setQuantity(count($cart->getProducts()));

		$stack->getSession()->set('cart', $cart);
		$stack->getSession()->getFlashBag()->add('success', 'Product added to cart!');

		return $this->redirectToRoute('product_list');
	}

	public function delete(Request $request, RequestStack $stack, ProductRepository $productRepository): Response
	{
		$productId = $request->get('id');
		$product = $productRepository->find($productId);

		if (!$product) {
			throw $this->createNotFoundException("Product for this id: {$productId} not found!");
		}

		$cart = $stack->getSession()->get('cart');

		$filteredProducts = array_filter($cart->getProducts(), function ($product) use ($productId) {
			return $product->getId() !== (int)$productId;
		});

		$cart->setProducts($filteredProducts);

		$cart->setQuantity(count($cart->getProducts()));

		$stack->getSession()->set('cart', $cart);
		$stack->getSession()->getFlashBag()->add('success', 'Product deleted from cart!');

		return $this->redirectToRoute('cart');
	}
}
