<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Service\CartService;
use Stripe\StripeClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PaymentController extends AbstractController
{

	public function payment(): Response
	{
		return $this->render('payment/index.html.twig');
	}

	public function paymentCheckout(CartService $cartService, ProductRepository $productRepository): JsonResponse|RedirectResponse
	{
		$cartProducts = $cartService->getCart()->getProducts();

		if (empty($cartProducts)) {
			$this->addFlash('notice', 'For order you need add products in you cart!');
			return $this->redirectToRoute('product_list');
		}

		$stripeProducts = [];

		foreach ($cartProducts as $cartProduct) {
			if ($cartProduct->isInStock()) {
				$product = $productRepository->find($cartProduct->getId());

				$stripeProducts[] = [
					'price_data' => [
						'currency' => 'usd',
						'unit_amount' => $product->getPrice(),
						'product_data' => [
							'name' => $product->getName(),
						]
					],
					'quantity' => $cartProduct->getQuantity()
				];
			}
		}

		$stripe = new StripeClient('sk_test_51QF8smF1xKsVMqq0LU9ejDvz12HPSyRmQbj6QavWN1aYoi1cqUMnepsx00AQC913mdg9uotTzskJLOqStZySoAd400Vwx6Mdnd');

		$YOUR_DOMAIN = 'http://project';

		$checkout_session = $stripe->checkout->sessions->create([
			'ui_mode' => 'embedded',
			'line_items' => $stripeProducts,
			'mode' => 'payment',
			'return_url' => $YOUR_DOMAIN . '/payment/return?session_id={CHECKOUT_SESSION_ID}',
		]);

		return $this->json(['clientSecret' => $checkout_session->client_secret], headers: [
			'Content-Type: application/json'
		]);
	}

	public function paymentReturn(Request $request): Response
	{
		$checkoutSessionId = $request->query->get('session_id');

		// @TODO
		if (!$checkoutSessionId) {
			$this->redirectToRoute('payment');
		}

		$stripe = new StripeClient('sk_test_51QF8smF1xKsVMqq0LU9ejDvz12HPSyRmQbj6QavWN1aYoi1cqUMnepsx00AQC913mdg9uotTzskJLOqStZySoAd400Vwx6Mdnd');

		try {
			$session = $stripe->checkout->sessions->retrieve($checkoutSessionId);

			return $this->redirectToRoute('order_create');
		} catch (\Error $e) {
			throw new \Error($e->getMessage());
		}
	}
}
