<?php

namespace App\Controller;

use App\Model\OrderModel;
use App\Repository\OrderRepository;
use App\Repository\UserRepository;
use App\Services\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends AbstractController
{
	public function showAll(OrderRepository $orderRepository, UserRepository $userRepository, Security $security): Response
	{
		$user = $userRepository->find($security->getUser()->getId());
		$orders = $orderRepository->findBy(['owner' => $user]);

		return $this->render('order/index.html.twig', [
			'orders' => $orders,
		]);
	}

	public function show(Request $request, OrderRepository $orderRepository): Response
	{
		$id = $request->get('id');
		$order = $orderRepository->find($id);

		if (!$order) {
			throw $this->createNotFoundException('Order not found for id: ' . $id);
		}

		$orderProducts = $order->getOrderProducts();

		return $this->render('order/show.html.twig', [
			'orderProducts' => $orderProducts
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

		return $this->redirectToRoute('orders');
	}

	public function delete(Request $request, OrderRepository $orderRepository, OrderModel $orderModel): Response
	{
		$id = $request->get('id');
		$order = $orderRepository->find($id);

		if (!$order) {
			throw $this->createNotFoundException('Order not found for id = ' . $id);
		}

		$orderModel->deleteOrder($order);

		return $this->redirectToRoute('orders');
	}


}
