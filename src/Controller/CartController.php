<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Services\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

class CartController extends AbstractController
{
	public function show(RequestStack $stack): Response
	{
		$cart = $stack->getSession()->get('cart', []);

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

	public function add(Request $request, CartService $cartService, ProductRepository $productRepository): Response
	{
		$productId = $request->get('id');
		$product = $productRepository->find($productId);

		if (!$product) {
			throw $this->createNotFoundException("Product for this id: {$productId} not found!");
		}

		$cartService->addProduct($product);

		return $this->redirectToRoute('product_list');
	}

	public function delete(Request $request, CartService $cartService, ProductRepository $productRepository): Response
	{
		$productId = $request->get('id');
		$product = $productRepository->find($productId);

		if (!$product) {
			throw $this->createNotFoundException("Product for this id: {$productId} not found!");
		}

		$cartService->deleteProduct($product);

		return $this->redirectToRoute('cart');
	}
}
