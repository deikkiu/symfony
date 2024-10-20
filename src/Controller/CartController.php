<?php

namespace App\Controller;

use App\Dto\ProductDto;
use App\Repository\ProductRepository;
use App\Services\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
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

	public function add(Request $request, CartService $cartService, ProductRepository $productRepository): JsonResponse
	{
		$id = $request->get('id');
		$product = $productRepository->find($id);

		if (!$product) {
			return $this->json(['success' => false, 'message' => 'Product not found!'], 404);
		}

		$cartService->addProduct($id);

		return $this->json(['success' => true]);
	}

	public function delete(Request $request, CartService $cartService, ProductRepository $productRepository): JsonResponse
	{
		$id = $request->get('id');
		$product = $productRepository->find($id);

		if (!$product) {
			return $this->json(['success' => false, 'message' => 'Product not found!'], 404);
		}

		$cartService->deleteProduct($id);

		return $this->json(['success' => true]);
	}

	public function remove(Request $request, CartService $cartService, ProductRepository $productRepository): JsonResponse
	{
		$id = $request->get('id');
		$product = $productRepository->find($id);

		if (!$product) {
			return $this->json(['success' => false, 'message' => 'Product not found!'], 404);
		}

		$cartService->removeFromCart($id);

		return $this->json(['success' => true, 'redirectUrl' => $this->generateUrl('cart')]);
	}
}
