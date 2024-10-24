<?php

namespace App\Controller;

use App\Services\CartService;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class CartController extends AbstractController
{
	public function show(CartService $cartService): Response
	{
		try {
			$cart = $cartService->getCart();
		} catch (\Exception $e) {
			$this->addFlash('notice', $e->getMessage());
			return $this->redirectToRoute('home');
		}

		$products = $cartService->getDetailedProducts($cart);
		$totalPrice = $cartService->calculateTotalPrice($cart);

		return $this->render('cart/index.html.twig', [
			'products' => $products,
			'quantity' => $cart->getQuantity(),
			'totalPrice' => $totalPrice,
		]);
	}

	public function add(Request $request, CartService $cartService, ProductRepository $productRepository): JsonResponse
	{
		$id = $request->get('id');
		$product = $productRepository->find($id);

		if (!$product || $product->isDraft()) {
			$this->json(['success' => false, 'message' => "Product with ID $id not found."], 404);
		}

		$cartService->addProductToCart($id);

		$cart = $cartService->getCart();
		$totalPrice = $cartService->calculateTotalPrice($cart);
		$quantity = $cart->getQuantity();

		return $this->json(['success' => true, 'message' => 'Product added to cart successfully!', 'totalPrice' => $totalPrice, 'quantity' => $quantity]);
	}

	public function delete(Request $request, CartService $cartService): JsonResponse
	{
		$id = $request->get('id');
		$cart = $cartService->getCart();
		$cartProduct = $cart->getProducts()[$id] ?? null;

		if (!$cartProduct) {
			$this->json(['success' => false, 'message' => "Product with ID $id not found."], 404);
		}

		$cartService->deleteProductFromCart($id);

		$totalPrice = $cartService->calculateTotalPrice($cart);
		$quantity = $cart->getQuantity();

		return $this->json(['success' => true, 'message' => 'Product deleted successfully!', 'totalPrice' => $totalPrice, 'quantity' => $quantity]);
	}

	public function remove(Request $request, CartService $cartService, ProductRepository $productRepository): Response
	{
		$id = $request->get('id');
		$product = $productRepository->find($id);

		if (!$product) {
			$this->createNotFoundException("Product with ID $id not found.");
		}

		$cartService->removeProductFromCart($id);

		return $this->redirectToRoute('cart');
	}
}

