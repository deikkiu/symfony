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
	public function __construct(
		private readonly CartService $cartService
	)
	{
	}

	public function show(): Response
	{
		$cart = $this->cartService->getCart();

		if ($this->cartService->cartIsValid()) {
			foreach ($this->cartService->getMessages() as $type => $message) {
				$this->addFlash($type, $message);
			}
		}

		[$products, $productsIsNotStock] = $this->cartService->getDetailedProducts($cart);
		$totalPrice = $this->cartService->calculateTotalPrice($cart);

		return $this->render('cart/index.html.twig', [
			'products' => $products,
			'productsIsNotStock' => $productsIsNotStock,
			'quantity' => $cart->getQuantity(),
			'totalPrice' => $totalPrice,
		]);
	}

	public function add(Request $request, ProductRepository $productRepository): JsonResponse
	{
		$id = $request->get('id');
		$product = $productRepository->find($id);

		if (!$product || $product->isDraft()) {
			return $this->json(['message' => "Product with ID $id not found."], 404);
		}

		$this->cartService->addProductToCart($id);

		$cart = $this->cartService->getCart();
		$totalPrice = $this->cartService->calculateTotalPrice($cart);
		$quantity = $cart->getQuantity();

		return $this->json(['message' => 'Product added to cart successfully!', 'totalPrice' => $totalPrice, 'quantity' => $quantity]);
	}

	public function delete(Request $request): JsonResponse
	{
		$id = $request->get('id');
		$cart = $this->cartService->getCart();
		$cartProduct = $cart->getProducts()[$id] ?? null;

		if (!$cartProduct) {
			return $this->json(['message' => "Product with ID $id not found."], 404);
		}

		$this->cartService->deleteProductFromCart($id);

		$totalPrice = $this->cartService->calculateTotalPrice($cart);
		$quantity = $cart->getQuantity();

		return $this->json(['message' => 'Product deleted successfully!', 'totalPrice' => $totalPrice, 'quantity' => $quantity]);
	}

	public function remove(Request $request, ProductRepository $productRepository): Response
	{
		$id = $request->get('id');
		$product = $productRepository->find($id);

		if (!$product) {
			$this->createNotFoundException("Product with ID $id not found.");
		}

		$this->cartService->removeProductFromCart($id);

		return $this->redirectToRoute('cart');
	}
}

