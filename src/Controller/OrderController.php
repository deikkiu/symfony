<?php

namespace App\Controller;

use App\Model\OrderModel;
use App\Services\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends AbstractController
{
	public function showAll(): Response
	{
		return $this->render('order/index.html.twig', [
			'controller_name' => 'OrderController',
		]);
	}

	public function create(OrderModel $orderModel, CartService $cartService, Security $security): Response
	{
		$user = $security->getUser();

		if (!$user) {
			return $this->redirectToRoute('login');
		}

		try {
			$cart = $cartService->getCart();

			if ($cart->getQuantity() < 1) {
				$this->addFlash('notice', 'Your cart is empty! Add some products for order.');
				return $this->redirectToRoute('product_list');
			}

			$orderModel->createOrder($cart);
		} catch (\Exception $e) {
			$this->addFlash('notice', $e->getMessage());
			return $this->redirectToRoute('cart');
		}

		return $this->redirectToRoute('order');
	}
}
