<?php

namespace App\Controller;

use App\Dto\ProductDto;
use App\Repository\ProductRepository;
use App\Services\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CartController extends AbstractController
{
	public function show(CartService $cartService, ProductRepository $productRepository): Response
	{
		$cart = $cartService->getCart();

		$products = [];

		foreach ($cart->getProducts() as $cartProduct) {
			$product = $productRepository->find($cartProduct['id']);
			$products[] = new ProductDTO($product, $cartProduct['quantity']);
		}

		$quantity = $cart->getQuantity();
		$totalPrice = $cart->getTotalPrice();

		return $this->render('cart/index.html.twig', [
			'products' => $products,
			'quantity' => $quantity,
			'totalPrice' => $totalPrice,
		]);
	}

	public function add(Request $request, CartService $cartService, ProductRepository $productRepository): Response
	{
		$id = $request->get('id');
		$quantity = $request->get('quantity');

		$product = $productRepository->find($id);

		if (!$product) {
			throw $this->createNotFoundException("Product for this id: {$id} not found!");
		}

		$cartService->addProduct($id, $quantity);

		return $this->redirectToRoute('product_list');
	}

	public function delete(Request $request, CartService $cartService, ProductRepository $productRepository): Response
	{
		$productId = $request->get('id');
		$product = $productRepository->find($productId);

		if (!$product) {
			throw $this->createNotFoundException("Product for this id: {$productId} not found!");
		}

		$cartService->deleteProduct($productId);

		return $this->redirectToRoute('cart');
	}
}
