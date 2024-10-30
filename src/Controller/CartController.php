<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CartController extends AbstractController
{
	public function show(CartService $cartService): Response
	{
		[$cart, $isValid, $messages] = $cartService->getCart();

		if (!$isValid) {
			foreach ($messages as $type => $message) {
				$this->addFlash($type, $message);
			}
		}

		[$products, $productsIsNotStock] = $cartService->getDetailedProducts($cart);
		$totalPrice = $cartService->calculateTotalPrice($cart);

		return $this->render('cart/index.html.twig', [
			'products' => $products,
			'productsIsNotStock' => $productsIsNotStock,
			'quantity' => $cart->getQuantity(),
			'totalPrice' => $totalPrice,
		]);
	}

	public function add(Request $request, CartService $cartService, ProductRepository $productRepository): JsonResponse
	{
		$id = $request->get('id');
		$product = $productRepository->find($id);

		if (!$product || $product->isDraft()) {
			return $this->json(['message' => "Product with ID $id not found."], 404);
		}

		$cartService->addProductToCart($id);

		[$cart] = $cartService->getCart();
		$totalPrice = $cartService->calculateTotalPrice($cart);
		$quantity = $cart->getQuantity();

		return $this->json(['message' => 'Product added to cart successfully!', 'totalPrice' => $totalPrice, 'quantity' => $quantity]);
	}

	public function delete(Request $request, CartService $cartService): JsonResponse
	{
		$id = $request->get('id');
		[$cart] = $cartService->getCart();
		$cartProduct = $cart->getProducts()[$id] ?? null;

		if (!$cartProduct) {
			return $this->json(['message' => "Product with ID $id not found."], 404);
		}

		$cartService->deleteProductFromCart($id);

		$totalPrice = $cartService->calculateTotalPrice($cart);
		$quantity = $cart->getQuantity();

		return $this->json(['message' => 'Product deleted successfully!', 'totalPrice' => $totalPrice, 'quantity' => $quantity]);
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

