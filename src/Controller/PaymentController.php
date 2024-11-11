<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Service\CartService;
use Stripe\StripeClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PaymentController extends AbstractController
{
	public function paymentCheckout(Request $request, CartService $cartService, ProductRepository $productRepository): JsonResponse
	{
		$cart = $cartService->getCart();
		$cartList = $cart->getList();

		$stripeProducts = [];

		foreach ($cartList as $cartItem) {
			if ($cartItem->isInStock()) {
				$product = $productRepository->find($cartItem->getId());

				$stripeProducts[] = [
					'price_data' => [
						'currency' => 'usd',
						'unit_amount' => $product->getPrice(),
						'product_data' => [
							'name' => $product->getName(),
						]
					],
					'quantity' => $cartItem->getQuantity()
				];
			}
		}

		$stripe = new StripeClient($this->getParameter('stripe.secret_key'));
		$DOMAIN = $request->getSchemeAndHttpHost();

		$checkout_session = $stripe->checkout->sessions->create([
			'ui_mode' => 'embedded',
			'line_items' => $stripeProducts,
			'mode' => 'payment',
			'return_url' => $DOMAIN . '/payment/return?session_id={CHECKOUT_SESSION_ID}',
		]);

		return $this->json(['clientSecret' => $checkout_session->client_secret], headers: [
			'Content-Type: application/json'
		]);
	}

	public function paymentReturn(Request $request): Response
	{
		$checkoutSessionId = $request->query->get('session_id');

		if (!$checkoutSessionId) {
			return $this->redirectToRoute('cart');
		}

		$stripe = new StripeClient($this->getParameter('stripe.secret_key'));

		try {
			$session = $stripe->checkout->sessions->retrieve($checkoutSessionId);
			$status = $session->status;

			return match ($status) {
				'complete' => $this->redirectToRoute('order_create'),
				'expired', 'open' => $this->redirectToRoute('cart')
			};
		} catch (\Error $e) {
			throw new \Error($e->getMessage());
		}
	}
}