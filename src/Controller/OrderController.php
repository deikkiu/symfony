<?php

namespace App\Controller;

use App\Entity\Order;
use App\Model\OrderModel;
use App\Repository\OrderRepository;
use App\Repository\UserRepository;
use App\Services\CartService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends AbstractController
{
	public function showAll(OrderRepository $orderRepository, Security $security, UserRepository $userRepository): Response
	{
		$user = $userRepository->find($security->getUser()->getId());
		$orders = $orderRepository->findBy(['owner' => $user, 'isDeleted' => false]);

		return $this->render('order/index.html.twig', [
			'orders' => $orders,
		]);
	}

	public function show(Request $request, OrderRepository $orderRepository): Response
	{
		$id = (int)$request->get('id');
		$order = $orderRepository->find($id);

		if (!$order) {
			throw $this->createNotFoundException("Order with ID $id not found.");
		}

		return $this->render('order/show.html.twig', [
			'orderProducts' => $order->getOrderProducts()
		]);
	}

	public function create(OrderModel $orderModel, CartService $cartService, Security $security): Response
	{
		if (!$security->getUser()) {
			return $this->redirectToRoute('login');
		}

		try {
			$cart = $cartService->getCart();
			$orderModel->createOrder($cart);
		} catch (\Exception $e) {
			$this->addFlash('notice', $e->getMessage());
			return $this->redirectToRoute('cart');
		}

		return $this->redirectToRoute('orders');
	}

	public function delete(Request $request, OrderModel $orderModel, EntityManagerInterface $entityManager): Response
	{
		$id = (int)$request->get('id');
		$order = $entityManager->getRepository(Order::class)->find($id);

		if (!$order) {
			$this->createNotFoundException("Order with ID $id not found.");
		}

		$orderModel->deleteOrder($order);

		return $this->redirectToRoute('orders');
	}
}
