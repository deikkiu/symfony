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
			return $this->redirectToRoute('cart');
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
		$id = (int)$request->get('id');
		$product = $productRepository->find($id);

		if (!$product) {
			$this->createNotFoundException("Product with ID $id not found.");
		}

		$cartService->addProductToCart($id);

		return $this->json(['success' => true, 'message' => 'Product added to cart successfully!']);
	}

	public function delete(Request $request, CartService $cartService): JsonResponse
	{
		$id = (int)$request->get('id');
		$cartService->deleteProductFromCart($id);

		return $this->json(['success' => true, 'message' => 'Product deleted successfully!']);
	}

	public function remove(Request $request, CartService $cartService): Response
	{
		$id = (int)$request->get('id');
		$cartService->removeProductFromCart($id);

		return $this->redirectToRoute('cart');
	}
}

