<?php

namespace App\Controller;

use App\Repository\OrderRepository;
use Stripe\StripeClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PaymentController extends AbstractController
{
	public function payment(Request $request, OrderRepository $orderRepository): Response
	{
		$order = $orderRepository->findOneBy(['id' => $request->get('id'), 'status' => 1]);

		if (!$order) {
			$this->createNotFoundException('Order not found');
		}

		$products = $order->getOrderProducts()->toArray();
		$stripeProducts = array_map(function ($item) {
			return [
				'price_data' => [
					'currency' => 'usd',
					'unit_amount' => $item->getPriceForOne(),
					'product_data' => [
						'name' => $item->getProduct()->getName(),
					]
				],
				'quantity' => $item->getQuantity()
			];
		}, $products);

		$stripe = new StripeClient('sk_test_51QF8smF1xKsVMqq0LU9ejDvz12HPSyRmQbj6QavWN1aYoi1cqUMnepsx00AQC913mdg9uotTzskJLOqStZySoAd400Vwx6Mdnd');
		header('Content-Type: application/json');

		$YOUR_DOMAIN = 'http://project';

		$checkout_session = $stripe->checkout->sessions->create([
			'ui_mode' => 'embedded',
			'line_items' => $stripeProducts,
			'mode' => 'payment',
			'return_url' => $YOUR_DOMAIN . '/payment/return/{CHECKOUT_SESSION_ID}',
		]);

		return $this->redirect($checkout_session->url);
	}

	public function paymentReturn(Request $request): Response
	{
		$checkoutId = $request->get('checkout_id');
	}
}
