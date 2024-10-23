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
		try {
			$cart = $cartService->getCart();
		} catch (\Exception $e) {
			$this->addFlash('notice', $e->getMessage());
			return $this->redirectToRoute('cart');
		}

		$products = [];
		$quantity = $cart->getQuantity();
		$totalPrice = 0;

		foreach ($cart->getProducts() as $cartProduct) {
			$product = $productRepository->find($cartProduct['id']);
			$products[] = new ProductDTO($product, $cartProduct['quantity']);
			$totalPrice += $product->getPrice() * $cartProduct['quantity'];
		}

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

		return $this->json(['success' => true, 'message' => 'Product added to cart successfully!']);
	}

	public function delete(Request $request, CartService $cartService, ProductRepository $productRepository): JsonResponse
	{
		$id = $request->get('id');
		$product = $productRepository->find($id);

		if (!$product) {
			return $this->json(['success' => false, 'message' => 'Product not found!'], 404);
		}

		$cartService->deleteProduct($id);

		return $this->json(['success' => true, 'message' => 'Product deleted successfully!']);
	}

	public function remove(Request $request, CartService $cartService, ProductRepository $productRepository): Response
	{
		$id = $request->get('id');
		$product = $productRepository->find($id);

		if (!$product) {
			$this->createNotFoundException('Product not found!');
		}

		$cartService->removeFromCart($id);

		return $this->redirectToRoute('cart');
	}
}
